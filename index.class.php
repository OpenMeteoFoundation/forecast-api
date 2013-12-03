<?php

date_default_timezone_set('UTC');


class Index {

  const FLAG_READ = 'a';
  const FLAG_WRITE = 'w';
  const FLAG_INIT = 'n';
  
  const SHM_KEY = 0x000000ff;
  const SHM_SIZE = 1e6;
  const SHM_OFFSET = 4;

  private $_shm_id;
  private $_flags;
  private $_index = array();
  private $_size = false;
  
  function __construct($flags) {
  
    $shm_id = shmop_open ( $this::SHM_KEY , $flags, 0644, $this::SHM_SIZE );
    if (!$shm_id) {
      throw new ShmException('Cannot open shm for index');
    }
    
    $this->_shm_id=$shm_id;
    $this->_flags=$flags;
    
    if ($this->_flags == $this::FLAG_INIT) {
      $this->reset_index();
    } else {
      $this->_read_index();
    }
        
  }
  
  function get_index() {
    return $this->_index;
  }
  
  function reset_index() { // for testing purpose only
    $this->_index=array();
    $this->_write_index();
  }
  
  function _read_index () {
    $json=shmop_read ($this->_shm_id, $this::SHM_OFFSET, $this->_get_size());
    if ($json === false) {
	throw new ShmException('Cannot read shm for index data');
    }
    $this->_index=json_decode($json, true);
  }
 
  function save_index() {
    $this->_write_index ();
  }
 
  function _write_index () {
    $json=json_encode($this->_index);
    $json_size=strlen($json);
    if ($json_size >= $this::SHM_SIZE) {
      throw new ShmException('Index shm size is too small');
    }
    $shm_bytes_written = shmop_write ($this->_shm_id, $json, $this::SHM_OFFSET);
    if ($shm_bytes_written != $json_size) {
      throw new ShmException('Error writing shm for index data');
    }
    $this->_set_size($json_size);
  }
  
  function _get_size () {
    if ($this->_size === false) {
      $binary=shmop_read ($this->_shm_id, 0, 4);
      if ($binary === false) {
	throw new ShmException('Cannot read shm for index size');
      }
      $array=unpack('V', $binary); //unsigned long (32 bit, little endian)
      $this->_size = $array[1];
    }
    return $this->_size;
  }
  
  function _set_size($size) {
    $binary=pack('V', $size);
    $shm_bytes_written = shmop_write ($this->_shm_id, $binary, 0);
    if ($shm_bytes_written != 4) {
      throw new ShmException('Cannot write shm for index size');
    }
    $this->_size=$size;
  }
  
  
   
 
  function domain_exists($domain) {
    return array_key_exists($domain, $this->_index);
  }
 
  function add_domain ($domain, $params) {
    if ($this->domain_exists($domain)) {
      throw new ConflictException ('This domain is already in index');
    }
    $this->_index[$domain]=array('_p'=>$params);
  }
  
  function rm_domain ($domain) {
    if (!$this->domain_exists($domain)) {
      throw new NotFoundException ('This domain is not in index');
    }
    foreach ($this->_index[$domain] as $key=>$v) {
      if ($key == '_p') continue;
      $this->rm_run($domain, $key);
    }
    unset($this->_index[$domain]);
  } 
  
  
  function run_exists($domain, $run) {
    if (!$this->domain_exists($domain)) return false;
    if (!array_key_exists($run, $this->_index[$domain])) return false;
    return true;
  }
 
  function add_run ($domain, $run, $nhours, $nvars) {
    if ($this->run_exists($domain, $run)) {
      throw new ConflictException ('This run is already in index');
    }
    if (!$this->domain_exists($domain)) {
      throw new NotFoundException ('This domain is not in index');
    }
    
    $nx=$this->_index[$domain]['_p']['nx'];
    $ny=$this->_index[$domain]['_p']['ny'];

    $shm_key=$this->_shm_new_var($nx*$ny*$nhours*$nvars*4);
    
    $ry=substr($run, 0, 4);
    $rm=substr($run, 4, 2);
    $rd=substr($run, 6, 2);
    $rh=substr($run, 8, 2);
    $run_time=mktime($rh, 0, 0, $rm, $rd, $ry, 0);
    
    $this->_index[$domain][$run]=array(
      'status'=>'new',
      'nhours'=>$nhours,
      'nvars'=>$nvars,
      'vars'=>array(),
      'shm_key'=>$shm_key,
      'time'=>$run_time
    );
    
    ksort($this->_index[$domain]);
    
    $this->_write_index();
  }
  
  function rm_run ($domain, $run) {
    if (!$this->run_exists($domain, $run)) {
      throw new NotFoundException ('This run is not in index');
    }
    
    $shm_key=$this->_index[$domain][$run]['shm_key'];
    $shm_id = shmop_open($shm_key, "w", 0, 0);
    shmop_delete($shm_id);
    shmop_close($shm_id);
    
    unset($this->_index[$domain][$run]);
  }
  
  function add_var($domain, $run, $varname) {
    if ($this->var_exists($domain, $run, $varname)) {
      throw new ConflictException ('This var is already in index');
    }
    $r=$this->_index[$domain][$run];
    
    $varcount=count($r['vars']);
    if ($varcount == $r['nvars']) {
      throw new ConflictException ('Too many vars for this run');
    }
    
    $this->_index[$domain][$run]['vars'][$varname]=$varcount;
    $this->_write_index();
    
    return $varcount;
  }
  
  function var_exists($domain, $run, $varname) {
    if (!$this->run_exists($domain, $run)) {
      throw new NotFoundException ('This run is not in index');
    }
    return array_key_exists($varname, $this->_index[$domain][$run]['vars']);
  }
  
  function get_run($domain, $run) {
    if (!$this->run_exists($domain, $run)) {
      throw new NotFoundException ('This run is not in index');
    }
    return $this->_index[$domain][$run];
  }
  
  function get_last_run($domain) {
    $last_run=end($this->_index[$domain]);
    while (array_key_exists('status', $last_run) && $last_run['status']!='ok') {
      $last_run=prev($this->_index[$domain]);
    }
    if (key($this->_index[$domain]) == '_p') {
      throw new NotFoundException ('No run is available');
    }
    return key($this->_index[$domain]);
  }

  function set_run_status($domain, $run, $status) {
    if (!$this->run_exists($domain, $run)) {
      throw new NotFoundException ('This run is not in index');
    }
    $this->_index[$domain][$run]['status']=$status;
  }
  
  function _shm_new_var ($size) {
    for ($i=0; $i<200; $i++) { // try 200 times
      $key=rand($this::SHM_KEY+1, 0xFFFFFFFF);
      $shm_id = shmop_open($key, "n", 0644, $size);
      if ($shm_id !== false) {
	shmop_close($shm_id);
	return $key;
      }
    }
    throw new ShmException ('Unable to allocate shared memory');
  }
  
  function get_params($domain) {
     if (!$this->domain_exists($domain)) {
      throw new ConflictException ('This domain is already in index');
    }
    return $this->_index[$domain]['_p'];
  }
  
  function __destruct() {
    shmop_close($this->_shm_id);
  }
}
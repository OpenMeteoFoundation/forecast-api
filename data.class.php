<?php

class Data {

  private $_shm_id;
  private $_rparam;
  private $_dparam;

  function __construct (&$index, $domain, $run) {
    $this->_dparam=$index->get_params($domain);
    $this->_rparam=$index->get_run($domain, $run);

    $this->_shm_id = shmop_open ($this->_rparam['shm_key'] ,'a' ,0, 0);
    if (!$this->_shm_id) {
      throw new ShmException('Cannot open shm');
    }
  }
  
  function get ($varname, $x, $y) {
    $r=$this->_rparam;
    $nx=$this->_dparam['nx'];
    if (!array_key_exists($varname, $r['vars'])) {
      throw NotFoundException("Variable '$varname' not found for $domain:$run");
    }
    $index=($x+$y*$nx)*$r['nhours']*$r['nvars']+$r['vars'][$varname]*$r['nhours'];
    $binary=shmop_read($this->_shm_id, $index*4, 4*$r['nhours']);
    return unpack('f*', $binary);
  }
  
  function __destruct() {
    shmop_close($this->_shm_id);
  } 

}
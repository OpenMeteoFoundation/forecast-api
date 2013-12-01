<?php

$_GET['domain']='eu12';
$_GET['run']='2013120106';
$_GET['frame']='0';

$run=$_GET['run'];
$domain=$_GET['domain'];
$frame=$_GET['frame'];

require('../index.class.php');
$index=new Index(Index::FLAG_WRITE);

$index->rm_run($domain, $run);


$nhours=13;
$nvars=3;
$index->add_run($domain, $run, $nhours, $nvars);


  $vars = array('wind10m_u', 'wind10m_v'/*, 'rain', 'temp2m', 'rh:0'*/);

  $r=$index->get_run($domain, $run);

  foreach ($vars as $var) {

    $v=explode(':',$var);
    $zlevel=0;
    if (array_key_exists(1, $v)) {
      $zlevel=$v[1];
    }
    
    $varid=$index->add_var($domain, $run, $var);
    $index->save_index();

    for ($frame=0; $frame<$nhours; $frame++) {

      $filename=sprintf('http://dap.omd.li/%s-pp_%d_%d.nc', $domain, $run, $frame);
      $cmd=sprintf('../load-dap %d %d %d %d %d %s %s %d', $r['shm_key'], $r['nhours'], $r['nvars'], $varid, $frame, $filename, $v[0], $zlevel);
	
      exec($cmd, $out, $ret);
      if ($ret != 0) {
	echo "ERROR : $cmd\n";
      }
      echo "ok $var $frame\n";
    }

      
  }

  print_r($index->get_index());

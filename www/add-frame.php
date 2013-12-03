<?php

$net=substr($_SERVER['REMOTE_ADDR'], 0, 10);

if ($net != '185.10.252') {
  header(' ', false, 401);
  echo "denied\n";
  exit();
}

$run=$_GET['run'];
$domain=$_GET['domain'];
$frame=$_GET['frame'];

require('/opt/forecast-api/exceptions.class.php');
require('/opt/forecast-api/index.class.php');
$index=new Index(Index::FLAG_WRITE);

$vars = array(
  'temp2m',
  'rh:0',
  'cloudpct_l',
  'cloudpct_m',
  'cloudpct_h',
  'rain',
  'pblh',
  'press:0',
  'wind10m_u',
  'wind10m_v',
  'wind_u_a:5',
  'wind_v_a:5',
  'wind_u_a:10',
  'wind_v_a:10',
  'wind_u_a:15',
  'wind_v_a:15',
  'wind_u_a:20',
  'wind_v_a:20'
);

$nvars=count($vars);
$nhours=73; // eu12

if (!$index->run_exists($domain, $run)) {
  $index->rm_old_run($domain);
  $index->add_run($domain, $run, $nhours, $nvars);
}
  $r=$index->get_run($domain, $run);


  foreach ($vars as $var) {

    $v=explode(':',$var);
    $zlevel=0;
    if (array_key_exists(1, $v)) {
      $zlevel=$v[1];
    }

    if (!$index->var_exists($domain, $run, $var)) {
      $varid=$index->add_var($domain, $run, $var);
      $index->save_index();
    } else {
      $varid=$r['vars'][$var];
    }


      $filename=sprintf('http://dap001.teclib.omd-infra.net/b/%s-pp_%d_%d.nc', $domain, $run, $frame);
      $cmd=sprintf('/opt/forecast-api/load-dap %d %d %d %d %d %s %s %d', $r['shm_key'], $r['nhours'], $r['nvars'], $varid, $frame, $filename, $v[0], $zlevel);
	
      exec($cmd, $out, $ret);
      if ($ret != 0) {
      	echo "ERROR $var $frame\n";
	echo "$cmd\n";
      } else {
	echo "ok $var $frame\n";
      }

      flush();
  }

if ($frame == '72') { //eu12
  $index->set_run_status($domain, $run, 'ok');
  $index->save_index();
}

  //print_r($index->get_index());

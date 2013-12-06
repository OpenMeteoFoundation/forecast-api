<?php

$status=200;
$msg='';

$callback=false;
if (array_key_exists('callback', $_GET)) {
  $callback=$_GET['callback'];
}

header('Access-Control-Allow-Origin: *');

ob_start("ob_gzhandler");

try {

  require('/opt/forecast-api/exceptions.class.php');
  require('/opt/forecast-api/index.class.php');
  require('/opt/forecast-api/data.class.php');
  require('/opt/forecast-api/projection.class.php');

  $index=new Index(Index::FLAG_READ);


  /*
  api.ometfn.net/0.1/forecast/eu12/44.44,4/full.json
  api.ometfn.net/0.1/forecast/eu12/44.44,4/now.json
  api.ometfn.net/0.1/forecast/eu12/paris,fr/full.xml
  api.ometfn.net/0.1/forecast/eu12/paris,fr/now.xml
  api.ometfn.net/0.1/forecast/eu12/paris,fr/24h.xml
  api.ometfn.net/0.1/forecast/eu12/paris,fr/48h.xml
  */

  // TODO: handle multiple domains
  
  if ($_GET['format'] == 'json') {
    if ($callback) {
      header('Content-type: text/javascript');
    } else {
      header('Content-type: application/json');
    }
  } else {
    header('Content-type: application/json');
    throw new NotFoundException('File not found');
  }

  if ($_GET['domain'] != 'eu12') {
    throw new NotFoundException('Domain not found');
  }
  $domain=$_GET['domain'];

  $pattern='^((?P<lat>-?[\d\.]+),(?P<lon>-?[\d\.]+))|((?P<city>.+),(?P<country>[\w]+))|(?P<id>\d+)^';
  if (!preg_match($pattern, $_GET['location'], $matches)) {
    throw new BadRequestException('Bad format for location');
  }

  //TODO: handle city and 
  if (empty($matches['lat']) || empty($matches['lon'])) {
    throw new BadRequestException('No lat/lon found in your request');  
  }

  $projection=new Projection($index, $domain);
  $xy = $projection->latlon_to_xy($matches['lat'], $matches['lon']);
  $x=$xy['x'];
  $y=$xy['y'];

  // TODO: handle multiple resolutions
  if (abs($xy['x_error']) >= 12000) {
    $km=round($xy['x_error']/1000);
    throw new NotFoundException("The point you requested is x:$km km out of $domain domain.");  
  }
  if (abs($xy['y_error']) >= 12000) {
    $km=round($xy['y_error']/1000);
    throw new NotFoundException("The point you requested is y:$km km out of $domain domain.");  
  }



  $run=$index->get_last_run($domain);
  $rparam=$index->get_run($domain, $run);

  $start=floor((time()-$rparam['time'])/3600);
  $stop=$start;
  switch ($_GET['file']) {
    case 'full':
      $start=0;
      $stop=$start+$rparam['nhours'];
      break;
    case 'now':
      $start=round((time()-$rparam['time'])/3600);
      $stop=$start+1;
      break;
    case '24h':
      $stop=$start+24;
      break;
    case '48h':
      $stop=$start+48;
      break;
    default:
      throw new NotFoundException('File not found');
  }


  $data=new Data($index, $domain, $run);

  if ($callback) {
    echo "$callback({\n";
  } else {
    echo "{\n";
  }
  echo "\"doc\":\"http://api.ometfn.net/0.1/forecast/doc\",\n";
  echo "\"license\":\"http://api.ometfn.net/0.1/forecast/license\",\n";
  echo "\"domain\":\"$domain\",\n";
  echo "\"run\":\"$run\",\n";
  echo "\"grid\":".json_encode($xy).",\n";

  $ntimes=$stop-$start;
  echo "\"ntimes\":$ntimes,\n";
  echo "\"times\":[";
  for ($i=$start; $i<$stop; $i++) {
    if ($i!=$start) echo ',';
    echo $rparam['time']+$i*3600;
  }
  echo "],\n";



  output_var('temp', 'temp2m', 1);
  output_var('rh', 'rh:0', 0);
  output_var('low_clouds', 'cloudpct_l', 0);
  output_var('medium_clouds', 'cloudpct_m', 0);
  output_var('high_clouds', 'cloudpct_h', 0);
  output_var('precipitations', 'rain', 2);
  output_var('pblh', 'pblh', 0);
  output_var('pressure', 'press:0', 1);

  output_wind('wind_10m_ground', 'wind10m_u', 'wind10m_v');
  output_wind('wind_1000m_msl', 'wind_u_a:5', 'wind_v_a:5');
  output_wind('wind_2000m_msl', 'wind_u_a:10', 'wind_v_a:10');
  output_wind('wind_3000m_msl', 'wind_u_a:15', 'wind_v_a:15');
  output_wind('wind_4000m_msl', 'wind_u_a:20', 'wind_v_a:20');
  
} catch (Exception $e) {

  ob_get_clean();
  ob_start("ob_gzhandler");

  switch(get_class($e)) {
    case 'ShmException':
    case 'PHPException':
      $status=500;
      break;
    case 'NotFoundException':
      $status=404;
      break;
    case 'BadRequestException':
      $status=400;
      break;
    case 'ConflictException':
      $status=409;
      break;
  }

  header(' ', true, $status);

  if (get_class($e) == 'PHPException') {
    $msg='PHP Error';
  } else {
    $msg=$e->getMessage();
  }
  if ($callback) {
    echo "$callback({\n";
  } else {
    echo "{\n";
  }
}

echo "\"status\":\"$status\",\n";
echo "\"msg\":\"$msg\",\n";
if (array_key_exists('SERVER_ADDR', $_SERVER))  {
  $srv=$_SERVER['SERVER_ADDR'];
} else {
  $srv='';
}
echo "\"srv\":\"$srv\"\n";

if ($callback) {
  echo "});\n";
} else {
  echo "}\n";
}


function output_var($jsonname, $dapname, $round) {
  global $start;
  global $stop;
  global $data;
  global $x;
  global $y;
  
  echo "\"$jsonname\":[";
  $var = $data->get($dapname, $x, $y);
  for ($i=$start; $i<$stop; $i++) {
    if ($i!=$start) echo ',';
    echo round($var[$i+1],$round);
  }
  echo "],\n";  
}

function output_wind($jsonname, $dapname_u, $dapname_v) {
  global $start;
  global $stop;
  global $data;
  global $x;
  global $y;
  
  $uvar=$data->get($dapname_u, $x, $y);
  $vvar=$data->get($dapname_v, $x, $y);
  
  $json_speed='';
  $json_dir='';
  
  for ($i=$start; $i<$stop; $i++) {
    if ($i!=$start) {
      $json_speed .= ',';
      $json_dir .= ',';
    }
    $u=$uvar[$i+1];
    $v=$vvar[$i+1];
    if ($u > 1E30 || $v > 1E30) {
     $json_speed.='null';
     $json_dir.='null';
    } else {
      $speed=sqrt($u*$u+$v*$v);
      $dir=(atan2($v, $u)/M_PI*180+360)%360;
      $json_speed.=round($speed,1);
      $json_dir.=round($dir,1);
    }
  }
  
  echo "\"{$jsonname}_speed\":[$json_speed],\n";
  echo "\"{$jsonname}_dir\":[$json_dir],\n";
}


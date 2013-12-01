<?php

require('../index.class.php');
require('../data.class.php');
require('../projection.class.php');

$index=new Index(Index::FLAG_READ);

$_GET['domain']='eu12';
$_GET['run']='2013120106';

$run='2013120106';
$domain='eu12';

$lat=45;
$lon=6;

$data=new Data($index, $domain, $run);
$projection=new Projection($index, $domain);

$xy = $projection->latlon_to_xy($lat, $lon);
$x=$xy['x'];
$y=$xy['y'];

$wind_u = $data->get('wind10m_u', $x, $y);
$wind_v = $data->get('wind10m_v', $x, $y);


<?php

require('index.class.php');

$index=new Index(Index::FLAG_WRITE);

$index->reset_index();

//$index->rm_domain('eu12');

$params=array(
  'nx'=>495,
  'ny'=>309
);

$index->add_domain("eu12", $params);
$index->save_index();
/*


$nhours=73;
$nvars=2;
$index->add_run("eu12", "2013120100", $nhours, $nvars);


$domain='eu12';
$index->add_var($domain, "2013120100", 'wind10m_v');

//$index->save_index();
print_r($index->get_index());

//$idx["domain"]["run"]["frame"]["param"]


*/
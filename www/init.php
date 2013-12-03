<?php

require('/opt/forecast-api/index.class.php');

$index=new Index(Index::FLAG_INIT);

$params=array(
  'nx'=>495,
  'ny'=>309
);

$index->add_domain("eu12", $params);
$index->save_index();

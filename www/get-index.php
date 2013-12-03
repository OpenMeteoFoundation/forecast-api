<?php

header('Content-type: text/plain');

require('/opt/forecast-api/exceptions.class.php');
require('/opt/forecast-api/index.class.php');
$index=new Index(Index::FLAG_WRITE);

print_r($index->get_index());

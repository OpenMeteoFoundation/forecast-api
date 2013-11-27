<?php

require("dataserver.class.php");

$ds=new DataServer('127.0.0.1', 9999);

$ds->ll_to_xy(41, 2);
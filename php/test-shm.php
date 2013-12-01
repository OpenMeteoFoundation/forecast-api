<?php

$shm_id = shmop_open (0x1234 ,'n' ,0644, 33*1024*1024);
var_dump($shm_id);

shmop_delete($shm_id);
shmop_close($shm_id);
<?php

class DataServer {
  
  private $_socket;

  function __construct($address, $service_port) {
    
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
	throw "socket_create() a échoué : raison :  " . socket_strerror(socket_last_error()) . "\n";
    }
    
    $result = socket_connect($socket, $address, $service_port);
    if ($socket === false) {
	throw "socket_connect() a échoué : raison : ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    }
    
    $this->_socket = $socket;
    
  }
  
  function ll_to_xy($lat, $lon) {
    
    $in=sprintf("ll2xy %f %f\n", $lat, $lon);
    
    socket_write($this->_socket, $in, strlen($in));
    $out = socket_read($this->_socket, 2048, PHP_NORMAL_READ);
    $outa = explode(' ', trim($out));
    
    print_r($outa);
  }

  function __destruct() {
    socket_close($this->_socket);
  }
}
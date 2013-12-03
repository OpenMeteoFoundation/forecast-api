<?php

function myErrorHandler($errno, $errstr, $errfile, $errline) {
   switch ($errno) {
    case E_USER_ERROR:
      throw new PHPException ('error',$errno, $errstr, $errfile, $errline);
      break;
    case E_USER_WARNING:
      throw new PHPException ('warning', $errno, $errstr, $errfile, $errline);
      break;
    case E_USER_NOTICE:
      throw new PHPException ('notice', $errno, $errstr, $errfile, $errline);
      break;
    default:
      throw new PHPException ('unknown', $errno, $errstr, $errfile, $errline);
      break;
    }
}
//set_error_handler("myErrorHandler");

class ShmException extends Exception { }
class BadRequestException extends Exception { }
class NotFoundException extends Exception { }
class ConflictException extends Exception { }
class PHPException extends Exception { }

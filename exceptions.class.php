<?php

function myErrorHandler($errno, $errstr, $errfile, $errline) {
   switch ($errno) {
    case E_USER_ERROR:
      throw PHPException ('error',$errno, $errstr, $errfile, $errline);
      break;
    case E_USER_WARNING:
      throw PHPException ('warning', $errno, $errstr, $errfile, $errline);
      break;
    case E_USER_NOTICE:
      throw PHPException ('notice', $errno, $errstr, $errfile, $errline);
      break;
    default:
      throw PHPException ('unknown', $errno, $errstr, $errfile, $errline);
      break;
    }
}
set_error_handler("myErrorHandler");

class ShmException extends Exception { }
class BadRequestException extends Exception { }
class NotFoundException extends Exception { }
class ConflictException extends Exception { }
class PHPException extends Exception { }
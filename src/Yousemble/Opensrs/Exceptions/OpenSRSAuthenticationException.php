<?php namespace Yousemble\LaravelOpensrs\Exceptions;

class OpenSRSAuthenticationException extends OpenSRSException{

  function __construct($message = "", $code = 0, \Exception $previous = NULL){
    parent::__construct($message, $code, $previous);
  }

}
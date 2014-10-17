<?php namespace Yousemble\LaravelOpensrs\OpenSRS;

class OpenSRSResponse{

  protected $attributes = null;
  protected $code = null;
  protected $text = null;
  protected $success = false;

  public function __construct(array $attributes){
    $this->code = array_get($attributes, 'response_code', null);
    $this->text = array_get($attributes, 'response_text', '');
    $this->success = array_get($attributes, 'is_success', false);
    $this->attributes = array_get($attributes, 'attributes', []);
  }

  public function getCode(){
    return (int) $this->code;
  }

  public function getMessage(){
    return $this->text;
  }

  public function isSuccess(){
    return (bool) $this->success;
  }

  public function getAttribute($name = null){

    if($name === null){
      return $this->attributes;
    }

    return array_get($this->attributes, $name, null);
  }

  public function attributeString(){
    return print_r($this->attributes, true);
  }


}
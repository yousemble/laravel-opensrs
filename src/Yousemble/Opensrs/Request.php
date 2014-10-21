<?php namespace Yousemble\Opensrs;

class Request{

  const PROTOCOL = 'XCP';

  protected $action = null;
  protected $object = null;
  protected $cookie = null;
  protected $attributes = null;

  public function __construct($action, $object, array $attributes, $cookie = null){
    $this->action = $action;
    $this->object = $object;
    $this->attributes = $attributes;
    $this->cookie = $cookie;
  }

  public function getData(){
    $data = [
      'protocol' => static::PROTOCOL,
      'action' => $this->action,
      'object' => $this->object,
      'attributes' => $this->attributes,
    ];

    if($this->cookie !== null){
      $data['cookie'] = $this->cookie;
    }

    return $data;
  }

  public function addContact($type, $contact_data){
    if(!isset($this->attributes['contact_set'])) $this->attributes['contact_set'] = [];
    if(!isset($this->attributes['contact_set'][$type])) $this->attributes['contact_set'][$type] = [];

    $this->attributes['contact_set'][$type] = $contact_data;
  }

}
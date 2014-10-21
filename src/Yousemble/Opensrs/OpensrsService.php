<?php namespace Yousemble\Opensrs;

use Yousemble\Opensrs\Exceptions\OpenSRSConnectionException;
use Yousemble\Opensrs\Exceptions\OpenSRSAuthenticationException;
use Yousemble\Opensrs\Exceptions\OpenSRSException;
use Yousemble\Opensrs\Exceptions\OpenSRSRequestException;

use Illuminate\Cache\CacheManager;

class OpensrsService{

  protected $config;
  protected $ops;
  protected $comm;
  protected $cookieStore;

  public function __construct(array $config, CacheManager $cookieStore = null){
    $this->config = new Config($config);
    $this->ops = new Ops($this->config);
    $this->comm = new Comm($this->config);

    $this->cookieStore = $cookieStore;
  }

  public function getCookie($domain){

    //TODO check the cookieStore first
    if($this->cookieStore !== null){

    }

    $request = new Request('set', 'cookie', [

      ]);

    return $this->processRequest($request);
  }

  public function deleteCookie($cookie){

    //check if we have the cookie and delete
    if($this->cookieStore !== null){

    }

    $request = new Request('delete', 'cookie', [
        'cookie' => $cookie
      ]);

    return $this->processRequest($request);
  }

  public function createSWRegisterDomainRequest(array $attributes){
    return new Request('SW_REGISTER', 'DOMAIN', $attributes);
  }

  public function processRequest(Request $request){
    $ops_request_str = $this->ops->encode($request->getData());
    $ops_response_str = $this->comm->processCommand($ops_request_str);
    $response_data = $this->ops->decode($ops_response_str);

    $response = new OpenSRSResponse($response_data);

    if($this->config->THROW_RESPONSE_EXCEPTION){
      $this->checkResponseException($response);
    }

    return $response;
  }

  protected function checkResponseException(OpenSRSResponse $response){
    if(!$response->isSuccess()){
      switch($response->getCode()){
        case 400:
        case 401:
          throw new OpenSRSAuthenticationException($response->getMessage(), $response->getCode());
        case 465:
          throw new OpenSRSRequestException($response->getMessage() . ' ' . $response->attributeString(), $response->getCode());
      }
    }
  }

  public function getConfig(){
    return $this->config;
  }

}
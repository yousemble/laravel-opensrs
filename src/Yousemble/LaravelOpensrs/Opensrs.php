<?php namespace Yousemble\LaravelOpensrs;

use Yousemble\LaravelOpensrs\OpenSRS\OpenSRSConfig;
use Yousemble\LaravelOpensrs\OpenSRS\OpenSRSComm;
use Yousemble\LaravelOpensrs\OpenSRS\OpenSRSOps;
use Yousemble\LaravelOpensrs\OpenSRS\OpenSRSRequest;
use Yousemble\LaravelOpensrs\OpenSRS\OpenSRSResponse;

use Yousemble\LaravelOpensrs\Exceptions\OpenSRSConnectionException;
use Yousemble\LaravelOpensrs\Exceptions\OpenSRSAuthenticationException;
use Yousemble\LaravelOpensrs\Exceptions\OpenSRSException;
use Yousemble\LaravelOpensrs\Exceptions\OpenSRSRequestException;

use Illuminate\Cache\CacheManager;

class OpenSRS{

  protected $config;
  protected $ops;
  protected $comm;
  protected $cookieStore;

  public function __construct(array $config, CacheManager $cookieStore = null){
    $this->config = new OpenSRSConfig($config);
    $this->ops = new OpenSRSOps($this->config);
    $this->comm = new OpenSRSComm($this->config);

    $this->cookieStore = $cookieStore;
  }

  public function getCookie($domain){

    //TODO check the cookieStore first
    if($this->cookieStore !== null){

    }

    $request = new OpenSRSRequest('set', 'cookie', [

      ]);

    return $this->processRequest($request);
  }

  public function deleteCookie($cookie){

    //check if we have the cookie and delete
    if($this->cookieStore !== null){

    }

    $request = new OpenSRSRequest('delete', 'cookie', [
        'cookie' => $cookie
      ]);

    return $this->processRequest($request);
  }

  public function createSWRegisterDomainRequest(array $attributes){
    return new OpenSRSRequest('SW_REGISTER', 'DOMAIN', $attributes);
  }

  public function processRequest(OpenSRSRequest $request){
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
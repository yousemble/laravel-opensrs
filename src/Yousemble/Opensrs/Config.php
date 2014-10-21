<?php namespace Yousemble\Opensrs;

class Config{

  /**
  * OpenSRS reseller username
  */
  public $OSRS_USERNAME = '';
  public $OSRS_PASSWORD = '';

  /**
  * OpenSRS reseller private Key. Please generate a key if you do not already have one.
  */
  public $OSRS_KEY = '';

  /**
  * OpenSRS default encryption type => ssl, sslv2, sslv3, tls
  */
  public $CRYPT_TYPE = 'ssl';

  /**
  * OpenSRS domain service API url.
  * LIVE => rr-n1-tor.opensrs.net, TEST => horizon.opensrs.net
  */
  public $OSRS_HOST = '';

  /**
  * OpenSRS API SSL port
  */
  public $OSRS_SSL_PORT = '55443';

  /**
  * OpenSRS protocol. XCP or TPP.
  */
  public $OSRS_PROTOCOL = 'XCP';

  /**
  * OpenSRS version
  */
  public $OSRS_VERSION = 'XML:0.1';

  /**
  * OpenSRS domain service debug flag
  */
  public $OSRS_DEBUG = false;

  /**
  * OpenSRS API fastlookup port`
  */
  public $OSRS_FASTLOOKUP_PORT = '51000';


  /**
  * OpenSRE Email API (OMA) Specific configurations
  * Please change the value CHANGEME to your value
  */

  /**
  * OMA HOST
  * LIVE => https://admin.hostedemail.com, TEST => https://admin.test.hostedemail.com
  */
  public $MAIL_HOST = 'https://admin.hostedemail.com';

  /**
  * Your company level username
  */
  public $MAIL_USERNAME = '';

  /**
  * Your company level password
  */
  public $MAIL_PASSWORD = '';

  /**
  * OMA environment LIVE or TEST
  */
  public $MAIL_ENV = 'LIVE';

  /**
  * OMA Client tool info. i.e. OpenSRS PHP Toolkit
  */
  public $MAIL_CLIENT = 'OpenSRS PHP Toolkit';


  /**
  * APP Email Specific configurations
  *
  * WARNING: This APP libs will eventually be deprecated and replace by OMA.
  */

  /**
  * OpenSRS APP HOST
  * LIVE => ssl://admin.hostedemail.com, TEST => ssl://admin.test.hostedemail.com
  */
  public $APP_MAIL_HOST = 'ssl://admin.hostedemail.com';

  /**
  * OpenSRS APP Username
  */
  public $APP_MAIL_USERNAME = '';

  /**
  * OpenSRS APP Password
  */
  public $APP_MAIL_PASSWORD = '';

  /**
  * OpenSRS APP domain
  */
  public $APP_MAIL_DOMAIN = '';

  /**
  * OpenSRS APP mail port
  */
  public $APP_MAIL_PORT = '4449';

  /**
  * OpenSRS APP mail portwait
  */
  public $APP_MAIL_PORTWAIT = '10';

  public $COMPRESS_XML = true;


  public $REQUEST_TIMEOUT = 120;
  public $RESPONSE_TIMEOUT = 120;

  public $THROW_RESPONSE_EXCEPTION = true;


  public function __construct(array $config = []){
    $has = get_object_vars($this);

    foreach ($config as $key => $value) {
      if(isset($has[$key])){
        $this->$key = $config[$key];
      }
    }
  }

}
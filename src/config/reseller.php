<?php

return array(
/**
* OpenSRS reseller username
*/
'OSRS_USERNAME' => 'opensrs_reseller_username_here',
'OSRS_PASSWORD' => '',

/**
* OpenSRS reseller private Key. Please generate a key if you do not already have one.
*/
'OSRS_KEY' => 'opensrs_reseller_private_key_here',

/**
* OpenSRS default encryption type => ssl, sslv2, sslv3, tls
*/
'CRYPT_TYPE' => 'ssl',

/**
* OpenSRS domain service API url.
* LIVE => rr-n1-tor.opensrs.net, TEST => horizon.opensrs.net
*/
'OSRS_HOST' => 'opensrs_host_here',

/**
* OpenSRS API SSL port
*/
'OSRS_SSL_PORT' => '55443',

/**
* OpenSRS protocol. XCP or TPP.
*/
'OSRS_PROTOCOL' => 'XCP',

/**
* OpenSRS version
*/
'OSRS_VERSION' => 'XML:0.1',

/**
* OpenSRS domain service debug flag
*/
'OSRS_DEBUG' => 0,

/**
* OpenSRS API fastlookup port`
*/
'OSRS_FASTLOOKUP_PORT' => '51000',


/**
* OpenSRE Email API (OMA) Specific configurations
* Please change the value CHANGEME to your value
*/

/**
* OMA HOST
* LIVE => https://admin.hostedemail.com, TEST => https://admin.test.hostedemail.com
*/
'MAIL_HOST' => 'https://admin.hostedemail.com',

/**
* Your company level username
*/
'MAIL_USERNAME' => 'company_level_username_here',

/**
* Your company level password
*/
'MAIL_PASSWORD' => 'company_level_password_here',

/**
* OMA environment LIVE or TEST
*/
'MAIL_ENV' => 'LIVE',

/**
* OMA Client tool info. i.e. OpenSRS PHP Toolkit
*/
'MAIL_CLIENT' => 'OpenSRS PHP Toolkit',


/**
* APP Email Specific configurations
*
* WARNING: This APP libs will eventually be deprecated and replace by OMA.
*/

/**
* OpenSRS APP HOST
* LIVE => ssl://admin.hostedemail.com, TEST => ssl://admin.test.hostedemail.com
*/
'APP_MAIL_HOST' => 'ssl://admin.hostedemail.com',

/**
* OpenSRS APP Username
*/
'APP_MAIL_USERNAME' => '',

/**
* OpenSRS APP Password
*/
'APP_MAIL_PASSWORD' => '',

/**
* OpenSRS APP domain
*/
'APP_MAIL_DOMAIN' => '',

/**
* OpenSRS APP mail port
*/
'APP_MAIL_PORT' => '4449',

/**
* OpenSRS APP mail portwait
*/
'APP_MAIL_PORTWAIT' => '10',


 'COMPRESS_XML' =>                    true,


 'REQUEST_TIMEOUT' =>                 120,  //in seconds


);

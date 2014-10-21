<?php namespace Yousemble\Opensrs;

use Yousemble\Opensrs\Exceptions\OpenSRSConnectionException;
use Yousemble\Opensrs\Exceptions\OpenSRSException;


class Comm{

  private $_socket = false;
  private $_socketErrorNum = false;
  private $_socketErrorMsg = false;

  protected $config = null;

  const CRLF = "\r\n";

  /**
   * openSRS_base object constructor
   *
   * Closes an existing socket connection, if we have one
   *
   * @since   3.1
   */
  public function __construct (OpenSRSConfig $config = null) {

    if($config === null){
      $this->config = new OpenSRSConfig();
    }else{
      $this->config = $config;
    }

    $this->_verifySystemProperties ();
  }

  /**
   * openSRS_base object destructor
   *
   * Closes an existing socket connection, if we have one
   *
   * @since   3.4
   */
  public function __destruct () {
    if (is_resource($this->_socket))
    {
      fclose($this->_socket);
    }
  }

  /**
   * Method to send a command to the server
   *
   * @param   string  $request Raw XML request
   *
   * @return  string  $data   Raw XML response
   *
   * @since   3.1
   */
  public function processCommand($request_xml) {
    // make or get the socket filehandle
    if (!$this->init_socket() ) {
      throw new OpenSRSConnectionException("Unable to establish socket: (". $this->_socketErrorNum .") ". $this->_socketErrorMsg);
    }

    $this->send_data($request_xml);
    $data = $this->read_data();

    return $data;
  }

  /**
   * Method to check the PHP version and OpenSSL PHP lib installation
   *
   * @since   3.1
   */
  private function _verifySystemProperties () {
    if (!function_exists('openssl_open')) {
      throw new OpenSRSException("PHP must be compiled using --with-openssl to use \"SSL\" encryption");
    }
  }


  /**
   * Method to initialize a socket connection to the OpenSRS server
   *
   * @return  boolean  True if connected
   *
   * @since   3.1
   */
  private function init_socket() {
    if ($this->is_connected()) return true;

    try{
      $this->_socket = fsockopen($this->config->CRYPT_TYPE . '://' . $this->config->OSRS_HOST, $this->config->OSRS_SSL_PORT, $this->_socketErrorNum, $this->_socketErrorMsg, $this->config->REQUEST_TIMEOUT);
    }catch(\Exception $e){
      throw new OpenSRSConnectionException('Failed to open socket connection',0, $e);
    }

    if (!$this->_socket) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * Method to check if a socket connection exists
   *
   * @return  boolean  True if connected
   *
   * @since   3.4
   */
  public function is_connected() {
    return (is_resource($this->_socket)) ? true : false;
  }

  /**
   * Method to close the socket connection
   *
   * @since   3.4
   */
  private function close_socket() {
    if (is_resource($this->_socket))
    {
      fclose($this->_socket);
    }
  }


  /**
   * Method to read data from the buffer stream
   *
   * @return  string  XML response
   * @since   3.1
   */
  private function read_data() {
    $buf = $this->readData($this->_socket);
    if (!$buf) {
      throw new OpenSRSConnectionException("Read buffer is empty.  Please make sure IP is whitelisted in RWI. Check the OSRS_KEY and OSRS_USERNAME in the config file as well.");
      $data = "";
    } else {
      $data = $buf;
    }
    if (!empty($this->config->OSRS_DEBUG)) print_r($data);

    return $data;
  }

  /**
   * Method to send data
   *
   * @param   string  $message  XML request
   * @return  string  $message  XML response
   * @since   3.1
   */
  private function send_data($message) {
    if (!empty($this->config->OSRS_DEBUG)) print_r($message);
    return $this->writeData( $this->_socket, $message );
  }

  /**
  * Writes a message to a socket (buffered IO)
  *
  * @param  int   &$fh  socket handle
  * @param  string  $msg    message to write
  */
  private function writeData(&$fh,$msg) {
    $header = "";
    $len = strlen($msg);

    $signature = md5(md5($msg.$this->config->OSRS_KEY).$this->config->OSRS_KEY);
    $header .= "POST / HTTP/1.0". static::CRLF;
    $header .= "Content-Type: text/xml" . static::CRLF;
    $header .= "X-Username: " . $this->config->OSRS_USERNAME . static::CRLF;
    $header .= "X-Signature: " . $signature . static::CRLF;
    $header .= "Content-Length: " . $len . static::CRLF . static::CRLF;

    fputs($fh, $header);
    fputs($fh, $msg, $len );
  }


  /**
  * Reads header data
  * @param  int   socket handle
  * @param  int   timeout for read
  * @return hash  hash containing header key/value pairs
  */
  private function readHeader($fh) {
    $header = array();
    /* HTTP/SSL connection method */
    $http_log ='';
    $line = fgets($fh, 4000);
    $http_log .= $line;
    if (!preg_match('/^HTTP\/1.1 ([0-9]{0,3}) (.*)\r\n$/',$line, $matches)) {
      throw new OpenSRSConnectionException("UNEXPECTED READ: Unable to parse HTTP response code. Please make sure IP is whitelisted in RWI.");
      return false;
    }
    $header['http_response_code'] = $matches[1];
    $header['http_response_text'] = $matches[2];

    while ($line != static::CRLF) {
      $line = fgets($fh, 4000);
      $http_log .= $line;
      if (feof($fh)) {
        throw new OpenSRSConnectionException("UNEXPECTED READ: Error reading HTTP header.");
        return false;
      }
      $matches = explode(': ', $line, 2);
      if (sizeof($matches) == 2) {
        $header[trim(strtolower($matches[0]))] = $matches[1];
      }
    }
    $header['full_header'] = $http_log;

    return $header;
  }

  /**
  * Reads data from a socket
  * @param  int   socket handle
  * @param  int   timeout for read
  * @return mixed buffer with data, or an error for a short read
  */
  private function readData(&$fh) {
    $len = 0;
    /* PHP doesn't have timeout for fread ... we just set the timeout for the socket */
    socket_set_timeout($fh, $this->config->RESPONSE_TIMEOUT);
    $header = $this->readHeader($fh);
    if (!$header || !isset($header{'content-length'}) || (empty($header{'content-length'}))) {
      throw new OpenSRSConnectionException("UNEXPECTED ERROR: No Content-Length header provided! Please make sure IP is whitelisted in RWI.");
    }

    $len = (int)$header{'content-length'};
    $line = '';
    while (strlen($line) < $len) {
      $line .= fread($fh, $len);
      if ($this->socketStatus()) {
        return false;
      }
    }

    if ($line) {
      $buf = $line;
    } else {
      $buf = false;
    }

    $this->close_socket();
    return $buf;
  }

    /**
   * Checks a socket for timeout or EOF
   * @param int     socket handle
   * @return  boolean   true if the socket has timed out or is EOF
   */
  private function socketStatus() {
    $return = false;
    if (is_resource($this->_socket)) {
      $temp = socket_get_status($this->_socket);
      if ($temp['timed_out']) $return = true;
      if ($temp['eof']) $return = true;
      unset($temp);
    }
    return $return;
  }



}
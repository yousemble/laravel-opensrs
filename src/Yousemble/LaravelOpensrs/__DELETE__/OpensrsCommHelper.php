<?php namespace Yousemble\LaravelOpensrs;

class OpensrsCommHelper{

  protected $_OPS_VERSION = '0.9';
  protected $_OPT     = '';
  protected $_SPACER    = ' ';    /* indent character */
  protected $_CRLF      = "\n";
  protected $_MSGTYPE_STD = 'standard';
  protected $_SESSID;
  protected $_MSGCNT;
  protected $CRLF     = "\r\n";

  protected $config = [
    'OSRS_USERNAME' => '',
    'OSRS_PASSWORD' => '',
    'OSRS_KEY' => '',
    'OSRS_PROTOCOL' => 'XCP',
    'OSRS_HOST' => 'horizon.opensrs.net',
    'OSRS_SSL_PORT' => '554433',
    'CRYPT_TYPE' => 'ssl',
    'OSRS_FASTLOOKUP_PORT' => '51000',
    'COMPRESS_XML' => true,
    'REQUEST_TIMEOUT' => 120,
  ];

  public function sendRequest(array $data){

    $xml = $this->encodeRequest($data);

  }

  private function sendHTTPSRequest($xml){

    $host = 'https://' . $this->config['OSRS_HOST'];
    $signature = md5(md5($xml.$this->config['OSRS_KEY']).$this->config['OSRS_KEY']);

    $curl = curl_init($host);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($curl, CURLOPT_PORT, $this->config['OSRS_SSL_PORT']);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml',
        'Content-Length: ' . strlen($xml),
        "X-Username: " . $this->config['OSRS_USERNAME'],
        "X-Signature: " . $signature,
    ]);

  }

  private function sendSSLRequest($xml){

    $error_num = false;
    $error_msg = false;

    $host = $this->config['CRYPT_TYPE'] . '://' . $this->config['OSRS_HOST'];

    $socket = fsockopen(
      $host,
      $this->config['OSRS_SSL_PORT'],
      $error_num,
      $error_msg,
      $this->config['REQUEST_TIMEOUT']
      );

    if (!$this->isSocketConnected($socket)) {
      throw new Exception('Failed to open connection to OpenSRS at ' . $host . " [{$error_num} : {$error_msg}]");
    }

    if($this->writeRequestHeaders($socket, $xml) === false){
      throw new Exception('Failed to writing headers on connection to OpenSRS at ' . $host . " [{$error_num} : {$error_msg}]");
    }

    if($this->writeRequestBody($socket, $xml)){
      throw new Exception('Failed to writing body on connection to OpenSRS at ' . $host . " [{$error_num} : {$error_msg}]");
    }

    $header = $this->readResponseHeaders($socket);
    $body = $this->readResponseBody($socket, $header);

    if($this->isSocketConnected($socket)){
      fclose($this->_socket);
    }

  }

  private function isSocketConnected(&$socket){
    $return = true;
    if($socket !== false && is_resource($socket)){
      $meta = socket_get_status($socket);
      if ($meta['timed_out']) $return = false;
      unset($meta);
    }else{
      return false;
    }
    return $return;
  }

  private function writeRequestHeaders(&$socket, $xml){
    $xml_length = strlen($xml);

    $header = "";

    $signature = md5(md5($xml.$this->config['OSRS_KEY']).$this->config['OSRS_KEY']);
    $header .= "POST / HTTP/1.0\r\n";
    $header .= "Content-Type: text/xml\r\n";
    $header .= "X-Username: " . $this->config['OSRS_USERNAME'] ."\r\n";
    $header .= "X-Signature: " . $signature . "\r\n";
    $header .= "Content-Length: " . $xml_length . "\r\n\r\n";

    return fwrite($socket, $header);
  }

  private function writeRequestBody(&$socket, $xml){
    $xml_length = strlen($xml);
    return fwrite($socket, $xml, $xml_length);
  }

  private function readResponseHeaders(&$socket){
    $header = array();
    /* HTTP/SSL connection method */
    $http_log ='';
    $line = fgets($fh, 4000);
    $http_log .= $line;
    if (!preg_match('/^HTTP\/1.1 ([0-9]{0,3}) (.*)\r\n$/',$line, $matches)) {
      throw new Exception("Unable to parse HTTP response code in OpenSRS response. Please make sure IP is whitelisted in RWI.");
    }
    $header['http_response_code'] = $matches[1];
    $header['http_response_text'] = $matches[2];

    while ($line != CRLF) {
      $line = fgets($fh, 4000);
      if (feof($fh)) {
        throw new Exception("oSRS Error - UNEXPECTED READ: Error reading HTTP header.");
      }
      $matches = explode(': ', $line, 2);
      if (sizeof($matches) == 2) {
        $header[trim(strtolower($matches[0]))] = $matches[1];
      }
    }

    if (!$header || !isset($header['content-length']) || (empty($header['content-length']))) {
      throw new Exception("oSRS Error - UNEXPECTED ERROR: No Content-Length header provided! Please make sure IP is whitelisted in RWI.");
    }

    $header['content-length'] = (int) $header['content-length'];

    return $header;
  }

  private function readResponseBody(&$socket, $header){
    $len = 0;

    $len = $header['content-length'];
    $line = '';
    while (strlen($line) < $len) {
      $line .= fread($socket, $len);
      if (!$this->isSocketConnected($socket)) {
        throw new Exception("Unexpected connection drop reading response body from OpenSRS");
      }
    }

    if (!$line) {
      throw new Exception("Error reading response body on OpenSRS connection");
    }

    return $line;
  }

  /**
   * Class constructor
   * Initialize variables, logs, etc.
   * @param array allows for setting various options (right now, just whether
   *          to use compression or not on the generated XML)
   */
  public function __construct(array $config) {

    $this->config = array_merge($this->config, $config);

    if ($config['COMPRESS_XML']) {
      $this->_OPT = 'compress';
      $this->_SPACER  = '';
      $this->_CRLF  = '';
    }

    $this->_SESSID = getmypid();
    $this->_MSGCNT = 0;
  }

  /**
   * Accepts an OPS protocol message or an file handle
   * and decodes the data into a PHP array
   * @param string    OPS message
   * @return  mixed   PHP array, or error
   */
  public function decodeResponse($response) {
    return $this->XML2PHP($response);    /* decode and return */
  }

  /**
   * XML Parser that converts an OPS protocol message into a PHP array
   * @param string    OPS message
   * @return  mixed   PHP array, or error
   */
  protected function XML2PHP($msg) {
    $_data = NULL;

    $xp = xml_parser_create();
    xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, false);
    xml_parser_set_option($xp, XML_OPTION_SKIP_WHITE, true);
    xml_parser_set_option($xp, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1');

    if (!xml_parse_into_struct($xp,$msg,$vals,$index)) {
      $error = sprintf('XML error: %s at line %d',
        xml_error_string(xml_get_error_code($xp)),
        xml_get_current_line_number($xp)
      );
      xml_parser_free($xp);
      trigger_error ("oSRS Error - ". $error, E_USER_WARNING);
      die();
    }

    xml_parser_free($xp);
    $temp = $depth = array();

    foreach($vals as $value) {
      switch ($value['tag']) {
        case 'OPS_envelope':
        case 'header':
        case 'body':
        case 'data_block':
          break;
        case 'version':
        case 'msg_id':
        case 'msg_type':
          $key = '_OPS_' . $value['tag'];
          $temp[$key] = $value['value'];
          break;
        case 'item':
          // Not every Item has attributes
          if (isSet($value['attributes'])) {
            $key = $value['attributes']['key'];
          } else {
            $key = "";
          }

          switch ($value['type']) {
            case 'open':
              array_push($depth, $key);
              break;
            case 'complete':
              array_push($depth, $key);
              $p = join('::',$depth);

              // enn_change - make sure that   $value['value']   is defined
              if (isSet($value['value'])){
                $temp[$p] = $value['value'];
              } else {
                $temp[$p] = "";
              }

              array_pop($depth);
              break;
            case 'close':
              array_pop($depth);
              break;
          }
          break;
        case 'dt_assoc':
        case 'dt_array':
          break;
      }
    }

    foreach ($temp as $key=>$value) {
      $levels = explode('::',$key);
      $num_levels = count($levels);

      if ($num_levels==1) {
        $_data[$levels[0]] = $value;
      } else {
        $pointer = &$_data;
        for ($i=0; $i<$num_levels; $i++) {
          if ( !isset( $pointer[$levels[$i]] ) ) {
            $pointer[$levels[$i]] = array();
          }
          $pointer = &$pointer[$levels[$i]];
        }
        $pointer = $value;
      }
    }
    return $_data;
  }


  /**
   * Converts a PHP array into an OPS message
   * @param array   PHP array
   * @return  string    OPS XML message
   */
  public function encodeRequest(array $array) {
    $this->_MSGCNT++;
    $msg_id = $this->_SESSID + $this->_MSGCNT;      /* addition removes the leading zero */
    $msg_type = $this->_MSGTYPE_STD;

    if ($array['protocol']) {
      $array['protocol'] = strtoupper($array['protocol']);
    }else{
      $array['protocol'] = $this->config['OSRS_PROTOCOL'];
    }
    if ($array['action']) {
      $array['action'] = strtoupper($array['action']);
    }
    if ($array['object']) {
      $array['object'] = strtoupper($array['object']);
    }

    $xml_data_block = $this->PHP2XML($array);
    $ops_msg = '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>' . $this->_CRLF .
      '<!DOCTYPE OPS_envelope SYSTEM "ops.dtd">' . $this->_CRLF .
      '<OPS_envelope>' . $this->_CRLF .
      $this->_SPACER . '<header>' . $this->_CRLF .
      $this->_SPACER . $this->_SPACER . '<version>' . $this->_OPS_VERSION . '</version>' . $this->_CRLF .
      $this->_SPACER . $this->_SPACER . '<msg_id>' . $msg_id . '</msg_id>' . $this->_CRLF .
      $this->_SPACER . $this->_SPACER . '<msg_type>' . $msg_type . '</msg_type>' . $this->_CRLF .
      $this->_SPACER . '</header>' . $this->_CRLF .
      $this->_SPACER . '<body>' . $this->_CRLF .
      $xml_data_block . $this->_CRLF .
      $this->_SPACER . '</body>' . $this->_CRLF .
      '</OPS_envelope>';

    return $ops_msg;
  }


  /**
   * Converts a PHP array into an OPS data_block tag
   * @param array   PHP array
   * @return  string    OPS data_block tag
   */
  protected function PHP2XML($data) {
    return str_repeat($this->_SPACER,2) . '<data_block>' . $this->_convertData($data, 3) . $this->_CRLF . str_repeat($this->_SPACER,2) . '</data_block>';
  }


  /**
   * Recursivly converts PHP data into XML
   * @param mixed   PHP array or data
   * @param int     ident level
   * @return  string    XML string
   */
  protected function _convertData(&$array, $indent=0) {
    $string = '';
    $IND = str_repeat($this->_SPACER,$indent);

    if (is_array($array)) {
      if ($this->_is_assoc($array)) {   # HASH REFERENCE
        $string .= $this->_CRLF . $IND . '<dt_assoc>';
        $end = '</dt_assoc>';
      } else {        # ARRAY REFERENCE
        $string .= $this->_CRLF . $IND . '<dt_array>';
        $end = '</dt_array>';
      }

      foreach ($array as $k=>$v) {
        $indent++;
        /* don't encode some types of stuff */
        if ((gettype($v)=='resource') || (gettype($v)=='user function') || (gettype($v)=='unknown type')) {
          continue;
        }

        $string .= $this->_CRLF . $IND . '<item key="' . $k . '"';
        if (gettype($v)=='object' && get_class($v)) {
          $string .= ' class="' . get_class($v) . '"';
        }

        $string .= '>';
        if (is_array($v) || is_object($v)) {
          $string .= $this->_convertData($v, $indent+1);
          $string .= $this->_CRLF . $IND . '</item>';
        } else {
          $string .= $this->_quoteXMLChars($v) . '</item>';
        }

        $indent--;
      }
      $string .= $this->_CRLF . $IND . $end;
    } else {          # SCALAR
      $string .= $this->_CRLF . $IND . '<dt_scalar>' .
        $this->_quoteXMLChars($array) . '</dt_scalar>';
    }
    return $string;
  }


  /**
   * Quotes special XML characters
   * @param string    string to quote
   * @return  string    quoted string
   */
  protected function _quoteXMLChars($string) {
    $search  = array ('&', '<', '>', "'", '"');
    $replace = array ('&amp;', '&lt;', '&gt;', '&apos;', '&quot;');
    $string = str_replace($search, $replace, $string);
    $string = utf8_encode($string);
    return $string;
  }


  /**
   * Determines if an array is associative or not, since PHP
   * doesn't really distinguish between the two, but Perl/OPS does
   * @param array   array to check
   * @return  boolean   true if the array is associative
   */
  protected function _is_assoc(&$array){
    if (is_array($array)) {
      foreach ($array as $k=>$v) {
        if (!is_int($k)) return true;
      }
    }
    return false;
  }

}
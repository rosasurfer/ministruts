<?php
/*
 * XML-RPC Inwx-Domrobot
 *
 * XML-RPC support in PHP is not enabled by default.
 * You will need to use the --with-xmlrpc[=DIR] configuration option when compiling PHP to enable XML-RPC support.
 *
 * Changelog
 * 04.08.2011 - v2.2
 *    - use "nested" methods  (e.g. domain.check)
 *    - removed nonce and secure-login
 *    - added setter and getter
 *    - added credentials params to login function
 *    - response utf-8 decoded
 *      - removed newlines and white spaces in xml request (verbosity=no_white_space)
 *    - added optional clTRID set/get functions
 *
 * 19.07.2011 - v2.1
 *      - using cookiefile instead of session
 *      - added login and logout function
 *      - added client version transmission
 *
 *
 * 2011 by InterNetworX Ltd. & Co. KG
 */

class domrobot {
   private $debug=false;
   private $address;
   private $language;
   private $customer=false;
   private $clTRID = null;

   private $_ver = "2.2";
   private $_cookiefile = "domrobot.tmp";

   function __construct($address) {
      $this->address = (substr($address,-1)!="/")?$address."/":$address;

      $seperator = (DIRECTORY_SEPARATOR=="/" || DIRECTORY_SEPARATOR=="\\")?DIRECTORY_SEPARATOR:"/";
      $this->_cookiefile = dirname(__FILE__).$seperator.$this->_cookiefile;

      if (file_exists($this->_cookiefile) && !is_writable($this->_cookiefile) ||
         !file_exists($this->_cookiefile) && !is_writeable(dirname(__FILE__))) {
         throw new Exception("Cannot write cookiefile: '{$this->_cookiefile}'. Please check file/folder permissions.",2400);
      }
   }

   public function setLanguage($language) {
      $this->language = $language;
   }
   public function getLanguage() {
      return $this->language;
   }

   public function setDebug($debug=false) {
      $this->debug = (bool)$debug;
   }
   public function getDebug() {
      return $this->debug;
   }

   public function setCustomer($customer) {
      $this->customer = (string)$customer;
   }
   public function getCustomer() {
      return $this->customer;
   }

   public function setClTrId($clTrId) {
      $this->clTRID = (string)$clTrId;
   }
   public function getClTrId() {
      return $this->clTRID;
   }

   public function login($username,$password) {
        $fp = fopen($this->_cookiefile, "w");
        fclose($fp);

        if (!empty($this->language)) {
         $params['lang'] = $this->language;
        }
      $params['user'] = $username;
      $params['pass'] = $password;

      return $this->call('account','login',$params);
   }

   public function logout() {
      $ret = $this->call('account','logout');
      if (file_exists($this->_cookiefile)) {
         unlink($this->_cookiefile);
      }
      return $ret;
   }

   public function call($object, $method, array $params=array()) {
      if (isset($this->customer) && $this->customer!="") {
         $params['subuser'] = $this->customer;
      }
      if (!empty($this->clTRID)) {
         $params['clTRID'] = $this->clTRID;
      }

      $request = xmlrpc_encode_request(strtolower($object.".".$method), $params, array("encoding"=>"UTF-8","escaping"=>"markup","verbosity"=>"no_white_space"));

      $header[] = "Content-Type: text/xml";
      $header[] = "Connection: keep-alive";
      $header[] = "Keep-Alive: 300";
      $header[] = "X-FORWARDED-FOR: ".@$_SERVER['HTTP_X_FORWARDED_FOR'];
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$this->address);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch,CURLOPT_TIMEOUT,65);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
      curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
      curl_setopt($ch,CURLOPT_COOKIEFILE,$this->_cookiefile);
      curl_setopt($ch,CURLOPT_COOKIEJAR,$this->_cookiefile);
      curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request);
      curl_setopt($ch,CURLOPT_USERAGENT,"DomRobot/{$this->_ver} (PHP ".phpversion().")");

      $response = curl_exec($ch);
      curl_close($ch);
      if ($this->debug) {
         echo "Request:\n".$request."\n";
         echo "Response:\n".$response."\n";
      }

      return xmlrpc_decode($response,'UTF-8');
   }
}

<?php
if (!isSet($_SERVER['REQUEST_METHOD']))            // exit, falls wir in einer Shell laufen
   exit(0);


$address = getRemoteAddress();
$name    = getHostByAddr($address);

if ($address == $name) echoPre('current IP address: '.$address);
else                   echoPre('current IP address: '.$address.'  ('.$name.')');


$value = getForwardedRemoteAddress();
if ($value) {
   if (!isIPAddress($value) || ($value==($name=getHostByAddr($value)))) {
      echoPre('forwarded for:      '.$value);
   }
   else {
      echoPre('forwarded for:      '.$value.'  ('.$name.')');
   }
}


// --------------------------------------------------


/**
 * Hilfsfunktion zur formatierten Ausgabe einer Variable.
 *
 * @param  mixed $var - die auszugebende Variable
 */
function echoPre($var) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = $var->__toString();
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   else {
      $str = (string) $var;
   }

   if (isSet($_SERVER['REQUEST_METHOD']))
      $str = '<div align="left"><pre style="z-index:65535; margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';
   $str .= "\n";

   echo $str;
}


/**
 * Gibt die IP-Adresse zurück, von der aus der Request ausgelöst wurde.
 *
 * @return string - IP-Adresse
 */
function getRemoteAddress() {
   return $_SERVER['REMOTE_ADDR'];
}


/**
 * Gibt den Wert des 'X-Forwarded-For'-Headers des aktuellen Requests zurück.
 *
 * @return string - Wert (ein oder mehrere IP-Adressen oder Hostnamen) oder NULL, wenn der Header nicht gesetzt ist
 */
function getForwardedRemoteAddress() {
   return getHeaderValue(array('X-Forwarded-For', 'X-UP-Forwarded-For'));
}


/**
 * Gibt den Wert des angegebenen Headers als String zurück. Wird ein Array mit mehreren Namen angegeben oder wurden
 * mehrere Header des angegebenen Namens übertragen, werden alle Werte dieser Header als eine komma-getrennte Liste
 * zurückgegeben (in der übertragenen Reihenfolge).
 *
 * @param  string|array $names - ein oder mehrere Headernamen
 *
 * @return string - Wert oder NULL, wenn die angegebenen Header nicht gesetzt sind
 */
function getHeaderValue($names) {
   if (is_string($names))
      $names = array($names);
   elseif (is_array($names)) {
      foreach ($names as $name)
         if (!is_string($name)) throw new Exception('Illegal argument type in argument $names: '.getType($name));
   }
   else throw new Exception('Illegal type of parameter $names: '.getType($names));

   $headers = getHeaders($names);
   if ($headers)
      return join(',', $headers);

   return null;
}


/**
 * Gibt die angegebenen Header als Array von Name-Wert-Paaren zurück (in der übertragenen Reihenfolge).
 *
 * @param  string|array $names - ein oder mehrere Namen; ohne Angabe werden alle Header zurückgegeben
 *
 * @return array - Name-Wert-Paare
 */
function getHeaders($names = null) {
   if     ($names === null)   $names = array();
   elseif (is_string($names)) $names = array($names);
   elseif (is_array($names)) {
      foreach ($names as $name) {
         if (!is_string($name)) throw new Exception('Illegal argument type in argument $names: '.getType($name));
      }
   }
   else throw new Exception('Illegal type of parameter $names: '.getType($names));

   // einmal alle Header einlesen
   static $headers = null;
   if ($headers === null) {
      if (function_exists('getAllHeaders')) {
         $headers = getAllHeaders();
         if ($headers === false)
            throw new Exception('Error reading request headers, getAllHeaders() returned: FALSE');
      }
      else {
         $headers = array();
         foreach ($_SERVER as $key => $value) {
            if(subStr($key, 0, 5) == 'HTTP_') {
               $key = strToLower(subStr($key, 5));
               $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', $key)));
               $headers[$key] = $value;
            }
         }
      }
   }

   // alle oder nur die gewünschten Header zurückgeben
   if (!$names)
      return $headers;

   return array_intersect_ukey($headers, array_flip($names), 'strCaseCmp');
}


/**
 * Ob der übergebene String eine syntaktisch gültige IP-Adresse ist.
 *
 * @param  string $string      - der zu überprüfende String
 * @param  bool   $returnBytes - Typ des Rückgabewertes
 *                               FALSE: Boolean (default)
 *                               TRUE:  Array mit den Adressbytes oder FALSE, wenn der String keine gültige IP-Adresse darstellt
 * @return bool|array
 */
function isIPAddress($string, $returnBytes=false) {
   static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';

   $result = is_string($string) && strLen($string) && preg_match($pattern, $string, $bytes);

   if ($result) {
      array_shift($bytes);

      foreach ($bytes as $i => $byte) {
         $b = (int) $byte;
         if (!is_string($byte) || $b > 255)
            return false;
         $bytes[$i] = $b;
      }

      if ($bytes[0] == 0)
         return false;

      return $returnBytes ? $bytes : true;
   }
   return false;
}

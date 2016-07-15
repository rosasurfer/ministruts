<?php
use rosasurfer\ministruts\core\StaticClass;

use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\RuntimeException;

use const rosasurfer\L_NOTICE;


/**
 * String
 */
final class String extends StaticClass {


   /**
    * Dekodiert einen String von UTF-8 nach ISO-8859-1. Enthält der String keine gültige UTF-8-Zeichensequenz,
    * wird der Original-Wert zurückgegeben.
    *
    * @param  mixed $string - der zu dekodierende String oder ein Array mit mehreren zu dekodierenden Strings
    *
    * @return mixed - dekodierte(r) String(s)
    */
   public static function decodeUtf8($string) {
      if (is_array($string)) {
         $array = array();
         foreach ($string as $key => $value) {
            if (is_string($key))
               $key = self::{__FUNCTION__}($key);
            $array[$key] = self::{__FUNCTION__}($value);
         }
         return $array;
      }

      if ($string===null || $string==='')
         return $string;

      if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));


      // prüfen, ob iconv() verfügbar ist, denn PHP schreibt bei fatalem Fehler keinen Fehler ins Errorlog: DANKE, PHP-Team !!!
      static $function_exists = null;
      if ($function_exists === null)
         if (!$function_exists = function_exists('iconv'))
            throw new RuntimeException('Fatal error: Call to undefined function iconv()');


      $php_errormsg = null;
      $decoded = @iconv('UTF-8', 'ISO-8859-1', $string);

      if (isSet($php_errormsg)) {
         if ($php_errormsg != 'iconv(): Detected an illegal character in input string' &&
             $php_errormsg != 'iconv(): Detected an incomplete multibyte character in input string') {
            Logger ::log($php_errormsg.': '.$string, L_NOTICE, __CLASS__);
         }
         return $string;
      }

      return $decoded;
   }
}

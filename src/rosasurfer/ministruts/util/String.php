<?php
/**
 * String
 */
final class String extends StaticClass {


   /**
    * Ob ein String UTF-8-kodiert ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isUtf8Encoded($string) {
      if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

      // TODO: String::isUtf8Encoded() enthält Fehler

      // z.B. /anmelden.php~ÝÜÃÂÛÚËÊ..... => TRUE
      return ($string!='' && self:: contains($string, 'Ã'));
   }


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
               $key = self:: decodeUtf8($key);
            $array[$key] = self:: decodeUtf8($value);
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
            throw new plRuntimeException('Fatal error: Call to undefined function iconv()');


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


   /**
    * Kodiert Strings von ISO-8859-1 nach UTF-8. Verarbeitet sowohl einzelne Strings als auch String-Arrays.
    *
    * @param  mixed $string - zu kodierende(r) String(s)
    *
    * @return mixed - kodierte(r) String(s)
    */
   public static function encodeUtf8($string) {
      if (is_array($string)) {
         $array = array();
         foreach ($string as $key => $value) {
            if (is_string($key))
               $key = self:: encodeUtf8($key);
            $array[$key] = self:: encodeUtf8($value);
         }
         return $array;
      }

      if (!is_string($string) || $string==='')
         return $string;

      return utf8_encode($string);
   }


   /**
    * Konvertiert Zeichen mit spezieller HTML-Bedeutung in ihre entsprechenden HTML-Entities.
    * Diese Methode macht dasselbe wie die interne PHP-Funktion gleichen Namens mit dem Unterschied,
    * daß der Default-Value des Parameters $quote_style nicht ENT_COMPAT sondern ENT_QUOTES ist.
    * Weiterhin können als erster Parameter auch String-Arrays übergeben werden.
    *
    * Bedeutung der optionalen Parameter: siehe PHP-Manual
    *
    * @param  mixed  $string       - der/die zu konvertierende/n Strings
    * @param  int    $quoteStyle   - siehe Bemerkungen
    * @param  string $charset      -
    * @param  bool   $doubleEncode - (PHP 5.2.3+)
    *
    * @return mixed - der/die konvertierte/n Strings
    *
    * Note:
    * -----
    * The translations performed are:
    *    '&' (ampersand) becomes '&amp;'
    *    '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set
    *    ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set
    *    '<' (less than) becomes '&lt;'
    *    '>' (greater than) becomes '&gt;'
    */
   public static function htmlSpecialChars($string, $quoteStyle=ENT_QUOTES, $charset='ISO-8859-1', $doubleEncode=true) {
      if (is_array($string)) {
         foreach ($string as $key => $value)
            $string[$key] = self:: htmlSpecialChars($value, $quoteStyle, $charset, $doubleEncode);
         return $string;
      }
      if (!strLen($string))
         return $string;

      if (PHP_VERSION < '5.2.3')
         return htmlSpecialChars($string, $quoteStyle, $charset);

      return htmlSpecialChars($string, $quoteStyle, $charset, $doubleEncode);
   }


   /**
    * Konvertiert alle Zeichen eines Strings in ihre entsprechenden HTML-Entities.
    * Diese Methode macht dasselbe wie die interne PHP-Funktion gleichen Namens mit dem Unterschied,
    * daß der Default-Value von quote_style nicht ENT_COMPAT sondern ENT_QUOTES ist.  Weiterhin können
    * als erster Parameter auch String-Arrays übergeben werden.
    *
    * Bedeutung der optionalen Parameter: siehe PHP-Manual
    *
    * @param  mixed  $string       - der/die zu konvertierende/n Strings
    * @param  int    $quoteStyle   - (see PHP manual)
    * @param  string $charset      -
    * @param  bool   $doubleEncode - (PHP 5.2.3+)
    *
    * @return mixed - der/die konvertierte/n Strings
    */
   public static function htmlEntities($string, $quoteStyle=ENT_QUOTES, $charset='ISO-8859-1', $doubleEncode=true) {
      if (is_array($string)) {
         foreach ($string as $key => $value)
            $string[$key] = self:: htmlEntities($value, $quoteStyle, $charset, $doubleEncode);
         return $string;
      }
      if (!strLen($string))
         return $string;

      if (PHP_VERSION < '5.2.3')
         return htmlEntities($string, $quoteStyle, $charset);

      return htmlEntities($string, $quoteStyle, $charset, $doubleEncode);
   }


   /**
    * Entfernt Zeilenumbrüche aus einem String und ersetzt sie mit Leerzeichen.  Mehrere aufeinanderfolgende
    * Zeilenumbrüche werden auf ein Leerzeichen reduziert.
    *
    * @param  string $string - der zu bearbeitende String
    *
    * @return String
    */
   public static function stripLineBreaks($string) {
      if ($string===null || $string==='')
         return $string;

      if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

      return str_replace(array("\r\n", "\r", "\n"),
                         array("\n"  , "\n", " " ),
                         $string);
   }


   /**
    * Ersetzt in einem String mehrfache durch einfache Leerzeichen.
    *
    * @param  string $string - der zu bearbeitende String
    *
    * @return String
    */
   public static function stripDoubleSpaces($string) {
      if ($string===null || $string==='')
         return $string;

      if (!is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

      return preg_replace('/\s+/', ' ', $string);
   }
}
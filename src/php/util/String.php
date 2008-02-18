<?
/**
 * String
 */
final class String extends StaticClass {


   /**
    * Ob ein String mit einem anderen String beginnt.
    *
    * @param string  $string     - der zu prüfende String
    * @param string  $start      - der zu suchende String
    * @param boolean $ignoreCase - ob bei der Suche Groß-/Kleinschreibung ignoriert werden soll (Default: nein)
    *
    * @return boolean
    */
   public static function startsWith($string, $start, $ignoreCase = false) {
      if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
      if ($start!==null && !is_string($start))   throw new IllegalTypeException('Illegal type of parameter $start: '.getType($start));
      if ($start == '')                          throw new InvalidArgumentException('Invalid argument $start: "'.$start.'"');
      if (!is_bool($ignoreCase))                 throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

      if ($ignoreCase)
         return (striPos($string, $start) === 0);

      return (strPos($string, $start) === 0);
   }


   /**
    * Ob ein String mit einem anderen String endet.
    *
    * @param string  $string     - der zu prüfende String
    * @param string  $end        - der zu suchende String
    * @param boolean $ignoreCase - ob bei der Suche Groß-/Kleinschreibung ignoriert werden soll (Default: nein)
    *
    * @return boolean
    */
   public static function endsWith($string, $end, $ignoreCase = false) {
      if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));
      if ($end!==null && !is_string($end))       throw new IllegalTypeException('Illegal type of parameter $end: '.getType($end));
      if (!is_bool($ignoreCase))                       throw new IllegalTypeException('Illegal type of parameter $ignoreCase: '.getType($ignoreCase));

      return self:: startsWith(strRev($string), strRev($end), $ignoreCase);
   }


   /**
    * Ob ein String einen anderen String enthält.
    *
    * @param string $haystack - der zu prüfende String
    * @param string $needle   - der zu suchende String
    * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: ja)
    *
    * @return boolean
    */
   public static function contains($haystack, $needle, $case = true) {
      if ($haystack!==null && !is_string($haystack)) throw new IllegalTypeException('Illegal type of parameter $haystack: '.getType($haystack));
      if ($needle!==null && !is_string($needle))     throw new IllegalTypeException('Illegal type of parameter $needle: '.getType($needle));
      if ($case!==true && $case!==false)             throw new IllegalTypeException('Illegal type of parameter $case: '.getType($case));

      if ($case)
         return (strPos($haystack, $needle) !== false);

      return (striPos($haystack, $needle) !== false);
   }


   /**
    * Ob ein String UTF-8-kodiert ist.
    *
    * @param string $string - der zu prüfende String
    *
    * @return boolean
    *
    * TODO: String::isUtf8Encoded() überarbeiten
    */
   public static function isUtf8Encoded($string) {
      if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

      return self:: contains($string, 'Ã');
   }


   /**
    * Dekodiert UTF-8-kodierte Strings nach ISO-8859-1. Verarbeitet sowohl einzelne Strings als auch
    * String-Arrays.
    *
    * @param mixed $string - der/die zu dekodierende/n Strings
    *
    * @return mixed - der/die dekodierte/n Strings
    */
   public static function decodeUtf8($string) {
      if (is_array($string)) {
         foreach ($string as $key => &$value)
            $string[$key] = self:: decodeUtf8($value);
         return $string;
      }
      if ($string!==null && !is_string($string)) throw new IllegalTypeException('Illegal type of parameter $string: '.getType($string));

      if (!self:: isUtf8Encoded($string))
         return $string;

      return html_entity_decode(htmlEntities($string, ENT_NOQUOTES, 'UTF-8'));
   }


   /**
    * Konvertiert Zeichen mit spezieller HTML-Bedeutung in ihre entsprechenden HTML-Entities.
    * Diese Methode macht dasselbe wie die interne PHP-Funktion gleichen Namens mit dem Unterschied,
    * daß der Default-Value von quote_style nicht ENT_COMPAT sondern ENT_QUOTES ist.  Weiterhin können
    * als erster Parameter auch String-Arrays übergeben werden.
    *
    * Bedeutung der optionalen Parameter: siehe PHP-Manual
    *
    * @param mixed  $string        - der/die zu konvertierende/n Strings
    * @param int    $quote_style   -
    * @param string $charset       -
    * @param bool   $double_encode - (PHP 5.2.3+)
    *
    * @return mixed - der/die konvertierte/n Strings
    */
   public static function htmlSpecialChars($string, $quote_style=ENT_QUOTES, $charset='ISO-8859-1', $double_encode=true) {
      if (is_array($string)) {
         foreach ($string as $key => &$value)
            $string[$key] = self:: htmlSpecialChars($value, $quote_style, $charset, $double_encode);
         return $string;
      }
      if ($string === null)
         return null;

      if (PHP_VERSION < '5.2.3')
         return htmlSpecialChars($string, $quote_style, $charset);

      return htmlSpecialChars($string, $quote_style, $charset, $double_encode);
   }


   /**
    * Konvertiert alle Zeichen eines Strings in ihre entsprechenden HTML-Entities.
    * Diese Methode macht dasselbe wie die interne PHP-Funktion gleichen Namens mit dem Unterschied,
    * daß der Default-Value von quote_style nicht ENT_COMPAT sondern ENT_QUOTES ist.  Weiterhin können
    * als erster Parameter auch String-Arrays übergeben werden.
    *
    * Bedeutung der optionalen Parameter: siehe PHP-Manual
    *
    * @param mixed  $string        - der/die zu konvertierende/n Strings
    * @param int    $quote_style   -
    * @param string $charset       -
    * @param bool   $double_encode - (PHP 5.2.3+)
    *
    * @return mixed - der/die konvertierte/n Strings
    */
   public static function htmlEntities($string, $quote_style=ENT_QUOTES, $charset='ISO-8859-1', $double_encode=true) {
      if (is_array($string)) {
         foreach ($string as $key => &$value)
            $string[$key] = self:: htmlEntities($value, $quote_style, $charset, $double_encode);
         return $string;
      }
      if ($string === null)
         return null;

      if (PHP_VERSION < '5.2.3')
         return htmlEntities($string, $quote_style, $charset);

      return htmlEntities($string, $quote_style, $charset, $double_encode);
   }
}

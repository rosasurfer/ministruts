<?
/**
 * String
 */
final class String extends StaticClass {


   /**
    * Ob ein String mit einem anderen String beginnt.
    *
    * @param string $haystack - der zu prüfende String
    * @param string $needle   - der zu suchende String
    * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: ja)
    *
    * @return boolean
    */
   public static function startsWith($haystack, $needle, $case = true) {
      if ($case)
         return (strPos($haystack, $needle) === 0);

      return (striPos($haystack, $needle) === 0);
   }


   /**
    * Ob ein String mit einem anderen String endet.
    *
    * @param string $haystack - der zu prüfende String
    * @param string $needle   - der zu suchende String
    * @param boolean $case    - ob bei der Suche Groß-/Kleinschreibung beachtet werden soll (Default: ja)
    *
    * @return boolean
    */
   public static function endsWith($haystack, $needle, $case = true) {
      return self:: startsWith(strRev($haystack), strRev($needle), $case);
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
      return self:: contains($string, 'Ã');
   }


   /**
    * Dekodiert einen UTF-8-kodierten String nach ISO-8859-1.
    *
    * @param string $string - der zu dekodierende String
    *
    * @return string
    */
   public static function decodeUtf8($string) {
      if (!self:: isUtf8Encoded($string))
         return $string;

      // TODO: htmlEntities('UTF-8') verfälscht den String, wenn er nicht utf-8-kodiert ist
      return html_entity_decode(htmlEntities($string, ENT_NOQUOTES, 'UTF-8'));
   }
}

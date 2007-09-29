<?
/**
 * StringUtils
 */
final class StringUtils extends StaticFactory {


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
}

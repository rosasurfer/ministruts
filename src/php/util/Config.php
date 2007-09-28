<?
/**
 * Config
 */
class Config extends Object {

   /**
    */
   public static function get($name) {
      return constant('self::'.$name);
   }
}

<?php
/**
 * PHP
 */
final class PHP extends StaticClass {


   /**
    * Stößt wenn möglich den Garbage-Collector an (erfordert mind. PHP-Version 5.3).
    *
    * @return bool - ob der Zugriff auf den Garbage-Collector möglich ist
    */
   public static function collectGarbage() {
      if (PHP_VERSION >= '5.3') {
         $enabled = gc_enabled();
         if (!$enabled)
            gc_enable();

         gc_collect_cycles();

         if (!$enabled)
            gc_disable();
         return true;
      }
      return false;
   }
}

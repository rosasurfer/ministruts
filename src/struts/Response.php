<?php
namespace rosasurfer\struts;

use rosasurfer\core\Singleton;

use const rosasurfer\CLI;


/**
 * Response
 *
 * Wrapper für den HTTP-Response.
 */
class Response extends Singleton {


   // Attribute-Pool
   private $attributes = array();


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
    * wurde. In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return self - Instanz oder NULL
    */
   public static function me() {
      if (!CLI) return Singleton::getInstance(static::class);
      return null;
   }


   /**
    * Speichert einen Wert unter dem angegebenen Schlüssel im Response.
    *
    * @param  string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param  mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, &$value) {
      $this->attributes[$key] = $value;
   }


   /**
    * Gibt den unter dem angegebenen Schlüssel gespeicherten Wert zurück oder NULL, wenn unter diesem
    * Namen kein Wert existiert.
    *
    * @param  string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - der gespeicherte Wert oder NULL
    */
   public function &getAttribute($key) {
      if (isSet($this->attributes[$key]))
         return $this->attributes[$key];

      $value = null;
      return $value;    // Referenz auf NULL
   }
}

<?php
use const rosasurfer\CLI;


/**
 * Wrapper für RequestBase
 *
 * NOTE: Wird eine projektspezifische Request-Implementierung benötigt, kann im Projekt eine eigene Klasse Request deklariert werden
 *       (die wie diese von RequestBase abgeleitet ist). Diese Klasse kann dann projektspezifisch angepaßt werden.
 *
 * @see RequestBase
 */
final class Request extends RequestBase {


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
    * wurde. In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return Singleton - Instanz oder NULL
    */
   public static function me() {
      if (!CLI)
         return Singleton ::getInstance(__CLASS__);
      return null;
   }
}

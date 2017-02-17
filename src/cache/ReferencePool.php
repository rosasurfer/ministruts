<?php
namespace rosasurfer\cache;

use rosasurfer\dependency\Dependency;
use rosasurfer\exception\IllegalTypeException;


/**
 * ReferencePool
 *
 * Die einfachste CachePeer-Implementierung.  Dieser Cache  speichert die Objekte nur im Speicher des
 * aktuellen Prozesses, die Werte bleiben also nach Ende des Requests nicht erhalten.  Er stellt jedoch
 * sicher, dass mehrere Zugriffe auf ein gespeichertes Object immer ein und dieselbe Instanz zurueckliefern
 * und wird deshalb intern von jedem Cache als Reference-Pool verwendet.
 *
 * TODO: ReferencePool muss ein Singleton sein
 */
final class ReferencePool extends CachePeer {


   /** @var mixed[] - Array mit den gespeicherten Referenzen */
   private $pool;


   /**
    * Constructor.
    *
    * Erzeugt eine neue Instanz.  Ein evt. angegebenes Label (Namespace) wird ignoriert, da ein ReferencePool
    * Referenzen verwaltet und im Speicher des Prozesses nur ein Namespace existiert.
    *
    * @param  string $label   - Cache-Bezeichner
    * @param  array  $options - zusaetzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
   }


   /**
    * ReferencePool-Instanzen benoetigen im Gegensatz zu anderen CachePeer-Implementierungen intern keinen
    * weiteren ReferencePool.  Diese Methode gibt daher einen Zeiger auf die Instanz selbst zurueck und
    * ueberschreibt damit die Default-Implementierung in CachePeer.
    *
    * @return self
    *
    * @see CachePeer::getReferencePool()
    */
   protected function getReferencePool() {
      return $this;
   }


   /**
    * Ob unter dem angegebenen Schluessel ein Wert im Cache gespeichert ist.
    *
    * @param  string $key - Schluessel
    *
    * @return bool
    */
   public function isCached($key) {
      if (!isSet($this->pool[$key]))
         return false;


      /*
      // Solange wir im ReferencePool nicht den created-Wert aus dem Cache haben, koennen wir expires
      // und minValidity nicht pruefen
      $dependency = $this->pool[$key][3];

      if ($dependency && !$dependency->getMinValidity() && !$dependency->isValid()) {
         unSet($this->pool[$key]);
         return false;
      }
      */

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurueck.  Existiert der Wert nicht, wird der angegebene Defaultwert
    * zurueckgegeben.
    *
    * @param  string $key     - Schluessel, unter dem der Wert gespeichert ist
    * @param  mixed  $default - Defaultwert (kann selbst auch NULL sein)
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schluessel existiert.
    *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurueckgegeben.
    */
   public function get($key, $default = null) {
      if ($this->isCached($key))
         return $this->pool[$key][1];

      return $default;
   }


   /**
    * Loescht einen Wert aus dem Cache.
    *
    * @param  string $key - Schluessel, unter dem der Wert gespeichert ist
    *
    * @return bool - TRUE bei Erfolg, FALSE, falls kein solcher Schluessel existier
    */
   public function drop($key) {
      if (isSet($this->pool[$key])) {
         unSet($this->pool[$key]);
         return true;
      }
      return false;
   }


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schluessel wird
    * ueberschrieben.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
    * Abhaengigkeit, wird der Wert automatisch ungueltig.
    *
    * @param  string      $key        - Schluessel, unter dem der Wert gespeichert wird
    * @param  mixed       $value      - der zu speichernde Wert
    * @param  int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt
    * @param  Dependency  $dependency - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
    *
    * @return bool - TRUE bei Erfolg, FALSE andererseits
    */
   public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
      if (!is_string($key))  throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if (!is_int($expires)) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      // im Cache wird ein Array[creation_timestamp, value, expires, dependency] gespeichert
      $this->pool[$key] = array($timestamp=null, $value, $expires=null, $dependency);

      return true;
   }
}

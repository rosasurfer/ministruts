<?php
/**
 * ReferencePool
 *
 * Die einfachste CachePeer-Implementierung.  Dieser Cache  speichert die Objekte nur im Speicher des
 * aktuellen Prozesses, die Werte bleiben also nach Ende des Requests nicht erhalten.  Er stellt jedoch
 * sicher, daß mehrere Zugriffe auf ein gespeichertes Object immer ein und dieselbe Instanz zurückliefern
 * und wird deshalb intern von jedem Cache als Reference-Pool verwendet.
 *
 * TODO: ReferencePool muß ein Singleton sein
 */
final class ReferencePool extends CachePeer {


   /**
    * Array mit den gespeicherten Referenzen
    */
   private /*mixed[][4]*/ $pool;


   /**
    * Constructor.
    *
    * Erzeugt eine neue Instanz.  Ein evt. angegebenes Label (Namespace) wird ignoriert, da ein ReferencePool
    * Referenzen verwaltet und im Speicher des Prozesses nur ein Namespace existiert.
    *
    * @param string $label   - Cache-Bezeichner
    * @param array  $options - zusätzliche Optionen
    */
   public function __construct($label = null, array $options = null) {
   }


   /**
    * ReferencePool-Instanzen benötigen im Gegensatz zu anderen CachePeer-Implementierungen intern keinen
    * weiteren ReferencePool.  Diese Methode gibt daher einen Zeiger auf die Instanz selbst zurück und
    * überschreibt damit die Default-Implementierung in CachePeer.
    *
    * @return ReferencePool
    *
    * @see CachePeer::getReferencePool()
    */
   protected function getReferencePool() {
      return $this;
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert im Cache gespeichert ist.
    *
    * @param string $key - Schlüssel
    *
    * @return bool
    */
   public function isCached($key) {
      if (!isSet($this->pool[$key]))
         return false;


      /*
      // Solange wir im ReferencePool nicht den created-Wert aus dem Cache haben, können wir expires
      // und minValidity nicht prüfen
      $dependency = $this->pool[$key][3];

      if ($dependency && !$dependency->getMinValidity() && !$dependency->isValid()) {
         unSet($this->pool[$key]);
         return false;
      }
      */

      return true;
   }


   /**
    * Gibt einen Wert aus dem Cache zurück.  Existiert der Wert nicht, wird der angegebene Defaultwert
    * zurückgegeben.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert ist
    * @param mixed  $default - Defaultwert (kann selbst auch NULL sein)
    *
    * @return mixed - Der gespeicherte Wert oder NULL, falls kein solcher Schlüssel existiert.
    *                 Achtung: Ist im Cache ein NULL-Wert gespeichert, wird ebenfalls NULL zurückgegeben.
    */
   public function get($key, $default = null) {
      if ($this->isCached($key))
         return $this->pool[$key][1];

      return $default;
   }


   /**
    * Löscht einen Wert aus dem Cache.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return bool - TRUE bei Erfolg, FALSE, falls kein solcher Schlüssel existier
    */
   public function drop($key) {
      if (isSet($this->pool[$key])) {
         unSet($this->pool[$key]);
         return true;
      }
      return false;
   }


   /**
    * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schlüssel wird
    * überschrieben.  Läuft die angegebene Zeitspanne ab oder ändert sich der Status der angegebenen
    * Abhängigkeit, wird der Wert automatisch ungültig.
    *
    * @param string      $key        - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed       $value      - der zu speichernde Wert
    * @param int         $expires    - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfällt
    * @param Dependency  $dependency - Abhängigkeit der Gültigkeit des gespeicherten Wertes
    *
    * @return bool - TRUE bei Erfolg, FALSE andererseits
    */
   public function set($key, &$value, $expires = Cache ::EXPIRES_NEVER, Dependency $dependency = null) {
      if (!is_string($key))         throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if ($expires!==(int)$expires) throw new IllegalTypeException('Illegal type of parameter $expires: '.getType($expires));

      // im Cache wird ein Array[creation_timestamp, value, expires, dependency] gespeichert
      $this->pool[$key] = array(/*time()*/null, $value, /*$expires*/null, $dependency);

      return true;
   }
}

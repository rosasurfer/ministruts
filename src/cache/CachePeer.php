<?php
namespace rosasurfer\cache;

use rosasurfer\cache\monitor\Dependency;
use rosasurfer\core\Object;


/**
 * CachePeer
 *
 * Abstrakte Basisklasse fuer Cache-Implementierungen.
 *
 * Anwendung:
 * ----------
 *
 *    Wert speichern:
 *
 *       CachePeer::set($key, $value, $expires);
 *
 *
 *    Wert hinzufuegen (nur speichern, wenn er noch nicht im Cache existiert):
 *
 *       CachePeer::add($key, $value, $expires)
 *
 *
 *    Wert ersetzen (nur speichern, wenn er bereits im Cache existiert):
 *
 *       CachePeer::replace($key, $value, $expires)
 *
 *
 *    Wert aus dem Cache holen:
 *
 *       $value = CachePeer::get($key);
 *
 *
 *    Wert loeschen:
 *
 *       CachePeer::drop($key);
 *
 * @see ApcCache
 * @see FileSystemCache
 * @see ReferencePool
 */
abstract class CachePeer extends Object {


    /** @var string */
    protected $label;

    /** @var string */
    protected $namespace;

    /** @var array */
    protected $options;

    /** @var ReferencePool */
    private $referencePool;


    /**
     * Gibt den lokalen ReferencePool zurueck.
     *
     * @return ReferencePool
     */
    protected function getReferencePool() {
        if (!$this->referencePool)
            $this->referencePool = new ReferencePool($this->label);
        return $this->referencePool;
    }


    /**
     * Gibt einen Wert aus dem Cache zurueck.  Existiert der Wert nicht, wird der angegebene Defaultwert
     * zurueckgegeben.
     *
     * @param  string $key                - Schluessel, unter dem der Wert gespeichert ist
     * @param  mixed  $default [optional] - Defaultwert (kann selbst auch NULL sein)
     *
     * @return mixed - Der gespeicherte Wert, NULL, falls kein solcher Schluessel existiert oder der
     *                 angegebene Defaultwert
     */
    abstract public function get($key, $default = null);


    /**
     * Loescht einen Wert aus dem Cache.
     *
     * @param  string $key - Schluessel, unter dem der Wert gespeichert ist
     *
     * @return bool - TRUE bei Erfolg, FALSE, falls kein solcher Schluessel existiert
     */
    abstract public function drop($key);


    /**
     * Ob unter dem angegebenen Schluessel ein Wert im Cache gespeichert ist.
     *
     * @param  string $key - Schluessel
     *
     * @return bool
     */
    abstract public function isCached($key);


    /**
     * Speichert einen Wert im Cache.  Ein schon vorhandener Wert unter demselben Schluessel wird
     * ueberschrieben.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
     * Abhaengigkeit, wird der Wert automatisch ungueltig.
     *
     * @param  string     $key                   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed      $value                 - der zu speichernde Wert
     * @param  int        $expires    [optional] - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt (default: nie)
     * @param  Dependency $dependency [optional] - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    abstract public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null);


    /**
     * Speichert einen Wert im Cache nur dann, wenn noch kein Wert unter dem angegebenen Schluessel
     * existiert.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
     * Abhaengigkeit, wird der Wert automatisch ungueltig.
     *
     * @param  string     $key                   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed      $value                 - der zu speichernde Wert
     * @param  int        $expires    [optional] - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt (default: nie)
     * @param  Dependency $dependency [optional] - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    final public function add($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        if ($this->isCached($key))
            return false;

        return $this->set($key, $value, $expires, $dependency);
    }


    /**
     * Speichert einen Wert im Cache nur dann, wenn unter dem angegebenen Schluessel bereits ein Wert
     * existiert.  Laeuft die angegebene Zeitspanne ab oder aendert sich der Status der angegebenen
     * Abhaengigkeit, wird der Wert automatisch ungueltig.
     *
     * @param  string     $key                   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed      $value                 - der zu speichernde Wert
     * @param  int        $expires    [optional] - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt (default: nie)
     * @param  Dependency $dependency [optional] - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    final public function replace($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        if (!$this->isCached($key))
            return false;

        return $this->set($key, $value, $expires, $dependency);
    }
}

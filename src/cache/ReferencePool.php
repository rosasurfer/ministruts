<?php
namespace rosasurfer\cache;

use rosasurfer\cache\monitor\Dependency;
use rosasurfer\core\assert\Assert;


/**
 * ReferencePool
 *
 * Die einfachste CachePeer-Implementierung.  Dieser Cache  speichert die Objekte nur im Speicher des
 * aktuellen Prozesses, die Werte bleiben also nach Ende des Requests nicht erhalten.  Er stellt jedoch
 * sicher, dass mehrere Zugriffe auf ein gespeichertes Object immer ein und dieselbe Instanz zurueckliefern
 * und wird deshalb intern von jedem Cache als Reference-Pool verwendet.
 *
 * @todo  ReferencePool muss ein Singleton sein
 */
final class ReferencePool extends CachePeer {


    /** @var array - Array mit den gespeicherten Referenzen */
    private $pool;


    /**
     * Constructor.
     *
     * Erzeugt eine neue Instanz.  Ein evt. angegebenes Label (Namespace) wird ignoriert, da ein ReferencePool
     * Referenzen verwaltet und im Speicher des Prozesses nur ein Namespace existiert.
     *
     * @param  string $label   [optional] - Cache-Bezeichner
     * @param  array  $options [optional] - zusaetzliche Optionen
     */
    public function __construct($label=null, array $options=[]) {
        $this->label   = $label;
        $this->options = $options;
    }


    /**
     * ReferencePool-Instanzen benoetigen im Gegensatz zu anderen CachePeer-Implementierungen intern keinen
     * weiteren ReferencePool.  Diese Methode gibt daher einen Zeiger auf die Instanz selbst zurueck und
     * ueberschreibt damit die Default-Implementierung in CachePeer.
     *
     * @return $this
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
        if (!isset($this->pool[$key]))
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
     * @param  string $key                - Schluessel, unter dem der Wert gespeichert ist
     * @param  mixed  $default [optional] - Defaultwert (kann selbst auch NULL sein)
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
        if (isset($this->pool[$key])) {
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
     * @param  string     $key                   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed      $value                 - der zu speichernde Wert
     * @param  int        $expires    [optional] - Zeitspanne in Sekunden, nach deren Ablauf der Wert verfaellt
     * @param  Dependency $dependency [optional] - Abhaengigkeit der Gueltigkeit des gespeicherten Wertes
     *
     * @return bool - TRUE bei Erfolg, FALSE andererseits
     */
    public function set($key, &$value, $expires = Cache::EXPIRES_NEVER, Dependency $dependency = null) {
        Assert::string($key,  '$key');
        Assert::int($expires, '$expires');

        // im Cache wird ein array(creation_timestamp, value, expires, dependency) gespeichert
        $this->pool[$key] = [$timestamp=null, $value, $expires=null, $dependency];

        return true;
    }
}

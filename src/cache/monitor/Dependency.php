<?php
namespace rosasurfer\cache\monitor;

use rosasurfer\core\Object;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;


/**
 * Dependency
 *
 * Abstrakte Basisklasse fuer Abhaengigkeiten von bestimmten Zustaenden oder Bedingungen.  Wird benutzt,
 * um abhaengig von einer Aenderung Aktionen auszuloesen.  Jede Implementierung dieser Klasse bildet eine
 * Abhaengigkeit von einem bestimmten Zustand oder einer bestimmten Bedingung ab.  Soll ein Zustand
 * prozessuebergreifend verfolgt werden, muss die Instanz in einem entprechenden Persistenz-Container
 * gespeichert werden (Session, Datenbank, Dateisystem etc.).  Die Implementierungen muessen serialisierbar
 * sein.
 *
 * Mehrere Abhaengigkeiten koennen durch logisches UND oder ODER kombiniert werden.
 *
 * Beispiel:
 * ---------
 *
 *    $dependency =               FileDependency::create('/etc/crontab')
 *                ->andDependency(FileDependency::create('/etc/hosts'))
 *                ->andDependency(FileDependency::create('/etc/resolve.conf'));
 *    ...
 *
 *    if (!$dependency->isValid()) {
 *       // Zustand hat sich geaendert, beliebige Aktion ausfuehren
 *    }
 *
 * Dieses Beispiel definiert eine gemeinsame Abhaengigkeit vom Zustand dreier Dateien '/etc/crontab',
 * '/etc/hosts' und '/etc/resolv.conf' (AND-Verknuepfung).  Solange keine dieser Dateien veraendert oder
 * geloescht wird, bleibt die Abhaengigkeit erfuellt und der Aufruf von $dependency->isValid() gibt TRUE
 * zurueck.  Nach Aenderung oder Loeschen einer dieser Dateien gibt der Aufruf von $dependency->isValid()
 * FALSE zurueck.
 *
 * @see ChainedDependency
 * @see FileDependency
 */
abstract class Dependency extends Object {


    /** @var int - Mindestgueltigkeit */
    private $minValidity = 0;


    /**
     * Ob das zu ueberwachende Ereignis oder ein Zustandswechsel eingetreten sind oder nicht.
     *
     * @return bool - TRUE, wenn die Abhaengigkeit weiterhin erfuellt ist.
     *                FALSE, wenn der Zustandswechsel eingetreten ist und die Abhaengigkeit nicht mehr erfuellt ist.
     */
    abstract public function isValid();


    /**
     * Kombiniert diese Abhaengigkeit mit einer weiteren durch ein logisches UND (AND).
     *
     * @param  Dependency $dependency - Abhaengigkeit
     *
     * @return Dependency
     */
    public function andDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;
        return ChainedDependency::create($this)->andDependency($dependency);
    }


    /**
     * Kombiniert diese Abhaengigkeit mit einer weiteren durch ein logisches ODER (OR).
     *
     * @param  Dependency $dependency - Abhaengigkeit
     *
     * @return Dependency
     */
    public function orDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;
        return ChainedDependency::create($this)->orDependency($dependency);
    }


    /**
     * Gibt die Mindestgueltigkeit dieser Abhaengigkeit zurueck.
     *
     * @return int - Mindestgueltigkeit in Sekunden
     */
    public function getMinValidity() {
        return $this->minValidity;
    }


    /**
     * Setzt die Mindestgueltigkeit dieser Abhaengigkeit.
     *
     * @param  int $time - Mindestgueltigkeit in Sekunden
     *
     * @return Dependency
     */
    public function setMinValidity($time) {
        Assert::int($time);
        if ($time < 0) throw new InvalidArgumentException('Invalid argument $time: '.$time);

        $this->minValidity = $time;
        return $this;
    }
}

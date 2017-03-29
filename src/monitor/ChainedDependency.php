<?php
namespace rosasurfer\monitor;

use rosasurfer\exception\RuntimeException;


/**
 * ChainedDependency
 */
class ChainedDependency extends Dependency {


    /** @var Dependency[] - Abhaengigkeiten des Gesamtausdrucks */
    private $dependencies;

    /** @var string - logischer Typ der Gesamtabhaengigkeit (AND oder OR) */
    private $type;


    /**
     * Constructor
     *
     * @param  Dependency $dependency - Abhaengigkeit
     */
    private function __construct(Dependency $dependency) {
        $this->dependencies[] = $dependency;
        $this->setMinValidity($dependency->getMinValidity());
    }


    /**
     * Erzeugt eine neue Instanz.
     *
     * @param  Dependency $dependency - Abhaengigkeit
     *
     * @return static
     */
    protected static function create(Dependency $dependency) {
        return new static($dependency);
    }


    /**
     * Kombiniert diese Abhaengigkeit mit einer weiteren durch ein logisches UND (AND).
     *
     * @param  Dependency $dependency - Abhaengigkeit
     *
     * @return self
     */
    public function andDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;

        if ($this->type == 'OR')
            return self::create($this)->andDependency($dependency);

        $this->type           = 'AND';
        $this->dependencies[] = $dependency;
        $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

        return $this;
    }


    /**
     * Kombiniert diese Abhaengigkeit mit einer weiteren durch ein logisches ODER (OR).
     *
     * @param  Dependency $dependency - Abhaengigkeit
     *
     * @return self
     */
    public function orDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;

        if ($this->type == 'AND')
            return self::create($this)->orDependency($dependency);

        $this->type           = 'OR';
        $this->dependencies[] = $dependency;
        $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

        return $this;
    }


    /**
     * Ob das zu ueberwachende Ereignis oder der Zustandswechsel eingetreten sind oder nicht.
     *
     * @return bool - TRUE, wenn die Abhaengigkeit weiterhin erfuellt ist.
     *                FALSE, wenn der Zustandswechsel eingetreten ist und die Abhaengigkeit nicht mehr erfuellt ist.
     */
    public function isValid() {
        if ($this->type == 'AND') {
            foreach ($this->dependencies as $dependency) {
                if (!$dependency->isValid())
                    return false;
            }
            return true;
        }

        if ($this->type == 'OR' ) {
            foreach ($this->dependencies as $dependency) {
                if ($dependency->isValid())
                    return true;
            }
            return false;
        }

        throw new RuntimeException('Unreachable code reached');
    }
}

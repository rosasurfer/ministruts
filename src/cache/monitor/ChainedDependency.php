<?php
namespace rosasurfer\cache\monitor;

use rosasurfer\core\exception\RuntimeException;


/**
 * ChainedDependency
 *
 * @phpstan-consistent-constructor
 */
class ChainedDependency extends Dependency {


    /** @var Dependency[] - all dependencies of the instance */
    private $dependencies;

    /** @var string - logical dependency type of the instance (AND | OR) */
    private $type;


    /**
     * Constructor
     *
     * @param  Dependency $dependency
     */
    private function __construct(Dependency $dependency) {
        $this->dependencies[] = $dependency;
        $this->setMinValidity($dependency->getMinValidity());
    }


    /**
     * Static helper to create a new instance.
     *
     * @param  Dependency $dependency
     *
     * @return static
     */
    protected static function create(Dependency $dependency) {
        return new static($dependency);
    }


    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isValid() {
        if ($this->type == 'AND') {
            foreach ($this->dependencies as $dependency) {
                if (!$dependency->isValid()) return false;
            }
            return true;
        }

        if ($this->type == 'OR' ) {
            foreach ($this->dependencies as $dependency) {
                if ($dependency->isValid()) return true;
            }
            return false;
        }

        throw new RuntimeException('Unreachable code reached');
    }
}

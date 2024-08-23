<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\cache\monitor;

use rosasurfer\ministruts\core\exception\RuntimeException;


/**
 * ChainedDependency
 */
class ChainedDependency extends Dependency {


    /** @var Dependency[] - all dependencies of the instance */
    private array $dependencies;

    /** @var string - logical dependency type of the instance (AND | OR) */
    private string $type;


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
    protected static function create(Dependency $dependency): self {
        return new static($dependency);
    }


    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function andDependency(Dependency $dependency): self {
        if ($dependency === $this) {
            return $this;
        }
        if ($this->type == 'OR') {
            return self::create($this)->andDependency($dependency);
        }

        $this->type = 'AND';
        $this->dependencies[] = $dependency;
        $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function orDependency(Dependency $dependency): self {
        if ($dependency === $this) {
            return $this;
        }
        if ($this->type == 'AND') {
            return self::create($this)->orDependency($dependency);
        }
        $this->type = 'OR';
        $this->dependencies[] = $dependency;
        $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isValid(): bool {
        if ($this->type == 'AND') {
            foreach ($this->dependencies as $dependency) {
                if (!$dependency->isValid()) {
                    return false;
                }
            }
            return true;
        }

        if ($this->type == 'OR' ) {
            foreach ($this->dependencies as $dependency) {
                if ($dependency->isValid()) {
                    return true;
                }
            }
            return false;
        }

        throw new RuntimeException('Unreachable code reached');
    }
}

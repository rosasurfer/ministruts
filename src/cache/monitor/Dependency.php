<?php
namespace rosasurfer\ministruts\cache\monitor;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;


/**
 * Base class for dependencies on states or conditions, to be used to trigger actions depending on state changes.
 *
 * An instance of this class represents a dependency on a state or condition. If state is to be tracked across processes,
 * the instance must be stored in a persistence container (cache, database or file system). Therefore implementations must
 * be serializable. Several instances may be combined.
 *
 * @example
 * <pre>
 *  &lt;?php
 *  $dependency = FileDependency::create('/etc/crontab')
 *  ->andDependency(FileDependency::create('/etc/hosts'))
 *  ->andDependency(FileDependency::create('/etc/resolve.conf'));
 *
 *  // ...
 *
 *  if (!$dependency->isValid()) {
 *      // state of one of the files has changed, trigger some action...
 *  }
 * </pre>
 *
 * The example defines a combined dependency on the state of three files. As long as no file is changed or deleted,
 * the dependency remains fulfilled and calling $dependency->isValid() returns TRUE. After changing or deleting one
 * of the files, calling $dependency->isValid() returns FALSE.
 */
abstract class Dependency extends CObject {


    /** @var int - min. validity of the instance in seconds */
    private $minValidity = 0;


    /**
     * Whether an event or state change to be monitored has occurred and invalidated the dependency.
     *
     * @return bool - TRUE if the the event didn't yet occur and the dependency is still valid
     *                FALSE if the event has occurred and invalidated the dependency
     */
    abstract public function isValid();


    /**
     * Combine this instance with another instance by using logical AND.
     *
     * @param  Dependency $dependency
     *
     * @return self
     */
    public function andDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;
        return ChainedDependency::create($this)->andDependency($dependency);
    }


    /**
     * Combine this instance with another instance by using logical OR.
     *
     * @param  Dependency $dependency
     *
     * @return self
     */
    public function orDependency(Dependency $dependency) {
        if ($dependency === $this)
            return $this;
        return ChainedDependency::create($this)->orDependency($dependency);
    }


    /**
     * Get the min. validity of the instance.
     *
     * @return int - seconds
     */
    public function getMinValidity() {
        return $this->minValidity;
    }


    /**
     * Set the min. validity of the instance.
     *
     * @param  int $time - seconds
     *
     * @return $this
     */
    public function setMinValidity($time) {
        Assert::int($time);
        if ($time < 0) throw new InvalidValueException('Invalid parameter $time: '.$time);

        $this->minValidity = $time;
        return $this;
    }
}

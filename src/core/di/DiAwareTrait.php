<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di;

use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\core\exception\RuntimeException;

/**
 * DiAwareTrait
 *
 * A trait adding the behavior "service awareness" to any class. Provides access to dependencies in
 * the application's service container. By using this trait any class can easily made service aware.
 */
trait DiAwareTrait {

    /**
     * Return the application's service container or resolve a named service and return its implementation.
     * This method should be used to access the service container from a class-context.
     *
     * @param  ?string $name [optional] - service identifier (default: none to return the service container itself)
     *
     * @return         object
     * @phpstan-return ($name is null ? DiInterface : object)
     */
    protected static function di(?string $name = null): object {
        $di = Application::getDi();
        if (!$di) throw new RuntimeException('Service container not available');

        if (isset($name)) {
            return $di->get($name);
        }
        return $di;
    }
}

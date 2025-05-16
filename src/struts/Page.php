<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\Singleton;


/**
 * Page
 *
 * A context for storing variables required for rendering views or view fragments, i.e. {@link Tile}s. During the render
 * process stored variables can be referenced, and new variables can be stored as long as they don't overwrite existing ones.
 *
 * @example
 * <pre>
 *  $page->title = 'HTML title';        // store a variable under the name "title"
 *  $var = $page->title;                // retrieve the variable named "title"
 * </pre>
 */
class Page extends Singleton {


    /** @var mixed[] - stored variables */
    protected array $properties = [];


    /**
     * Return the {@link Singleton} instance of this class.
     *
     * @return static
     */
    public static function me(): self {
        /** @var static $instance */
        $instance = self::getInstance(static::class);
        return $instance;
    }


    /**
     * Lookup and return a stored variable.
     *
     * @param  string $name               - variable name
     * @param  mixed  $default [optional] - value to return if no such variable exists (default: NULL)
     *
     * @return mixed - value
     */
    public static function get(string $name, $default = null) {
        $page = self::me();

        if (\key_exists($name, $page->properties))
            return $page->properties[$name];

        return $default;
    }


    /**
     * Store a variable in the Page context.
     *
     * @param  string $name  - variable name
     * @param  mixed  $value - variable value
     *
     * @return mixed - the same value
     */
    public static function set(string $name, $value) {
        self::me()->__set($name, $value);
        return $value;
    }


    /**
     * Return the variable stored under the specified name.
     *
     * @param  string $name - variable name
     *
     * @return mixed - value
     */
    public function __get(string $name) {
        if (\key_exists($name, $this->properties))
            return $this->properties[$name];
        return null;
    }


    /**
     * Set the variable with the specified name.
     *
     * @param  string $name  - variable name
     * @param  mixed  $value - variable value
     *
     * @return void
     */
    public function __set(string $name, $value): void {
        if (isset($value)) {
            $this->properties[$name] = $value;
        }
        else {
            unset($this->properties[$name]);
        }
    }


    /**
     * Return all variables stored in the context.
     *
     * @return mixed[] - values
     */
    public function values(): array {
        return self::me()->properties;
    }
}

<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\core\assert\Assert;


/**
 * Page
 *
 * Container, in dem fuer den Renderprozess benoetigte Objekte oder Variablen abgelegt werden koennen.
 * Beim Rendern kann auf diese Daten aus dem HTML zugegriffen werden.  Innerhalb eines Seitenfragments
 * koennen auch Daten im Container gespeichert werden, jedoch nur, wenn dabei keine vorhandenen
 * Daten ueberschrieben werden.
 *
 * Beispiel:
 * ---------
 *    $page->title = 'HTML-Title';
 *
 * Speichert die Variable "title" mit dem Wert 'HTML-Title' im Page-Context
 *
 *    $var = $page->title;
 *
 * Gibt die gespeicherte Eigenschaft mit dem Namen "title" zurueck.
 */
class Page extends Singleton {


    /** @var array - Property-Pool */
    protected $properties = [];


    /**
     * Gibt die Singleton-Instanz dieser Klasse zurueck.
     *
     * @return static
     */
    public static function me() {
        return self::getInstance(static::class);
    }


    /**
     * Lookup and return a property stored in the instance.
     *
     * @param  string $name                - property name
     * @param  mixed  $altValue [optional] - value to return if no such property exists
     *
     * @return mixed - value
     */
    public static function get($name, $altValue = null) {
        $page = self::me();

        if (\key_exists($name, $page->properties))
            return $page->properties[$name];

        return $altValue;
    }


    /**
     * Speichert einen Wert in der Page.
     *
     * @param  string $key   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed  $value - der zu speichernde Wert
     */
    public static function set($key, $value) {
        return self::me()->__set($key, $value);
    }


    /**
     * Magic method. Returns the property stored under the specified key.
     *
     * @param  string $name - property name
     *
     * @return mixed - value
     */
    public function __get($name) {
        if (\key_exists($name, $this->properties))
            return $this->properties[$name];
        return null;
    }


    /**
     * Magische Methode, die die Eigenschaft mit dem angegebenen Namen setzt.  Wird automatisch
     * aufgerufen und ermoeglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
     *
     * @param  string $name  - Name der Eigenschaft
     * @param  mixed  $value - Wert
     */
    public function __set($name, $value) {
        Assert::string($name, '$name');

        if ($value !== null) {
            $this->properties[$name] = $value;
        }
        else {
            unset($this->properties[$name]);
        }
    }


    /**
     * Return all page values stored in the instance.
     *
     * @return array - values
     */
    public function values() {
        return self::me()->properties;
    }
}

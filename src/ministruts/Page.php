<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalTypeException;


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
 *
 * @todo   Properties aus dem Tiles-Context muessen auch in der Page erreichbar sein
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
        return Singleton::getInstance(static::class);
    }


    /**
     * Gibt einen Wert aus der Page zurueck.
     *
     * @param  string $key - Schluessel, unter dem der Wert gespeichert ist
     *
     * @return mixed - der gespeicherte Wert oder NULL, falls kein solcher Schluessel existiert
     */
    public static function get($key) {
        return self::me()->__get($key);
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
     * Magische PHP-Methode, die die Eigenschaft mit dem angegebenen Namen zurueckgibt. Wird automatisch
     * aufgerufen und ermoeglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
     *
     * @param  string $name - Name der Eigenschaft
     *
     * @return mixed        - Wert oder NULL, wenn die Eigenschaft nicht existiert
     */
    public function __get($name) {
        return isSet($this->properties[$name]) ? $this->properties[$name] : null;
    }


    /**
     * Magische Methode, die die Eigenschaft mit dem angegebenen Namen setzt.  Wird automatisch
     * aufgerufen und ermoeglicht den Zugriff auf Eigenschaften mit dynamischen Namen.
     *
     * @param  string $name  - Name der Eigenschaft
     * @param  mixed  $value - Wert
     */
    public function __set($name, $value) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

        if ($value !== null) {
            $this->properties[$name] = $value;
        }
        else {
            unset($this->properties[$name]);
        }
    }
}
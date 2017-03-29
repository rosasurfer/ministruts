<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;

use const rosasurfer\CLI;


/**
 * Response
 *
 * Wrapper fuer den HTTP-Response.
 */
class Response extends Singleton {


    /** @var array - Attribute-Pool */
    private $attributes = [];


    /**
     * Gibt die Singleton-Instanz dieser Klasse zurueck, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
     * wurde. In allen anderen Faellen, z.B. bei Aufruf in der Konsole, wird NULL zurueckgegeben.
     *
     * @return static|null - Instanz oder NULL
     */
    public static function me() {
        if (!CLI) return Singleton::getInstance(static::class);
        return null;
    }


    /**
     * Speichert einen Wert unter dem angegebenen Schluessel im Response.
     *
     * @param  string $key   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed  $value - der zu speichernde Wert
     */
    public function setAttribute($key, &$value) {
        $this->attributes[$key] = $value;
    }


    /**
     * Gibt den unter dem angegebenen Schluessel gespeicherten Wert zurueck oder NULL, wenn unter diesem
     * Namen kein Wert existiert.
     *
     * @param  string $key - Schluessel, unter dem der Wert gespeichert ist
     *
     * @return mixed - der gespeicherte Wert oder NULL
     */
    public function &getAttribute($key) {
        if (isSet($this->attributes[$key]))
            return $this->attributes[$key];

        $value = null;
        return $value;    // Referenz auf NULL
    }
}

<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\exception\RuntimeException;

use const rosasurfer\CLI;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * Response
 *
 * Wrapper fuer den HTTP-Response.
 */
class Response extends Singleton {


    /** @var int - HTTP status code */
    protected $status = 0;

    /** @var array - Attribute-Pool */
    protected $attributes = [];


    /**
     * Gibt die Singleton-Instanz dieser Klasse zurueck, wenn das Script im Kontext eines HTTP-Requestes aufgerufen
     * wurde. In allen anderen Faellen, z.B. bei Aufruf in der Konsole, wird NULL zurueckgegeben.
     *
     * @return static
     *
     * @throws RuntimeException if not called from the web interface
     */
    public static function me() {
        if (CLI) throw new RuntimeException('Cannot create a '.static::class.' instance in a non-web context.');
        return Singleton::getInstance(static::class);
    }


    /**
     * Set the response status code.
     *
     * @param  int $status - HTTP response status
     *
     * @return $this
     */
    public function setStatus($status) {
        if (!is_int($status)) throw new IllegalTypeException('Illegal type of parameter $status: '.getType($status));
        if ($status < 1)      throw new InvalidArgumentException('Invalid argument $status: '.$status);

        $this->status = $status;
        return $this;
    }


    /**
     * Return the HTTP response status.
     *
     * @return int
     */
    public function getStatus() {
        return $this->status;
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

<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * ActionForward
 *
 * Ein ActionForward bezeichnet ein Ziel, zu dem nach Aufruf einer Action verzweigt wird.  Er hat die
 * folgenden Eigenschaften:
 *
 *   name     - logischer Name, unter dem der ActionForward gefunden werden kann
 *   path     - physische Resource (z.B. HTML-Datei), Klassenname eines Layouts oder URL
 *   redirect - ob ein Redirect ausgeloest werden soll (nur bei URL, default: false)
 */
class ActionForward extends Object {


    /** @var string - Default-Bezeichner, mit dem nach erfolgreicher Validierung nach einem ActionForward gesucht wird. */
    const VALIDATION_SUCCESS_KEY = 'success';

    /** @var string - Default-Bezeichner, mit dem nach fehlgeschlagener Validierung nach einem ActionForward gesucht wird. */
    const VALIDATION_ERROR_KEY   = 'error';

    /**
     *  @var string - Geschuetzter Forward-Bezeichner, ueber den zur Laufzeit ein Redirect-Forward auf die URL des aktuell
     *                verwendeten ActionMappings erzeugt werden kann.
     */
    const __SELF = '__self';

    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var string */
    protected $label;

    /** @var bool */
    protected $redirect;

    /** @var bool - ob diese Komponente vollstaendig konfiguriert ist */
    protected $configured = false;


    /**
     * Erzeugt einen neuen ActionForward mit den angegebenen Daten.
     *
     * @param  string $name     - logischer Name des Forwards
     * @param  string $path     - Pfad der Instanz
     * @param  bool   $redirect - Redirect-Flag fuer diese Instanz
     */
    public function __construct($name, $path, $redirect=false) {
        $this->setName($name)
             ->setPath($path)
             ->setRedirect($redirect);
    }


    /**
     * Get den Namen dieses Forwards.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Return the path of dieses Forwards.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }


    /**
     * Return the label of dieses Forwards.
     *
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }


    /**
     * Return the redirect property of dieses Forwards.
     *
     * @return bool
     */
    public function isRedirect() {
        return $this->redirect;
    }


    /**
     * Setzt den Namen dieses Forwards.
     *
     * @param  string $name
     *
     * @return self
     */
    public function setName($name) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))    throw new InvalidArgumentException('Invalid argument $name: '.$name);

        $this->name = $name;
        return $this;
    }


    /**
     * Setzt den Pfad dieses Forwards.
     *
     * @param  string $path
     *
     * @return self
     */
    public function setPath($path) {
        if ($this->configured) throw new IllegalStateException('Configuration is frozen');
        if (!is_string($path)) throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
        if (!strLen($path))    throw new InvalidArgumentException('Invalid argument $path: '.$path);

        $this->path = $path;
        return $this;
    }


    /**
     * Setzt das Label dieses Forwards. Das Label wird in HTML-Kommentaren etc. verwendet.
     *
     * @param  string $label - Label
     *
     * @return self
     */
    public function setLabel($label) {
        if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
        if (!is_string($label)) throw new IllegalTypeException('Illegal type of parameter $label: '.getType($label));
        if (!strLen($label))    throw new InvalidArgumentException('Invalid argument $label: '.$label);

        $this->label = $label;
        return $this;
    }


    /**
     * Setzt das Redirect-Flag dieses Forwards.
     *
     * @param  bool $redirect
     *
     * @return self
     */
    public function setRedirect($redirect) {
        if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
        if (!is_bool($redirect)) throw new IllegalTypeException('Illegal type of parameter $redirect: '.getType($redirect));

        $this->redirect = $redirect;
        return $this;
    }


    /**
     * Fuegt dem Querystring dieses ActionForwards ein weiteres Key-Value-Paar hinzu.
     *
     * @param  string $key   - Schluessel
     * @param  scalar $value - Wert (int|float|string|bool)
     *
     * @return self
     */
    public function addQueryData($key, $value) {
        if ($this->configured)      throw new IllegalStateException('Configuration is frozen');
        if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
        if (is_null($value))        $value = '';
        elseif (is_bool($value))    $value = (int) $value;
        elseif (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        $value = (string) $value;

        // TODO: Uebergabe von mehreren Werten ermoeglichen

        $separator = (strPos($this->path, '?')!==false) ? '&' : '?';

        $this->path .= $separator.$key.'='.str_replace(array(' ', '#', '&'), array('%20', '%23', '%26'), $value);

        return $this;
    }


    /**
     * Friert die Konfiguration dieser Komponente ein. Nachdem Aufruf dieser Methode kann die Konfiguration
     * der Komponente nicht mehr veraendert werden.
     *
     * @return self
     */
    public function freeze() {
        if (!$this->configured)
            $this->configured = true;

        return $this;
    }


    /**
     * Erzeugt einen neuen ActionForward, der auf dieser Instanz basiert. Die Konfiguration des neuen
     * Forwards ist noch nicht eingefroren, sodass diese Methode zum "Modifizieren" vorhandener Forwards
     * benutzt werden kann.
     *
     * @return self
     *
     * @see ActionForward::freeze()
     */
    public function copy() {
        $forward = clone $this;
        $forward->configured = false;
        return $forward;
    }
}

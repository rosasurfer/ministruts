<?php
use rosasurfer\ministruts\exceptions\IllegalStateException;
use rosasurfer\ministruts\exceptions\IllegalTypeException;
use rosasurfer\ministruts\exceptions\InvalidArgumentException;


/**
 * ActionForward
 *
 * Ein ActionForward bezeichnet ein Ziel, zu dem nach Aufruf einer Action verzweigt wird.  Er hat die
 * folgenden Eigenschaften:
 *
 *   name     - logischer Name, unter dem der ActionForward gefunden werden kann
 *   path     - physische Resource (z.B. HTML-Datei), Klassenname eines Layouts oder URL
 *   redirect - ob ein Redirect ausgelöst werden soll (nur bei URL, default: false)
 */
class ActionForward extends Object {


   /**
    * Der Default-Bezeichner, mit dem nach erfolgreicher Validierung nach einem ActionForward gesucht wird.
    */
   const /*string*/ VALIDATION_SUCCESS_KEY = 'success';


   /**
    * Der Default-Bezeichner, mit dem nach fehlgeschlagener Validierung nach einem ActionForward gesucht wird.
    */
   const /*string*/ VALIDATION_ERROR_KEY   = 'error';


   /**
    * Geschützter Forward-Bezeichner, über den zur Laufzeit ein Redirect-Forward auf die URL des aktuell
    * verwendeten ActionMappings erzeugt werden kann.
    */
   const /*string*/ __SELF = '__self';


   protected /*string*/ $name;
   protected /*string*/ $path;
   protected /*string*/ $label;
   protected /*bool*/   $redirect;

   // ob diese Komponente vollständig konfiguriert ist
   protected /*bool*/ $configured = false;


   // Getter
   public /*string*/ function getName()    { return $this->name;     }
   public /*string*/ function getPath()    { return $this->path;     }
   public /*string*/ function getLabel()   { return $this->label;    }
   public /*bool*/   function isRedirect() { return $this->redirect; }


   /**
    * Erzeugt einen neuen ActionForward mit den angegebenen Daten.
    *
    * @param  string $name     - logischer Name des Forwards
    * @param  string $path     - Pfad der Instanz
    * @param  bool   $redirect - Redirect-Flag für diese Instanz
    */
   public function __construct($name, $path, $redirect=false) {
      $this->setName($name)
           ->setPath($path)
           ->setRedirect($redirect);
   }


   /**
    * Setzt den Namen dieses Forwards.
    *
    * @param  string $name
    *
    * @return ActionForward
    */
   public function setName($name) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if ($name==='')        throw new InvalidArgumentException('Invalid argument $name: '.$name);

      $this->name = $name;
      return $this;
   }


   /**
    * Setzt den Pfad dieses Forwards.
    *
    * @param  string $path
    *
    * @return ActionForward
    */
   public function setPath($path) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($path)) throw new IllegalTypeException('Illegal type of parameter $path: '.getType($path));
      if ($path==='')        throw new InvalidArgumentException('Invalid argument $path: '.$path);

      $this->path = $path;
      return $this;
   }


   /**
    * Setzt das Label dieses Forwards. Das Label wird in HTML-Kommentaren etc. verwendet.
    *
    * @param  string $label - Label
    *
    * @return ActionForward
    */
   public function setLabel($label) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($label)) throw new IllegalTypeException('Illegal type of parameter $label: '.getType($label));
      if ($label==='')        throw new InvalidArgumentException('Invalid argument $label: '.$label);

      $this->label = $label;
      return $this;
   }


   /**
    * Setzt das Redirect-Flag dieses Forwards.
    *
    * @param  bool $redirect
    *
    * @return ActionForward
    */
   public function setRedirect($redirect) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_bool($redirect)) throw new IllegalTypeException('Illegal type of parameter $redirect: '.getType($redirect));

      $this->redirect = $redirect;
      return $this;
   }


   /**
    * Fügt dem Querystring dieses ActionForwards ein weiteres Key-Value-Paar hinzu.
    *
    * @param  string $key   - Schlüssel
    * @param  scalar $value - Wert (int|double|string|bool)
    *
    * @return ActionForward
    */
   public function addQueryData($key, $value) {
      if ($this->configured)      throw new IllegalStateException('Configuration is frozen');
      if (!is_string($key))       throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));
      if ($value === null)        $value = '';
      elseif (is_bool($value))    $value = (int) $value;
      elseif (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

      $value = (string) $value;

      // TODO: Übergabe von mehreren Werten ermöglichen

      $separator = (strPos($this->path, '?')!==false) ? '&' : '?';

      $this->path .= $separator.$key.'='.str_replace(array(' ', '#', '&'), array('%20', '%23', '%26'), $value);

      return $this;
   }


   /**
    * Friert die Konfiguration dieser Komponente ein. Nachdem Aufruf dieser Methode kann die Konfiguration
    * der Komponente nicht mehr verändert werden.
    *
    * @return ActionForward
    */
   public function freeze() {
      if (!$this->configured)
         $this->configured = true;

      return $this;
   }


   /**
    * Erzeugt einen neuen ActionForward, der auf dieser Instanz basiert. Die Konfiguration des neuen
    * Forwards ist noch nicht eingefroren, sodaß diese Methode zum "Modifizieren" vorhandener Forwards
    * benutzt werden kann.
    *
    * @return ActionForward
    *
    * @see ActionForward::freeze()
    */
   public function copy() {
      $forward = clone $this;
      $forward->configured = false;
      return $forward;
   }
}

<?
/**
 * ActionForward
 *
 * Ein ActionForward bezeichnet ein Ziel, zu dem nach Aufruf einer Action verzweigt wird, mit den
 * folgenden Eigenschaften:
 *
 *   name     - logischer Name, unter dem der ActionForward gefunden werden kann
 *   path     - physische Resource (z.B. HTML-Datei), Klassenname eines Layouts oder URL
 *   redirect - ob ein Redirect ausgelöst werden soll (nur bei URL, default: false)
 */
class ActionForward extends Object {


   /**
    * Geschützter Forward-Bezeichner, über den zur Laufzeit ein Redirect-Forward auf die URL des aktuell
    * verwendeten ActionMappings erreicht werden kann.
    */
   const __SELF = '__self';


   protected $name;                  // string
   protected $path;                  // string
   protected $redirect;              // boolean

   // ob diese Komponente vollständig konfiguriert ist
   protected $configured = false;    // boolean


   // Getter
   public function getName()    { return $this->name;     }
   public function getPath()    { return $this->path;     }
   public function isRedirect() { return $this->redirect; }


   /**
    * Erzeugt einen neuen ActionForward mit den angegebenen Daten.
    *
    * @param string  $name     - Name der Instanz
    * @param string  $path     - Pfad der Instanz
    * @param boolean $redirect - Redirect-Flag für diese Instanz
    */
   public function __construct($name, $path, $redirect = false) {
      $this->setName($name);
      $this->setPath($path);
      $this->setRedirect($redirect);
   }


   /**
    * Setzt den Namen dieses Forwards.
    *
    * @param string $name
    *
    * @return ActionForward
    */
   public function setName($name) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      $this->name = $name;
      return $this;
   }


   /**
    * Setzt den Pfad dieses Forwards.
    *
    * @param string $path
    *
    * @return ActionForward
    */
   public function setPath($path) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($path)) throw new IllegalTypeException('Illegal type of argument $path: '.getType($path));

      $this->path = $path;
      return $this;
   }


   /**
    * Setzt das Redirect-Flag dieses Forwards.
    *
    * @param boolean $redirect
    *
    * @return ActionForward
    */
   public function setRedirect($redirect) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_bool($redirect)) throw new IllegalTypeException('Illegal type of argument $redirect: '.getType($redirect));

      $this->redirect = $redirect;
      return $this;
   }


   /**
    * Fügt dem Querystring dieses Forwards ein Key-Value-Paar hinzu.
    *
    * @param string $key   - Schlüssel
    * @param string $value - Wert
    *
    * @return ActionForward
    */
   public function addQueryData($key, $value) {
      if ($this->configured)  throw new IllegalStateException('Configuration is frozen');
      if (!is_string($key))   throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of argument $value: '.getType($value));

      $this->path .= (strPos($this->path, '?')===false ? '?':'&').$key.'='.$value;
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
?>

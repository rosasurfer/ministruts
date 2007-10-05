<?
/**
 * ActionForward
 *
 * Ein ActionForward bezeichnet ein Ziel, zu dem nach Aufruf einer Action verzweigt wird.
 * Instanzen dieser Klasse können innerhalb eines ActionMapping konfiguriert oder bei Bedarf
 * auch manuell erzeugt werden.
 *
 * Ein ActionForward hat mindestens die folgenden Eigenschaften:
 *
 *   name     - logischer Name, unter dem der ActionForward gefunden werden kann
 *
 *   path     - HTML-Layout (beginnend mit '/'), Klassenname eines Layouts oder URL (wenn Redirect)
 *
 *   redirect - ob der ActionForward einen Redirect auslösen soll (default: false)
 */
class ActionForward extends Object {


   private $name;                   // string
   private $path;                   // string
   private $redirect;               // boolean

   // ob diese Komponente vollständig konfiguriert ist
   private $configured = false;     // boolean


   // Getter
   public function getName()    { return $this->name;     }
   public function getPath()    { return $this->path;     }
   public function isRedirect() { return $this->redirect; }


   // new ActionForward()->
   public static function create() {
      return new self();
   }


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
    * Friert die Konfiguration dieser Komponente ein.
    */
   public function freeze() {
      $this->configured = true;
   }


   /**
    * Erzeugt einen neuen ActionForward, der auf dieser Instanz basiert.
    * Nützlich zum manuellen Erzeugen und Modifizieren von vorhandenen ActionForwards.
    *
    * @return ActionForward
    */
   public function copy() {
      return new self($this->getName(), $this->getPath(), $this->isRedirect());
   }
}
?>

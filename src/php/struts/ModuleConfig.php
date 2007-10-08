<?
/**
 * ModuleConfig
 */
class ModuleConfig extends Object {


   /**
    * Ob diese Komponente vollständig konfiguriert ist. Wenn dieses Flag gesetzt ist, wirft jeder Versuch,
    * die Konfiguration zu ändern, eine IllegalStateException.
    */
   protected $configured = false;


   /**
    * Der Prefix dieses Modules relative zur APPLICATION_ROOT_URL. Anhand dieses Prefixes werden die
    * verschiedenen Module einer Anwendung unterschieden.  Die ModuleConfig mit dem Leerstring als Prefix
    * ist die Default-Konfiguration der Anwendung.
    */
   protected $prefix;


   protected $globalForwards = array();
   protected $mappings       = array();
   protected /* ActionMapping */ $defaultMapping;


   // Klassenname der RequestProcessor-Implementierung, die für dieses Module benutzt wird
   protected $processorClass = 'RequestProcessor';


   /**
    * Erzeugt eine neue ModuleConfig.
    *
    * @param string $prefix     - der Prefix dieses Modules relativ zur Basis-URL der Anwendung
    * @param string $configfile - Pfad zur Konfigurationsdatei dieses Modules
    */
   public function __construct($prefix, $configfile) {
      $this->setPrefix($prefix);

      // parse XML-config
   }


   /**
    * Gibt den Prefix dieser ModuleConfig zurück. Anhand dieses Prefix werde die verschiedenen Module der
    * Anwendung unterschieden.
    *
    * @return string
    */
   public function getPrefix() {
      return $this->prefix;
   }


   /**
    * Setzt den Prefix der ModuleConfig.
    *
    * @param string prefix
    */
   public function setPrefix($prefix) {
      if ($this->configured)   throw new IllegalStateException('Configuration is frozen');
      if (!is_string($prefix)) throw new IllegalTypeException('Illegal type of argument $prefix: '.getType($prefix));

      $this->prefix = $prefix;
   }


   /**
    * Fügt dieser Modulkonfiguration unter dem angegebenen Namen einen globalen ActionForward hinzu.
    * Der angegebene Name kann vom internen Namen des Forwards abweichen, sodaß die Definition von Aliassen
    * möglich ist (ein Forward ist unter mehreren Namen auffindbar).
    *
    * @param string        $name
    * @param ActionForward $forward
    */
   public function addGlobalForward($name, ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of argument $name: '.getType($name));

      if (isSet($this->globalForwards[$name]))
         throw new RuntimeException('Non-unique identifier detected for global ActionForwards: '.$name);

      $this->globalForwards[$name] = $forward;
   }


   /**
    * Fügt dieser Modulkonfiguration ein ActionMapping hinzu.
    *
    * @param ActionMapping $mapping
    */
   public function addActionMapping(ActionMapping $mapping) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      if ($mapping->isDefault()) {
         if ($this->defaultMapping)
            throw new RuntimeException('Only one ActionMapping can be marked as "default" within a module.');

         $this->defaultMapping = $mapping;
      }

      $this->mappings[$mapping->getPath()] = $mapping;
   }


   /**
    * Gibt das ActionMapping für den angegebenen Pfad zurück. Zuerst wird nach einer genauen Übereinstimmung
    * gesucht und danach, wenn keines gefunden wurde, nach einem Default-ActionMapping.
    *
    * @param string $path
    *
    * @return ActionMapping
    */
   public function findActionMapping($path) {
      if (isSet($this->mappings[$path]))
         return $this->mappings[$path];

      return $this->defaultMapping;
   }


   /**
    * Setzt den Klassennamen der RequestProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RequestProcessor sein.
    *
    * @param string $className
    */
   public function setProcessorClass($className) {
      if ($this->configured)                                       throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                                  throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, $parent='RequestProcessor')) throw new InvalidArgumentException("Not a subclass of $parent: ".$className);

      $this->processorClass = $className;
   }


   /**
    * Gibt den Klassennamen der RequestProcessor-Implementierung zurück.
    *
    * @return string
    */
   public function getProcessorClass() {
      return $this->processorClass;
   }


   /**
    * Friert die Konfiguration ein, sodaß sie später nicht mehr geändert werden kann.
    */
   public function freeze() {
      if (!$this->configured) {
         foreach ($this->globalForwards as $forward)
            $forward->freeze();

         foreach ($this->mappings as $mapping)
            $mapping->freeze();

         $this->configured = true;
      }
   }


   /**
    * Sucht und gibt den globalen ActionForward mit dem angegebenen Namen zurück.
    * Wird kein Forward gefunden, wird NULL zurückgegeben.
    *
    * @param $name - logischer Name des ActionForwards
    *
    * @return ActionForward
    */
   public function findForward($name) {
      if (isSet($this->globalForwards[$name]))
         return $this->globalForwards[$name];
      return null;
   }
}
?>

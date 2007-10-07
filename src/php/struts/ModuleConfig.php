<?
/**
 * ModuleConfig
 */
class ModuleConfig extends Object {


   // globale ActionForwards
   private $globalForwards = array();

   // ActionMappings
   private $mappings = array();

   // Default-ActionMapping (wenn angegeben)
   private $defaultMapping;

   // Klassenname der RequestProcessor-Implementierung, die für dieses Module benutzt wird
   private $processorClass = 'RequestProcessor';

   // Ob diese Komponente vollständig konfiguriert ist.
   private $configured = false;


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
    * @param ActionMapping $mapping
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

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
   private /* ActionMapping */ $defaultMapping;

   // Klassenname der RequestProcessor-Implementierung, die für dieses Module benutzt wird
   private $processorClass = 'RequestProcessor';

   // Ob diese Komponente vollständig konfiguriert ist.
   private $configured = false;


   /**
    * Fügt dieser Modulkonfiguration einen globalen ActionForward hinzu.
    *
    * @param ActionForward $forward
    */
   public function addGlobalForward(ActionForward $forward) {
      if ($this->configured) throw new IllegalStateException('Configuration is frozen');

      $this->globalForwards[$forward->getName()] = $forward;
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
    * Setzt den Klassennamen der RequestProcessor-Implementierung, die für dieses Module benutzt wird.
    * Diese Klasse muß eine Subklasse von RequestProcessor sein.
    *
    * @param string $className
    */
   public function setProcessorClass($className) {
      if ($this->configured)                               throw new IllegalStateException('Configuration is frozen');
      if (!is_string($className))                          throw new IllegalTypeException('Illegal type of argument $className: '.getType($className));
      if (!is_subclass_of($className, 'RequestProcessor')) throw new InvalidArgumentException('Not a RequestProcessor subclass: '.$className);

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
      foreach ($this->globalForwards as $forward)
         $forward->freeze();

      foreach ($this->mappings as $mapping)
         $mapping->freeze();

      $this->configured = true;
   }
}
?>

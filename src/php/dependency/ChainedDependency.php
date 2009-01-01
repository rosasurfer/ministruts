<?
/**
 * ChainedDependency
 */
class ChainedDependency extends ChainableDependency {


   /**
    * Constructor
    */
   private function __construct() {
   }


   /**
    * die definierten Abhängigkeiten dieser Instanz
    */
   private /*IDependency[]*/ $dependencies = array();


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return ChainedDependency
    */
   public static function create() {
      return new self();
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren. Die neue Abhängigkeit wird nach allen anderen
    * vorhandenen Abhängigkeiten eingefügt.
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function add(IDependency $dependency) {
      $this->dependencies[] = $dependency;
      return $this;
   }


   /**
    * Ob das zu überwachende Ereignis oder der Zustandswechsel eingetreten sind oder nicht.
    *
    * @return boolean - TRUE, wenn die Abhängigkeit weiterhin erfüllt ist.
    *                   FALSE, wenn der Zustandswechsel eingetreten ist und die Abhängigkeit nicht mehr erfüllt ist.
    */
   public function isValid() {
      foreach ($this->dependencies as $dependency) {
         if (!$dependency->isValid())
            return false;
      }
      return true;
   }
}
?>

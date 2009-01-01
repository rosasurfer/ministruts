<?
/**
 * ChainedDependency
 */
class ChainedDependency extends ChainableDependency {


   /**
    * Abhängigkeiten des Gesamtausdrucks
    */
   private /*IDependency[]*/ $dependencies;


   /**
    * logischer Typ der Gesamtabhängigkeit (AND oder OR)
    */
   private /*string*/ $type;


   /**
    * Constructor
    *
    * @param IDependency $dependency - Abhängigkeit
    */
   private function __construct(IDependency $dependency) {
      $this->dependencies[] = $dependency;
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   protected static function create(IDependency $dependency) {
      return new self($dependency);
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches UND (AND).
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function andThat(IDependency $dependency) {
      if ($this->type == 'OR')
         return self ::create($this)->andThat($dependency);

      $this->dependencies[] = $dependency;
      $this->type           = 'AND';
      return $this;
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches ODER (OR).
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function orThat(IDependency $dependency) {
      if ($this->type == 'AND')
         return self ::create($this)->orThat($dependency);

      $this->dependencies[] = $dependency;
      $this->type           = 'OR';
      return $this;
   }


   /**
    * Ob das zu überwachende Ereignis oder der Zustandswechsel eingetreten sind oder nicht.
    *
    * @return boolean - TRUE, wenn die Abhängigkeit weiterhin erfüllt ist.
    *                   FALSE, wenn der Zustandswechsel eingetreten ist und die Abhängigkeit nicht mehr erfüllt ist.
    */
   public function isValid() {
      if ($this->type == 'AND') {
         foreach ($this->dependencies as $dependency) {
            if (!$dependency->isValid())
               return false;
         }
         return true;
      }

      if ($this->type == 'OR' ) {
         foreach ($this->dependencies as $dependency) {
            if ($dependency->isValid())
               return true;
         }
         return false;
      }

      throw new RuntimeException('Unreachable code reached');
   }
}
?>

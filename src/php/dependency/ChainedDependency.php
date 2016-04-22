<?php
/**
 * ChainedDependency
 */
class ChainedDependency extends Dependency {


   /**
    * Abhängigkeiten des Gesamtausdrucks
    */
   private /*Dependency[]*/ $dependencies;


   /**
    * logischer Typ der Gesamtabhängigkeit (AND oder OR)
    */
   private /*string*/ $type;


   /**
    * Constructor
    *
    * @param  Dependency $dependency - Abhängigkeit
    */
   private function __construct(Dependency $dependency) {
      $this->dependencies[] = $dependency;
      $this->setMinValidity($dependency->getMinValidity());
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param  Dependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   protected static function create(Dependency $dependency) {
      return new self($dependency);
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches UND (AND).
    *
    * @param  Dependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function andDependency(Dependency $dependency) {
      if ($dependency === $this)
         return $this;

      if ($this->type == 'OR')
         return self ::create($this)->andDependency($dependency);

      $this->type           = 'AND';
      $this->dependencies[] = $dependency;
      $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

      return $this;
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches ODER (OR).
    *
    * @param  Dependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function orDependency(Dependency $dependency) {
      if ($dependency === $this)
         return $this;

      if ($this->type == 'AND')
         return self ::create($this)->orDependency($dependency);

      $this->type           = 'OR';
      $this->dependencies[] = $dependency;
      $this->setMinValidity(max($this->getMinValidity(), $dependency->getMinValidity()));

      return $this;
   }


   /**
    * Ob das zu überwachende Ereignis oder der Zustandswechsel eingetreten sind oder nicht.
    *
    * @return bool - TRUE, wenn die Abhängigkeit weiterhin erfüllt ist.
    *                FALSE, wenn der Zustandswechsel eingetreten ist und die Abhängigkeit nicht mehr erfüllt ist.
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

      throw new plRuntimeException('Unreachable code reached');
   }
}

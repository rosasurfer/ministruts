<?
/**
 * MinAgeDependency
 *
 * Abhängigkeit von einem Mindestalter der aktuellen Instanz.  Die Abhängigkeit ist erfüllt, sowie
 * ein minimales Alter erreicht wird.
 */
class MinAgeDependency extends ChainableDependency {


   /**
    * Altersgrenze (Unix-Timestamp) dieser Abhängigkeit
    */
   private /*int*/ $limit;


   /**
    * Constructor
    *
    * @param int $age - minimales Alter in Sekunden
    */
   public function __construct($age) {
      if (!is_int($age)) throw new IllegalTypeException('Illegal type of argument $age: '.getType($age));
      if ($age < 0)      throw new InvalidArgumentException('Invalid argument $age: '.$age);

      $this->limit = mkTime() + $age;
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param int $age - minimales Alter in Sekunden
    *
    * @return MaxAgeDependency
    */
   public static function create($age) {
      return new self($age);
   }


   /**
    * Ob das der Abhängigkeit zugrunde liegende Mindestalter erreicht wurde.
    *
    * @return boolean - TRUE, wenn das Mindestalter errreicht wurde.
    *                   FALSE, wenn das Mindestalter noch nicht erreicht wurde.
    */
   public function isValid() {
      $now = mkTime();
      if ($now < $this->limit)
         return false;

      return true;
   }
}
?>

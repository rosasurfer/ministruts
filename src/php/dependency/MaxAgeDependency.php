<?
/**
 * MaxAgeDependency
 *
 * Abhängigkeit von einem Höchstalter der aktuellen Instanz.  Die Abhängigkeit ist erfüllt, solange
 * das maximale Alter nicht überschritten wurde.
 */
class MaxAgeDependency extends ChainableDependency {


   /**
    * Altersgrenze (Unix-Timestamp) dieser Abhängigkeit
    */
   private /*int*/ $deadline;


   /**
    * Constructor
    *
    * @param int $age - maximales Alter in Sekunden
    */
   public function __construct($age) {
      if (!is_int($age)) throw new IllegalTypeException('Illegal type of argument $age: '.getType($age));
      if ($age < 0)      throw new InvalidArgumentException('Invalid argument $age: '.$age);

      $this->deadline = mkTime() + $age;
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @param int $age - maximales Alter in Sekunden
    *
    * @return MaxAgeDependency
    */
   public static function create($age) {
      return new self($age);
   }


   /**
    * Ob die der Abhängigkeit zugrunde liegende Altersgrenze überschritten wurde.
    *
    * @return boolean - TRUE, wenn die Grenze nicht überschritten wurde.
    *                   FALSE, wenn die Grenze überschritten wurde.
    */
   public function isValid() {
      $now = mkTime();
      if ($now > $this->deadline)
         return false;

      return true;
   }
}
?>

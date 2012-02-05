<?
/**
 * Dependency
 *
 * Abstrakte Basisklasse für Abhängigkeiten von bestimmten Zuständen oder Bedingungen.  Wird benutzt,
 * um abhängig von einer Änderung Aktionen auszulösen.  Jede Implementierung dieser Klasse bildet eine
 * Abhängigkeit von einem bestimmten Zustand oder einer bestimmten Bedingung ab.  Soll ein Zustand
 * prozeßübergreifend verfolgt werden, muß die Instanz in einem entprechenden Persistenz-Container
 * gespeichert werden (Session, Datenbank, Dateisystem etc.).  Die Implementierungen müssen serialisierbar
 * sein.
 *
 * Mehrere Abhängigkeiten können durch logisches UND oder ODER kombiniert werden.
 *
 * Beispiel:
 * ---------
 *
 *    $dependency =               FileDependency::create('/etc/crontab')
 *                ->andDependency(FileDependency::create('/etc/hosts'))
 *                ->andDependency(FileDependency::create('/etc/resolve.conf'));
 *    ...
 *
 *    if (!$dependency->isValid()) {
 *       // Zustand hat sich geändert, beliebige Aktion ausführen
 *    }
 *
 * Dieses Beispiel definiert eine gemeinsame Abhängigkeit vom Zustand dreier Dateien '/etc/crontab',
 * '/etc/hosts' und '/etc/resolv.conf' (AND-Verknüpfung).  Solange keine dieser Dateien verändert oder
 * gelöscht wird, bleibt die Abhängigkeit erfüllt und der Aufruf von $dependency->isValid() gibt TRUE
 * zurück.  Nach Änderung oder Löschen einer dieser Dateien gibt der Aufruf von $dependency->isValid()
 * FALSE zurück.
 *
 * @see ChainedDependency
 * @see FileDependency
 */
abstract class Dependency extends Object {


   /**
    * Mindestgültigkeit
    */
   private /*int*/ $minValidity = 0;


   /**
    * Ob das zu überwachende Ereignis oder ein Zustandswechsel eingetreten sind oder nicht.
    *
    * @return bool - TRUE, wenn die Abhängigkeit weiterhin erfüllt ist.
    *                FALSE, wenn der Zustandswechsel eingetreten ist und die Abhängigkeit nicht mehr erfüllt ist.
    */
   abstract public function isValid();


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches UND (AND).
    *
    * @param Dependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function andDependency(Dependency $dependency) {
      if ($dependency === $this)
         return $this;

      return ChainedDependency ::create($this)
                               ->andDependency($dependency);
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches ODER (OR).
    *
    * @param Dependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function orDependency(Dependency $dependency) {
      if ($dependency === $this)
         return $this;

      return ChainedDependency ::create($this)
                               ->orDependency($dependency);
   }


   /**
    * Gibt die Mindestgültigkeit dieser Abhängigkeit zurück.
    *
    * @return int - Mindestgültigkeit in Sekunden
    */
   public function getMinValidity() {
      return $this->minValidity;
   }


   /**
    * Setzt die Mindestgültigkeit dieser Abhängigkeit.
    *
    * @param int $time - Mindestgültigkeit in Sekunden
    *
    * @return ChainedDependency
    */
   public function setMinValidity($time) {
      if ($time!==(int)$time) throw new IllegalTypeException('Illegal type of argument $time: '.getType($time));
      if ($time < 0)          throw new plInvalidArgumentException('Invalid argument $time: '.$time);

      $this->minValidity = $time;

      return $this;
   }
}
?>

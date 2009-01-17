<?
/**
 * ChainableDependency
 *
 * Abstrakte Basisklasse für Abhängigkeiten von bestimmten Zuständen oder Bedingungen.  Jede Implementierung
 * dieser Klasse bildet eine Abhängigkeit von einem bestimmten Zustand oder einer bestimmten Bedingung ab.
 * Abhängigkeiten können durch logisches UND oder logisches ODER kombiniert werden.
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
 *       // irgendeine Aktion
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
abstract class ChainableDependency extends Object implements IDependency {


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches UND (AND).
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function andDependency(IDependency $dependency) {
      return ChainedDependency ::create($this)
                               ->andDependency($dependency);
   }


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren durch ein logisches ODER (OR).
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function orDependency(IDependency $dependency) {
      return ChainedDependency ::create($this)
                               ->orDependency($dependency);
   }
}
?>

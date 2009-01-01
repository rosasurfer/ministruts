<?
/**
 * ChainableDependency
 *
 * Abstrakte Basisklasse für Abhängigkeiten von bestimmten Zuständen oder Bedingungen.  Jede Implementierung
 * dieser Klasse bildet eine Abhängigkeit von einem bestimmten Zustand oder einer bestimmten Bedingung ab.
 * Abhängigkeiten können kombiniert werden.
 *
 * Anwendungsbeispiel:
 * -------------------
 *
 *    $dependency = FileDependency::create('/etc/crontab')
 *            ->add(FileDependency::create('/etc/hosts'))
 *            ->add(FileDependency::create('/etc/resolve.conf'));
 *    ...
 *
 *    if (!$dependency->isValid()) {
 *       // irgendeine Aktion
 *    }
 *
 * Dieses Beispiel definiert eine gemeinsame Abhängigkeit vom Zustand dreier Dateien '/etc/crontab',
 * '/etc/hosts' und '/etc/resolv.conf'.  Solange keine dieser Dateien verändert oder gelöscht wird,
 * bleibt die Abhängigkeit erfüllt und der Aufruf von $dependency->isValid() gibt TRUE zurück.  Nach
 * Änderung oder Löschen einer dieser Dateien gibt der Aufruf von $dependency->isValid() FALSE zurück.
 *
 * @see ChainedDependency
 * @see FileDependency
 */
abstract class ChainableDependency extends Object implements IDependency {


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren. Die neue Abhängigkeit wird nach allen anderen
    * vorhandenen Abhängigkeiten eingefügt (logisches AND).
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return ChainedDependency
    */
   public function add(IDependency $dependency) {
      return ChainedDependency ::create()
                               ->add($this)
                               ->add($dependency);
   }
}
?>

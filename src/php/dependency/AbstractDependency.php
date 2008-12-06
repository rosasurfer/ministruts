<?
/**
 * AbstractDependency
 *
 * Abstrakte Basisklasse für Abhängigkeiten von bestimmten Ereignissen.  Jede einzelne Implementation
 * dieser Klasse bildet eine Abhängigkeit vom Eintreten eines einzelnen spezifischen Ereignisses ab.
 * Abhängigkeiten können kombiniert werden.
 *
 * Anwendungsbeispiel:
 * -------------------
 *
 *    $dependency = FileDependency::create('/etc/crontab')
 *            ->add(FileDependency::create('/etc/hosts'))
 *            ->add(FileDependency::create('/etc/resolve.conf'));
 *    ....
 *
 *    if ($dependency->isStatusChanged()) {
 *       // irgendeine Aktion
 *    }
 *
 * Dieses Beispiel definiert eine gemeinsame Abhängigkeit vom Zustand dreier Dateien '/etc/crontab',
 * '/etc/hosts' und '/etc/resolv.conf'.  Solange keine der Dateien verändert oder gelöscht wird, bleibt
 * die Abhängigkeit erfüllt und der Aufruf von $dependency->isStatusChanged() gibt FALSE zurück.
 * Nach Änderung oder Löschen einer der Dateien gibt der Aufruf von $dependency->isStatusChanged()
 * TRUE zurück.
 */
abstract class AbstractDependency extends Object implements IDependency {


   /**
    * die definierten Abhängigkeiten dieser Instanz
    */
   private /*IDependency[]*/ $dependencies = array();


   /**
    * Kombiniert diese Abhängigkeit mit einer weiteren. Die hinzugefügte Abhängigkeit wird nach allen
    * anderen vorhandenen Abhängigkeiten eingefügt.
    *
    * @param IDependency $dependency - Abhängigkeit
    *
    * @return AbstractDependency
    */
   public function add(IDependency $dependency) {
      $this->dependencies[] = $dependency;
      return $this;
   }


   /**
    * Ob sich die den Abhängigkeiten zugrunde liegende Zustände geändert haben oder nicht.
    *
    * @return boolean
    */
   public function isStatusChanged() {
      foreach ($this->dependencies as $dependency) {
         if ($dependency->isStatusChanged())
            return true;
      }
      return false;
   }
}
?>

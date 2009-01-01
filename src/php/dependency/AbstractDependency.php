<?
/**
 * AbstractDependency
 *
 * Abstrakte Basisklasse für Abhängigkeiten von bestimmten Zuständen oder Bedingungen.  Jede einzelne
 * Implementierung dieser Klasse bildet eine Abhängigkeit von einem bestimmten Zustand oder einer bestimmten
 * Bedingung. Abhängigkeiten können kombiniert werden.
 *
 * Anwendungsbeispiel:
 * -------------------
 *
 *    $dependency = FileDependency::create('/etc/crontab')
 *            ->add(FileDependency::create('/etc/hosts'))
 *            ->add(FileDependency::create('/etc/resolve.conf'));
 *    ....
 *
 *    if (!$dependency->isValid()) {
 *       // irgendeine Aktion
 *    }
 *
 * Dieses Beispiel definiert eine gemeinsame Abhängigkeit vom Zustand dreier Dateien '/etc/crontab',
 * '/etc/hosts' und '/etc/resolv.conf'.  Solange keine der Dateien verändert oder gelöscht wird, bleibt
 * die Abhängigkeit erfüllt und der Aufruf von $dependency->isValid() gibt TRUE zurück.  Nach Änderung
 * oder Löschen einer der Dateien gibt der Aufruf von $dependency->isValid() FALSE zurück.
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

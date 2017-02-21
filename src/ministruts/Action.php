<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * Action
 */
abstract class Action extends Object {


    protected /*ActionMapping*/ $mapping;
    protected /*ActionForm   */ $form;


    /**
     * Constructor
     *
     * Erzeugt eine neue Action.
     *
     * @param  ActionMapping   $mapping - das Mapping, zu dem die Action gehoert
     * @param  ActionForm|null $form    - die ActionForm (default: none)
     */
    public function __construct(ActionMapping $mapping, ActionForm $form = null) {
        $this->mapping = $mapping;
        $this->form    = $form;
    }


    /**
     * Gibt das aktuelle ActionMapping, das die Verwendung dieser Action definiert, zurueck.
     *
     * @return ActionMapping
     */
    final public function getMapping() {
        return $this->mapping;
    }


    /**
     * Allgemeiner Pre-Processing-Hook, der von Subklassen bei Bedarf ueberschrieben werden kann.  Gibt NULL
     * zurueck, wenn die Verarbeitung fortgesetzt werden soll oder eine ActionForward-Instanz, wenn die
     * Verarbeitung abgebrochen und zu dem vom Forward beschriebenen Ziel verzweigt werden soll.
     * Die Default-Implementierung macht nichts.
     *
     * Note: Zur Vereinfachung kann statt einer Instanz auch der Name eines ActionForward aus der
     *       struts-config.xml zurueckgeben werden.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ActionForward|string|null
     */
    public function executeBefore(Request $request, Response $response) {
        return null;
    }


    /**
     * Fuehrt die Action aus und gibt einen ActionForward zurueck, der beschreibt, zu welcher Resource
     * verzweigt werden soll. Muss implementiert werden.
     *
     * NOTE: Statt einer Instanz kann auch der Name eines ActionForward aus der "struts-config.xml" zurueckgeben werden.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ActionForward|string
     */
    abstract public function execute(Request $request, Response $response);


    /**
     * Allgemeiner Post-Processing-Hook, der von Subklassen bei Bedarf ueberschrieben werden kann.
     *
     * Besondere Vorsicht ist anzuwenden, da zu dem Zeitpunkt, da diese Methode aufgerufen wird, der
     * Content schon ausgeliefert und der Response schon fertiggestellt sein KANN. Die Methode ist fuer
     * Aufraeumarbeiten nuetzlich, z.B. das Committen von Transaktionen oder das Schliessen von
     * Datenbankverbindungen.
     * Die Default-Implementierung macht nichts.
     *
     * @param  Request            $request
     * @param  Response           $response
     * @param  ActionForward|null $forward  - der originale ActionForward, wie ihn die Action zurueckgegeben hat
     *
     * @return ActionForward|null - der originale oder ein modifizierter ActionForward (z.B. mit weiteren
     *                              Query-Parameter)
     */
    public function executeAfter(Request $request, Response $response, ActionForward $forward = null) {
        return $forward;
    }


    /**
     * Sucht den ActionForward mit dem angegebenen Namen und gibt ihn zurueck. Zuerst werden die lokalen
     * Forwards des ActionMapping der Action durchsucht, danach die globalen Forwards des Modules. Wird
     * kein entsprechender Forward gefunden, wird NULL zurueckgegeben.
     *
     * @param  string $name - Bezeichner des ActionForwards
     *
     * @return ActionForward
     *
     * @see ActionMapping::findForward()
     */
    protected function findForward($name) {
        return $this->mapping->findForward($name);
    }
}

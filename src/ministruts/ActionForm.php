<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * ActionForm
 */
abstract class ActionForm extends Object {


    /** @var Request^transient - the current Request the form belongs to */
    protected $request;

    /** @var string^transient - dispatch action key */
    protected $actionKey;


    /**
     * Constructor
     *
     * Erzeugt eine neue ActionForm fuer den aktuellen Request.
     *
     * @param  Request $request - der aktuelle Request
     */
    public function __construct(Request $request) {
        $this->request = $request;

        // ggf. definierten DispatchAction-Key auslesen
        if     (isSet($_REQUEST['action'  ])) $this->actionKey = $_REQUEST['action'  ];
        elseif (isSet($_REQUEST['action.x'])) $this->actionKey = $_REQUEST['action.x'];  // submit-Type "image"

        // Parameter einlesen
        $this->populate($request);
    }


    /**
     * Liest die im Request uebergebenen Parameter ein.  Muss anwendungsabhaengig implementiert werden.
     *
     * @param  Request $request - der aktuelle Request
     */
    abstract protected function populate(Request $request);


    /**
     * Ob die eingelesenen Parameter gueltig sind. Muss anwendungsabhaengig ueberschrieben werden.
     *
     * @return bool - TRUE, wenn die uebergebenen Parameter gueltig sind, FALSE andererseits
     *                (diese Default-Implementierung gibt immer TRUE zurueck)
     */
    public function validate() {
        return true;
    }


    /**
     * Gibt den DispatchAction-Key zurueck (Beschreibung: siehe java.struts.DispatchAction).
     *
     * @return string - Action-Key oder NULL, wenn kein Wert angegeben wurde
     */
    public function getActionKey() {
        return $this->actionKey;
    }


    /**
     * Prevent serialization of transient properties.                    // access level encoding
     *                                                                   // ---------------------
     * @return string[] - array of property names to serialize           // private:   "\0{className}\0{propertyName}"
     */                                                                  // protected: "\0*\0{propertyName}"
    public function __sleep() {                                          // public:    "{propertyName}"
        $array = (array) $this;
        unset($array["\0*\0request"  ]);
        unset($array["\0*\0actionKey"]);
        return array_keys($array);
    }


    /**
     * Reinitialisiert die transienten Werte dieser Instanz.  Wird intern nach dem Deserialisieren
     * aufgerufen.
     */
    public function __wakeUp() {
        $this->__construct(Request::me());
    }
}

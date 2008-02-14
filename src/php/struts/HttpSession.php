<?
/**
 * HttpSession
 *
 * Wrapper für die aktuelle HttpSession des Users.
 */
class HttpSession extends Singleton {


   /**
    * der Request, zu dem wir gehören
    */
   protected /*Request*/ $request;


   /**
    * Ob die Session neu ist oder nicht. Die Session ist neu, wenn der User die Session-ID noch nicht
    * kennt.
    */
   protected /*bool*/ $new = null;


   /**
    * Konstruktor
    *
    * @param Request $request - der Request, zu dem die Session gehört
    */
   protected function __construct(Request $request) {
      $this->request = $request;
      $this->init();
   }


   /**
    * Initialisiert diese Session.
    */
   protected function init() {
      /**
       * PHP läßt sich ohne weiteres manipulierte Session-IDs unterschieben, solange diese keine
       * ungültigen Zeichen enthalten (IDs wie PHPSESSID=111 werden anstandslos akzeptiert). Wenn
       * session_start() zurückkehrt, gibt es mit den eingebauten PHP-Mitteln keine elegante Möglichkeit
       * mehr, festzustellen, ob die Session-ID von PHP oder vom User generiert wurde. Daher wird in
       * jeder Session mit neuer ID eine zusätzliche Markierungsvariable gespeichert. Fehlt diese
       * Markierung nach der Initialisierung, wurde die ID nicht hier generiert. In diesem Fall wird
       * die Session aus Sicherheitsgründen verworfen und eine neue erzeugt.
       */

      // Session starten oder fortsetzen
      if (!$this->request->isSession()) {
         try {
            session_start();
         }
         catch (PHPErrorException $error) {
            if (strPos($error->getMessage(), 'The session id contains illegal characters') === false)
               throw $error;              // andere Fehler weiterreichen
            session_regenerate_id();      // neue ID generieren
         }
      }

      // Session prüfen
      if (sizeOf($_SESSION) == 0) {          // neue Session gestartet, woher kommt die ID ?
         $sName = session_name();
         $sId   = session_id();

         // TODO: Verwendung von $_COOKIE und $_REQUEST ist unsicher
         if     (isSet($_COOKIE [$sName]) && $_COOKIE [$sName] == $sId) $fromUser = true;    // vom Cookie
         elseif (isSet($_REQUEST[$sName]) && $_REQUEST[$sName] == $sId) $fromUser = true;    // aus GET/POST
         else                                                           $fromUser = false;

         if ($fromUser)
            session_regenerate_id(true);     // neue ID generieren und alte Datei löschen

         // Marker setzen, ab jetzt ist sizeOf($_SESSION) immer > 0
         $_SESSION['__SESSION_CREATED__'  ] = microTime(true);
         $_SESSION['__SESSION_IP__'       ] = $_SERVER['REMOTE_ADDR'];     // TODO: forwarded remote IP einbauen
         $_SESSION['__SESSION_USERAGENT__'] = $_SERVER['HTTP_USER_AGENT'];

         $this->new = true;
      }
      else {                                 // vorhandene Session fortgesetzt
         $this->new = false;
      }
   }


   /**
    * Ob diese Session neu ist oder nicht. Die Session ist neu, wenn der User die aktuelle Session-ID noch nicht kennt.
    *
    * @return boolean
    */
   public function isNew() {
      return $this->new;
   }


   /**
    * Gibt die Session-ID der Session zurück.
    *
    * @return string - Session-ID
    */
   public function getId() {
      return session_id();
   }


   /**
    * Gibt den Namen der Sessionvariable zurück.
    *
    * @return string - Name
    */
   public function getName() {
      return session_name();
   }


   /**
    * Gibt den unter dem angegebenen Schlüssel in der Session gespeicherten Wert zurück oder NULL,
    * wenn unter diesem Schlüssel kein Wert existiert.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    *
    * @return mixed - der gespeicherte Wert oder NULL
    */
   public function getAttribute($key) {
      return isSet($_SESSION[$key]) ? $_SESSION[$key] : null;
   }


   /**
    * Speichert in der Session unter dem angegebenen Schlüssel einen Wert.  Ein unter dem selben
    * Schlüssel schon vorhandener Wert wird ersetzt.
    *
    * Ist der übergebene Wert NULL, hat dies den selben Effekt wie der Aufruf von
    * HttpSession::removeAttributes($key)
    *
    * @param string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, $value) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of argument $key: '.getType($key));

      if ($value !== null) {
         $_SESSION[$key] = $value;
      }
      else {
         $this->removeAttributes($key);
      }
   }


   /**
    * Löscht die unter den angegebenen Schlüsseln in der Session gespeicherten Werte. Existiert
    * unter einem Schlüssel kein Wert, macht die Methode gar nichts. Es können mehrere Schlüssel
    * angegeben werden.
    *
    * @param string $key - Schlüssel, unter dem der Wert gespeichert ist
    */
   public function removeAttributes($key /*, $key2, $key3 ...*/) {
      foreach (func_get_args() as $key)
         unset($_SESSION[$key]);
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert in der Session existiert.
    *
    * @param string $key - Schlüssel
    *
    * @return boolean
    */
   public function isAttribute($key) {
      return isSet($_SESSION[$key]);
   }


   /**
    * Entfernt alle gespeicherten Informationen aus der aktuellen Session.
    *
    * @return boolean - TRUE, wenn alle gespeicherten Informationen gelöscht wurden
    *                   FALSE, wenn keine Session existiert
   function clear() {
      if (isSession()) {
         $keys = array_keys($_SESSION);
         foreach ($keys as $key) {
            if (!String ::startsWith($key, '__SESSION_'))
               unSet($_SESSION[$key]);
         }
         return true;
      }
      return false;
   }
    */
}
?>

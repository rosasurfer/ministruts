<?php
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
      // Prüfen, ob eine Session bereits außerhalb dieser Instanz gestartet wurde
      if ($request->isSession())
         throw new IllegalStateException('Cannot initialize '.__CLASS__.', found already started session. Use this class *only* for session handling!');

      $this->request = $request;
      $this->init();
   }


   /**
    * Initialisiert diese Session.
    */
   protected function init() {
      /**
       * PHP läßt sich ohne weiteres manipulierte Session-IDs unterschieben, solange diese keine ungültigen Zeichen enthalten
       * (IDs wie PHPSESSID=111 werden anstandslos akzeptiert).  Wenn session_start() zurückkehrt, gibt es mit den vorhandenen
       * PHP-Mitteln keine vernünftige Möglichkeit mehr, festzustellen, ob die Session-ID von PHP oder (künstlich?) vom User
       * generiert wurde.  Daher wird in dieser Methode jede neue Session mit einer zusätzliche Markierung versehen.  Fehlt diese
       * Markierung nach Rückkehr von session_start(), wurde die ID nicht von PHP generiert.  Aus Sicherheitsgründen wird eine
       * solche Session verworfen und eine neue ID erzeugt.
       */
      $request = $this->request;

      // Session-Cookie auf Application beschränken, um mehrere Projekte je Domain zu ermöglichen
      $params = session_get_cookie_params();
      session_set_cookie_params($params['lifetime'],
                                $request->getApplicationPath().'/',
                                $params['domain'],
                                $params['secure'],
                                $params['httponly']);

      // Session starten bzw. fortsetzen
      try {
         session_start();
      }
      catch (PHPErrorException $error) {
         if (strPos($error->getMessage(), 'The session id contains illegal characters') === false)
            throw $error;                 // andere Fehler weiterreichen
         session_regenerate_id();         // neue ID generieren
      }


      // Inhalt der Session prüfen
      // TODO: Session verwerfen, wenn der User zwischen Cookie- und URL-Übertragung wechselt
      if (sizeOf($_SESSION) == 0) {          // 0 bedeutet, die Session ist (für diese Methode) neu
         $sessionName = session_name();
         $sessionId   = session_id();        // prüfen, woher die ID kommt ...

         // TODO: Verwendung von $_COOKIE und $_REQUEST ist unsicher
         if     (isSet($_COOKIE [$sessionName]) && $_COOKIE [$sessionName] == $sessionId) $fromUser = true; // ID kommt vom Cookie
         elseif (isSet($_REQUEST[$sessionName]) && $_REQUEST[$sessionName] == $sessionId) $fromUser = true; // ID kommt aus GET/POST
         else                                                                             $fromUser = false;

         if ($fromUser)
            session_regenerate_id(true);     // neue ID generieren und alte Datei löschen

         // Marker setzen, ab jetzt ist sizeOf($_SESSION) immer > 0
         // TODO: $request->getHeader() einbauen
         $_SESSION['__SESSION_CREATED__'  ] = microTime(true);
         $_SESSION['__SESSION_IP__'       ] = $request->getRemoteAddress();      // TODO: forwarded remote IP einbauen
         $_SESSION['__SESSION_USERAGENT__'] = $request->getHeaderValue('User-Agent');

         $this->new = true;
      }
      else {                                 // vorhandene Session fortgesetzt
         $this->new = false;
      }
   }


   /**
    * Ob diese Session neu ist oder nicht. Die Session ist neu, wenn der User die aktuelle Session-ID noch nicht kennt.
    *
    * @return bool
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
    * Gibt den unter dem angegebenen Schlüssel in der Session gespeicherten Wert zurück oder den
    * angegebenen Alternativwert, falls kein Wert unter diesem Schlüssel existiert.
    *
    * @param string $key     - Schlüssel, unter dem der Wert gespeichert ist
    * @param mixed  $default - Default- bzw. Alternativwert (kann selbst auch NULL sein)
    *
    * @return mixed - der gespeicherte Wert oder NULL
    */
   public function getAttribute($key, $default = null) {
      if (isSet($_SESSION[$key]))
         return $_SESSION[$key];

      return $default;
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
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

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
    * @return bool
    */
   public function isAttribute($key) {
      return isSet($_SESSION[$key]);
   }


   /**
    * Entfernt alle gespeicherten Informationen aus der aktuellen Session.
    *
    * @return bool - TRUE, wenn alle gespeicherten Informationen gelöscht wurden
    *                FALSE, wenn keine Session existiert
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

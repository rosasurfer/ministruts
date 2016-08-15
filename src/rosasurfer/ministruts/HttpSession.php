<?php
use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\PHPError;

use rosasurfer\ministruts\Request;


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
    * Constructor
    *
    * @param  Request $request - der Request, zu dem die Session gehört
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
                                $request->getApplicationBaseUri(),
                                $params['domain'],
                                $params['secure'],
                                $params['httponly']);

      // Session starten bzw. fortsetzen
      try {
         session_start();
      }
      catch (PHPError $error) {
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

         $this->reset($fromUser);            // if $fromUser=TRUE: generate new session id
      }
      else {                                 // vorhandene Session fortgesetzt
         $this->new = false;
      }
   }


   /**
    * Reset this session to a clean and new state.
    *
    * @param  bool $regenerateId - whether or not to generate a new session id and to delete an old session file
    */
   public function reset($regenerateId) {
      if (!is_bool($regenerateId)) throw new IllegalTypeException('Illegal type of parameter $regenerateId: '.getType($regenerateId));

      if ($regenerateId) {
         // assign new id, delete old file
         session_regenerate_id(true);
      }

      // empty the session
      $this->removeAttribute(array_keys($_SESSION));

      // initialize the session
      $request = $this->request;                                              // TODO: $request->getHeader() einbauen
      $_SESSION['__SESSION_CREATED__'  ] = microTime(true);
      $_SESSION['__SESSION_IP__'       ] = $request->getRemoteAddress();      // TODO: forwarded remote IP einbauen
      $_SESSION['__SESSION_USERAGENT__'] = $request->getHeaderValue('User-Agent');

      $this->new = true;
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
    * @param  string $key     - Schlüssel, unter dem der Wert gespeichert ist
    * @param  mixed  $default - Default- bzw. Alternativwert (kann selbst auch NULL sein)
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
    * HttpSession::removeAttribute($key)
    *
    * @param  string $key   - Schlüssel, unter dem der Wert gespeichert wird
    * @param  mixed  $value - der zu speichernde Wert
    */
   public function setAttribute($key, $value) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

      if ($value !== null) {
         $_SESSION[$key] = $value;
      }
      else {
         $this->removeAttribute($key);
      }
   }


   /**
    * Delete session values stored under the specified key(s).
    *
    * @param  string|array $key - single session key or array of session keys of values to remove
    * @param  ...               - variable length list of more keys
    */
   public function removeAttribute($key /*...*/) {
      foreach (func_get_args() as $i => $key) {
         if (is_array($key)) {
            foreach ($key as $n => $arrayKey) {
               if (!is_string($arrayKey)) throw new IllegalTypeException('Illegal type of parameter '.$i.'['.$n.']: '.getType($arrayKey));
               unset($_SESSION[$arrayKey]);
            }
         }
         else {
            if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter '.$i.': '.getType($key));
            unset($_SESSION[$key]);
         }
      }
   }


   /**
    * Ob unter dem angegebenen Schlüssel ein Wert in der Session existiert.
    *
    * @param  string $key - Schlüssel
    *
    * @return bool
    */
   public function isAttribute($key) {
      return isSet($_SESSION[$key]);
   }
}


!defined('rosasurfer\ministruts\MODULE_KEY') && include(__DIR__.'/definitions.php');
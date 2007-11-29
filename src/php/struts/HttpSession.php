<?
/**
 * HttpSession
 *
 * Wrapper für die aktuelle HttpSession des Requests.
 */
class HttpSession extends Singleton {


   /**
    * der Request, zu dem wir gehören
    */
   protected /*Request*/ $request;


   /**
    * Ob die Session neu ist oder nicht. Die Session ist neu, wenn der User die Session-ID noch nicht kennt.
    */
   protected /*bool*/ $new = null;


   /**
    * Gibt die Session-Instanz zurück.
    * Bei Aufruf in der Konsole wird NULL zurückgegeben.
    *
    * @return HttpSession - Instanz
    *
    * @throws IllegalStateException - wenn der Aufruf nicht in einem HTTP-Kontext erfolgt (z.B. Konsole)
    */
   public static function me() {
      $request = Request ::me();
      if (!$request)
         throw new IllegalStateException('You can not use '.__CLASS__.' in this context.');

      return Singleton ::getInstance(__CLASS__, $request);
   }


   /**
    * Konstruktor
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
       * PHP läßt sich ohne weiteres manipulierte Session-IDs unterschieben, solange diese keine ungültigen
       * Zeichen enthalten (IDs wie PHPSESSID=111 werden anstandslos akzeptiert). Wenn session_start()
       * zurückkehrt, gibt es mit den eingebauten PHP-Mitteln keine elegante Möglichkeit mehr, festzustellen,
       * ob die Session-ID von PHP oder vom User generiert wurde. Daher wird in jeder Session mit neuer ID
       * eine zusätzliche Markierungsvariable gespeichert. Fehlt diese Markierung nach der Initialisierung,
       * wurde die ID nicht hier generiert. In diesem Fall wird die Session aus Sicherheitsgründen verworfen
       * und eine neue erzeugt.
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
         $sname = session_name();
         $sid = session_id();

         // TODO: Verwendung von $_COOKIE und $_REQUEST ist unsicher
         if     (isSet($_COOKIE [$sname]) && $_COOKIE [$sname] == $sid) $fromUser = true;    // vom Cookie
         elseif (isSet($_REQUEST[$sname]) && $_REQUEST[$sname] == $sid) $fromUser = true;    // aus GET/POST
         else                                                           $fromUser = false;

         if ($fromUser) {
            session_regenerate_id(true);     // neue ID generieren und alte Datei löschen
         }

         // Marker setzen, ab jetzt: sizeOf($_SESSION) > 0
         $_SESSION['__SESSION_CREATED__'  ] = time();
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
}
?>

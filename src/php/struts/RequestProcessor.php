<?
/**
 * RequestProcessor
 */
class RequestProcessor extends Object {


   // ModuleConfig, zu der wir gehören
   protected /* ModuleConfig */ $moduleConfig;

   private $logDebug;
   private $logInfo;
   private $logNotice;


   /**
    * Erzeugt einen neuen RequestProcessor.
    *
    * @param ModuleConfig $config - Modulkonfiguration, der dieser RequestProcessor zugeordnet ist
    */
   public function __construct(ModuleConfig $config) {
      $loglevel = Logger ::getLogLevel(__CLASS__);

      $this->logDebug  = ($loglevel <= L_DEBUG);
      $this->logInfo   = ($loglevel <= L_INFO);
      $this->logNotice = ($loglevel <= L_NOTICE);

      $this->moduleConfig = $config;
   }


   /**
    * Verarbeitet den übergebenen Request und gibt entweder den entsprechenden Content aus oder leitet auf eine andere Resource um.
    *
    * @param Request  $request
    * @param Response $response
    */
   final public function process(Request $request, Response $response) {

      // Pfad zur Actionauswahl ermitteln
      $path = $this->processPath($request, $response);


      // falls notwendig, ein Locale setzen
      $this->processLocale($request, $response);


      // falls notwendig, Content-Type und no-caching-Header setzen
      $this->processContentType($request, $response);
      $this->processNoCache($request, $response);


      // allgemeinen Preprocessing-Hook aufrufen
      if (!$this->processPreprocess($request, $response)) {
         $this->logDebug && Logger ::log('Preprocessor hook returned false', L_DEBUG, __CLASS__);
         return;
      }


      // ActionMessages, auf die schon zugegriffen wurde, aus der Session löschen
      $this->processCachedMessages($request, $response);


      // das entsprechende ActionMapping ermitteln
      $mapping = $this->processMapping($request, $response, $path);
      if (!$mapping) {
         $this->logDebug && Logger ::log('Could not find a mapping for this request', L_DEBUG, __CLASS__);
         return;
      }


      // ggf. benötigte Rollen überprüfen
      if (!$this->processRoles($request, $response, $mapping)) {
         $this->logDebug && Logger ::log('User does not have any required role, denying access', L_DEBUG, __CLASS__);
         return;
      }


      // falls im Mapping statt einer Action ein Forward konfiguriert wurde, diesen verarbeiten
      if (!$this->processMappingForward($request, $response, $mapping))
         return;


      // die ActionForm des Mappings erzeugen
      $form = $this->processActionForm($request, $response, $mapping);


      // die Action des Mappings erzeugen
      $action = $this->processActionCreate($request, $response, $mapping);


      // die Action aufrufen
      $forward = $this->processActionExceute($request, $response, $action, $form);


      // den zurückgegebenen ActionForward verarbeiten
      $this->processActionForward($request, $response, $forward);
   }


   /**
    * Gibt die Pfadkomponente des Requests, die für die ActionMapping-Auswahl benutzt wird,
    * zurück.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processPath(Request $request, Response $response) {
      $path = $request->getPathInfo();

      // führende Verzeichnisse abschneiden
      return strRChr($path, '/'); // !!! to-do: Module prefix abschneiden
   }


   /**
    * Wählt bei Bedarf ein Locale für den aktuellen User aus.
    *
    * Note: Die Auswahl eines Locale löst automatisch die Erzeugung einer HttpSession aus.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processLocale(Request $request, Response $response) {
   }


   /**
    * Setzt bei Bedarf den Default-Content-Type (und den Zeichensatz) für den Response.
    *
    * Note: Der Header wird überschrieben, falls die Anfrage in einem Redirect endet.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processContentType(Request $request, Response $response) {
   }


   /**
    * Setzt bei Bedarf den 'no-cache' Header für den Response.
    *
    * Note: Der Header wird überschrieben, falls die Anfrage in einem Redirect endet.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processNoCache(Request $request, Response $response) {
   }


   /**
    * Allgemeiner Preprocessing-Hook, der bei Bedarf überschrieben werden kann. Muß TRUE
    * zurückgeben, wenn die Verarbeitung nach dem Aufruf normal fortgesetzt werden soll,
    * oder FALSE, wenn bereits eine Anwort generiert wurde.
    * Die Default-Implementierung macht nichts.
    *
    * @param Request  $request
    * @param Response $response
    *
    * @return boolean
    */
   protected function processPreprocess(Request $request, Response $response) {
      return true;
   }


   /**
    * Löscht ActionMessages, die in der HttpSession gespeichert sind und auf die schon zugegriffen wurde.
    * Dies erlaubt das Speichern von Nachrichten in der Session, die nur einmal angezeigt werden können und
    * danach automatisch verschwinden. Dadurch können z.B. trotz eines Redirects Fehlermeldungen angezeigt werden.
    *
    * @param Request  $request
    * @param Response $response
    */
   protected function processCachedMessages(Request $request, Response $response) {
      if ($request->isSession() && isSet($_SESSION[Request ::MESSAGE_KEY])) {
         $errors =& $_SESSION[Request ::MESSAGE_KEY];

         foreach ($errors as $key => $error)
            if ($error['accessed'])
               unset($_SESSION[Request ::MESSAGE_KEY][$key]);

         if(sizeOf($errors) == 0)
            unset($_SESSION[Request ::MESSAGE_KEY]);
      }
   }

   /**
    * Ermittelt das zu benutzende ActionMapping.
    *
    * @param Request  $request
    * @param Response $response
    * @param string   $path     - Pfadkomponente zur ActionMapping-Auswahl
    *
    * @return ActionMapping
    */
   protected function processMapping(Request $request, Response $response, $path) {
      $mapping = $this->moduleConfig->findActionMapping($path);

      if (!$mapping) {      // no mapping can be found to process this request
         echoPre("Not found: 404\n\nThe requested URL $path was not found on this server");
      }

      return $mapping;
   }


   /**
    * Wenn die Action Zugriffsbeschränkungen hat, sicherstellen, daß der User mindestens eine der angegebenen
    * Rollen inne hat. Gibt TRUE zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt werden soll,
    * oder FALSE, wenn der Zugriff nicht gewährt wird.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processRoles(Request $request, Response $response, ActionMapping $mapping) {
      return true;
   }


   /**
    * Verarbeitet einen direkt im ActionMapping angegebenen ActionForward (wenn angegeben). Gibt TRUE zurück, wenn
    * die Verarbeitung fortgesetzt werden soll, oder FALSE, wenn der Request bereits beendet wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return boolean
    */
   protected function processMappingForward(Request $request, Response $response, ActionMapping $mapping) {
      $forward = $mapping->getForward();
      if (!$forward)
         return true;

      $this->doForward($forward);
      return false;
   }


   /**
    * Erzeugt und gibt die ActionForm des angegebenen Mappings zurück (wenn konfiguriert). Ist keine ActionForm konfiguriert,
    * wird NULL zurückgegeben.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return ActionForm
    */
   protected function processActionForm(Request $request, Response $response, ActionMapping $mapping) {
      $class = $mapping->getForm();
      if (!$class)
         return null;
      return new $class($request);
   }


   /**
    * Erzeugt und gibt die Action zurück, die für das angegebene Mapping konfiguriert wurde.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionMapping $mapping
    *
    * @return Action
    */
   protected function processActionCreate(Request $request, Response $response, ActionMapping $mapping) {
      $class = $mapping->getAction();
      return new $class($mapping);
   }


   /**
    * Übergibt den Request der angegebenen Action zur Bearbeitung und gibt den von der Action zurückgegebenen ActionForward zurück.
    *
    * @param Request    $request
    * @param Response   $response
    * @param Action     $action
    * @param ActionForm $form
    *
    * @return ActionForward
    */
   protected function processActionExceute(Request $request, Response $response, Action $action, ActionForm $form=null) {
      return $action->execute($form, $request, $response);
   }


   /**
    * Verarbeitet den von der Action zurückgegebenen ActionForward.  Leitet auf die Resource weiter, die der ActionForward bezeichnet.
    *
    * @param Request       $request
    * @param Response      $response
    * @param ActionForward $forward
    */
   protected function processActionForward(Request $request, Response $response, ActionForward $forward=null) {
      if ($forward) {
         if ($forward->isRedirect()) {
            echoPre('redirect');
         }
         else {
            // include
            echoPre('include');
         }
         echoPre($forward);
      }
   }
}
?>

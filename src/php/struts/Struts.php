<?
/**
 * Struts
 *
 * Globale Konstanten für das Struts-Framework.
 */
final class Struts extends StaticFactory {


   /**
    * Der Klassenname der Default-RequestProcessor-Implementierung.
    */
   const DEFAULT_PROCESSOR_CLASS = 'RequestProcessor';


   /**
    * Der Klassenname der Default-ActionMapping-Implementierung.
    */
   const DEFAULT_MAPPING_CLASS = 'ActionMapping';


   /**
    * Der Klassenname der Default-ActionForward-Implementierung.
    */
   const DEFAULT_FORWARD_CLASS = 'ActionForward';


   /**
    * Der Basisklassenname der Action-Implementierung.
    */
   const ACTION_BASE_CLASS = 'Action';


   /**
    * Der Basisklassenname der ActionForm-Implementierung.
    */
   const ACTION_FORM_BASE_CLASS = 'ActionForm';


   /**
    * Request- oder Session-Key, unter dem eventuelle ActionErrors gespeichert sind.
    */
   const ACTION_ERROR_KEY = 'org.apache.struts.action.ERROR';


   /**
    * Request- oder Session-Key, unter dem die aktuelle ActionForm-Instanz gespeichert ist.
    */
   const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';


   /**
    * Request-Key, unter dem das aktuelle ActionMapping gespeichert ist.
    */
   const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';


   /**
    * Request- oder Session-Key, unter dem eventuelle ActionMessages gespeichert sind.
    */
   const ACTION_MESSAGE_KEY = 'org.apache.struts.action.MESSAGE';


   /**
    * Session-Key, unter dem ein vom User gewähltes Locale gespeichert wird.
    */
   const LOCALE_KEY = 'org.apache.struts.action.LOCALE';


   /**
    * Request-Key, unter dem die verfügbaren MessageResources gespeichert sind (Internationalisierung).
    */
   const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';
}
?>

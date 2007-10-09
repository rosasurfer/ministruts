<?
/**
 * Struts
 *
 * Globale Konstanten für das Struts-Framework.
 */
final class Struts extends StaticFactory {


   /**
    * Request- oder Session-Key, unter dem eventuelle ActionErrors gespeichert sind.
    */
   const ACTION_ERROR_KEY = 'org.apache.struts.action.ERROR';


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

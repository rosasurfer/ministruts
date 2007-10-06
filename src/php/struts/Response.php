<?
/**
 * Response
 *
 * Wrapper für den HTTP-Response.
 */
final class Response extends Singleton {


   /**
    * Gibt die aktuelle Klasseninstanz zurück, wenn das Script im Kontext eines HTTP-Requestes aufgerufen wurde.
    * In allen anderen Fällen, z.B. bei Aufruf in der Konsole, wird NULL zurückgegeben.
    *
    * @return Response - Instanz oder NULL
    */
   public static function me() {
      if (isSet($_SERVER['REQUEST_METHOD']))
         return Singleton ::getInstance(__CLASS__);
      return null;
   }
}
?>

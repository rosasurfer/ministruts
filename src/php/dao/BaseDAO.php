<?
/**
 * BaseDAO
 *
 * Basis aller DAO's.
 */
abstract class BaseDAO extends Singleton {


   // Mapping-Constanten
   const T_BOOL     = 1;         // boolean
   const T_INT      = 2;         // int
   const T_FLOAT    = 3;         // float
   const T_STRING   = 4;         // string
   const T_SET      = 5;         // set
   const T_NULL     = true;      // null
   const T_NOT_NULL = false;     // not null


   /**
    * Datenbank-Alias der Entityklasse
    */
   private /*string*/ $dbAlias;


   /**
    * Worker dieses DAO's
    */
   private /*DB*/ $worker;


   /**
    * prozessweiter Reference-Cache
    */
   private /*PersistableObject[]*/ $identityMap;


   /**
    * Name der Entityklasse
    */
   protected /*string*/ $objectClass;


   /**
    * Konstruktor
    *
    * Erzeugt einen neuen DAO.
    *
    * @param  string $alias - Aliasname der Datenbank, mit dem die Object-Klasse verbunden ist
    */
   protected function __construct() {
      $this->objectClass = subStr(get_class($this), 0, -3);
      $this->worker = DBPool ::getDB($this->mapping['link']);
   }


   // single object getters
   public function getByQuery($query) {
      return $this->worker
                  ->executeSql($query);
   }

   // object's list getters
   public function getListByQuery($query) {
      return $this->worker
                  ->executeSql($query);
   }

   // DML statement
   public function executeSql($sql) {
      return $this->worker
                  ->executeSql($sql);
   }
}
?>

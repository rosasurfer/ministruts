<?
/**
 * CommonDAO
 *
 * Ein einfacher DAO, der die Grundfunktionaliät bereitstellt.  Kann anwendungsspezifisch erweitert
 * werden.
 */
class CommonDAO extends Singleton {


   // Mapping-Constanten
   const T_BOOL     = 1;               // boolean
   const T_BOOLEAN  = self ::T_BOOL;
   const T_INT      = 2;               // int
   const T_INTEGER  = self ::T_INT;
   const T_FLOAT    = 3;               // float
   const T_STRING   = 4;               // string
   const T_SET      = 5;               // set
   const T_NULL     = true;            // null
   const T_NOT_NULL = false;           // not null


   // Worker dieses DAO's
   private /*DB*/ $worker;


   // Name der Entityklasse, für die der DAO zuständig ist
   private /*string*/ $entityClass;


   /**
    * Konstruktor
    *
    * Erzeugt einen neuen DAO.
    *
    * @param string $alias - Aliasname der Datenbank, mit dem die Entity-Klasse verbunden ist.
    */
   protected function __construct() {
      $this->entityClass = subStr(get_class($this), 0, -3);
   }


   /**
    * Gibt den Wert des internen Ergebniszählers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    * (siehe found_rows():  http://dev.mysql.com/doc/refman/5.1/en/information-functions.html)
    *
    * @return int - Gesamtanzahl von Ergebnissen (ohne Berücksichtigung einer LIMIT-Klausel)
    */
   final public function countFoundItems() {
      return $this->getWorker()->countFoundItems();
   }


   /**
    * single object getters
    */
   final public function getByQuery($query) {
      return $this->getWorker()->getByQuery($query);
   }


   /**
    * object list getters
    */
   final public function getListByQuery($query, $count = false) {
      return $this->getWorker()->getListByQuery($query, $count);
   }


   /**
    */
   final public function executeSql($sql, $count = false) {
      return $this->getWorker()->executeSql($sql, $count);
   }


   /**
    * Gibt das Mapping der Entity-Klasse des DAO zurück.
    *
    * @return array
    */
   final public function getMapping() {
      return $this->mapping;
   }


   /**
    * Gibt den Namen der Entity-Klasse dieses DAO's zurück.
    *
    * @return string - Klassenname
    */
   final public function getEntityClass() {
      return $this->entityClass;
   }


   /**
    * Gibt den für die persistente Klasse dieses DAO zuständigen DB-Adapter zurück.
    *
    * @return DB
    */
   final public function getDB() {
      return $this->getWorker()->getDB();
   }


   /**
    * Gibt den Worker dieses DAO zurück. Ein Worker implementiert eine konkrete Caching-Strategie und
    * kann Entity-spezifisch konfiguriert werden.
    *
    * @return DaoWorker
    */
   private function getWorker() {
      if (!$this->worker) {
         $this->worker = new DaoWorker($this);
      }
      return $this->worker;
   }
}
?>

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
    * Prozessweiter Reference-Cache
    *
    * TODO: ReferencePool implementieren
    */
   private /*PersistableObject[]*/ $identityMap;


   /**
    * Name der Entityklasse, für die der DAO zuständig ist
    */
   protected /*string*/ $objectClass;


   protected $foundItemsCounter = 0;


   /**
    * Konstruktor
    *
    * Erzeugt einen neuen DAO.
    *
    * @param  string $alias - Aliasname der Datenbank, mit dem die Entity-Klasse verbunden ist.
    */
   protected function __construct() {
      $this->objectClass = subStr(get_class($this), 0, -3);
      $this->worker = DBPool ::getDB($this->mapping['link']);
   }


   /**
    * Gibt den Wert des internen Ergebniszählers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    * (siehe found_rows():  http://dev.mysql.com/doc/refman/5.1/en/information-functions.html)
    *
    * @return int - Gesamtanzahl von Ergebnissen (ohne LIMIT-Klausel)
    */
   public function getFoundItemsCounter() {
      return $this->foundItemsCounter;
   }


   /**
    * single object getters
    */
   public function getByQuery($query) {
      return $this->getWorker()
                  ->executeSql($query);
   }


   /**
    * object's list getters
    */
   public function getListByQuery($query) {
      return $this->getWorker()
                  ->executeSql($query);
   }


   /**
    * DML statement
    */
   public function executeSql($sql) {
      return $this->getWorker()
                  ->executeSql($sql);
   }


   /**
    * Konvertiert ein eindeutiges DB-Resultset in eine PersistableObject-Instanz.
    *
    * @param array $result - Rückgabewert einer Datenbankabfrage
    *
    * @return array - Instanz
    *
    * @throws DatabaseException - wenn ein mehrzeiliges Resultset übergeben wird
    */
   protected function makeObject(array $result) {
      if ($result['rows'] > 1) throw new DatabaseException('Unexpected, non-unique query result: '.$result);

      $instance = null;

      if ($result['rows'] == 1) {
         $row = mysql_fetch_assoc($result['set']);
         $instance = PersistableObject ::createInstance($this->objectClass, $row);
      }
      $this->foundItemsCounter = $instance ? 1 : 0;

      return $instance;
   }


   /**
    * Konvertiert ein DB-Resultset in PersistableObject-Instanzen.
    *
    * @param array $result - Rückgabewert einer Datenbankabfrage
    *
    * @return array - Instanzen
    */
   protected function makeObjects(array $result) {
      $instances = array();

      while ($row = mysql_fetch_assoc($result['set'])) {
         $instances[] = PersistableObject ::createInstance($this->objectClass, $row);
      }
      $this->foundItemsCounter = $result['rows'];

      return $instances;
   }


   /**
    * Gibt den Worker (die konkrete Connector-Implementierung) für diesen DAO zurück.
    *
    * @return DB
    */
   private function getWorker() {
      return $this->worker;
   }
}
?>

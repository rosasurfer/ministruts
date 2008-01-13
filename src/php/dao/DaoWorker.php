<?
/**
 * DaoWorker
 */
class DaoWorker extends Object {


   // DAO der Entity-Klasse dieses Workers
   private /*CommonDAO*/ $dao;


   // Name der Entity-Klasse dieses Workers
   protected $entityClass;


   // DB-Adapter der Entity-Klasse dieses Workers
   private /*DB*/ $adapter;


   private $foundItemsCounter = 0;


   /**
    * Konstruktor
    *
    * Erzeugt einen neuen Worker für den angegebenen DAO.
    *
    * @param CommonDAO $dao - CommonDAO
    */
   public function __construct(CommonDAO $dao) {
      $this->dao = $dao;
      $this->entityClass = $dao->getEntityClass();
   }


   /**
    * single object getters
    */
   public function getByQuery($query) {
      $result = $this->executeSql($query);
      return $this->makeObject($result);
   }


   /**
    * object's list getters
    */
   public function getListByQuery($query, $count = false) {
      $result = $this->executeSql($query, $count);
      return $this->makeObjects($result);
   }


   /**
    */
   public function executeSql($sql, $count = false) {
      $result = $this->getDB()->executeSql($sql);

      if ($count) {
         $result2 = $this->executeSql('select found_rows()');
         $this->foundItemsCounter = (int) mysql_result($result2['set'], 0);
      }
      else {
         $this->foundItemsCounter = $result['rows'];
      }

      return $result;
   }


   /**
    * Gibt den Wert des internen Ergebniszählers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    * (siehe found_rows():  http://dev.mysql.com/doc/refman/5.1/en/information-functions.html)
    *
    * @return int - Gesamtanzahl von Ergebnissen (ohne LIMIT-Klausel)
    */
   public function countFoundItems() {
      return $this->foundItemsCounter;
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
   public function makeObject(array $result) {
      if ($result['rows'] > 1) throw new DatabaseException('Unexpected non-unique query result: '.$result);

      $instance = null;

      if ($result['rows'] == 1) {
         $row = mysql_fetch_assoc($result['set']);
         $instance = PersistableObject ::createInstance($this->entityClass, $row);
      }

      return $instance;
   }


   /**
    * Konvertiert ein DB-Resultset in PersistableObject-Instanzen.
    *
    * @param array $result - Rückgabewert einer Datenbankabfrage
    *
    * @return array - Instanzen
    */
   public function makeObjects(array $result) {
      $instances = array();

      while ($row = mysql_fetch_assoc($result['set'])) {
         $instances[] = PersistableObject ::createInstance($this->entityClass, $row);
      }

      return $instances;
   }


   /**
    * Gibt den DB-Adapter zurück.
    *
    * @return DB
    */
   private function getDB() {
      if (!$this->adapter) {
         $mapping = $this->dao->getMapping();
         $this->adapter = DBPool ::getDB($mapping['link']);
      }
      return $this->adapter;
   }
}
?>

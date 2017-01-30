<?php
namespace rosasurfer\dao;

use rosasurfer\core\Object;

use rosasurfer\db\DBPool;

use rosasurfer\exception\DatabaseException;
use rosasurfer\exception\IllegalTypeException;


/**
 * DaoWorker
 *
 * Ein DaoWorker implementiert eine konkrete Caching-Strategie. Zu jeder persistenten Klasse gehört
 * genau ein DaoWorker.
 */
class DaoWorker extends Object {


   /** @var CommonDao - DAO der Entity-Klasse dieses Workers */
   private $dao;

   /** @var string - Name der Entity-Klasse dieses Workers */
   protected $entityClass;

   /** @var DB - DB-Adapter der Entity-Klasse dieses Workers */
   private $adapter;

   /** @var int */
   private $foundItemsCounter = 0;


   /**
    * Constructor
    *
    * Erzeugt einen neuen Worker für den angegebenen DAO.
    *
    * @param  CommonDao $dao
    */
   public function __construct(CommonDao $dao) {
      $this->dao = $dao;
      $this->entityClass = $dao->getEntityClass();
   }


   /**
    * single object getter
    */
   public function getByQuery($query) {
      $result = $this->executeSql($query);
      return $this->makeObject($result);
   }


   /**
    * object's list getter
    */
   public function getListByQuery($query, $count = false) {
      $result = $this->executeSql($query, $count);
      return $this->makeObjects($result);
   }


   /**
    * Führt eine SQL-Anweisung aus. Gibt das Ergebnis als mehrdimensionales Array zurück.
    *
    * @param  string $sql   - SQL-Anweisung
    * @param  bool   $count - ob der interne Ergebniszähler aktualisiert werden soll
    *
    * @return array['set' ] - das zurückgegebene Resultset (nur bei SELECT-Statement)
    *              ['rows'] - Anzahl der betroffenen Datensätze (nur bei SELECT/INSERT/UPDATE-Statement)
    */
   public function executeSql($sql, $count = false) {
      $result = $this->getDb()->executeSql($sql);

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
    * @return int - Gesamtanzahl von Ergebnissen der letzten Abfrage (ohne Berücksichtigung einer LIMIT-Klausel)
    */
   public function countFoundItems() {
      return $this->foundItemsCounter;
   }


   /**
    * Konvertiert ein eindeutiges DB-Resultset in eine PersistableObject-Instanz.
    *
    * @param  array $result - Rückgabewert einer Datenbankabfrage
    *
    * @return array - Instanz
    *
    * @throws DatabaseException - wenn ein mehrzeiliges Resultset übergeben wird
    */
   public function makeObject(array $result) {
      if (getType($result['set']) != 'resource') throw new IllegalTypeException('Illegal type of parameter $result[set]: '.getType($result['set']));
      if ($result['rows'] > 1)                   throw new DatabaseException('Unexpected non-unique query result: '.$result);

      $instance = null;

      if ($result['rows']) {
         $row = mysql_fetch_assoc($result['set']);
         $instance = PersistableObject::createInstance($this->entityClass, $row);
      }

      return $instance;
   }


   /**
    * Konvertiert ein DB-Resultset in PersistableObject-Instanzen.
    *
    * @param  array $result - Rückgabewert einer Datenbankabfrage
    *
    * @return array - Instanzen
    */
   public function makeObjects(array $result) {
      if (getType($result['set']) != 'resource') throw new IllegalTypeException('Illegal type of parameter $result[set]: '.getType($result['set']));

      $instances = array();

      while ($row = mysql_fetch_assoc($result['set'])) {
         $instances[] = PersistableObject::createInstance($this->entityClass, $row);
      }

      return $instances;
   }


   /**
    * Gibt den der persistenten Klasse zugrunde liegenden DB-Adapter zurück.
    *
    * @return DB
    */
   public function getDb() {
      if (!$this->adapter) {
         $mapping = $this->dao->getMapping();
         $this->adapter = DBPool::getDb($mapping['connection']);
      }
      return $this->adapter;
   }
}

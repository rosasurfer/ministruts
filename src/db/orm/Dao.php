<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;

use rosasurfer\exception\ConcurrentModificationException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * Dao
 *
 * Abstract DAO base class.
 */
abstract class Dao extends Singleton {


   // Mapping-Constanten
   const T_BOOL     = 1;               // bool
   const T_BOOLEAN  = self::T_BOOL;
   const T_INT      = 2;               // int
   const T_INTEGER  = self::T_INT;
   const T_FLOAT    = 3;               // float
   const T_STRING   = 4;               // string
   const T_SET      = 5;               // set
   const T_NULL     = true;            // null
   const T_NOT_NULL = false;           // not null


   /** @var array - database mapping; "abstract" member, must be re-defined in the concrete DAO */
   protected $mapping = [];

   /** @var Worker - Worker dieses DAO's */
   private $worker;

   /** @var string - Name der Entityklasse, für die der DAO zuständig ist */
   private $entityClass;


   /**
    * Constructor
    *
    * Erzeugt einen neuen DAO.
    */
   protected function __construct() {
      $this->entityClass = subStr(get_class($this), 0, -3);
   }


   /**
    * Gibt den Wert des internen Ergebniszählers zurück. Kann bei seitenweiser Ergebnisanzeige
    * statt einer zweiten Datenbankabfrage benutzt werden.
    * (siehe found_rows():  http://dev.mysql.com/doc/refman/5.1/en/information-functions.html)
    *
    * @return int - Gesamtanzahl von Ergebnissen der letzten Abfrage (ohne Berücksichtigung einer LIMIT-Klausel)
    */
   public function countFoundItems() {
      return $this->getWorker()->countFoundItems();
   }


   /**
    * single object getter
    */
   final public function fetchOne($sql) {
      return $this->getWorker()->fetchOne($sql);
   }


   /**
    * object list getter
    */
   final public function fetchAll($sql, $count = false) {
      return $this->getWorker()->fetchAll($sql, $count);
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
    * @return Connector
    */
   final public function getConnector() {
      return $this->getWorker()->getConnector();
   }


   /**
    * Alias for self::getConnector()
    *
    * Gibt den für die persistente Klasse dieses DAO zuständigen DB-Adapter zurück.
    *
    * @return Connector
    */
   final public function getDb() {
      return $this->getConnector();
   }


   /**
    * Gibt den Worker dieses DAO zurück. Ein Worker implementiert eine konkrete Caching-Strategie und
    * kann Entity-spezifisch konfiguriert werden.
    *
    * @return Worker
    */
   private function getWorker() {
      if (!$this->worker) {
         $this->worker = new Worker($this);
      }
      return $this->worker;
   }


   /**
    * Gibt die aktuelle Version des übergebenen Objects zurück.
    *
    * @param  PersistableObject $object - PersistableObject-Instanz
    *
    * @return PersistableObject
    */
   public final function refresh(PersistableObject $object) {
      $class = $this->getEntityClass();
      if (!$object instanceof $class) throw new InvalidArgumentException('Cannot refresh instances of '.get_class($object));
      if (!$object->isPersistent())   throw new InvalidArgumentException('Cannot refresh non-persistent '.get_class($object));

      $mapping   = $this->getMapping();
      $tablename = $mapping['table'];
      $id        = $object->getId();

      $sql = "select *
                 from `$tablename`
                 where id = $id";
      $instance = $this->fetchOne($sql);

      if (!$instance) throw new ConcurrentModificationException('Error refreshing '.get_class($object).' ('.$id.'), data row not found');

      return $instance;
   }
}
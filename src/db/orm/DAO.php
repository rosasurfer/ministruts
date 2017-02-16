<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\ResultInterface    as IResult;

use rosasurfer\exception\ConcurrentModificationException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * DAO
 *
 * Abstract DAO base class.
 */
abstract class DAO extends Singleton {


   // Mapping-Constanten
   const T_BOOL     = 1;
   const T_INT      = 2;
   const T_FLOAT    = 3;
   const T_STRING   = 4;
   const T_SET      = 5;
   const T_NULL     = true;
   const T_NOT_NULL = false;


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
    * Find a single matching record and convert it to an object of the model class.
    *
    * @param  string $query - SQL query
    *
    * @return PersistableObject
    */
   public function findOne($query) {
      return $this->getWorker()->findOne($query);
   }


   /**
    * Find all matching records and convert them to objects of the model class.
    *
    * @param  string $query - SQL query
    *
    * @return PersistableObject[]
    */
   public function findAll($query) {
      return $this->getWorker()->findAll($query);
   }


   /**
    * Execute a SQL statement and return the result. This method should be used if the SQL statement returns rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return IResult
    */
   public function query($sql) {
      return $this->getWorker()->query($sql);
   }


   /**
    * Gibt das Mapping der Entity-Klasse des DAO zurück.
    *
    * @return array
    */
   public final function getMapping() {
      return $this->mapping;
   }


   /**
    * Gibt den Namen der Entity-Klasse dieses DAO's zurück.
    *
    * @return string - Klassenname
    */
   public final function getEntityClass() {
      return $this->entityClass;
   }


   /**
    * Return the database adapter for the DAO's entity class.
    *
    * @return IConnector
    */
   public final function db() {
      return $this->getWorker()->getConnector();
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
   public function refresh(PersistableObject $object) {
      $class = $this->getEntityClass();
      if (!$object instanceof $class) throw new InvalidArgumentException('Cannot refresh instances of '.get_class($object));
      if (!$object->isPersistent())   throw new InvalidArgumentException('Cannot refresh non-persistent '.get_class($object));

      $mapping   = $this->getMapping();
      $tablename = $mapping['table'];
      $id        = $object->getId();

      $sql = "select *
                 from $tablename
                 where id = $id";
      $instance = $this->findOne($sql);

      if (!$instance) throw new ConcurrentModificationException('Error refreshing '.get_class($object).' ('.$id.'), data row not found');

      return $instance;
   }
}

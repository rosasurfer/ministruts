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


   /** @var Worker - the worker this DAO uses */
   protected $worker;

   /** @var string - the name of the DAO's entity class */
   protected $entityClass;


   /**
    * Constructor
    *
    * Create a new DAO.
    */
   protected function __construct() {
      $this->entityClass = subStr(get_class($this), 0, -3);
   }


   /**
    * Find a single matching record and convert it to an object of the model class.
    *
    * @param  string $query     - SQL query
    * @param  bool   $allowMany - whether or not the query is allowed to return a multi-row result (default: no)
    *
    * @return PersistableObject
    *
    * @throws MultipleRowsException if the query returned multiple rows and $allowMany was not set to TRUE.
    */
   public function findOne($query, $allowMany=false) {
      return $this->getWorker()->findOne($query, $allowMany);
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
    * Return the mapping of the DAO's entity class.
    *
    * @return array
    */
   public final function getMapping() {
      return $this->mapping;
   }


   /**
    * Return the name of the DAO's entity class.
    *
    * @return string
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
    * Return the Worker the DAO uses.
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
    * Reload and return a fresh version of the specified object.
    *
    * @param  PersistableObject $object
    *
    * @return PersistableObject - refreshed version (a new and different instance)
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

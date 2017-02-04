<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;

use rosasurfer\db\ConnectionPool;
use rosasurfer\db\Result;


/**
 * Worker
 *
 * A Worker converts database records to PHP objects. For every model class exists exactly one Worker instance.
 * Only one PHP object is created for database records returned multiple times (e.g. by multiple queries).
 */
class Worker extends Object {


   /** @var Dao - Dao of the Worker's model class */
   private   $dao;

   /** @var string - class name of the Worker's model */
   protected $entityClass;

   /** @var Connector - database adapter of the Worker's model */
   private   $connector;


   /**
    * Constructor
    *
    * Create a new Worker for the specified Dao.
    *
    * @param  Dao $dao
    */
   public function __construct(Dao $dao) {
      $this->dao = $dao;
      $this->entityClass = $dao->getEntityClass();
   }


   /**
    * Find a single record and convert it to an object of the model class.
    *
    * @param  string $query - SQL query
    *
    * @return PersistableObject
    */
   public function findOne($query) {
      $result = $this->executeSql($query);
      return $this->makeObject($result);
   }


   /**
    * Find multiple records and convert them to objects of the model class.
    *
    * @param  string $query - SQL query
    *
    * @return PersistableObject[]
    */
   public function findMany($query) {
      $result = $this->executeSql($query);
      return $this->makeObjects($result);
   }


   /**
    * Execute a SQL statement and return the result.
    *
    * @param  string $sql - SQL statement
    *
    * @return Result - Depending on the statement type the result may or may not contain a result set.
    */
   public function executeSql($sql) {
      return $this->getConnector()->executeSql($sql);
   }


   /**
    * Convert the next row of a Result to an object of the model class.
    *
    * @param  Result $result
    *
    * @return PersistableObject - instance or NULL if the Result doesn't hold any more rows
    */
   protected function makeObject(Result $result) {

      // TODO: Lookup and return an existing instance instead of a copy.

      $row = $result->fetchNext();
      if ($row)
         return PersistableObject::createInstance($this->entityClass, $row);
      return null;
   }


   /**
    * Convert all remaining rows of a Result to objects of the model class.
    *
    * @param  Result $result
    *
    * @return PersistableObject[] - arry of instances or an empty array if the Result doesn't hold any more rows
    */
   protected function makeObjects(Result $result) {

      // TODO: Lookup and return existing instances instead of copies.

      $instances = array();
      while ($row = $result->fetchNext()) {
         $instances[] = PersistableObject::createInstance($this->entityClass, $row);
      }
      return $instances;
   }


   /**
    * Return the database adapter of the Worker's model class.
    *
    * @return Connector
    */
   public function getConnector() {
      if (!$this->connector) {
         $mapping = $this->dao->getMapping();
         $this->connector = ConnectionPool::getConnector($mapping['connection']);
      }
      return $this->connector;
   }
}

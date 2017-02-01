<?php
namespace rosasurfer\db;

use rosasurfer\core\Object;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\InvalidArgumentException;


/**
 * Connector
 *
 * Abstract super class for concrete storage mechanism adapters.
 */
abstract class Connector extends Object {


   /**
    * Default constructor.
    *
    * To create an instance use self::create().
    */
   protected function __construct() {
   }


   /**
    * Destructor
    *
    * Make sure that on destruction of the instance a pending transaction is rolled back and the connection is closed.
    */
   public function __destruct() {
      try {
         if ($this->isConnected()) {
            if ($this->isInTransaction())
               $this->rollback();
            $this->disconnect();
         }
      }
      catch (\Exception $ex) {
         // Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
         // @see  http://php.net/manual/en/language.oop5.decon.php
         ErrorHandler::handleDestructorException($ex);
         throw $ex;
      }
   }


   /**
    * Create a new Connector and initialize it with connection specific configuration values.
    *
    * @param  string   $class   - Connector class name
    * @param  string[] $config  - connection configuration
    * @param  string[] $options - additional connection options (default: none)
    *
    * @return self
    */
   public static function create($class, array $config, array $options=[]) {
      if (!is_subclass_of($class, __CLASS__)) throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
      return new $class($config, $options);
   }


   /**
    * Connect the adapter with the database.
    */
   abstract protected function connect();


   /**
    * Disconnect the adapter from the database.
    */
   abstract protected function disconnect();


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   abstract protected function isConnected();


   /**
    * Execute a SQL statement and return the result.
    *
    * @param  string $sql - SQL statement
    *
    * @return array['set' ] - a result set (for SELECT statements only)
    *              ['rows'] - number of affected or modified rows (for SELECT/INSERT/UPDATE statements only)
    */
   abstract public function executeSql($sql);


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  string $sql - SQL statement
    *
    * @return mixed
    */
   abstract public function executeRaw($sql);


   /**
    * Start a new transaction.
    *
    * @return self
    */
   abstract public function begin();


   /**
    * Commit a pending transaction.
    *
    * @return self
    */
   abstract public function commit();


   /**
    * Roll back a pending transaction.
    *
    * @return self
    */
   abstract public function rollback();


   /**
    * Whether or not the connection currently is in a pending transaction.
    *
    * @return bool
    */
   abstract public function isInTransaction();
}

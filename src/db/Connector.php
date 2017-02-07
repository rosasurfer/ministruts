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
    * Connect the adapter to the database.
    *
    * @return self
    */
   abstract protected function connect();


   /**
    * Disconnect the adapter from the database.
    *
    * @return self
    */
   abstract protected function disconnect();


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   abstract protected function isConnected();


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return Result
    *
    * @throws DatabaseException in case of failure
    */
   abstract public function query($sql);


   /**
    * Execute a SQL statement and skip result set processing. This method should be used for SQL statements not returning
    * rows. If the database driver does not support this functionality the statement is forwarded to Connector::query().
    *
    * @param  string $sql - SQL statement
    *
    * @return int - Number of rows affected by the statement. This value may be unreliable.
    *               (see the specific connector implementation)
    *
    * @throws DatabaseException in case of failure
    */
   abstract public function execute($sql);


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - A variable receiving the number of affected rows. This value may be unreliable.
    *                                      (see the specific connector implementation)
    * @return mixed - raw driver response
    *
    * @throws DatabaseException in case of failure
    */
   abstract public function executeRaw($sql, &$affectedRows=0);


   /**
    * Start a new transaction.
    *
    * @return self
    */
   abstract public function begin();


   /**
    * Commit an active transaction.
    *
    * @return self
    */
   abstract public function commit();


   /**
    * Roll back an active transaction.
    *
    * @return self
    */
   abstract public function rollback();


   /**
    * Whether or not the connection currently is in a transaction.
    *
    * @return bool
    */
   abstract public function isInTransaction();


   /**
    * Return the type of the database system the connector is used for.
    *
    * @return string
    */
   public function getType() {
      return $this->type;
   }


   /**
    * Return the connector's internal connection object.
    *
    * @return resource|object - resource or connection handler instance
    */
   abstract public function getInternalHandler();
}

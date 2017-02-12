<?php
namespace rosasurfer\db;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\ResultInterface    as IResult;


/**
 * Interface for storage mechanism adapters.
 */
interface ConnectorInterface {


   /**
    * Connect the adapter to the database.
    *
    * @return self
    */
   public function connect();


   /**
    * Disconnect the adapter from the database.
    *
    * @return self
    */
   public function disconnect();


   /**
    * Whether or not the adapter currently is connected to the database.
    *
    * @return bool
    */
   public function isConnected();


   /**
    * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
    *
    * @param  string $sql - SQL statement
    *
    * @return IResult
    *
    * @throws DatabaseException in case of failure
    */
   public function query($sql);


   /**
    * Execute a SQL statement and skip result set processing. This method should be used for SQL statements not returning
    * rows. If the database driver does not support this functionality the statement is forwarded to IConnector::query().
    *
    * @param  string $sql - SQL statement
    *
    * @return int - Number of rows affected if the statement was an INSERT/UPDATE/DELETE statement
    *
    * @throws DatabaseException in case of failure
    */
   public function execute($sql);


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - A variable receiving the number of affected rows.
    *
    * @return mixed - raw driver response
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql, &$affectedRows=0);


   /**
    * Start a new transaction.
    *
    * @return self
    */
   public function begin();


   /**
    * Commit an active transaction.
    *
    * @return self
    */
   public function commit();


   /**
    * Roll back an active transaction.
    *
    * @return self
    */
   public function rollback();


   /**
    * Whether or not the connection currently is in a transaction.
    *
    * @return bool
    */
   public function isInTransaction();


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (connector specific, see the db README).
    *
    * @return int - generated ID or 0 (zero) if no ID was generated;
    *               -1 if the DBMS doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId();


   /**
    * Return the type of the database system the connector is used for.
    *
    * @return string
    */
   public function getType();


   /**
    * Return the connector's internal connection object.
    *
    * @return resource|object - connection handle or handler instance
    */
   public function getInternalHandler();
}

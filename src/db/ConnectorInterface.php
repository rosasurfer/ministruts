<?php
namespace rosasurfer\db;


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
    * Execute a SQL statement and return the result.
    *
    * @param  string $sql - SQL statement
    *
    * @return ResultInterface
    *
    * @throws DatabaseException in case of failure
    */
   public function query($sql);


   /**
    * Execute a SQL statement and skip result set processing.
    *
    * @param  string $sql - SQL statement
    *
    * @return int - Number of rows affected by the statement.
    *
    * @throws DatabaseException in case of failure
    */
   public function execute($sql);


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  _IN_  string $sql          - SQL statement
    * @param  _OUT_ int   &$affectedRows - a variable receiving the number of affected rows
    *
    * @return mixed - raw driver response
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql, &$affectedRows=0);


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (connector specific, see the README).
    *
    * @return int - generated ID or 0 (zero) if no ID was generated;
    *               -1 if the dbms doesn't support this functionality
    */
   public function lastInsertId();


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
    * Return the type of the database system the connector is used for.
    *
    * @return string
    */
   public function getType();


   /**
    * Return the connector's internal connection object.
    *
    * @return resource|object - resource or connection handler instance
    */
   public function getInternalHandler();
}

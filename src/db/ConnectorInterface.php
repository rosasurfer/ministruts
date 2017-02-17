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
    * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
    * can be used in queries "as-is" and doesn't need additional quoting.
    *
    * @param  string $name - identifier to escape
    *
    * @return string - escaped and quoted identifier
    */
   public function escapeIdentifier($name);


   /**
    * Escape a DBMS string literal, i.e. a string value. The resulting string can be used in queries "as-is" and doesn't
    * need additional quoting.
    *
    * @param  string $value - value to escape
    *
    * @return string - escaped and quoted string value
    */
   public function escapeLiteral($value);


   /**
    * Escape a string value. The resulting string must be quoted according to the DBMS before it can be used in queries.
    *
    * @param  string $value - value to escape
    *
    * @return string - escaped but not quoted string value
    */
   public function escapeString($value);


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
    * Execute a SQL statement and skip potential result set processing. This method should be used for SQL statements not
    * returning rows. If the database driver does not support it the statement is forwarded to IConnector::query().
    *
    * @param  string $sql - SQL statement
    *
    * @return self
    *
    * @throws DatabaseException in case of failure
    */
   public function execute($sql);


   /**
    * Execute a SQL statement and return the internal driver's raw response.
    *
    * @param  string $sql - SQL statement
    *
    * @return mixed - raw driver response
    *
    * @throws DatabaseException in case of failure
    */
   public function executeRaw($sql);


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
    * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session;
    *               -1 if the DBMS doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId();


   /**
    * Return the number of rows affected by the last row modifying statement (connector specific, see the db README).
    *
    * @return int - last number of affected rows or 0 (zero) if no rows were modified yet in the current session;
    *               -1 if the DBMS doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastAffectedRows();


   /**
    * Return the connector's internal connection object.
    *
    * @return resource|object - connection handle or handler instance
    */
   public function getInternalHandler();


   /**
    * Return the type of the DBMS the connector is used for.
    *
    * @return string
    */
   public function getType();


   /**
    * Return the version of the DBMS the connector is used for as a string.
    *
    * @return string
    */
   public function getVersionString();


   /**
    * Return the version ID of the DBMS the connector is used for as an integer.
    *
    * @return int
    */
   public function getVersionNumber();
}

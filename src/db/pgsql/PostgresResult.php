<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class PostgresResult extends Result {


   /** @var IConnector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var resource - the underlying driver's original result resource */
   protected $result;

   /** @var int - number of rows modified by the statement */
   protected $affectedRows;

   /** @var int - number of rows in the result set (if any) */
   protected $numRows;


   /**
    * Constructor
    *
    * Create a new PostgresResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector $connector    - Connector managing the database connection
    * @param  string     $sql          - executed SQL statement
    * @param  resource   $result       - A result resource or NULL for result-less SQL statements. SELECT queries not
    *                                    matching any rows produce an empty result resource.
    * @param  int        $affectedRows - number of rows modified by the statement
    */
   public function __construct(IConnector $connector, $sql, $result, $affectedRows) {
      if (!is_string($sql))                           throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_resource($result) && !is_null($result)) throw new IllegalTypeException('Illegal type of parameter $result: '.getType($result));

      $this->connector    = $connector;
      $this->sql          = $sql;
      $this->result       = $result;
      $this->affectedRows = $affectedRows;
   }


   /**
    * Destructor
    *
    * Release an internal result set.
    */
   public function __destruct() {
      try {
         if ($this->result) {
            pg_free_result($this->result);
            $this->result = null;
         }
      }
      catch (\Exception $ex) {
         throw ErrorHandler::handleDestructorException($ex);
      }
   }


   /**
    * Fetch the next row from the result set.
    *
    * @param  int $mode - Controls how the returned array is indexed. Can take one of the following values:
    *                     ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
    *
    * @return array - array of columns or NULL if no more rows are available
    */
   public function fetchNext($mode=ARRAY_BOTH) {
      if (!$this->result)
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = PGSQL_ASSOC; break;
         case ARRAY_NUM:   $mode = PGSQL_NUM;   break;
         default:          $mode = PGSQL_BOTH;
      }
      return pg_fetch_array($this->result, null, $mode) ?: null;
   }


   /**
    * Return the number of rows affected if the SQL was an INSERT/UPDATE/DELETE statement. Unreliable for specific UPDATE
    * statements (matched but unmodified rows are reported as changed) and for multiple statement queries.
    *
    * @return int
    */
   public function affectedRows() {
      return (int) $this->affectedRows;
   }


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   public function numRows() {
      if ($this->numRows === null) {
         if ($this->result) $this->numRows = pg_num_rows($this->result);
         else               $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (usually an INSERT).
    *
    * This function returnes the most recently generated ID. It's value is not reset between queries.
    *
    * @return int - generated ID or 0 (zero) if no previous statement generated yet an ID;
    *               -1 if the PostgreSQL server version doesn't support this functionality
    */
   public function lastInsertId() {
      return $this->connector->lastInsertId();
   }


   /**
    * Get the underlying driver's original result object.
    *
    * @return resource
    */
   public function getInternalResult() {
      return $this->result;
   }
}

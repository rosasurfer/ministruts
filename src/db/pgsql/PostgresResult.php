<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * returned rows.
 */
class PostgresResult extends Result {


   // For documentation: status codes returned by pg_result_status(PGSQL_STATUS_LONG)

   /** @var int - The string sent to the server was empty. */
   const STATUS_EMPTY_QUERY    = \PGSQL_EMPTY_QUERY;

   /** @var int - Successful completion of a command returning no rows. */
   const STATUS_COMMAND_OK     = \PGSQL_COMMAND_OK;

   /** @var int - Successful completion of a command returning rows. */
   const STATUS_TUPLES_OK      = \PGSQL_TUPLES_OK;

   /** @var int - Copy Out (from server) data transfer started. */
   const STATUS_COPY_OUT       = \PGSQL_COPY_OUT;

   /** @var int - Copy In (to server) data transfer started. */
   const STATUS_COPY_IN        = \PGSQL_COPY_IN;

   /** @var int - The server's response was not understood. */
   const STATUS_BAD_RESPONSE   = \PGSQL_BAD_RESPONSE;

   /** @var int - A nonfatal error (a notice or warning) occurred. */
   const STATUS_NONFATAL_ERROR = \PGSQL_NONFATAL_ERROR;

   /** @var int - A fatal error occurred. */
   const STATUS_FATAL_ERROR    = \PGSQL_FATAL_ERROR;


   /** @var IConnector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var resource - the underlying driver's original result resource */
   protected $hResult;

   /** @var int - last number of affected rows (not reset between queries) */
   protected $lastAffectedRows = 0;

   /** @var int - number of rows in the result set (if any) */
   protected $numRows;


   /**
    * Constructor
    *
    * Create a new PostgresResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector $connector        - Connector managing the database connection
    * @param  string     $sql              - executed SQL statement
    * @param  resource   $hResult          - result handle
    * @param  int        $lastAffectedRows - last number of affected rows of the connection
    */
   public function __construct(IConnector $connector, $sql, $hResult, $lastAffectedRows) {
      if (!is_string($sql))           throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_resource($hResult))     throw new IllegalTypeException('Illegal type of parameter $hResult: '.getType($hResult));
      if (!is_int($lastAffectedRows)) throw new IllegalTypeException('Illegal type of parameter $lastAffectedRows: '.getType($lastAffectedRows));

      $this->connector        = $connector;
      $this->sql              = $sql;
      $this->hResult          = $hResult;
      $this->lastAffectedRows = $lastAffectedRows;
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
      if (!$this->hResult)
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = PGSQL_ASSOC; break;
         case ARRAY_NUM:   $mode = PGSQL_NUM;   break;
         default:          $mode = PGSQL_BOTH;
      }
      return pg_fetch_array($this->hResult, null, $mode) ?: null;
   }


   /**
    * Return the last ID generated for a SERIAL column by a SQL statement. The value is not reset between queries
    * (see the db README).
    *
    * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session;
    *               -1 if the PostgreSQL server version doesn't support this functionality
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId() {
      return $this->connector->lastInsertId();
   }


   /**
    * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement up to creation time of this instance.
    * For UPDATE and DELETE statements this is the number of matched rows. The value is not reset between queries
    * (see the db README).
    *
    * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastAffectedRows() {
      return (int) $this->lastAffectedRows;
   }


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   public function numRows() {
      if ($this->numRows === null) {
         if ($this->hResult) $this->numRows = pg_num_rows($this->hResult);
         else                $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    * Get the underlying driver's original result object.
    *
    * @return resource
    */
   public function getInternalResult() {
      return $this->hResult;
   }


   /**
    * Release the internal resources held by the Result.
    */
   public function release() {
      if ($this->hResult) {
         $tmp = $this->hResult;
         $this->hResult = null;
         pg_free_result($tmp);
      }
   }


   /**
    * Return a readable version of a result status code.
    *
    * @param  int $status - status code as returned by pg_result_status(PGSQL_STATUS_LONG)
    *
    * @return string
    */
   public static function statusToStr($status) {
      if (!is_int($status)) throw new IllegalTypeException('Illegal type of parameter $status: '.getType($status));

      switch ($status) {
         case PGSQL_EMPTY_QUERY   : return 'PGSQL_EMPTY_QUERY';
         case PGSQL_COMMAND_OK    : return 'PGSQL_COMMAND_OK';
         case PGSQL_TUPLES_OK     : return 'PGSQL_TUPLES_OK';
         case PGSQL_COPY_OUT      : return 'PGSQL_COPY_OUT';
         case PGSQL_COPY_IN       : return 'PGSQL_COPY_IN';
         case PGSQL_BAD_RESPONSE  : return 'PGSQL_BAD_RESPONSE';
         case PGSQL_NONFATAL_ERROR: return 'PGSQL_NONFATAL_ERROR';
         case PGSQL_FATAL_ERROR   : return 'PGSQL_FATAL_ERROR';
      }
      return (string) $status;
   }
}

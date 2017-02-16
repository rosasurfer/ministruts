<?php
namespace rosasurfer\db\mysql;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\NoMoreRowsException;
use rosasurfer\db\Result;

use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * returned rows.
 */
class MySQLResult extends Result {


   /** @var IConnector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var resource - the database connector's original result handle */
   protected $hResult;

   /** @var int - last inserted row id of the connection at instance creation time (not reset between queries) */
   protected $lastInsertId = 0;

   /** @var int - last number of affected rows (not reset between queries) */
   protected $lastAffectedRows = 0;

   /** @var int - number of rows returned by the statement (NULL to distinguish between an unset and a zero value) */
   protected $numRows = null;

   /** @var int - index of the row fetched by the next unqualified fetch* method call or -1 when hit the end */
   protected $nextRowIndex = 0;


   /**
    * Constructor
    *
    * Create a new MySQLResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector $connector        - connector managing the database connection
    * @param  string     $sql              - executed SQL statement
    * @param  resource   $hResult          - result handle or NULL for result-less SQL query; SELECT queries not matching
    *                                        any rows produce an empty result resource
    * @param  int        $lastInsertId     - last inserted ID of the connection
    * @param  int        $lastAffectedRows - last number of affected rows of the connection
    */
   public function __construct(IConnector $connector, $sql, $hResult, $lastInsertId, $lastAffectedRows) {
      if (!is_string($sql))                             throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_resource($hResult) && !is_null($hResult)) throw new IllegalTypeException('Illegal type of parameter $hResult: '.getType($hResult));
      if (!is_int($lastInsertId))                       throw new IllegalTypeException('Illegal type of parameter $lastInsertId: '.getType($lastInsertId));
      if (!is_int($lastAffectedRows))                   throw new IllegalTypeException('Illegal type of parameter $lastAffectedRows: '.getType($lastAffectedRows));

      $this->connector        = $connector;
      $this->sql              = $sql;
      $this->hResult          = $hResult;
      $this->lastInsertId     = $lastInsertId;
      $this->lastAffectedRows = $lastAffectedRows;

      if ($hResult && !mysql_num_fields($hResult)) {     // close empty results and release them
         mysql_free_result($hResult);
         $hResult            = null;
         $this->numRows      = 0;
         $this->nextRowIndex = -1;
      }
      else {
         $this->nextRowIndex = 0;
      }
      $this->hResult = $hResult;
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
      if (!$this->hResult || $this->nextRowIndex < 0)
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = MYSQL_ASSOC; break;
         case ARRAY_NUM:   $mode = MYSQL_NUM;   break;
         default:          $mode = MYSQL_BOTH;
      }

      $row = mysql_fetch_array($this->hResult, $mode);
      if ($row) {
         $this->nextRowIndex++;
      }
      else {
         $row                = null;
         $this->nextRowIndex = -1;
      }
      return $row;
   }


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement up to creation time of this instance.
    * The value is not reset between queries (see the db README).
    *
    * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastInsertId() {
      return (int) $this->lastInsertId;
   }


   /**
    * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement up to creation time of this instance.
    * Since MySQL 5.5.5 this value also includes rows affected by ALTER TABLE and LOAD DATA INFILE statements. The value is
    * not reset between queries (see the db README).
    *
    * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
    *
    * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
    */
   public function lastAffectedRows() {
      return (int) $this->lastAffectedRows;
   }


   /**
    * Return the number of rows returned by the query.
    *
    * @return int
    */
   public function numRows() {
      if ($this->numRows === null) {
         if ($this->hResult) $this->numRows = mysql_num_rows($this->hResult);
         else                $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    *
    *
   public function countFoundItems() {
      $result = $this->query('select found_rows()');
      return $this->foundItemsCounter = $result->fetchInt();
   }
   */


   /**
    * Fetch a single field of a row from the result set.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNoMoreRows - alternative value to return if no more rows are available
    *
    * @return mixed - content of a single cell (can be NULL)
    *
    * @throws NoMoreRowsException if no more rows are available and parameter $onNoMoreRows was not set
    *
   public function fetchField($column=null, $row=null, $onNoMoreRows=null) {
      // TODO: mysql_result() compares column names in a case-insensitive way (no manual fiddling needed)
      return mysql_result($this->resultSet, $row=0, $column);
   }
   */


   /**
    * Release the internal resources held by the Result.
    */
   public function release() {
      if ($this->hResult) {
         $tmp = $this->hResult;
         $this->hResult      = null;
         $this->nextRowIndex = -1;
         mysql_free_result($tmp);
      }
   }


   /**
    * Return the result's internal result object.
    *
    * @return resource - result handle or NULL for a result-less query
    */
   public function getInternalResult() {
      return $this->hResult;
   }
}

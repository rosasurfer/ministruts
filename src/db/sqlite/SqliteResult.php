<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use rosasurfer\exception\IllegalArgumentException;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\UnimplementedFeatureException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SqliteResult extends Result {


   /** @var IConnector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var \SQLite3Result - the underlying driver's original result object */
   protected $result;

   /** @var int - number of rows modified by the statement */
   protected $affectedRows;

   /** @var int - number of rows in the result set (if any) */
   protected $numRows;


   /**
    * Constructor
    *
    * Create a new SqliteResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector     $connector    - Connector managing the database connection
    * @param  string         $sql          - executed SQL statement
    * @param  \SQLite3Result $result       - A SQLite3Result or NULL for result-less SQL statements. SELECT queries not
    *                                        matching any rows and DELETE statements produce an empty SQLite3Result.
    * @param  int            $affectedRows - number of rows modified by the statement
    */
   public function __construct(IConnector $connector, $sql, \SQLite3Result $result=null, $affectedRows=0) {
      if (func_num_args() < 4) throw new IllegalArgumentException('Illegal number of arguments: '.func_num_args());
      if (!is_string($sql))    throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      $this->connector    = $connector;
      $this->sql          = $sql;
      $this->result       = $result;
      $this->affectedRows = $affectedRows;
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
         case ARRAY_ASSOC: $mode = SQLITE3_ASSOC; break;
         case ARRAY_NUM:   $mode = SQLITE3_NUM;   break;
         default:          $mode = SQLITE3_BOTH;
      }
      return $this->result->fetchArray($mode) ?: null;
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
      throw new UnimplementedFeatureException();

      if ($this->numRows === null) {
         if ($this->result) $this->numRows = mysql_num_rows($this->result);
         else               $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (usually an INSERT).
    *
    * This function returnes the most recently generated ID. It's value is not reset between queries.
    *
    * @return int - generated ID or 0 (zero) if no previous statement yet generated an ID
    */
   public function lastInsertId() {
      return $this->connector->lastInsertId();
   }


   /**
    * Get the underlying driver's original result object.
    *
    * @return \SQLite3Result
    */
   public function getInternalResult() {
      return $this->result;
   }
}

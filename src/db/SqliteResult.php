<?php
namespace rosasurfer\db;

use \SQLite3Result;

use rosasurfer\exception\IllegalArgumentException;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;
use rosasurfer\exception\UnimplementedFeatureException;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SqliteResult extends Result {


   /** @var Connector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var SQLite3Result - the underlying driver's original result object */
   protected $resultSet;

   /** @var int */
   protected $numRows;


   /**
    * Constructor
    *
    * Create a new SqliteResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  SqliteConnector $connector - Connector managing the database connection
    * @param  string          $sql       - executed SQL statement
    * @param  SQLite3Result   $result    - A SQLite3Result or NULL for result-less SQL statements. SELECT queries not
    *                                      matching any rows produce an empty SQLite3Result.
    */
   public function __construct(SqliteConnector $connector, $sql, SQLite3Result $result=null) {
      if (func_num_args() < 3) throw new IllegalArgumentException('Illegal number of arguments: '.func_num_args());
      if (!is_string($sql))    throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));

      $this->connector = $connector;
      $this->sql       = $sql;
      $this->resultSet = $result;
   }


   /**
    * Fetch the next row from the result set.
    *
    * @param  int $mode - Controls how the returned array is indexed. Can take one of the following values:
    *                     ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
    *
    * @return array - array of columns or NULL if no (more) rows are available
    */
   public function fetchNext($mode=ARRAY_BOTH) {
      if (!$this->resultSet)
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = SQLITE3_ASSOC; break;
         case ARRAY_NUM:   $mode = SQLITE3_NUM;   break;
         default:          $mode = SQLITE3_BOTH;
      }
      return $this->resultSet->fetchArray($mode) ?: null;
   }


   /**
    * Return the number of rows in the result set.
    *
    * @return int
    */
   public function numRows() {
      throw new UnimplementedFeatureException();

      if ($this->numRows === null) {
         if ($this->resultSet) $this->numRows = mysql_num_rows($this->resultSet);
         else                  $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    * Get the underlying driver's original result object.
    *
    * @return SQLite3Result
    */
   public function getInternalResult() {
      return $this->resultSet;
   }
}

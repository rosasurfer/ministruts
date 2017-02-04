<?php
namespace rosasurfer\db;

use \SQLite3Result;

use rosasurfer\exception\IllegalArgumentException;
use rosasurfer\exception\IllegalTypeException;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SqliteResult extends Result {


   /** @var Connector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var SQLite3Result */
   protected $set;


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
      $this->set       = $result;
   }


   /**
    * Fetch the next row from the result set (if any).
    *
    * @return mixed[] - associative array of columns or NULL if no more rows are available
    */
   public function fetchNext() {
      if (!$this->set)
         return null;
      return $this->set->fetchArray(SQLITE3_ASSOC) ?: null;
   }
}

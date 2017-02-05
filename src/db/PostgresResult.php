<?php
namespace rosasurfer\db;

use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class PostgresResult extends Result {


   /** @var Connector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var resource - the underlying driver's original result resource */
   protected $resultSet;


   /**
    * Constructor
    *
    * Create a new PostgresResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  PostgresConnector $connector - Connector managing the database connection
    * @param  string            $sql       - executed SQL statement
    * @param  resource          $result    - A result resource or NULL for result-less SQL statements. SELECT queries not
    *                                        matching any rows produce an empty result resource.
    */
   public function __construct(PostgresConnector $connector, $sql, $result) {
      if (!is_string($sql))                        throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_resource($result) && $result!==null) throw new IllegalTypeException('Illegal type of parameter $result: '.getType($result));

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
         case ARRAY_ASSOC: $mode = PGSQL_ASSOC; break;
         case ARRAY_NUM:   $mode = PGSQL_NUM;   break;
         default:          $mode = PGSQL_BOTH;
      }
      return pg_fetch_array($this->resultSet, null, $mode) ?: null;
   }


   /**
    * Get the underlying driver's original result object.
    *
    * @return resource
    */
   public function getInternalResult() {
      return $this->resultSet;
   }
}

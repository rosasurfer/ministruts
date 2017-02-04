<?php
namespace rosasurfer\db;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class PostgresResult extends Result {


   /** @var Connector - used database connector */
   protected $connector;

   /** @var string - SQL statement the result was generated from */
   protected $sql;

   /** @var resource - generated result resource */
   protected $set;


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
      return pg_fetch_array($this->set, null, PGSQL_ASSOC) ?: null;
   }
}

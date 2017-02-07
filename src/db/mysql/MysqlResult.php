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
class MysqlResult extends Result {


   /** @var Connector - used database connector */
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
    * Create a new MysqlResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  MysqlConnector $connector    - Connector managing the database connection
    * @param  string         $sql          - executed SQL statement
    * @param  resource       $result       - A result resource or NULL for result-less SQL statements. SELECT queries not
    *                                        matching any rows produce an empty result resource.
    * @param  int            $affectedRows - number of rows modified by the statement
    */
   public function __construct(MysqlConnector $connector, $sql, $result, $affectedRows) {
      if (!is_string($sql))                        throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_resource($result) && $result!==null) throw new IllegalTypeException('Illegal type of parameter $result: '.getType($result));

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
    * @return array - array of columns or NULL if no (more) rows are available
    */
   public function fetchNext($mode=ARRAY_BOTH) {
      if (!$this->result)
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = MYSQL_ASSOC; break;
         case ARRAY_NUM:   $mode = MYSQL_NUM;   break;
         default:          $mode = MYSQL_BOTH;
      }
      return mysql_fetch_array($this->result, $mode) ?: null;
   }


   /**
    * Return the number of rows affected if the SQL was an INSERT/UPDATE/DELETE/REPLACE statement. Unreliable for specific
    * UPDATE statements. Matched but unmodified rows are reported as changed if the connection flag CLIENT_FOUND_ROWS is set.
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
         if ($this->result) $this->numRows = mysql_num_rows($this->result);
         else               $this->numRows = 0;
      }
      return $this->numRows;
   }


   /**
    * Fetch a single field of a row from the result set.
    *
    * @param  string|int $column       - name or offset of the column to fetch from (default: 0)
    * @param  int        $row          - row to fetch from, starting at 0 (default: the next row)
    * @param  mixed      $onNoMoreRows - alternative value to return if no (more) rows are available
    *
    * @return mixed - content of a single cell (can be NULL)
    *
    * @throws NoMoreRowsException if no (more) rows are available and parameter $alt was not specified
    *
   public function fetchField($column=null, $row=null, $onNoMoreRows=null) {
      // TODO: mysql_result() compares column names in a case-insensitive way (no manual fiddling needed)
      return mysql_result($this->resultSet, $row=0, $column);
   }
   */


   /**
    * Get the underlying driver's original result object.
    *
    * @return resource
    */
   public function getInternalResult() {
      return $this->result;
   }
}

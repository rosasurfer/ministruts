<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\UnimplementedFeatureException;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SQLiteResult extends Result {


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

   /** @var int - the last inserted row id of the connection at instance creation time */
   protected $lastInsertId;


   /**
    * Constructor
    *
    * Create a new SQLiteResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector     $connector    - connector managing the database connection
    * @param  string         $sql          - executed SQL statement
    * @param  \SQLite3Result $result       - result-less queries produce an empty SQLite3Result
    * @param  int            $affectedRows - number of rows modified by the statement
    * @param  int            $lastInsertId - last inserted ID of the connection
    */
   public function __construct(IConnector $connector, $sql, \SQLite3Result $result, $affectedRows, $lastInsertId) {
      if (!is_string($sql))       throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_int($affectedRows)) throw new IllegalTypeException('Illegal type of parameter $affectedRows: '.getType($affectedRows));
      if (!is_int($lastInsertId)) throw new IllegalTypeException('Illegal type of parameter $lastInsertId: '.getType($lastInsertId));

      $this->connector    = $connector;
      $this->sql          = $sql;
      $this->affectedRows = $affectedRows;
      $this->lastInsertId = $lastInsertId;

      if (!$result->numColumns()) {       // close empty results and release them to prevent access
         $result->finalize();             // @see bug in SQLite3Result::fetchArray()
         $result = null;
      }
      $this->result = $result;
   }


   /**
    * Destructor
    *
    * Release an internal result set.
    */
   public function __destruct() {
      try {
         if ($this->result) {
            $this->result->finalize();
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
    * Return the Result's internal result object.
    *
    * @return \SQLite3Result - instance or NULL for result-less queries
    */
   public function getInternalResult() {
      return $this->result;
   }
}

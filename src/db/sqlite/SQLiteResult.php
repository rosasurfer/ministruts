<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use rosasurfer\exception\IllegalTypeException;

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

   /** @var int - last inserted row id of the connection at instance creation time (not reset between queries) */
   protected $lastInsertId = 0;

   /** @var int - last number of affected rows (not reset between queries) */
   protected $lastAffectedRows = 0;

   /** @var int - number of rows returned by the query */
   protected $numRows = null;          // NULL to distinguish between an unset and a zero value

   /** @var int - index of the next row to be fetched; NULL if we stepped over the edge */
   protected $nextRowPointer = 0;


   /**
    * Constructor
    *
    * Create a new SQLiteResult instance. Called only when execution of a SQL statement returned successful.
    *
    * @param  IConnector     $connector        - connector managing the database connection
    * @param  string         $sql              - executed SQL statement
    * @param  \SQLite3Result $result           - result-less queries produce an empty SQLite3Result
    * @param  int            $lastInsertId     - last inserted ID of the connection
    * @param  int            $lastAffectedRows - last number of affected rows of the connection
    */
   public function __construct(IConnector $connector, $sql, \SQLite3Result $result, $lastInsertId, $lastAffectedRows) {
      if (!is_string($sql))           throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
      if (!is_int($lastInsertId))     throw new IllegalTypeException('Illegal type of parameter $lastInsertId: '.getType($lastInsertId));
      if (!is_int($lastAffectedRows)) throw new IllegalTypeException('Illegal type of parameter $lastAffectedRows: '.getType($lastAffectedRows));

      $this->connector        = $connector;
      $this->sql              = $sql;
      $this->lastInsertId     = $lastInsertId;
      $this->lastAffectedRows = $lastAffectedRows;

      if (!$result->numColumns()) {       // close empty results and release them to prevent access
         $result->finalize();             // @see bug in SQLite3Result::fetchArray()
         $result               = null;
         $this->nextRowPointer = null;
         $this->numRows        = 0;
      }
      else {
         $this->nextRowPointer = 0;
      }
      $this->result = $result;
   }


   /**
    * Fetch the next row from the result set.
    *
    * @param  int $mode - Controls how the returned array is indexed. Can take one of the following values:
    *                     ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
    *
    * @return array - Array of columns or NULL if no more rows are available. The types of the values of the returned array
    *                 are mapped from SQLite3 types as follows:
    *                 - Integers are mapped to int if they fit into the range PHP_INT_MIN..PHP_INT_MAX, otherwise to string.
    *                 - Floats are mapped to float.
    *                 - NULL values are mapped to NULL.
    *                 - Strings and blobs are mapped to string.
    */
   public function fetchNext($mode=ARRAY_BOTH) {
      if (!$this->result || $this->nextRowPointer===null)   // no automatic result reset()
         return null;

      switch ($mode) {
         case ARRAY_ASSOC: $mode = SQLITE3_ASSOC; break;
         case ARRAY_NUM:   $mode = SQLITE3_NUM;   break;
         default:          $mode = SQLITE3_BOTH;
      }

      $row = $this->result->fetchArray($mode);
      if ($row) {
         $this->nextRowPointer++;
      }
      else {
         if ($this->numRows === null) {                     // update $numRows on-the-fly if not yet happened
            $this->numRows = $this->nextRowPointer;
         }
         $row                  = null;                      // prevent fetchArray() to trigger an automatic reset()
         $this->nextRowPointer = null;                      // on second $row == null
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
    * For UPDATE or DELETE statements this is the number of matched rows. The value is not reset between queries (see the db
    * README).
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
         // No support for num_rows() in SQLite3, we need to count rows manually.
         //
         // TODO: All we need to do is to use fetchNext() to step over the edge and go back.
         //       It updates $numRows automatically.

         $result  = $this->result;
         $pointer = $this->nextRowPointer;
         $rows    = 0;

         while ($result->fetchArray(SQLITE3_ASSOC)) { // step from the current position to the end
            ++$rows;
            ++$this->nextRowPointer;
         }
                                                      // !$nextRowPointer = no rows at all should never happen here
         $result->reset();
         $this->nextRowPointer = 0;                   // step back to start

         while ($pointer--) {                         // step back to the former "current" position
            $result->fetchArray(SQLITE3_ASSOC);
            ++$rows;
            ++$this->nextRowPointer;
         }
         $this->numRows = $rows;
      }
      return $this->numRows;
   }


   /**
    * Return the result's internal result object.
    *
    * @return \SQLite3Result - result handler or NULL for result-less queries
    */
   public function getInternalResult() {
      return $this->result;
   }


   /**
    * Release the internal resources held by the Result.
    */
   public function release() {
      if ($this->result) {
         $tmp = $this->result;
         $this->result         = null;
         $this->nextRowPointer = null;
         $tmp->finalize();
      }
   }
}

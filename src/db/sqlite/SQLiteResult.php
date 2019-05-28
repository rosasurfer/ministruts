<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\core\assert\Assert;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\Result;

use const rosasurfer\ARRAY_ASSOC;
use const rosasurfer\ARRAY_BOTH;
use const rosasurfer\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SQLiteResult extends Result {


    /** @var string - SQL statement the result was generated from */
    protected $sql;

    /** @var \SQLite3Result - the database connector's original result object */
    protected $result;

    /** @var int - last inserted row id of the connection at instance creation time (not reset between queries) */
    protected $lastInsertId = 0;

    /** @var int - last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;

    /** @var int - number of rows returned by the statement */
    protected $numRows;


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
        Assert::string($sql,              '$sql');
        Assert::int   ($lastInsertId,     '$lastInsertId');
        Assert::int   ($lastAffectedRows, '$lastAffectedRows');

        $this->connector        = $connector;
        $this->sql              = $sql;
        $this->lastInsertId     = $lastInsertId;
        $this->lastAffectedRows = $lastAffectedRows;

        if (!$result->numColumns()) {           // close empty results and release them to prevent access
            $result->finalize();                // @see bug in SQLite3Result::fetchArray()
            $result             = null;
            $this->numRows      = 0;
            $this->nextRowIndex = -1;
        }
        else {
            $this->nextRowIndex = 0;
        }
        $this->result = $result;
    }


    /**
     * Fetch the next row from the result set.
     *
     * The types of the values of the returned array are mapped from SQLite3 types as follows:                  <br>
     *  - Integers are mapped to int if they fit into the range PHP_INT_MIN...PHP_INT_MAX, otherwise to string. <br>
     *  - Floats are mapped to float.                                                                           <br>
     *  - NULL values are mapped to NULL.                                                                       <br>
     *  - Strings and blobs are mapped to string.                                                               <br>
     *
     * {@inheritdoc}
     */
    public function fetchRow($mode = ARRAY_BOTH) {
        if (!$this->result || $this->nextRowIndex < 0)        // no automatic result reset()
            return null;

        switch ($mode) {
            case ARRAY_ASSOC: $mode = SQLITE3_ASSOC; break;
            case ARRAY_NUM:   $mode = SQLITE3_NUM;   break;
            default:          $mode = SQLITE3_BOTH;
        }

        $row = $this->result->fetchArray($mode);
        if ($row) {
            $this->nextRowIndex++;
        }
        else {
            if ($this->numRows === null)                       // update $numRows on-the-fly if not yet happened
                $this->numRows = $this->nextRowIndex;
            $row                = null;                        // prevent fetchArray() to trigger an automatic reset()
            $this->nextRowIndex = -1;                          // on second $row == null
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
            // no support for num_rows() in SQLite3, need to count manually
            $previous = $this->nextRowIndex;

            while ($this->fetchRow());                          // loop from current position to the end

            // we hit the end
            if ($this->numRows) {
                $this->result->reset();                         // back to start
                $this->nextRowIndex = 0;
                while ($previous--) {                           // loop back to former position
                    $this->fetchRow();
                }
            }
        }
        return $this->numRows;
    }


    /**
     * {@inheritdoc}
     */
    public function release() {
        if ($this->result) {
            $tmp = $this->result;
            $this->result       = null;
            $this->nextRowIndex = -1;

            if ($this->connector->isConnected()) {              // if disconnected the result is already released
                $tmp->finalize();
            }
        }
    }


    /**
     * Return this result's internal SQLite3Result object.
     *
     * @return \SQLite3Result - result handler or NULL for result-less queries
     */
    public function getInternalResult() {
        return $this->result;
    }
}

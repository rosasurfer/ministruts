<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\sqlite;

use SQLite3Result;

use rosasurfer\ministruts\core\exception\IllegalAccessException;
use rosasurfer\ministruts\db\ConnectorInterface as Connector;
use rosasurfer\ministruts\db\Result;

use const rosasurfer\ministruts\ARRAY_ASSOC;
use const rosasurfer\ministruts\ARRAY_BOTH;
use const rosasurfer\ministruts\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * a result set.
 */
class SQLiteResult extends Result {

    /** @var ?SQLite3Result - the database connector's original result object */
    protected ?SQLite3Result $result = null;

    /** @var int - last inserted row id of the connection at instance creation time (not reset between queries) */
    protected int $lastInsertId = 0;

    /** @var int - last number of affected rows (not reset between queries) */
    protected int $lastAffectedRows = 0;

    /** @var int - number of rows returned by the statement */
    protected int $numRows = -1;


    /**
     * Constructor
     *
     * Called only when execution of a SQL statement returned successfully.
     *
     * @param  Connector     $connector        - connector managing the database connection
     * @param  string        $sql              - executed SQL statement
     * @param  SQLite3Result $result           - result-less queries produce an empty SQLite3Result
     * @param  int           $lastInsertId     - last inserted ID of the connection
     * @param  int           $lastAffectedRows - last number of affected rows of the connection
     */
    public function __construct(Connector $connector, string $sql, SQLite3Result $result, int $lastInsertId, int $lastAffectedRows) {
        parent::__construct($connector, $sql);

        $this->lastInsertId = $lastInsertId;
        $this->lastAffectedRows = $lastAffectedRows;

        if (!$result->numColumns()) {                           // close empty results and release them to prevent access
            $result->finalize();                                // @see bug in SQLite3Result::fetchArray()
            $result = null;
            $this->numRows = 0;
            $this->nextRowIndex = -1;
        }
        else {
            $this->nextRowIndex = 0;
        }
        $this->result = $result;
    }


    /**
     * {@inheritDoc}
     *
     * @param  int $mode [optional] - Controls how the returned array is indexed. Can take one of the following values:
     *                                ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
     *
     * @return array<?scalar>|null - array of columns or NULL if no more rows are available
     *
     * The returned result types are mapped from SQLite3 types as follows:                                          <br>
     *  - Integers are mapped to 'int' if they fit into the range PHP_INT_MIN...PHP_INT_MAX, otherwise to 'string'. <br>
     *  - Floats are mapped to 'float'.                                                                             <br>
     *  - NULL values are mapped to 'null'.                                                                         <br>
     *  - Strings and blobs are mapped to 'string'.                                                                 <br>
     */
    public function fetchRow(int $mode = ARRAY_BOTH): ?array {
        if (!$this->result || $this->nextRowIndex < 0) {        // no automatic result reset()
            return null;
        }

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
            $this->numRows = $this->nextRowIndex;               // update $numRows whenever we hit the end
            $row = null;                                        // prevent fetchArray() to trigger an automatic reset()
            $this->nextRowIndex = -1;                           // on second $row == null
        }
        return $row;
    }


    /**
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement up to creation time of this instance.
     * The value is not reset between queries (see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     */
    public function lastInsertId(): int {
        return $this->lastInsertId;
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement up to creation time of this instance.
     * For UPDATE or DELETE statements this is the number of matched rows. The value is not reset between queries (see the db
     * README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     */
    public function lastAffectedRows(): int {
        return $this->lastAffectedRows;
    }


    /**
     * {@inheritDoc}
     */
    public function numRows(): int {
        if ($this->numRows < 0) {
            if (!$this->result) throw new IllegalAccessException('Cannot call method '.__FUNCTION__.'() after the result has been released');

            // no support for num_rows() in SQLite3, need to count manually
            $previous = $this->nextRowIndex;

            while ($this->fetchRow());                          // loop from current position to the end

            // we hit the end, $numRows is updated
            if ($this->numRows > 0) {                           // @phpstan-ignore greater.alwaysFalse ($this->fetchRow() has side effects PHPStan doesn't detect)
                $this->result->reset();                         // back to start
                $this->nextRowIndex = 0;
                while ($previous--) {                           // loop back to restore previous position
                    $this->fetchRow();
                }
            }
        }
        return $this->numRows;
    }


    /**
     * {@inheritDoc}
     */
    public function release(): void {
        if ($this->result) {
            $tmp = $this->result;
            $this->result = null;
            $this->nextRowIndex = -1;

            if ($this->connector->isConnected()) {              // if disconnected the result is already released
                $tmp->finalize();
            }
        }
    }


    /**
     * Return this result's internal SQLite3Result object.
     *
     * @return ?SQLite3Result - result handler or NULL for result-less queries
     */
    public function getInternalResult() {
        return $this->result;
    }
}

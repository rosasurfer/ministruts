<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\pgsql;

use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;
use rosasurfer\ministruts\db\Result;

use const rosasurfer\ministruts\ARRAY_ASSOC;
use const rosasurfer\ministruts\ARRAY_BOTH;
use const rosasurfer\ministruts\ARRAY_NUM;


/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * returned rows.
 */
class PostgresResult extends Result {


    // Status codes as returned by pg_result_status(PGSQL_STATUS_LONG)

    /** @var int - The string sent to the server was empty. */
    const STATUS_EMPTY_QUERY    = \PGSQL_EMPTY_QUERY;

    /** @var int - Successful completion of a command returning no rows. */
    const STATUS_COMMAND_OK     = \PGSQL_COMMAND_OK;

    /** @var int - Successful completion of a command returning rows. */
    const STATUS_TUPLES_OK      = \PGSQL_TUPLES_OK;

    /** @var int - Copy Out (from server) data transfer started. */
    const STATUS_COPY_OUT       = \PGSQL_COPY_OUT;

    /** @var int - Copy In (to server) data transfer started. */
    const STATUS_COPY_IN        = \PGSQL_COPY_IN;

    /** @var int - The server's response was not understood. */
    const STATUS_BAD_RESPONSE   = \PGSQL_BAD_RESPONSE;

    /** @var int - A nonfatal error (a notice or warning) occurred. */
    const STATUS_NONFATAL_ERROR = \PGSQL_NONFATAL_ERROR;

    /** @var int - A fatal error occurred. */
    const STATUS_FATAL_ERROR    = \PGSQL_FATAL_ERROR;


    /** @var string - SQL statement the result was generated from */
    protected $sql;

    /** @var ?resource - the database connector's original result handle */
    protected $hResult = null;

    /** @var int - last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;

    /** @var int - number of rows returned by the statement */
    protected $numRows;


    /**
     * Constructor
     *
     * Create a new PostgresResult instance. Called only when execution of a SQL statement returned successful.
     *
     * @param  IConnector $connector        - Connector managing the database connection
     * @param  string     $sql              - executed SQL statement
     * @param  resource   $hResult          - result handle
     * @param  int        $lastAffectedRows - last number of affected rows of the connection
     */
    public function __construct(IConnector $connector, $sql, $hResult, $lastAffectedRows) {
        Assert::string($sql, '$sql');
        Assert::resource($hResult, '$hResult');
        Assert::int($lastAffectedRows, '$lastAffectedRows');

        $this->connector        = $connector;
        $this->sql              = $sql;
        $this->lastAffectedRows = $lastAffectedRows;

        if (!pg_num_fields($hResult)) {
            $this->numRows      = 0;
            $this->nextRowIndex = -1;
        }
        else {
            $this->nextRowIndex = 0;
        }
        $this->hResult = $hResult;
    }


    /**
     * {@inheritdoc}
     *
     * @param  int $mode [optional] - Controls how the returned array is indexed. Can take one of the following values:
     *                                ARRAY_ASSOC, ARRAY_NUM, or ARRAY_BOTH (default).
     *
     * @return array<?scalar>|null - array of columns or NULL if no more rows are available
     */
    public function fetchRow($mode = ARRAY_BOTH) {
        if (!$this->hResult || $this->nextRowIndex < 0)
            return null;

        switch ($mode) {
            case ARRAY_ASSOC: $mode = PGSQL_ASSOC; break;
            case ARRAY_NUM:   $mode = PGSQL_NUM;   break;
            default:          $mode = PGSQL_BOTH;
        }

        $row = pg_fetch_array($this->hResult, null, $mode);
        if ($row) {
            $this->nextRowIndex++;
        }
        else {
            $row                = null;
            $this->nextRowIndex = -1;
        }
        return $row;
    }


    /**
     * Return the last ID generated for a SERIAL column by a SQL statement. The value is not reset between queries
     * (see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session;
     *               -1 if the PostgreSQL server version doesn't support this functionality
     *
     * @link   https://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastInsertId() {
        return $this->connector->lastInsertId();
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement up to creation time of this instance.
     * For UPDATE and DELETE statements this is the number of matched rows. The value is not reset between queries
     * (see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     *
     * @link   https://github.com/rosasurfer/ministruts/tree/master/src/db
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
            if ($this->hResult) $this->numRows = pg_num_rows($this->hResult);
            else                $this->numRows = 0;
        }
        return $this->numRows;
    }


    /**
     * {@inheritdoc}
     */
    public function release() {
        if ($this->hResult) {
            $tmp = $this->hResult;
            $this->hResult = null;
            $this->nextRowIndex = -1;
            pg_free_result($tmp);
        }
    }


    /**
     * Return this result's internal result object.
     *
     * @return resource - result handle
     */
    public function getInternalResult() {
        return $this->hResult;
    }


    /**
     * Return a readable version of a result status code.
     *
     * @param  int $status - status code as returned by pg_result_status(PGSQL_STATUS_LONG)
     *
     * @return string
     */
    public static function statusToStr($status) {
        Assert::int($status);

        switch ($status) {
            case PGSQL_EMPTY_QUERY   : return 'PGSQL_EMPTY_QUERY';
            case PGSQL_COMMAND_OK    : return 'PGSQL_COMMAND_OK';
            case PGSQL_TUPLES_OK     : return 'PGSQL_TUPLES_OK';
            case PGSQL_COPY_OUT      : return 'PGSQL_COPY_OUT';
            case PGSQL_COPY_IN       : return 'PGSQL_COPY_IN';
            case PGSQL_BAD_RESPONSE  : return 'PGSQL_BAD_RESPONSE';
            case PGSQL_NONFATAL_ERROR: return 'PGSQL_NONFATAL_ERROR';
            case PGSQL_FATAL_ERROR   : return 'PGSQL_FATAL_ERROR';
        }
        return (string) $status;
    }
}

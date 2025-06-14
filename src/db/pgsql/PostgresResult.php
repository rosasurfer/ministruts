<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\pgsql;

use PgSql\Result as PgSqlResult;

use rosasurfer\ministruts\db\ConnectorInterface as Connector;
use rosasurfer\ministruts\db\Result;

use const rosasurfer\ministruts\ARRAY_ASSOC;
use const rosasurfer\ministruts\ARRAY_BOTH;
use const rosasurfer\ministruts\ARRAY_NUM;

/**
 * Represents the result of an executed SQL statement. Depending on the statement type the result may or may not contain
 * returned rows.
 */
class PostgresResult extends Result {

    // status codes as returned by pg_result_status(PGSQL_STATUS_LONG)

    /** The string sent to the server was empty. */
    public const STATUS_EMPTY_QUERY = \PGSQL_EMPTY_QUERY;

    /** Successful completion of a command returning no rows. */
    public const STATUS_COMMAND_OK = \PGSQL_COMMAND_OK;

    /** Successful completion of a command returning rows. */
    public const STATUS_TUPLES_OK = \PGSQL_TUPLES_OK;

    /** Copy Out (from server) data transfer started. */
    public const STATUS_COPY_OUT = \PGSQL_COPY_OUT;

    /** Copy In (to server) data transfer started. */
    public const STATUS_COPY_IN = \PGSQL_COPY_IN;

    /** The server's response was not understood. */
    public const STATUS_BAD_RESPONSE = \PGSQL_BAD_RESPONSE;

    /** A nonfatal error (a notice or warning) occurred. */
    public const STATUS_NONFATAL_ERROR = \PGSQL_NONFATAL_ERROR;

    /** A fatal error occurred. */
    public const STATUS_FATAL_ERROR = \PGSQL_FATAL_ERROR;


    /**
     * @var  resource|PgSqlResult|null - the database connector's original result handle
     * @phpstan-var PgSqlResultId|null
     */
    protected $result = null;

    /** @var int - last number of affected rows (not reset between queries) */
    protected int $lastAffectedRows = 0;

    /** @var int - number of rows returned by the statement */
    protected int $numRows;


    /**
     * Constructor
     *
     * Called only when execution of a SQL statement returned successfully.
     *
     * @param         Connector            $connector        - the connector managing the database connection
     * @param         string               $sql              - executed SQL statement
     * @param         resource|PgSqlResult $result           - result handle or object (before/since PHP8.1+)
     * @phpstan-param PgSqlResultId        $result
     * @param         int                  $lastAffectedRows - last number of affected rows of the connection
     */
    public function __construct(Connector $connector, string $sql, $result, int $lastAffectedRows) {
        parent::__construct($connector, $sql);

        $this->result = $result;
        $this->lastAffectedRows = $lastAffectedRows;

        if (!pg_num_fields($result)) {
            $this->numRows = 0;
            $this->nextRowIndex = -1;
        }
        else {
            $this->numRows = pg_num_rows($result);
            $this->nextRowIndex = 0;
        }
    }


    /**
     * {@inheritDoc}
     *
     * @param  int $mode [optional]
     *
     * @return array<?scalar>|null
     */
    public function fetchRow(int $mode = ARRAY_BOTH): ?array {
        if (!$this->result || $this->nextRowIndex < 0) {
            return null;
        }

        switch ($mode) {
            case ARRAY_ASSOC: $mode = PGSQL_ASSOC; break;
            case ARRAY_NUM:   $mode = PGSQL_NUM;   break;
            default:          $mode = PGSQL_BOTH;
        }

        $row = pg_fetch_array($this->result, null, $mode);
        if ($row) {
            $this->nextRowIndex++;
        }
        else {
            $row = null;
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
     */
    public function lastInsertId(): int {
        return $this->connector->lastInsertId();
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement up to creation time of this instance.
     * For UPDATE and DELETE statements this is the number of matched rows. The value is not reset between queries
     * (see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     */
    public function lastAffectedRows(): int {
        return $this->lastAffectedRows;
    }


    /**
     * Return the number of rows returned by the query.
     *
     * @return int
     */
    public function numRows(): int {
        return $this->numRows;
    }


    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function release(): void {
        if ($this->result) {
            $tmp = $this->result;
            $this->result = null;
            $this->nextRowIndex = -1;
            pg_free_result($tmp);
        }
    }


    /**
     * Return this result's internal result object.
     *
     * @return resource|PgSqlResult|null - result handle
     * @phpstan-return PgSqlResultId|null
     */
    public function getInternalResult() {
        return $this->result;
    }


    /**
     * Return a readable version of a result status code.
     *
     * @param  int $status - status code as returned by pg_result_status(PGSQL_STATUS_LONG)
     *
     * @return string
     */
    public static function statusToStr(int $status): string {
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

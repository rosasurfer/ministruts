<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strContains;
use function rosasurfer\strContainsI;
use function rosasurfer\strStartsWithI;


/**
 * PostgresConnector
 */
class PostgresConnector extends Connector {


    /** @var string - DBMS type */
    protected $type = 'pgsql';

    /** @var string - DBMS version string */
    protected $versionString;

    /** @var int - DBMS version number */
    protected $versionNumber;

    /** @var string[] - configuration options */
    protected $options = [];

    /** @var resource - internal connection handle */
    protected $hConnection;

    /** @var int - transaction nesting level */
    protected $transactionLevel = 0;

    /** @var int - the last inserted row id (not reset between queries) */
    protected $lastInsertId = null;        // distinguish between "not yet set" and "zero"

    /** @var int - the last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;


    /**
     * Constructor
     *
     * Create a new PostgresConnector instance.
     *
     * @param  array $options - PostgreSQL typical configuration options
     */
    public function __construct(array $options) {
        $this->setOptions($options);
    }


    /**
     * Set connection options.
     *
     * @param  string[] $options
     *
     * @return self
     */
    protected function setOptions(array $options) {
        $this->options = $options;
        return $this;
    }


    /**
     * Resolve and return the PostgreSQL connection string from the passed connection options.
     *
     * @return string
     */
    private function getConnectionString() {
        // currently supported keywords:  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS
        $paramKeywords = [
            'host',
            'hostaddr',
            'port',
            'dbname',
            'user',
            'password',
            'connect_timeout',
            'client_encoding',
          //'options',                                      // TODO: requires special implementation
            'application_name',
            'fallback_application_name',
            'keepalives',
            'keepalives_idle',
            'keepalives_interval',
            'keepalives_count',
            'tty',                                          // ignored
            'sslmode',
            'requiressl',                                   // deprecated in favor of 'sslmode'
            'sslcompression',
            'sslcert',
            'sslkey',
            'sslrootcert',
            'sslcrl',
            'requirepeer',
            'krbsrvname',
          //'gsslib',                                       // rejected by php_pgsql 9.4.1
            'service',
        ];
        $paramKeywords = array_flip($paramKeywords);
        $connStr = '';

        foreach ($this->options as $key => $value) {
            if (!isSet($paramKeywords[$key])) continue;
            if (is_array($value))             continue;

            if (!strLen($value)) {
                $value = "''";
            }
            else {
                $value = str_replace(['\\', "'"], ['\\\\', "\'"], $value);
                if (strContains($value, ' '))
                    $value = "'".$value."'";
            }
            $connStr .= $key.'='.$value.' ';
        }

        return trim($connStr);
    }
    /*
    The connection string can be empty to use all default parameters, or it can contain one or more parameter settings
    separated by whitespace. Each parameter setting is in the form `keyword=value`. Spaces around the equal sign are
    optional. To write an empty value or a value containing spaces, surround it with single quotes, e.g.,
    `keyword='a value'`. Single quotes and backslashes within the value must be escaped with a backslash, i.e., \' and \\.

    The 'options' parameter can be used to set command line parameters to be invoked by the server.

    @see  http://php.net/manual/en/function.pg-connect.php
    @see  https://www.postgresql.org/docs/7.4/static/pgtcl-pgconnect.html
    @see  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-CONNSTRING

    ---------------------------------------------------------------------------------------------------------------------
    Examples:
    - host=/tmp                                                             // connect to socket
    - options='--application_name=$appName'                                 // send $appName to backend (pgAdmin, logs)
    - options='--client_encoding=UTF8'                                      // set client encoding

    - putEnv('PGSERVICEFILE=/path/to/your/service/file/pg_service.conf');   // external connection configuration
      pg_connect("service=testdb");

      @see  https://www.postgresql.org/docs/9.6/static/libpq-pgservice.html
    */


    /**
     * Connect the adapter to the database.
     *
     * @return self
     */
    public function connect() {
        $connStr = $this->getConnectionString();
        try {
            $this->hConnection = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
            !$this->hConnection && trigger_error(@$php_errormsg, E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Cannot connect to PostgreSQL server with connection string: "'.$connStr.'"');
        }
        return $this;
    }


    /**
     * Disconnect the adapter from the database.
     *
     * @return self
     */
    public function disconnect() {
        if ($this->isConnected()) {
            $tmp = $this->hConnection;
            $this->hConnection = null;
            pg_close($tmp);
        }
        return $this;

        // TODO: If there are open large object resources on the connection, do not close the connection before closing all
        //       large object resources.
        // @see  http://php.net/manual/en/function.pg-close.php
    }


    /**
     * Whether or not the adapter currently is connected to the database.
     *
     * @return bool
     */
    public function isConnected() {
        return is_resource($this->hConnection);
    }


    /**
     * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
     * can be used in queries "as-is" and doesn't need additional quoting.
     *
     * @param  string $name - identifier to escape
     *
     * @return string - escaped and quoted identifier; PostgreSQL:  "{$name}"
     */
    public function escapeIdentifier($name) {
        if (!$this->isConnected())
            $this->connect();
        return pg_escape_identifier($this->hConnection, $name);
    }


    /**
     * Escape a DBMS string literal, i.e. a string value. The resulting string can be used in queries "as-is" and doesn't
     * need additional quoting.
     *
     * PostgreSQL: =  E'{escape($value)}'
     *
     * @param  scalar $value - value to escape
     *
     * @return scalar - escaped and quoted string or scalar value if the value was not a string
     */
    public function escapeLiteral($value) {
        // bug or feature: pg_escape_literal(null) => '' quoted empty string instead of 'null'
        if ($value === null)
            return 'null';

        if (is_int($value) || is_float($value))
            return (string) $value;

        if (!$this->isConnected())
            $this->connect();
        return pg_escape_literal($this->hConnection, $value);
    }


    /**
     * Escape a string value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * PostgreSQL: = escape_chars($value, ['\', "'"])
     *
     * @param  scalar $value - value to escape
     *
     * @return string|null - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        // bug or feature: pg_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return null;

        if (!$this->isConnected())
            $this->connect();
        return pg_escape_string($this->hConnection, $value);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return PostgresResult
     *
     * @throws DatabaseException in case of failure
     */
    public function query($sql) {
        $result = $this->executeRaw($sql);
        return new PostgresResult($this, $sql, $result, $this->lastAffectedRows());
    }


    /**
     * Execute a SQL statement and skip potential result set processing. This method should be used for SQL statements not
     * returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return self
     *
     * @throws DatabaseException in case of failure
     */
    public function execute($sql) {
        $result = $this->executeRaw($sql);
        if (is_resource($result))
            pg_free_result($result);
        return $this;
    }


    /**
     * Execute a SQL statement and return the internal driver's raw response.
     *
     * @param  string $sql - SQL statement
     *
     * @return resource - result handle
     *
     * @throws DatabaseException in case of failure
     */
    public function executeRaw($sql) {
        if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
        if (!$this->isConnected())
            $this->connect();

        // execute statement
        $result = null;
        try {
            $result = pg_query($this->hConnection, $sql);         // wraps multi-statement queries in a transaction
            $result || trigger_error(pg_last_error($this->hConnection), E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('SQL: "'.$sql.'"');
        }

        $status_string = pg_result_status($result, PGSQL_STATUS_STRING);

        // reset last_insert_id on INSERTs, afterwards it's resolved on request as it requires an extra SQL query
        if (strStartsWithI($status_string, 'INSERT '))
            $this->lastInsertId = null;

        // track last_affected_rows
        $pattern = '/^(INSERT|UPDATE|DELETE)\b/i';
        if (preg_match($pattern, $status_string))
            $this->lastAffectedRows = pg_affected_rows($result);

        return $result;
        /*
        $db->query("drop table if exists t_test");
        $db->query("create table t_test (id serial primary key, name varchar(100) not null)");
        $db->query("insert into t_test (name) values ('a'), ('b'), ('c'), ('123')");
        $db->query("explain select count(*) from t_test");
        $db->query("update t_test set name='c' where name in ('c')");
        $db->query("select * from t_test where name in ('a','b')");
        $db->query("select * from t_test where name in ('a','b') limit 1");
        $db->query("update t_test set name='aa' where name in ('c')");
        $db->query("select * from t_test where name = 'no-one'");
        $db->query("select count(*) from t_test");
        $db->query("delete from t_test where name = 'a' or name = 'b'");
        $db->query("select count(*) from t_test");
        $db->query("insert into t_test (name) values ('y'), ('z') returning id");
        $db->query("insert into t_test (name) values ('x')");
        $db->query("explain select count(*) from t_test");
        $db->query("select * from t_test");
        $db->query("begin; select * from t_test; commit");
        $db->query("select * from t_test where name = 'no-one'");

        $result  = $db->query($sql);
        $handler = $db->getInternalHandler();
        $msg = str_pad(explode(' ', $sql, 2)[0].':', 9).'  lastInsertId=%s'.'  affected_rows='.pg_affected_rows($result->getInternalResult()).'  lastAffectedRows='.$db->lastAffectedRows().'  num_rows='.pg_num_rows($result->getInternalResult()).'  status_long='.str_pad(\rosasurfer\db\pgsql\PostgresResult::statusToStr(pg_result_status($result->getInternalResult(), PGSQL_STATUS_LONG)), 16).'  status_string='.pg_result_status($result->getInternalResult(), PGSQL_STATUS_STRING);
        $msg = sprintf($msg, $db->lastInsertId());
        echoPre($msg);

        drop:      lastInsertId=0  affected_rows=0  lastAffectedRows=0  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=DROP TABLE
        create:    lastInsertId=0  affected_rows=0  lastAffectedRows=0  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=CREATE TABLE
        insert:    lastInsertId=4  affected_rows=4  lastAffectedRows=4  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=INSERT 0 4
        explain:   lastInsertId=4  affected_rows=0  lastAffectedRows=4  num_rows=2  status_long=PGSQL_TUPLES_OK   status_string=EXPLAIN
        update:    lastInsertId=4  affected_rows=1  lastAffectedRows=1  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=UPDATE 1
        select:    lastInsertId=4  affected_rows=2  lastAffectedRows=1  num_rows=2  status_long=PGSQL_TUPLES_OK   status_string=SELECT 2
        select:    lastInsertId=4  affected_rows=1  lastAffectedRows=1  num_rows=1  status_long=PGSQL_TUPLES_OK   status_string=SELECT 1
        update:    lastInsertId=4  affected_rows=1  lastAffectedRows=1  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=UPDATE 1
        select:    lastInsertId=4  affected_rows=0  lastAffectedRows=1  num_rows=0  status_long=PGSQL_TUPLES_OK   status_string=SELECT 0
        select:    lastInsertId=4  affected_rows=1  lastAffectedRows=1  num_rows=1  status_long=PGSQL_TUPLES_OK   status_string=SELECT 1
        delete:    lastInsertId=4  affected_rows=2  lastAffectedRows=2  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=DELETE 2
        select:    lastInsertId=4  affected_rows=1  lastAffectedRows=2  num_rows=1  status_long=PGSQL_TUPLES_OK   status_string=SELECT 1
        insert:    lastInsertId=6  affected_rows=2  lastAffectedRows=2  num_rows=2  status_long=PGSQL_TUPLES_OK   status_string=INSERT 0 2
        insert:    lastInsertId=7  affected_rows=1  lastAffectedRows=1  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=INSERT 0 1
        explain:   lastInsertId=7  affected_rows=0  lastAffectedRows=1  num_rows=2  status_long=PGSQL_TUPLES_OK   status_string=EXPLAIN
        select:    lastInsertId=7  affected_rows=5  lastAffectedRows=1  num_rows=5  status_long=PGSQL_TUPLES_OK   status_string=SELECT 5
        begin:     lastInsertId=7  affected_rows=0  lastAffectedRows=1  num_rows=0  status_long=PGSQL_COMMAND_OK  status_string=COMMIT
        select:    lastInsertId=7  affected_rows=0  lastAffectedRows=1  num_rows=0  status_long=PGSQL_TUPLES_OK   status_string=SELECT 0
      */

        // TODO: All queries must be sent via pg_send_query()/pg_get_result(). All errors must be analyzed per result
        //       via pg_result_error(). This way we get access to SQLSTATE codes and to custom exception handling.
        //
        //       PDO and missing support for asynchronous queries:
        // @see  http://grokbase.com/t/php/php-pdo/09b2hywmak/asynchronous-requests
        // @see  http://stackoverflow.com/questions/865017/pg-send-query-cannot-set-connection-to-blocking-mode
        // @see  https://bugs.php.net/bug.php?id=65015

        /*
        pg_send_query($this->hConnection, $sql);
        $result = pg_get_result($this->hConnection);    // get one result per statement from a multi-statement query

        echoPre(pg_result_error($result));              // analyze errors

        echoPre('PGSQL_DIAG_SEVERITY           = '.pg_result_error_field($result, PGSQL_DIAG_SEVERITY          ));
        echoPre('PGSQL_DIAG_SQLSTATE           = '.pg_result_error_field($result, PGSQL_DIAG_SQLSTATE          ));
        echoPre('PGSQL_DIAG_MESSAGE_PRIMARY    = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY   ));
        echoPre('PGSQL_DIAG_MESSAGE_DETAIL     = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_DETAIL    ));
        echoPre('PGSQL_DIAG_MESSAGE_HINT       = '.pg_result_error_field($result, PGSQL_DIAG_MESSAGE_HINT      ));
        echoPre('PGSQL_DIAG_STATEMENT_POSITION = '.pg_result_error_field($result, PGSQL_DIAG_STATEMENT_POSITION));
        echoPre('PGSQL_DIAG_INTERNAL_POSITION  = '.pg_result_error_field($result, PGSQL_DIAG_INTERNAL_POSITION ));
        echoPre('PGSQL_DIAG_INTERNAL_QUERY     = '.pg_result_error_field($result, PGSQL_DIAG_INTERNAL_QUERY    ));
        echoPre('PGSQL_DIAG_CONTEXT            = '.pg_result_error_field($result, PGSQL_DIAG_CONTEXT           ));
        echoPre('PGSQL_DIAG_SOURCE_FILE        = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_FILE       ));
        echoPre('PGSQL_DIAG_SOURCE_LINE        = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_LINE       ));
        echoPre('PGSQL_DIAG_SOURCE_FUNCTION    = '.pg_result_error_field($result, PGSQL_DIAG_SOURCE_FUNCTION   ));
        // ----------------------------------------------------------------------------------------------------------

        $>  select lastval()
        ERROR:  lastval is not yet defined in this session
        PGSQL_DIAG_SEVERITY           = ERROR
        PGSQL_DIAG_SQLSTATE           = 55000
        PGSQL_DIAG_MESSAGE_PRIMARY    = lastval is not yet defined in this session
        PGSQL_DIAG_MESSAGE_DETAIL     =
        PGSQL_DIAG_MESSAGE_HINT       =
        PGSQL_DIAG_STATEMENT_POSITION =
        PGSQL_DIAG_INTERNAL_POSITION  =
        PGSQL_DIAG_INTERNAL_QUERY     =
        PGSQL_DIAG_CONTEXT            =
        PGSQL_DIAG_SOURCE_FILE        = sequence.c
        PGSQL_DIAG_SOURCE_LINE        = 794
        PGSQL_DIAG_SOURCE_FUNCTION    = lastval
        // ----------------------------------------------------------------------------------------------------------

        $>  insert into t_doesnotexist (name) values ('a')
        ERROR:  relation "t_doesnotexist" does not exist
        LINE 1: insert into t_doesnotexist (name) values ('a'), ('b'), ('c')
                          ^
        PGSQL_DIAG_SEVERITY           = ERROR
        PGSQL_DIAG_SQLSTATE           = 42P01
        PGSQL_DIAG_MESSAGE_PRIMARY    = relation "t_doesnotexist" does not exist
        PGSQL_DIAG_MESSAGE_DETAIL     =
        PGSQL_DIAG_MESSAGE_HINT       =
        PGSQL_DIAG_STATEMENT_POSITION = 13
        PGSQL_DIAG_INTERNAL_POSITION  =
        PGSQL_DIAG_INTERNAL_QUERY     =
        PGSQL_DIAG_CONTEXT            =
        PGSQL_DIAG_SOURCE_FILE        = parse_relation.c
        PGSQL_DIAG_SOURCE_LINE        = 866
        PGSQL_DIAG_SOURCE_FUNCTION    = parserOpenTable
      */
    }


    /**
     * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
     *
     * @return self
     */
    public function begin() {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if (!$this->transactionLevel)
            $this->execute('begin');

        $this->transactionLevel++;
        return $this;
    }


    /**
     * Commit a pending transaction.
     *
     * @return self
     */
    public function commit() {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if      (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
        else if (!$this->transactionLevel) trigger_error('No database transaction to commit', E_USER_WARNING);
        else {
            if ($this->transactionLevel == 1)
                $this->execute('commit');
            $this->transactionLevel--;
        }
        return $this;
    }


    /**
     * Roll back an active transaction. If a nested transaction is active only the transaction nesting level is decreased.
     * If only one (the outer most) transaction is active the transaction is rolled back.
     *
     * @return self
     */
    public function rollback() {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if      (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
        else if (!$this->transactionLevel) trigger_error('No database transaction to roll back', E_USER_WARNING);
        else {
            if ($this->transactionLevel == 1)
                $this->execute('rollback');
            $this->transactionLevel--;
        }
        return $this;
    }


    /**
     * Whether or not the connection currently is in a transaction.
     *
     * @return bool
     */
    public function isInTransaction() {
        if ($this->isConnected())
            return ($this->transactionLevel > 0);
        return false;
    }


    /**
     * Return the last ID generated for a SERIAL column by a SQL statement. The value is not reset between queries (see the
     * db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     *               -1 if the PostgreSQL version doesn't support this functionality
     *
     * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastInsertId() {
        if ($this->lastInsertId === null) {
            $version = $this->getVersionNumber();
            if ($version < 8001000) {              // 8.1
                $this->lastInsertId = -1;
            }
            else {
                try {
                    $this->lastInsertId = $this->query("select lastVal()")->fetchInt();
                }
                catch (\Exception $ex) {
                    if (!strContainsI($ex->getMessage(), 'ERROR:  lastval is not yet defined in this session'))
                        throw $ex;
                    $this->lastInsertId = 0;
                }
            }
        }
        return (int) $this->lastInsertId;
        /*
        @see  https://www.postgresql.org/docs/9.6/static/functions-sequence.html
        @see  http://stackoverflow.com/questions/6485778/php-postgres-get-last-insert-id/6488840
        @see  http://stackoverflow.com/questions/22530585/how-to-turn-off-multiple-statements-in-postgres
        @see  http://php.net/manual/en/function.pg-query-params.php
        @see  http://stackoverflow.com/questions/24182521/how-to-find-out-if-a-sequence-was-initialized-in-this-session
        @see  http://stackoverflow.com/questions/32991564/how-to-check-in-postgres-that-lastval-is-defined
        @see  http://stackoverflow.com/questions/55956/mysql-insert-id-alternative-for-postgresql
      */
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement. For UPDATE and DELETE statements
     * this is the number of matched rows. The value is not reset between queries (see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     *
     * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastAffectedRows() {
        return (int) $this->lastAffectedRows;
    }


    /**
     * Return the connector's internal connection object.
     *
     * @return resource - the internal connection handle
     */
    public function getInternalHandler() {
        if (!$this->isConnected())
            $this->connect();
        return $this->hConnection;
    }


    /**
     * Return the type of the DBMS the connector is used for.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }


    /**
     * Return the version of the DBMS the connector is used for as a string.
     *
     * @return string - e.g. "9.1.23-rc"
     */
    public function getVersionString() {
        if (is_null($this->versionString)) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionString = pg_version($this->hConnection)['server'];
        }
        return $this->versionString;
    }


    /**
     * Return the version ID of the DBMS the connector is used for as an integer.
     *
     * @return int - e.g. 9001023 for version string "9.1.23-rc"
     */
    public function getVersionNumber() {
        if (is_null($this->versionNumber)) {
            $version = $this->getVersionString();
            if (!preg_match('/^(\d+)\.(\d+).(\d+)/', $version, $match))
                throw new \UnexpectedValueException('Unexpected version string "'.$version.'"');

            $major   = (int) $match[1];
            $minor   = (int) $match[2];
            $release = (int) $match[3];

            $this->versionNumber = $major*1000000 + $minor*1000 + $release;
        }
        return $this->versionNumber;
    }
}

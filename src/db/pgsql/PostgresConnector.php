<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use function rosasurfer\strContains;
use function rosasurfer\strContainsI;
use function rosasurfer\strStartsWithI;

use const rosasurfer\NL;


/**
 * PostgresConnector
 *
 *
 * Connection configuration:
 * <pre>
 *  +-------------------------------------+-----------------+-----------------+
 *  | config setting                      | value           | default value   |
 *  +-------------------------------------+-----------------+-----------------+
 *  | db.{connection}.connector           | postgres        | -               |
 *  | db.{connection}.{keyword}           | {value}         | -               |
 *  +-------------------------------------+-----------------+-----------------+
 * </pre>
 * All connection keywords supported by PostgreSQL can be specified. The keyword "options" can be used as a regular
 * PostgreSQL connection keyword for command line options.
 *
 *
 * Additional user-defined options:
 * <pre>
 *  +-------------------------------------+-----------------+-----------------+
 *  | option                              | value           | default value   |
 *  +-------------------------------------+-----------------+-----------------+
 *  | db.{connection}.options.search_path | [schemaName]    | "$user", public |
 *  | db.{connection}.options.{name1}     | {value}         | -               |
 *  | ...                                 | ...             | -               |
 *  | db.{connection}.options.{nameN}     | {value}         | -               |
 *  +-------------------------------------+-----------------+-----------------+
 * </pre>
 * User-defined options can be specified as nested suboptions and are sent to the database as system variables in the
 * scope of the active session.
 *
 *
 * @see  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS)
 */
class PostgresConnector extends Connector {


    /** @var string - DBMS type */
    protected $type = 'pgsql';

    /** @var string - DBMS version string */
    protected $versionString;

    /** @var int - DBMS version number */
    protected $versionNumber;

    /** @var string[]|string[][] - connection options */
    protected $options = [];

    /** @var string[] - session variables */
    protected $sessionVars = [];

    /** @var ?resource - internal connection handle */
    protected $hConnection = null;

    /** @var int - transaction nesting level */
    protected $transactionLevel = 0;

    /** @var ?int - the last inserted row id (not reset between queries) */
    protected $lastInsertId = null;        // distinguish between "not yet set" and "zero"

    /** @var int - the last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;


    /**
     * Constructor
     *
     * Create a new PostgresConnector instance.
     *
     * @param  array $options - PostgreSQL connection options
     */
    public function __construct(array $options) {
        $this->setOptions($options);
    }


    /**
     * Set connection options.
     *
     * @param  string[] $options
     *
     * @return $this
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
        // supported keywords:  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS
        $paramKeywords = [
            'host',
            'hostaddr',
            'port',
            'dbname',
            'user',
            'password',
            'connect_timeout',
            'client_encoding',
            'options',
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
        $paramKeywords = \array_flip($paramKeywords);
        $connStr = '';

        foreach ($this->options as $key => $value) {
            $keyL = trim(strtolower($key));

            if (!isset($paramKeywords[$keyL])) continue;    // unknown keyword

            if (is_array($value)) {
                if ($keyL != 'options') continue;           // "options" is the only allowed keyword with nested settings

                // split regular connection options and session variables
                if (isset($value[''])) {
                    $this->options[$key] = $value[''];
                    unset($value['']);
                    $this->sessionVars = $value;
                    $value = $this->options[$key];          // the root value goes into the connection string
                }
                else {
                    unset($this->options[$key]);
                    $this->sessionVars = $value;
                    continue;                               // "options" has only session vars and no root value [""]
                }
            }

            if (!strlen($value)) {
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


    /**
     * Return a textual description of the database connection options.
     *
     * @return string
     */
    protected function getConnectionDescription() {
        $options = $this->options;

        if (is_resource($this->hConnection)) {
            $host        = pg_host($this->hConnection);
            $port        = pg_port($this->hConnection);
            $db          = pg_dbname($this->hConnection);
            $path        = $this->getSchemaSearchPath();
            $description = $host.':'.$port.'/'.$db.' (schema search path: '.$path.')';
        }
        else {
            if      (isset($options['hostaddr'])) $host = $options['hostaddr'];
            else if (isset($options['host'    ])) $host = $options['host'    ];
            else                                  $host = '';

            if (isset($options['port'])) $port = ':'.$options['port'];
            else                         $port = '';

            if      (isset($options['dbname'])) $db = $options['dbname'];
            else if (isset($options['user'  ])) $db = $options['user'  ];
            else                                $db = '';

            $description = $host.$port.'/'.$db;
        }
        return $description;
    }


    /**
     * Return the database's current schema search path.
     *
     * @return ?string - schema search path or NULL in case of errors
     */
    protected function getSchemaSearchPath() {
        $options = $this->options;
        $path = null;

        while ($this->hConnection) {
            $ex = null;
            try {
                $result = pg_query($this->hConnection, 'show search_path');
                $row    = pg_fetch_array($result, null, PGSQL_NUM);
                $path   = $row[0];

                if (strContains($path, '"$user"') && isset($options['user'])) {
                    $path = str_replace('"$user"', $options['user'], $path);
                }
                break;
            }
            catch (\Throwable $ex) {}
            catch (\Exception $ex) {}                   // @phpstan-ignore-line

            if (strContainsI($ex->getMessage(), 'current transaction is aborted, commands ignored until end of transaction block')) {
                if ($this->transactionLevel > 0) {
                    $this->transactionLevel = 1;        // immediately skip nested transactions
                    $this->rollback();
                    continue;
                }
            }
            throw $ex;
        }
        return $path;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return $this
     */
    public function connect() {
        if (!function_exists('\pg_connect')) throw new RuntimeException('Undefined function pg_connect() (pgsql extension is not available)');

        $connStr = $this->getConnectionString();
        $ex = null;

        try {
            error_clear_last();
            $this->hConnection = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
            if (!$this->hConnection) throw new DatabaseException(error_get_last()['message']);
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }   // @phpstan-ignore-line
        if ($ex) throw $ex->addMessage('Cannot connect to PostgreSQL server with connection string: "'.$connStr.'"');

        $this->setConnectionOptions();
        return $this;
    }


    /**
     * Set the configured connection options.
     *
     * @return $this
     */
    protected function setConnectionOptions() {
        $options = $this->sessionVars;
        //$options['time_zone'] = date_default_timezone_get();      // synchronize connection timezone with PHP timezone

        foreach ($options as $option => $value) {
            if (strlen($value)) {
                $this->execute('set '.$option.' to '.$value);       // as is (no quoting)
            }
        }
        return $this;
    }


    /**
     * Disconnect the adapter from the database.
     *
     * @return $this
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
     * Whether the adapter is currently connected to the database.
     *
     * @return bool
     */
    public function isConnected() {
        return is_resource($this->hConnection);
    }


    /**
     *
     */
    public function escapeIdentifier($name) {
        Assert::string($name);

        if (!$this->isConnected())
            $this->connect();

        if (strContains($name, '.')) {
            $names = explode('.', $name);

            foreach ($names as &$subname) {
                $subname = pg_escape_identifier($this->hConnection, $subname);
            }
            unset($subname);

            return join('.', $names);
        }
        return pg_escape_identifier($this->hConnection, $name);
    }


    /**
     *
     */
    public function escapeLiteral($value) {
        // bug or feature: pg_escape_literal(null) => '' quoted empty string instead of 'null'
        if ($value === null)  return 'null';
        Assert::scalar($value);

        if (is_bool ($value)) return $value ? 'true':'false';
        if (is_int  ($value)) return (string) $value;
        if (is_float($value)) return (string) $value;

        if (!$this->isConnected())
            $this->connect();

        $value = $this->fixUtf8Encoding($value);    // pg_query(): ERROR: invalid byte sequence for encoding "UTF8"

        return pg_escape_literal($this->hConnection, $value);
    }


    /**
     *
     */
    public function escapeString($value) {
        // bug or feature: pg_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return null;
        Assert::string($value);

        if (!$this->isConnected())
            $this->connect();

        $value = $this->fixUtf8Encoding($value);    // pg_query(): ERROR: invalid byte sequence for encoding "UTF8"

        return pg_escape_string($this->hConnection, $value);
    }


    /**
     * Fix the encoding of a potentially non-UTF-8 encoded value.
     *
     * @param string $value - potentially non-UTF-8 encoded value
     *
     * @return string - UTF-8 encoded value
     *
     * @see https://www.drupal.org/node/434802
     */
    private function fixUtf8Encoding($value) {
        $encoding = mb_detect_encoding($value, null, true);
        if ($encoding!='ASCII' && $encoding!='UTF-8')
            $value = utf8_encode($value);
        return $value;
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return PostgresResult
     *
     * @throws DatabaseException on errors
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
     * @return $this
     *
     * @throws DatabaseException on errors
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
     * @throws DatabaseException on errors
     */
    public function executeRaw($sql) {
        Assert::string($sql);
        if (!$this->isConnected())
            $this->connect();

        /** @var resource $result */
        $result = null;

        // execute statement
        $ex = null;
        try {
            $result = pg_query($this->hConnection, $sql);         // wraps multi-statement queries in a transaction
            if (!$result) throw new DatabaseException(pg_last_error($this->hConnection));
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }   // @phpstan-ignore-line

        if ($ex) throw $ex->addMessage('Database: '.$this->getConnectionDescription().NL.'SQL: "'.$sql.'"');

        /** @var string $status */
        $status = pg_result_status($result, PGSQL_STATUS_STRING);

        // reset last_insert_id on INSERTs, afterwards it's resolved on request as it requires an extra SQL query
        if (strStartsWithI($status, 'INSERT ')) {
            $this->lastInsertId = null;
        }

        // track last_affected_rows
        $pattern = '/^(INSERT|UPDATE|DELETE)\b/i';
        if (preg_match($pattern, $status))
            $this->lastAffectedRows = pg_affected_rows($result);

        return $result;
    }


    /**
     * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function rollback() {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if (!$this->isConnected()) {
            trigger_error('Not connected', E_USER_WARNING);
        }
        else if (!$this->transactionLevel) {
            //trigger_error('No database transaction to roll back', E_USER_WARNING);
        }
        else {
            if ($this->transactionLevel == 1)
                $this->execute('rollback');
            $this->transactionLevel--;
        }
        return $this;
    }


    /**
     * Whether the connection currently is in a transaction.
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
        if (!isset($this->lastInsertId)) {
            $version = $this->getVersionNumber();

            if ($version < 8001000) {              // 8.1
                $this->lastInsertId = -1;
            }
            else {
                $ex = null;
                try {
                    $this->lastInsertId = $this->query('select lastVal()')->fetchInt();
                }
                catch (\Throwable $ex) {}
                catch (\Exception $ex) {}   // @phpstan-ignore-line

                if ($ex) {
                    if (stripos($ex->getMessage(), 'ERROR:  lastval is not yet defined in this session') === false)
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
     * Whether the DBMS's SQL dialect supports 'insert into ... returning ...' syntax.
     *
     * @return bool
     */
    public function supportsInsertReturn() {
        return true;
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
        if ($this->versionString === null) {
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
        if ($this->versionNumber === null) {
            $version = $this->getVersionString();

            $match = null;
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

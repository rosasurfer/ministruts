<?php
namespace rosasurfer\db\pgsql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strContains;
use function rosasurfer\strStartsWithI;

use const rosasurfer\NL;


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


    /**
     * Return a textual description of the main database connection options.
     *
     * @return string
     */
    protected function getConnectionDescription() {
        $options = $this->options;

        if (is_resource($this->hConnection)) {
            $host = pg_host($this->hConnection);
            $port = pg_port($this->hConnection);
            $db   = pg_dbname($this->hConnection);
            $path = $this->query('show search_path')->fetchString();

            if (strContains($path, '"$user"') && isSet($options['user'])) {
                $path = str_replace('"$user"', $options['user'], $path);
            }
            $description = $host.':'.$port.'/'.$db.' (schema search path: '.$path.')';
        }
        else {
            if      (isSet($options['hostaddr'])) $host = $options['hostaddr'];
            else if (isSet($options['host'    ])) $host = $options['host'    ];
            else                                  $host = '';

            if (isSet($options['port'])) $port = ':'.$options['port'];
            else                         $port = '';

            if      (isSet($options['dbname'])) $db = $options['dbname'];
            else if (isSet($options['user'  ])) $db = $options['user'  ];
            else                                $db = '';

            $description = $host.$port.'/'.$db;
        }
        return $description;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return $this
     */
    public function connect() {
        $connStr = $this->getConnectionString();
        try {
            $php_errormsg = '';
            $this->hConnection = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
            !$this->hConnection && trigger_error($php_errormsg, E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Cannot connect to PostgreSQL server with connection string: "'.$connStr.'"');
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
     * Whether or not the adapter currently is connected to the database.
     *
     * @return bool
     */
    public function isConnected() {
        return is_resource($this->hConnection);
    }


    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));

        if (!$this->isConnected())
            $this->connect();
        return pg_escape_identifier($this->hConnection, $name);
    }


    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($value) {
        // bug or feature: pg_escape_literal(null) => '' quoted empty string instead of 'null'
        if ($value === null)  return 'null';

        if (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        if (is_bool ($value)) return (string)(int) $value;
        if (is_int  ($value)) return (string)      $value;
        if (is_float($value)) return (string)      $value;

        if (!$this->isConnected())
            $this->connect();
        return pg_escape_literal($this->hConnection, $value);
    }


    /**
     * {@inheritdoc}
     */
    public function escapeString($value) {
        // bug or feature: pg_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return null;
        if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

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
     * @return $this
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
            throw $ex->addMessage('Database: '.$this->getConnectionDescription().NL.'SQL: "'.$sql.'"');
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
                    $this->lastInsertId = $this->query('select lastVal()')->fetchInt();
                }
                catch (\Exception $ex) {
                    if (striPos($ex->getMessage(), 'ERROR:  lastval is not yet defined in this session') === false)
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
     * Whether or not the DBMS's SQL dialect supports 'insert into ... returning ...' syntax.
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

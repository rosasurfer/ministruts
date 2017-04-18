<?php
namespace rosasurfer\db\mysql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strContains;

use const rosasurfer\NL;


/**
 * MySQLConnector
 *
 * Connection configuration:
 * <pre>
 * +-----------------------------+-----------------+---------------------------------+
 * | setting                     | value           | default value                   |
 * +-----------------------------+-----------------+---------------------------------+
 * | db.{name}.connector         | mysql           | -                               |
 * | db.{name}.host              | [host[:port]]   | localhost:3306                  |
 * | db.{name}.username          | [user]          | the current system user         |
 * | db.{name}.password          | [password]      | (no password)                   |
 * | db.{name}.database          | [dbName]        | (no selection)                  |
 * | db.{name}.options.charset   | [charsetName]   | utf8                            |
 * | db.{name}.options.collation | [collationName] | utf8_unicode_ci                 |
 * | db.{name}.options.sql_mode  | [mode]          | traditional,high_not_precedence |
 * | db.{name}.options.timezone  | [tzName]        | the current local timezone      |
 * +-----------------------------+-----------------+---------------------------------+
 * </pre>
 *
 * Additional MySQL options may be specified under the "options" key.
 */
class MySQLConnector extends Connector {


    /** @var string - DBMS type */
    protected $type = 'mysql';

    /** @var string - DBMS version string */
    protected $versionString;

    /** @var int - DBMS version number */
    protected $versionNumber;

    /** @var string */
    protected $host;

    /** @var int */
    protected $port;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $database;

    /** @var string[] - configuration options */
    protected $options = [];

    /** @var resource - internal connection handle */
    protected $hConnection;

    /** @var int - transaction nesting level */
    protected $transactionLevel = 0;

    /** @var int - the last inserted row id (not reset between queries) */
    protected $lastInsertId = 0;

    /** @var int - the last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;


    /**
     * Constructor
     *
     * Create a new MySQLConnector instance.
     *
     * @param  array $options - MySQL typical configuration options
     */
    public function __construct(array $options) {
        if (isSet($options['host'    ])) $this->setHost    ($options['host'    ]);
        if (isSet($options['username'])) $this->setUsername($options['username']);
        if (isSet($options['password'])) $this->setPassword($options['password']);
        if (isSet($options['database'])) $this->setDatabase($options['database']);
        if (isSet($options['options' ])) $this->setOptions ($options['options' ]);
    }


    /**
     * Set the database server's hostname, and port (if any).
     *
     * @param  string $hostname - format: "hostname[:port]"
     *
     * @return $this
     */
    protected function setHost($hostname) {
        if (!is_string($hostname)) throw new IllegalTypeException('Illegal type of parameter $hostname: '.getType($hostname));
        if (!strLen($hostname))    throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (empty)');

        $host = $hostname;
        $port = null;

        if (strPos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
            $host = trim($host);
            if (!strLen($host)) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (empty host name)');

            $port = trim($port);
            if (!ctype_digit($port)) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (not a port)');
            $port = (int) $port;
            if (!$port || $port > 65535) throw new InvalidArgumentException('Invalid parameter $hostname: "'.$hostname.'" (illegal port)');
        }

        $this->host = $host;
        $this->port = $port;
        return $this;
    }


    /**
     * Set the username for the connection.
     *
     * @param  string $name
     *
     * @return $this
     */
    protected function setUsername($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))    throw new InvalidArgumentException('Invalid parameter $name: "'.$name.'" (empty)');

        $this->username = $name;
        return $this;
    }


    /**
     * Set the password for the connection (if any).
     *
     * @param  string $password - may be empty or NULL (no password)
     *
     * @return $this
     */
    protected function setPassword($password) {
        if (is_null($password)) $password = '';
        else if (!is_string($password)) throw new IllegalTypeException('Illegal type of parameter $password: '.getType($password));

        $this->password = $password;
        return $this;
    }


    /**
     * Set the name of the default database schema to use.
     *
     * @param  string $name - schema name
     *
     * @return $this
     */
    protected function setDatabase($name) {
        if (!is_null($name) && !is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
        if (!strLen($name))
            $name = null;

        $this->database = $name;
        return $this;
    }


    /**
     * Set additonal connection options.
     *
     * @param  string[] $options
     *
     * @return $this
     */
    protected function setOptions(array $options) {
        // Here we might filter for known options.
        $this->options = $options;
        return $this;
    }


    /**
     * Return a textual description of the main database connection options.
     *
     * @return string
     */
    protected function getConnectionDescription() {
        $host = $this->host;
        $port = $this->port     ? ':'.$this->port     : '';
        $db   = $this->database ? '/'.$this->database : '';
        return $host.$port.$db;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return $this
     */
    public function connect() {
        $host = $this->host; if ($this->port) $host .= ':'.$this->port;
        $user = $this->username;
        $pass = $this->password;

        // connect
        try {                                                                   // CLIENT_FOUND_ROWS
            $php_errormsg = '';
            /** @var resource $hConnection */
            $hConnection = mysql_connect($host, $user, $pass, $newLink=true/*, $flags=2 */);
            !$hConnection && trigger_error($php_errormsg, E_USER_ERROR);
            $this->hConnection = $hConnection;
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Can not connect to MySQL server on "'.$host.'"');
        }

        $this->setConnectionOptions();
        $this->selectDatabase();
        return $this;
    }


    /**
     * Set the configured connection options.
     *
     * @return $this
     */
    protected function setConnectionOptions() {
        try {
            $options = $this->options;
            $options['time_zone'] = date_default_timezone_get();    // synchronize connection timezone with PHP timezone

            $value = null;
            foreach ($options as $option => $value) {
                if (strLen($value)) {
                    if (strToLower($option) == 'charset') {
                        // use mysql-function instead of SQL "set character set {$value}" for valid mysql_real_escape_string()
                        mysql_set_charset($value, $this->hConnection) || trigger_error(mysql_error($this->hConnection), E_USER_ERROR);
                    }
                    else {
                        if (!is_numeric($value))
                            $value = "'".$value."'";
                        $this->execute('set '.$option.' = '.$value);
                    }
                }
            }
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Can not set system variable "'.$value.'"')->setCode(mysql_errno($this->hConnection));
        }
        return $this;
    }


    /**
     * Pre-select a configured database.
     *
     * @return $this
     */
    protected function selectDatabase() {
        if ($this->database !== null) {
            try {
                mysql_select_db($this->database, $this->hConnection) || trigger_error(mysql_error($this->hConnection), E_USER_ERROR);
            }
            catch (IRosasurferException $ex) {
                throw $ex->addMessage('Can not select database "'.$this->database.'"')->setCode(mysql_errno($this->hConnection));
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
            mysql_close($tmp);
        }
        return $this;
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

        if (strContains($name, '.')) {
            $names = explode('.', $name);

            foreach ($names as &$subname) {
                $subname = '`'.str_replace('`', '``', $subname).'`';
            }; unset($subname);

            return join('.', $names);
        }
        return '`'.str_replace('`', '``', $name).'`';
    }


    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($value) {
        // bug or feature: mysql_real_escape_string(null) => empty string instead of NULL
        if ($value === null)  return 'null';

        if (!is_scalar($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        if (is_bool ($value)) return (string)(int) $value;
        if (is_int  ($value)) return (string)      $value;
        if (is_float($value)) return (string)      $value;

        $escaped = $this->escapeString($value);
        return "'".$escaped."'";
    }


    /**
     * {@inheritdoc}
     */
    public function escapeString($value) {
        // bug or or feature: mysql_real_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return null;
        if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

        if (!$this->isConnected())
            $this->connect();
        return mysql_real_escape_string($value, $this->hConnection);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return MySQLResult
     *
     * @throws DatabaseException in case of failure
     */
    public function query($sql) {
        $result = $this->executeRaw($sql);
        if (!is_resource($result))
            $result = null;
        return new MySQLResult($this, $sql, $result, $this->lastInsertId(), $this->lastAffectedRows());
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
        //
        // TODO: check mysql_unbuffered_query() with mysql_free_result() for larger result sets
        //       @see  https://www.percona.com/blog/2006/06/26/handling-of-big-parts-of-data/
        //       @see  https://dev.mysql.com/doc/refman/5.7/en/mysql-use-result.html

        $result = $this->executeRaw($sql);
        if (is_resource($result))
            mysql_free_result($result);               // release the result
        return $this;
    }


    /**
     * Execute a SQL statement and return the internal driver's raw response.
     *
     * @param  string $sql - SQL statement
     *
     * @return resource|bool - result handle or boolean (depending on the statement type)
     *
     * @throws DatabaseException in case of failure
     */
    public function executeRaw($sql) {
        if (!is_string($sql)) throw new IllegalTypeException('Illegal type of parameter $sql: '.getType($sql));
        if (!$this->isConnected())
            $this->connect();

        // execute statement
        try {
            $result = mysql_query($sql, $this->hConnection);
            $result || trigger_error('SQL-Error '.mysql_errno($this->hConnection).': '.mysql_error($this->hConnection), E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Database: '.$this->getConnectionDescription().NL.'SQL: "'.$sql.'"')->setCode(mysql_errno($this->hConnection));
        }

        $affected = 0;

        // track last_insert_id
        $id = mysql_insert_id($this->hConnection);
        if ($id) $this->lastInsertId = $id + ($affected=mysql_affected_rows($this->hConnection)) - 1;

        // track last_affected_rows
        if (!is_resource($result)) {                                               // a row returning statement never modifies rows
            $version = $this->getVersionNumber();
            if ($version < 5005005) $pattern = '/^\s*(INSERT|UPDATE|DELETE)\b/i';   // < 5.5.5.
            else                    $pattern = '/^\s*(INSERT|UPDATE|DELETE|ALTER\s+TABLE|LOAD\s+DATA\s+INFILE)\b/i';
            if (preg_match($pattern, $sql)) {
                if (!$id) $affected = mysql_affected_rows($this->hConnection);
                $this->lastAffectedRows = $affected;
            }
        }
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
            $this->execute('start transaction');

        $this->transactionLevel++;
        return $this;
    }


    /**
     * Commit an active transaction. If a nested transaction is active only the transaction nesting level is decreased.
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
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement. The value is not reset between queries
     * (see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     *
     * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastInsertId() {
        return (int) $this->lastInsertId;
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement. Since MySQL 5.5.5 this value also
     * includes rows affected by ALTER TABLE and LOAD DATA INFILE statements. The value is not reset between queries (see the
     * db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     *
     * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
     * @link   http://dev.mysql.com/doc/refman/5.5/en/information-functions.html#function_row-count
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
        return false;
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
     * @return string - e.g. "5.0.37-community-log"
     */
    public function getVersionString() {
        if ($this->versionString === null) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionString = mysql_get_server_info($this->hConnection);
        }
        return $this->versionString;
    }


    /**
     * Return the version ID of the DBMS the connector is used for as an integer.
     *
     * @return int - e.g. 5000037 for version string "5.0.37-community-log"
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

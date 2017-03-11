<?php
namespace rosasurfer\db\mysql;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\strContains;


/**
 * MySQLConnector
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    protected function setOptions(array $options) {
        // Here we might filter for known options.
        $this->options = $options;
        return $this;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return self
     */
    public function connect() {
        $host = $this->host; if ($this->port) $host .= ':'.$this->port;
        $user = $this->username;
        $pass = $this->password;

        // connect
        try {                                                                   // CLIENT_FOUND_ROWS
            $this->hConnection = mysql_connect($host, $user, $pass, $newLink=true/*, $flags=2 */);
            !$this->hConnection && trigger_error(@$php_errormsg, E_USER_ERROR);
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
     * @return self
     */
    protected function setConnectionOptions() {
        try {
            $options = $this->options;
            $options['time_zone'] = date_default_timezone_get();    // synchronize connection timezone with PHP timezone

            foreach ($options as $option => $value) {
                if (strLen($value)) {
                    if (strToLower($option) == 'charset') {
                        // use mysql-function instead of SQL "set character set {$value}" for valid mysql_real_escape_string()
                        mysql_set_charset($value, $this->hConnection) || trigger_error(mysql_error($this->hConnection), E_USER_ERROR);
                    }
                    else {
                        if (!is_numeric($value))
                            $value = "'".$value."'";
                        $sql = 'set '.$option.' = '.$value;
                        $this->execute($sql) || trigger_error(mysql_error($this->hConnection), E_USER_ERROR);
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
     * @return self
     */
    protected function selectDatabase() {
        if ($this->database) {
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
     * @return self
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
     * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
     * can be used in queries "as-is" and doesn't need additional quoting.
     *
     * MySQL: = `{$name}`.`{$subname}`
     *
     * @param  string $name - identifier to escape
     *
     * @return string - escaped and quoted identifier
     */
    public function escapeIdentifier($name) {
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
     * Escape a DBMS string literal, i.e. a string value. The resulting string can be used in queries "as-is" and doesn't
     * need additional quoting.
     *
     * MySQL: = '{escapeString($value)}'
     *
     * @param  scalar $value - value to escape
     *
     * @return scalar - escaped and quoted string or scalar value if the value was not a string
     */
    public function escapeLiteral($value) {
        // bug or feature: mysql_real_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return 'null';

        if (is_int($value) || is_float($value))
            return (string) $value;

        $escaped = $this->escapeString($value);
        return "'".$escaped."'";
    }


    /**
     * Escape a string value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * MySQL: = addSlashes($value, ['\', "'", '"'])
     *
     * @param  scalar $value - value to escape
     *
     * @return string|null - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        // bug or or feature: mysql_real_escape_string(null) => empty string instead of NULL
        if ($value === null)
            return null;

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
     * @return self
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
        $result = null;
        try {
            $result = mysql_query($sql, $this->hConnection);
            $result || trigger_error('SQL-Error '.mysql_errno($this->hConnection).': '.mysql_error($this->hConnection), E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('SQL: "'.$sql.'"')->setCode(mysql_errno($this->hConnection));
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
    /*
    $db->query("drop table if exists t_test");
    $db->query("create table t_test (id int auto_increment primary key, name varchar(100) not null)");
    $db->query("insert into t_test (name) values ('a'), ('b'), ('c'), ('123')");
    $db->query("set @a = 5");
    $db->query("explain select count(*) from t_test");
    $db->query("update t_test set name='c' where name in ('c')");
    $db->query("select * from t_test where name in ('a','b')");
    $db->query("select * from t_test where name in ('a','b') limit 1");
    $db->query("update t_test set name='aa' where name in ('c')");
    $db->query("select * from t_test where name = 'no-one'");
    $db->query("select count(*) from t_test");
    $db->query("delete from t_test where name = 'a' or name = 'b'");
    $db->query("select count(*) from t_test");
    $db->query("insert into t_test (name) values ('y'), ('z')");
    $db->query("insert into t_test (name) values ('x')");
    $db->query("explain select count(*) from t_test");
    $db->query("select * from t_test");
    $db->query("select * from t_test where name = 'no-one'");

    $result  = $db->query($sql);
    $handler = $db->getInternalHandler();
    echoPre(str_pad(explode(' ', $sql, 2)[0].':', 9).'  insert_id='.mysql_insert_id($handler).'  lastInsertID='.$db->lastInsertId().'  affected_rows='.mysql_affected_rows($handler).'  lastAffectedRows='.$db->lastAffectedRows().'  result='.($result->getInternalResult() ? 'resource' : '        ').'  num_rows='.($result->getInternalResult() ? mysql_num_rows($result->getInternalResult()) : 0).'  info='.mysql_info($handler));

    drop:      insert_id=0  lastInsertID=0  affected_rows=0  lastAffectedRows=0  result=          num_rows=0  info=
    create:    insert_id=0  lastInsertID=0  affected_rows=0  lastAffectedRows=0  result=          num_rows=0  info=
    insert:    insert_id=1  lastInsertID=4  affected_rows=4  lastAffectedRows=4  result=          num_rows=0  info=Records: 4  Duplicates: 0  Warnings: 0
    set:       insert_id=0  lastInsertID=4  affected_rows=0  lastAffectedRows=4  result=          num_rows=0  info=
    explain:   insert_id=0  lastInsertID=4  affected_rows=1  lastAffectedRows=4  result=resource  num_rows=1  info=
    update:    insert_id=0  lastInsertID=4  affected_rows=0  lastAffectedRows=0  result=          num_rows=0  info=Rows matched: 1  Changed: 0  Warnings: 0
    select:    insert_id=0  lastInsertID=4  affected_rows=2  lastAffectedRows=0  result=resource  num_rows=2  info=
    select:    insert_id=0  lastInsertID=4  affected_rows=1  lastAffectedRows=0  result=resource  num_rows=1  info=
    update:    insert_id=0  lastInsertID=4  affected_rows=1  lastAffectedRows=1  result=          num_rows=0  info=Rows matched: 1  Changed: 1  Warnings: 0
    select:    insert_id=0  lastInsertID=4  affected_rows=0  lastAffectedRows=1  result=resource  num_rows=0  info=
    select:    insert_id=0  lastInsertID=4  affected_rows=1  lastAffectedRows=1  result=resource  num_rows=1  info=
    delete:    insert_id=0  lastInsertID=4  affected_rows=2  lastAffectedRows=2  result=          num_rows=0  info=
    select:    insert_id=0  lastInsertID=4  affected_rows=1  lastAffectedRows=2  result=resource  num_rows=1  info=
    insert:    insert_id=5  lastInsertID=6  affected_rows=2  lastAffectedRows=2  result=          num_rows=0  info=Records: 2  Duplicates: 0  Warnings: 0
    insert:    insert_id=7  lastInsertID=7  affected_rows=1  lastAffectedRows=1  result=          num_rows=0  info=
    explain:   insert_id=0  lastInsertID=7  affected_rows=1  lastAffectedRows=1  result=resource  num_rows=1  info=
    select:    insert_id=0  lastInsertID=7  affected_rows=5  lastAffectedRows=1  result=resource  num_rows=5  info=
    select:    insert_id=0  lastInsertID=7  affected_rows=0  lastAffectedRows=1  result=resource  num_rows=0  info=
   */


    /**
     * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
     *
     * @return self
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
        if (is_null($this->versionString)) {
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

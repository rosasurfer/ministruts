<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\WINDOWS;


/**
 * SQLiteConnector
 *
 * Connector for SQLite/SQLite3 databases. Supported configuration values (see constructor):
 *
 *  "file"   The database file to connect to. A relative location is resolved relative to the application's root directory
 *           as defined by APPLICATION_ROOT. By default the file is opened in mode SQLITE3_OPEN_READWRITE.
 *
 * Note:
 * -----
 * The php_sqlite3 extension v0.7-dev is broken. The first initial call of SQLite3Result::fetchArray() and calls after a
 * SQLite3Result::reset() trigger the re-execution of an already executed query. The workaround for DDL and DML statements
 * is to check with SQLite3Result::numColumns() for an empty result before calling fetchArray(). There is no workaround to
 * prevent multiple executions of SELECT queries except of using the PDO adapter.
 *
 * @see  http://bugs.php.net/bug.php?id=64531
 */
class SQLiteConnector extends Connector {


    /** @var string - DBMS type */
    protected $type = 'sqlite';

    /** @var string - DBMS version string */
    protected $versionString;

    /** @var int - DBMS version number */
    protected $versionNumber;

    /** @var string - database file to connect to */
    protected $file;

    /** @var string[] - configuration options */
    protected $options = [];

    /** @var \SQLite3 - internal database handler instance */
    protected $handler;

    /** @var int - transaction nesting level */
    protected $transactionLevel = 0;

    /** @var int - the last inserted row id (not reset between queries) */
    protected $lastInsertId = 0;

    /** @var int - the last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;

    /** @var bool - whether or not a query to execute can skip results */
    private $skipResults = false;


    /**
     * Constructor
     *
     * Create a new SQLiteConnector instance.
     *
     * @param  array $options - SQLite typical configuration options. See the class description for supported values.
     */
    public function __construct(array $options) {
        if (isSet($options['file'])) $this->setFile($options['file']);
        $this->setOptions($options);
    }


    /**
     * Set the file name of the database to connect to. May be ":memory:" to use an in-memory database.
     *
     * @param  string $file
     *
     * @return self
     */
    protected function setFile($file) {
        if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $file: '.getType($file));
        if (!strLen($file))    throw new InvalidArgumentException('Invalid parameter $file: "'.$file.'" (empty)');

        $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $file) : ($file[0]!='/');
        $relativePath && $file=APPLICATION_ROOT.DIRECTORY_SEPARATOR.$file;

        $this->file = $file;
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
        $this->options = $options;
        return $this;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return self
     */
    public function connect() {
        try {                                                                // available flags:
            $flags = SQLITE3_OPEN_READWRITE;                                  // SQLITE3_OPEN_CREATE
            $handler = new \SQLite3($this->file, $flags);                     // SQLITE3_OPEN_READONLY
            !$handler && trigger_error(@$php_errormsg, E_USER_ERROR);         // SQLITE3_OPEN_READWRITE
        }
        catch (IRosasurferException $ex) {
            $file = $this->file;
            $what = $where = null;
            if (file_exists($file)) {
                $what = 'open';
                if (is_dir($file=realPath($file)))
                    $where = ' (directory)';
            }
            else {
                $what = ($flags & SQLITE3_OPEN_CREATE) ? 'create':'find';
                if      ( WINDOWS && preg_match('/^[a-z]:/i', $file)) $absolutePath = true;
                else if (!WINDOWS && $file[0]=='/')                   $absolutePath = true;
                else                                                  $absolutePath = false;
                if (!$absolutePath) $where = ' in "'.getCwd().'"';
            }
            throw $ex->addMessage('Cannot '.$what.' SQLite database file "'.$file.'"'.$where);
        }
        $this->handler = $handler;
    }


    /**
     * Disconnect the adapter from the database.
     *
     * @return self
     */
    public function disconnect() {
        if ($this->isConnected()) {
            $handler = $this->handler;
            $this->handler = null;
            $handler->close();
        }
        return $this;
    }


    /**
     * Whether or not the adapter currently is connected to the database.
     *
     * @return bool
     */
    public function isConnected() {
        return is_object($this->handler);
    }


    /**
     * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
     * can be used in queries "as-is" and doesn't need additional quoting.
     *
     * @param  string $name - identifier to escape
     *
     * @return string - escaped and quoted identifier; SQLite:  "{$name}"
     */
    public function escapeIdentifier($name) {
        return '"'.str_replace('"', '""', $name).'"';
    }


    /**
     * Escape a DBMS string literal, i.e. a string value. The resulting string can be used in queries "as-is" and doesn't
     * need additional quoting.
     *
     * SQLite: = '{$this->escapeString($value)}'
     *
     * @param  scalar $value - value to escape
     *
     * @return scalar - escaped and quoted string or scalar value if the value was not a string
     */
    public function escapeLiteral($value) {
        // bug: SQLite3::escapeString(null) => empty string instead of NULL
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
     * SQLite: = escape($value, $chars=["'"], $escape_character="'")
     *
     * @param  scalar $value - value to escape
     *
     * @return string|null - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        // bug or feature: SQLite3::escapeString(null) => empty string instead of NULL
        if ($value === null)
            return null;

        if (!$this->isConnected())
            $this->connect();
        return $this->handler->escapeString($value);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return SQLiteResult
     *
     * @throws DatabaseException in case of failure
     */
    public function query($sql) {
        try {
            $lastExecMode = $this->skipResults;
            $this->skipResults = false;

            $result = $this->executeRaw($sql);
            return new SQLiteResult($this, $sql, $result, $this->lastInsertId(), $this->lastAffectedRows());
        }
        finally {
            $this->skipResults = $lastExecMode;
        }
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
        try {
            $lastExecMode = $this->skipResults;
            $this->skipResults = true;

            $result = $this->executeRaw($sql);              // In contrast to the documentation SQLite3::exec() and therefore
            $result->finalize();                            // self::executeRaw() always return a SQLite3Result, never a boolean.
                                                            // Thus it is safe to unconditionally call finalize().
            return $this;
        }
        finally {
            $this->skipResults = $lastExecMode;
        }
    }


    /**
     * Execute a SQL statement and return the internal driver's raw response.
     *
     * @param  string $sql - SQL statement
     *
     * @return \SQLite3Result
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
            if ($this->skipResults) $result = $this->handler->exec($sql);     // TRUE on success, FALSE on error
            else                    $result = $this->handler->query($sql);    // bug: always SQLite3Result, never boolean
            $result || trigger_error('Error '.$this->handler->lastErrorCode().', '.$this->handler->lastErrorMsg(), E_USER_ERROR);
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('SQL: "'.$sql.'"');
        }

        // track last_insert_id
        $this->lastInsertId = $this->handler->lastInsertRowID();

        // track last_affected_rows
        $this->lastAffectedRows = $this->handler->changes();

        return $result;
    }
    /*
    $db->query("drop table if exists t_test");
    $db->query("create table t_test (id integer primary key, name varchar(100) not null)");
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
    echoPre(str_pad(explode(' ', $sql, 2)[0].':', 9).'  lastInsertRowID='.$handler->lastInsertRowID().'  lastInsertId='.$db->lastInsertId().'  changes='.$handler->changes().'  lastAffectedRows='.$db->lastAffectedRows().'  total_changes='.$db->query('select total_changes()')->fetchInt().'  result='.(is_object($result->getInternalResult()) ? 'object':'      ').'  numRows='.$result->numRows());

    drop:      lastInsertRowID=0  lastInsertId=0  changes=0  lastAffectedRows=0  total_changes=0  result=        num_rows=0
    create:    lastInsertRowID=0  lastInsertId=0  changes=0  lastAffectedRows=0  total_changes=0  result=        num_rows=0
    insert:    lastInsertRowID=4  lastInsertId=4  changes=4  lastAffectedRows=4  total_changes=4  result=        num_rows=0
    explain:   lastInsertRowID=4  lastInsertId=4  changes=4  lastAffectedRows=4  total_changes=4  result=object  num_rows=10
    update:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=5  result=        num_rows=0
    select:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=5  result=object  num_rows=2
    select:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=5  result=object  num_rows=1
    update:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=6  result=        num_rows=0
    select:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=6  result=object  num_rows=0
    select:    lastInsertRowID=4  lastInsertId=4  changes=1  lastAffectedRows=1  total_changes=6  result=object  num_rows=1
    delete:    lastInsertRowID=4  lastInsertId=4  changes=2  lastAffectedRows=2  total_changes=8  result=        num_rows=0
    select:    lastInsertRowID=4  lastInsertId=4  changes=2  lastAffectedRows=2  total_changes=8  result=object  num_rows=1
    insert:    lastInsertRowID=6  lastInsertId=6  changes=2  lastAffectedRows=2  total_changes=10  result=        num_rows=0
    insert:    lastInsertRowID=7  lastInsertId=7  changes=1  lastAffectedRows=1  total_changes=11  result=        num_rows=0
    explain:   lastInsertRowID=7  lastInsertId=7  changes=1  lastAffectedRows=1  total_changes=11  result=object  num_rows=10
    select:    lastInsertRowID=7  lastInsertId=7  changes=1  lastAffectedRows=1  total_changes=11  result=object  num_rows=5
    select:    lastInsertRowID=7  lastInsertId=7  changes=1  lastAffectedRows=1  total_changes=11  result=object  num_rows=0
   */


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
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement. The value is not reset between queries.
     * (see the db README)
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     *
     * @link   http://github.com/rosasurfer/ministruts/tree/master/src/db
     */
    public function lastInsertId() {
        return (int) $this->lastInsertId;
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
     * @return \SQLite3 - the internal connection handler
     */
    public function getInternalHandler() {
        if (!$this->isConnected())
            $this->connect();
        return $this->handler;
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
     * @return string - e.g. "3.5.9-rc"
     */
    public function getVersionString() {
        if (is_null($this->versionString)) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionString = $this->handler->version()['versionString'];
        }
        return $this->versionString;
    }


    /**
     * Return the version ID of the DBMS the connector is used for as an integer.
     *
     * @return int - e.g. 3005009 for version string "3.5.9-rc"
     */
    public function getVersionNumber() {
        if (is_null($this->versionNumber)) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionNumber = $this->handler->version()['versionNumber'];
        }
        return $this->versionNumber;
    }
}

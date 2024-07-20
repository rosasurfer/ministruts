<?php
namespace rosasurfer\db\sqlite;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\db\Connector;
use rosasurfer\db\DatabaseException;

use function rosasurfer\isRelativePath;

use const rosasurfer\NL;


/**
 * SQLiteConnector
 *
 * Connector configuration:
 * <pre>
 *  +--------------------------------------+-------------+----------------------------+
 *  | setting                              | value       | default value              |
 *  +--------------------------------------+-------------+----------------------------+
 *  | db.{name}.connector                  | sqlite      | -                          |
 *  | db.{name}.file                       | db-filename | - (1)                      |
 *  | db.{name}.options.open_mode          | [openMode]  | SQLITE3_OPEN_READWRITE (2) |
 *  | db.{name}.options.foreign_keys       | [on|off]    | on                         |
 *  | db.{name}.options.recursive_triggers | [on|off]    | on                         |
 *  +--------------------------------------+-------------+----------------------------+
 * </pre>
 *  (1) - A relative db file location is interpreted as relative to <tt>Config["app.dir.root"]</tt>.                        <br>
 *  (2) - Available flags: SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READONLY | SQLITE3_OPEN_READWRITE                             <br>
 *
 * Additional SQLite pragma options can be specified under the "options" key.
 *
 *
 * Notes:                                                                                                                   <br>
 * ------                                                                                                                   <br>
 * The php_sqlite3 module version v0.7-dev (at least PHP 5.6.12-5.6.40) has a bug. The first call of
 * {@link \SQLite3Result::fetchArray()} and calls after a {@link \SQLite3Result::reset()} trigger re-execution of an already
 * executed query. The workaround for DDL and DML statements is to check with {@link \SQLite3Result::numColumns()} for an
 * empty result before calling <tt>fetchArray()</tt>. There is <b>no</b> workaround to prevent multiple executions of SELECT
 * queries except using a different SQLite adapter, e.g. the PDO SQLite3 adapter.
 *
 * @see  http://bugs.php.net/bug.php?id=64531
 */
class SQLiteConnector extends Connector {


    /** @var string - DBMS type */
    protected $type = 'sqlite';

    /** @var ?string - DBMS version string */
    protected $versionString = null;

    /** @var ?int - DBMS version number */
    protected $versionNumber = null;

    /** @var string - database file to connect to */
    protected $file;

    /** @var string[] - configuration options */
    protected $options = [];

    /** @var ?\SQLite3 - internal database handler instance */
    protected $sqlite = null;

    /** @var int - transaction nesting level */
    protected $transactionLevel = 0;

    /** @var int - the last inserted row id (not reset between queries) */
    protected $lastInsertId = 0;

    /** @var int - the last number of affected rows (not reset between queries) */
    protected $lastAffectedRows = 0;

    /** @var bool - whether a query to execute can skip results */
    private $skipResults = false;


    /**
     * Constructor
     *
     * Create a new SQLiteConnector instance.
     *
     * @param  array $options - SQLite connection options. See the class description for supported values.
     */
    public function __construct(array $options) {
        if (isset($options['file'])) $this->setFile($options['file']);
        unset($options['file']);
        $this->setOptions($options);
    }


    /**
     * Set the file name of the database to connect to. May be ":memory:" to use an in-memory database.
     *
     * @param  string $file - A relative database file location is interpreted as relative to the application's storage
     *                        directory (if configured). If the file is not found an attempt is made to find it in the
     *                        application's root directory.
     * @return $this
     */
    protected function setFile($file) {
        Assert::string($file);
        if (!strlen($file)) throw new InvalidArgumentException('Invalid parameter $file: "'.$file.'" (empty)');

        if ($file == ':memory:' || !isRelativePath($file)) {
            $this->file = $file;
        }
        else {
            $rootDir = $this->di('config')['app.dir.root'];
            $this->file = str_replace('\\', '/', $rootDir.'/'.$file);
        }
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
        $this->options = $options;
        return $this;
    }


    /**
     * Connect the adapter to the database.
     *
     * @return $this
     */
    public function connect() {
        if (!class_exists('SQLite3')) throw new RuntimeException('Undefined class \SQLite3 (sqlite3 extension is not available)');

        $flags = SQLITE3_OPEN_READWRITE;                                    // available flags:
        $ex = null;                                                         // 1: SQLITE3_OPEN_READONLY
        try {                                                               // 2: SQLITE3_OPEN_READWRITE
            $this->sqlite = new \SQLite3($this->file, $flags);              // 4: SQLITE3_OPEN_CREATE
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }   // @phpstan-ignore-line

        if ($ex) {
            $file = $this->file;
            $what = $where = null;
            if (file_exists($file)) {
                $what = 'open';
                if (is_dir($file=realpath($file))) {
                    $where = ' (directory)';
                }
            }
            else {
                $what = ($flags & SQLITE3_OPEN_CREATE) ? 'create' : 'find'; // @phpstan-ignore-line
                isRelativePath($file) && $where=' in "'.getcwd().'"';
            }
            throw $ex->addMessage('Cannot '.$what.' database file "'.$file.'"'.$where);
        }

        $this->setConnectionOptions();
        return $this;
    }


    /**
     * Set the configured connection options.
     *
     * @return $this
     */
    protected function setConnectionOptions() {
        //$options = $this->options;
        //foreach ($this->options as $option => $value) {
        //    $this->execute('set '.$option.' = '.$value);
        //}

        $this->execute('pragma foreign_keys = on');
        $this->execute('pragma recursive_triggers = on');

        return $this;
    }


    /**
     * Disconnect the adapter from the database.
     *
     * @return $this
     */
    public function disconnect() {
        if ($this->isConnected()) {
            $sqlite = $this->sqlite;
            $this->sqlite = null;
            $sqlite->close();
        }
        return $this;
    }


    /**
     * Whether the adapter currently is connected to the database.
     *
     * @return bool
     */
    public function isConnected() {
        return is_object($this->sqlite);
    }


    /**
     *
     */
    public function escapeIdentifier($name) {
        Assert::string($name);
        return '"'.str_replace('"', '""', $name).'"';
    }


    /**
     *
     */
    public function escapeLiteral($value) {
        // bug or feature: SQLite3::escapeString(null) => empty string instead of NULL
        if ($value === null)  return 'null';
        Assert::scalar($value);

        if (is_bool ($value)) return (string)(int) $value;
        if (is_int  ($value)) return (string)      $value;
        if (is_float($value)) return (string)      $value;

        $escaped = $this->escapeString($value);
        return "'".$escaped."'";
    }


    /**
     *
     */
    public function escapeString($value) {
        // bug or feature: SQLite3::escapeString(null) => empty string instead of NULL
        if ($value === null)
            return null;
        Assert::string($value);

        if (!$this->isConnected())
            $this->connect();
        return $this->sqlite->escapeString($value);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return SQLiteResult
     *
     * @throws DatabaseException on errors
     */
    public function query($sql) {
        try {
            $lastExecMode = $this->skipResults;
            $this->skipResults = false;

            /** @var \SQLite3Result $result */
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
     * @return $this
     *
     * @throws DatabaseException on errors
     */
    public function execute($sql) {
        try {
            $lastExecMode = $this->skipResults;
            $this->skipResults = true;

            $this->executeRaw($sql);
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
     * @return \SQLite3Result|bool
     *
     * @throws DatabaseException on errors
     */
    public function executeRaw($sql) {
        Assert::string($sql);
        if (!$this->isConnected())
            $this->connect();

        // execute statement
        $result = $ex = false;
        try {
            if ($this->skipResults) $result = $this->sqlite->exec($sql);        // TRUE on success, FALSE on error
            else                    $result = $this->sqlite->query($sql);       // bug: always SQLite3Result, never boolean
            if (!$result) throw new DatabaseException('Error '.$this->sqlite->lastErrorCode().', '.$this->sqlite->lastErrorMsg());
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex); }   // @phpstan-ignore-line
        if ($ex) throw $ex->addMessage('Database: '.$this->file.NL.'SQL: "'.$sql.'"');

        // track last_insert_id
        $this->lastInsertId = $this->sqlite->lastInsertRowID();

        // track last_affected_rows
        $this->lastAffectedRows = $this->sqlite->changes();

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
     * Whether the DBMS's SQL dialect supports 'insert into ... returning ...' syntax.
     *
     * @return bool
     */
    public function supportsInsertReturn() {
        return false;
    }


    /**
     * Return this connector's internal SQLite3 connection object.
     *
     * @return \SQLite3 - the internal connection handler
     */
    public function getInternalHandler() {
        if (!$this->isConnected())
            $this->connect();
        return $this->sqlite;
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
        if (!isset($this->versionString)) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionString = $this->sqlite->version()['versionString'];
        }
        return $this->versionString;
    }


    /**
     * Return the version ID of the DBMS the connector is used for as an integer.
     *
     * @return int - e.g. 3005009 for version string "3.5.9-rc"
     */
    public function getVersionNumber() {
        if (!isset($this->versionNumber)) {
            if (!$this->isConnected())
                $this->connect();
            $this->versionNumber = $this->sqlite->version()['versionNumber'];
        }
        return $this->versionNumber;
    }
}

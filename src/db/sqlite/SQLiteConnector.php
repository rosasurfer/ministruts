<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\sqlite;

use SQLite3;
use SQLite3Result;
use Throwable;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\ExceptionInterface as RosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\db\Connector;
use rosasurfer\ministruts\db\DatabaseException;

use function rosasurfer\ministruts\isRelativePath;
use function rosasurfer\ministruts\realpath;

use const rosasurfer\ministruts\NL;

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
 *  (1) - A relative file path is interpreted relative to <tt>Config["app.dir.root"]</tt>.                                  <br>
 *  (2) - Available flags: SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READONLY | SQLITE3_OPEN_READWRITE                             <br>
 *
 * Additional SQLite pragma options can be specified under the "options" key.
 *
 *
 * Notes:                                                                                                                   <br>
 * ------                                                                                                                   <br>
 * The php_sqlite3 module version v0.7-dev (at least PHP 5.6.12-5.6.40) has a bug. The first call of
 * {@link SQLite3Result::fetchArray()} and calls after a {@link SQLite3Result::reset()} trigger re-execution of an already
 * executed query. The workaround for DDL and DML statements is to check with {@link SQLite3Result::numColumns()} for an
 * empty result before calling <tt>fetchArray()</tt>. There is <b>no</b> workaround to prevent multiple executions of SELECT
 * queries except using a different SQLite adapter, e.g. the PDO SQLite3 adapter.
 *
 * @see  https://bugs.php.net/bug.php?id=64531
 */
class SQLiteConnector extends Connector {

    /** @var string - DBMS type */
    protected string $type = 'sqlite';

    /** @var ?string - DBMS version string */
    protected ?string $versionString = null;

    /** @var ?int - DBMS version number */
    protected ?int $versionNumber = null;

    /** @var string - database file to connect to */
    protected string $file;

    /** @var array<string, string> - configuration options */
    protected array $options = [];

    /** @var ?SQLite3 - internal database handler instance */
    protected ?SQLite3 $sqlite = null;

    /** @var int - transaction nesting level */
    protected int $transactionLevel = 0;

    /** @var int - the last inserted row id (not reset between queries) */
    protected int $lastInsertId = 0;

    /** @var int - the last number of affected rows (not reset between queries) */
    protected int $lastAffectedRows = 0;

    /** @var bool - whether a query to execute can skip results */
    private bool $skipResults = false;


    /**
     * Constructor
     *
     * Create a new SQLiteConnector instance.
     *
     * @param  array<string, string> $options - SQLite connection options. See the class description for supported values.
     */
    public function __construct(array $options) {
        if (isset($options['file'])) {
            $this->setFile($options['file']);
            unset($options['file']);
        }
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
    protected function setFile(string $file): self {
        if (!strlen($file)) throw new InvalidValueException('Invalid parameter $file: "'.$file.'" (empty)');

        if ($file == ':memory:' || !isRelativePath($file)) {
            $this->file = $file;
        }
        else {
            /** @var Config $config */
            $config = $this->di('config');
            $rootDir = $config['app.dir.root'];
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
    protected function setOptions(array $options): self {
        $this->options = $options;
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function connect(): self {
        /** @var int $flags */
        $flags = SQLITE3_OPEN_READWRITE;                                        // available flags:
        try {                                                                   // 1: SQLITE3_OPEN_READONLY
            $this->sqlite = new SQLite3($this->file, $flags);                   // 2: SQLITE3_OPEN_READWRITE
        }                                                                       // 4: SQLITE3_OPEN_CREATE
        catch (Throwable $ex) {
            if (!$ex instanceof RosasurferException) {
                $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex);
            }
            $file = $this->file;
            $doWhat = $where = null;
            if (file_exists($file)) {
                $doWhat = 'open';
                if (is_dir($file = realpath($file))) {
                    $where = ' (directory)';
                }
            }
            else {
                $doWhat = $flags & SQLITE3_OPEN_CREATE ? 'create' : 'find';
                if (isRelativePath($file)) $where=' in "'.getcwd().'"';
            }
            throw $ex->appendMessage("Cannot $doWhat database file \"$file\"$where");
        }

        $this->setConnectionOptions();
        return $this;
    }


    /**
     * Set the configured connection options.
     *
     * @return $this
     */
    protected function setConnectionOptions(): self {
        //$options = $this->options;
        //foreach ($this->options as $option => $value) {
        //    $this->execute('set '.$option.' = '.$value);
        //}

        $this->execute('pragma foreign_keys = on');
        $this->execute('pragma recursive_triggers = on');

        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function disconnect(): self {
        if ($this->isConnected()) {
            /** @var SQLite3 $sqlite */
            $sqlite = $this->sqlite;
            $this->sqlite = null;
            $sqlite->close();
        }
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool {
        return $this->sqlite !== null;
    }


    /**
     * {@inheritDoc}
     */
    public function escapeIdentifier(string $name): string {
        return '"'.str_replace('"', '""', $name).'"';
    }


    /**
     * {@inheritDoc}
     */
    public function escapeLiteral($value): string {
        // bug or feature: SQLite3::escapeString(null) => empty string instead of NULL
        if ($value === null)  return 'null';
        if (is_bool ($value)) return (string)(int) $value;
        if (is_int  ($value)) return (string)      $value;
        if (is_float($value)) return (string)      $value;

        $escaped = $this->escapeString($value);
        return "'$escaped'";
    }


    /**
     * {@inheritDoc}
     */
    public function escapeString(?string $value): ?string {
        // bug or feature: SQLite3::escapeString(null) => empty string instead of NULL
        if ($value === null) {
            return null;
        }
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @var SQLite3 $sqlite */
        $sqlite = $this->sqlite;

        return $sqlite->escapeString($value);
    }


    /**
     * {@inheritDoc}
     */
    public function query($sql): SQLiteResult {
        try {
            $lastExecMode = $this->skipResults;
            $this->skipResults = false;

            /** @var SQLite3Result $result */
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
     */
    public function execute(string $sql): self {
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
     * {@inheritDoc}
     *
     * @return SQLite3Result|bool
     */
    public function executeRaw(string $sql) {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @var SQLite3 $sqlite */
        $sqlite = $this->sqlite;

        // execute statement
        $result = false;
        try {
            if ($this->skipResults) $result = $sqlite->exec($sql);      // TRUE on success, FALSE on error
            else                    $result = $sqlite->query($sql);     // bug: always SQLite3Result, never boolean
            if (!$result) throw new DatabaseException('Error '.$sqlite->lastErrorCode().', '.$sqlite->lastErrorMsg());
        }
        catch (Throwable $ex) {
            if (!$ex instanceof RosasurferException) {
                $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex);
            }
            throw $ex->appendMessage("Database: $this->file".NL."SQL: \"$sql\"");
        }

        // track last_insert_id
        $this->lastInsertId = $sqlite->lastInsertRowID();

        // track last_affected_rows
        $this->lastAffectedRows = $sqlite->changes();

        return $result;
    }


    /**
     * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
     *
     * @return $this
     */
    public function begin(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if (!$this->transactionLevel)
            $this->execute('begin');

        $this->transactionLevel++;
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function commit(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if     (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
        elseif (!$this->transactionLevel) trigger_error('No database transaction to commit', E_USER_WARNING);
        else {
            if ($this->transactionLevel == 1) {
                $this->execute('commit');
            }
            $this->transactionLevel--;
        }
        return $this;
    }


    /**
     * Roll back an active transaction. If a nested transaction is active only the nesting level is decreased.
     * If only one (the outer most) transaction is active the transaction is rolled back.
     *
     * @return $this
     */
    public function rollback(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if     (!$this->isConnected())    trigger_error('Not connected', E_USER_WARNING);
        elseif (!$this->transactionLevel) trigger_error('No database transaction to roll back', E_USER_WARNING);
        else {
            if ($this->transactionLevel == 1) {
                $this->execute('rollback');
            }
            $this->transactionLevel--;
        }
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function isInTransaction(): bool {
        if ($this->isConnected()) {
            return $this->transactionLevel > 0;
        }
        return false;
    }


    /**
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement.
     * The value is not reset between queries (see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     */
    public function lastInsertId(): int {
        return (int) $this->lastInsertId;
    }


    /**
     * Return the number of rows affected by the last INSERT, UPDATE or DELETE statement. For UPDATE and DELETE statements
     * this is the number of matched rows. The value is not reset between queries (see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session
     */
    public function lastAffectedRows(): int {
        return (int) $this->lastAffectedRows;
    }


    /**
     * {@inheritDoc}
     */
    public function supportsInsertReturn(): bool {
        return false;
    }


    /**
     * Return this connector's internal SQLite3 connection object.
     *
     * @return SQLite3 - the internal connection handler
     */
    public function getInternalHandler(): SQLite3 {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @var SQLite3 $sqlite */
        $sqlite = $this->sqlite;
        return $sqlite;
    }


    /**
     * {@inheritDoc}
     */
    public function getType(): string {
        return $this->type;
    }


    /**
     * {@inheritDoc}
     *
     * @return string - e.g. "3.5.9-rc"
     */
    public function getVersionString(): string {
        if (!isset($this->versionString)) {
            if (!$this->isConnected()) {
                $this->connect();
            }
            /** @var SQLite3 $sqlite */
            $sqlite = $this->sqlite;
            $this->versionString = $sqlite->version()['versionString'];
        }
        /** @var string $versionString */
        $versionString = $this->versionString;
        return $versionString;
    }


    /**
     * {@inheritDoc}
     *
     * @return int - e.g. 3005009 for version string "3.5.9-rc"
     */
    public function getVersionNumber(): int {
        if (!isset($this->versionNumber)) {
            if (!$this->isConnected()) {
                $this->connect();
            }
            /** @var SQLite3 $sqlite */
            $sqlite = $this->sqlite;
            $this->versionNumber = $sqlite->version()['versionNumber'];
        }
        /** @var int $versionNumber */
        $versionNumber = $this->versionNumber;
        return $versionNumber;
    }
}

<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\pgsql;

use Throwable;
use UnexpectedValueException;
use PgSql\Connection as PgSqlConnection;
use PgSql\Result as PgSqlResult;

use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\db\Connector;
use rosasurfer\ministruts\db\DatabaseException;

use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strStartsWithI;

use const rosasurfer\ministruts\NL;

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
 * @see  https://www.postgresql.org/docs/9.6/static/libpq-connect.html#LIBPQ-PARAMKEYWORDS)
 */
class PostgresConnector extends Connector {

    /** @var string - DBMS type */
    protected string $type = 'pgsql';

    /** @var ?string - DBMS version string */
    protected ?string $versionString = null;

    /** @var ?int - DBMS version number */
    protected ?int $versionNumber = null;

    /** @var mixed[] - connection options */
    protected array $options = [];

    /** @var string[] - session variables */
    protected array $sessionVars = [];

    /**
     * @var resource|PgSqlConnection|null - PostgreSQL connection handle
     * @phpstan-var PgSqlConnectionId|null
     */
    protected $connection = null;

    /** @var int - transaction nesting level */
    protected int $transactionLevel = 0;

    /** @var ?int - the last inserted row id (not reset between queries) */
    protected ?int $lastInsertId = null;    // distinguish between "not yet set" and "zero"

    /** @var int - the last number of affected rows (not reset between queries) */
    protected int $lastAffectedRows = 0;


    /**
     * Constructor
     *
     * @param  mixed[] $options - PostgreSQL connection options
     */
    public function __construct(array $options) {
        $this->setOptions($options);
    }


    /**
     * Set connection options.
     *
     * @param  mixed[] $options
     *
     * @return $this
     */
    protected function setOptions(array $options): self {
        $this->options = $options;
        return $this;
    }


    /**
     * Resolve and return the PostgreSQL connection string from the passed connection options.
     *
     * @return string
     */
    private function getConnectionString(): string {
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
    protected function getConnectionDescription(): string {
        $options = $this->options;

        if ($this->connection) {
            $host = pg_host($this->connection);
            $port = pg_port($this->connection);
            $db   = pg_dbname($this->connection);
            $path = $this->getSchemaSearchPath();
            $description = "$host:$port/$db (schema search path: $path)";
        }
        else {
            if     (isset($options['hostaddr'])) $host = $options['hostaddr'];
            elseif (isset($options['host'    ])) $host = $options['host'    ];
            else                                 $host = '';

            if (isset($options['port'])) $port = ":$options[port]";
            else                         $port = '';

            if     (isset($options['dbname'])) $db = $options['dbname'];
            elseif (isset($options['user'  ])) $db = $options['user'  ];
            else                               $db = '';

            $description = "$host$port/$db";
        }
        return $description;
    }


    /**
     * Return the database's current schema search path.
     *
     * @return ?string - schema search path or NULL in case of errors
     */
    protected function getSchemaSearchPath(): ?string {
        $options = $this->options;
        $path = null;

        while ($this->connection) {
            try {
                $result = pg_query($this->connection, 'show search_path');
                if (!$result) throw new DatabaseException(pg_last_error($this->connection));

                $row = pg_fetch_array($result, null, PGSQL_NUM);
                if (!$row) throw new DatabaseException(pg_last_error($this->connection));

                /** @var string $path */
                $path = $row[0];

                if (strContains($path, '"$user"') && isset($options['user'])) {
                    $path = str_replace('"$user"', $options['user'], $path);
                }
                break;
            }
            catch (Throwable $ex) {
                if (strContains($ex->getMessage(), 'current transaction is aborted, commands ignored until end of transaction block')) {
                    if ($this->transactionLevel > 0) {
                        $this->transactionLevel = 1;        // immediately skip nested transactions
                        $this->rollback();
                        continue;
                    }
                }
                throw $ex;
            }
        }
        return $path;
    }


    /**
     * {@inheritDoc}
     */
    public function connect(): self {
        $connStr = $this->getConnectionString();

        try {
            error_clear_last();
            $connection = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
            if (!$connection) throw new DatabaseException(error_get_last()['message'] ?? '');

            $this->connection = $connection;
        }
        catch (Throwable $ex) {
            if (!$ex instanceof IRosasurferException) {
                $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex);
            }
            throw $ex->appendMessage("Cannot connect to PostgreSQL server with connection string: \"$connStr\"");
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
     * {@inheritDoc}
     */
    public function disconnect(): self {
        if ($this->isConnected()) {
            /** @phpstan-var PgSqlConnectionId $connection */
            $connection = $this->connection;
            $this->connection = null;
            pg_close($connection);
        }
        return $this;

        // TODO: If there are open large object resources on the connection, do not close the connection before closing all
        //       large object resources.
        // @see  https://php.net/manual/en/function.pg-close.php
    }


    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool {
        return isset($this->connection);
    }


    /**
     * {@inheritDoc}
     */
    public function escapeIdentifier(string $name): string {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @phpstan-var PgSqlConnectionId $connection */
        $connection = $this->connection;

        if (strContains($name, '.')) {
            $names = explode('.', $name);

            foreach ($names as &$subname) {
                $subname = pg_escape_identifier($connection, $subname);
            }
            unset($subname);

            return join('.', $names);
        }
        return pg_escape_identifier($connection, $name);
    }


    /**
     * {@inheritDoc}
     */
    public function escapeLiteral($value): string {
        // bug or feature: pg_escape_literal(null) => '' quoted empty string instead of 'null'
        if ($value === null)  return 'null';
        if (is_bool ($value)) return $value ? 'true':'false';
        if (is_int  ($value)) return (string) $value;
        if (is_float($value)) return (string) $value;

        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @phpstan-var PgSqlConnectionId $connection */
        $connection = $this->connection;

        $value = $this->fixUtf8Encoding($value);    // pg_query(): ERROR: invalid byte sequence for encoding "UTF8"

        return pg_escape_literal($connection, $value);
    }


    /**
     * {@inheritDoc}
     */
    public function escapeString(?string $value): ?string {
        // bug or feature: pg_escape_string(null) => empty string instead of NULL
        if ($value === null) {
            return null;
        }
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @phpstan-var PgSqlConnectionId $connection */
        $connection = $this->connection;

        $value = $this->fixUtf8Encoding($value);    // pg_query(): ERROR: invalid byte sequence for encoding "UTF8"

        return pg_escape_string($connection, $value);
    }


    /**
     * Fix the encoding of a potentially non-UTF-8 encoded value.
     *
     * @param  string $value - potentially non-UTF-8 encoded value
     *
     * @return string - UTF-8 encoded value
     *
     * @see https://www.drupal.org/node/434802
     */
    private function fixUtf8Encoding(string $value): string {
        $encoding = mb_detect_encoding($value, null, true);
        if ($encoding!='ASCII' && $encoding!='UTF-8') {
            $value = utf8_encode($value);
        }
        return $value;
    }


    /**
     * {@inheritDoc}
     *
     * @return PostgresResult
     */
    public function query($sql): PostgresResult {
        $result = $this->executeRaw($sql);
        return new PostgresResult($this, $sql, $result, $this->lastAffectedRows());
    }


    /**
     * {@inheritDoc}
     */
    public function execute(string $sql): self {
        $result = $this->executeRaw($sql);
        if (is_resource($result))
            pg_free_result($result);
        return $this;
    }


    /**
     * {@inheritDoc}
     *
     * @return resource|PgSqlResult - result
     * @phpstan-return PgSqlResultId
     */
    public function executeRaw(string $sql) {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @phpstan-var PgSqlConnectionId $connection */
        $connection = $this->connection;

        // execute statement
        $result = null;
        try {
            $result = pg_query($connection, $sql);              // wraps multi-statement queries in a transaction
            if (!$result) throw new DatabaseException(pg_last_error($connection));
        }
        catch (Throwable $ex) {
            if (!$ex instanceof IRosasurferException) {
                $ex = new DatabaseException($ex->getMessage(), $ex->getCode(), $ex);
            }
            throw $ex->appendMessage('Database: '.$this->getConnectionDescription().NL."SQL: \"$sql\"");
        }

        /** @var string $status */
        $status = pg_result_status($result, PGSQL_STATUS_STRING);

        // reset last_insert_id on INSERTs, afterwards it's resolved on request as it requires an extra SQL query
        if (strStartsWithI($status, 'INSERT ')) {
            $this->lastInsertId = null;
        }

        // track last_affected_rows
        $pattern = '/^(INSERT|UPDATE|DELETE)\b/i';
        if (preg_match($pattern, $status)) {
            $this->lastAffectedRows = pg_affected_rows($result);
        }
        return $result;
    }


    /**
     * Start a new transaction. If there is already an active transaction only the transaction nesting level is increased.
     *
     * @return $this
     */
    public function begin(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException('Negative transaction nesting level detected: '.$this->transactionLevel);

        if (!$this->transactionLevel) {
            $this->execute('begin');
        }
        $this->transactionLevel++;
        return $this;
    }


    /**
     * {@inheritDoc}
     */
    public function commit(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException("Negative transaction nesting level detected: $this->transactionLevel");

        // don't throw an exception; trigger a warning which goes to the log
        if (!$this->isConnected()) {
            trigger_error('Not connected', E_USER_WARNING);
        }
        elseif (!$this->transactionLevel) {
            trigger_error('No database transaction to commit', E_USER_WARNING);
        }
        else {
            if ($this->transactionLevel == 1) {
                $this->execute('commit');
            }
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
    public function rollback(): self {
        if ($this->transactionLevel < 0) throw new RuntimeException("Negative transaction nesting level detected: $this->transactionLevel");

        // don't throw an exception; trigger a warning which goes to the log
        if (!$this->isConnected()) {
            trigger_error('Not connected', E_USER_WARNING);
        }
        elseif (!$this->transactionLevel) {
            //trigger_error('No database transaction to roll back', E_USER_WARNING);
        }
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
     * Return the last ID generated for a SERIAL column by a SQL statement. The value is not reset
     * between queries (see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session
     *               -1 if the PostgreSQL version doesn't support this functionality
     */
    public function lastInsertId(): int {
        if (!isset($this->lastInsertId)) {
            $version = $this->getVersionNumber();

            if ($version < 8_001_000) {            // 8.1
                $this->lastInsertId = -1;
            }
            else {
                try {
                    $this->lastInsertId = $this->query('select lastVal()')->fetchInt();
                }
                catch (Throwable $ex) {
                    if (stripos($ex->getMessage(), 'ERROR:  lastval is not yet defined in this session') === false) {
                        throw $ex;
                    }
                    $this->lastInsertId = 0;
                }
            }
        }
        return (int) $this->lastInsertId;
        /*
        @see  https://www.postgresql.org/docs/9.6/static/functions-sequence.html
        @see  https://stackoverflow.com/questions/6485778/php-postgres-get-last-insert-id/6488840
        @see  https://stackoverflow.com/questions/22530585/how-to-turn-off-multiple-statements-in-postgres
        @see  https://php.net/manual/en/function.pg-query-params.php
        @see  https://stackoverflow.com/questions/24182521/how-to-find-out-if-a-sequence-was-initialized-in-this-session
        @see  https://stackoverflow.com/questions/32991564/how-to-check-in-postgres-that-lastval-is-defined
        @see  https://stackoverflow.com/questions/55956/mysql-insert-id-alternative-for-postgresql
      */
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
        return true;
    }


    /**
     * Return the connector's internal connection object.
     *
     * @return resource|PgSqlConnection - the internal connection object
     * @phpstan-return PgSqlConnectionId
     */
    public function getInternalHandler() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @phpstan-var PgSqlConnectionId $connection */
        $connection = $this->connection;

        return $connection;
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
     * @return string - e.g. "9.1.23-rc"
     */
    public function getVersionString(): string {
        if ($this->versionString === null) {
            if (!$this->isConnected()) {
                $this->connect();
            }
            /** @phpstan-var PgSqlConnectionId $connection */
            $connection = $this->connection;

            $version = pg_version($connection);
            $versionStr = (string)($version['server'] ?? '');
            Assert::stringNotEmpty($versionStr, "unexpected pgsql version string \"$versionStr\"");

            $this->versionString = $versionStr;
        }
        return $this->versionString;
    }


    /**
     * {@inheritDoc}
     *
     * @return int - e.g. 9001023 for version string "9.1.23-rc"
     */
    public function getVersionNumber(): int {
        if ($this->versionNumber === null) {
            $version = $this->getVersionString();

            $match = null;
            if (!preg_match('/^(\d+)\.(\d+).(\d+)/', $version, $match)) {
                throw new UnexpectedValueException("Unexpected pgsql version string \"$version\"");
            }
            $major   = (int) $match[1];
            $minor   = (int) $match[2];
            $release = (int) $match[3];

            $this->versionNumber = $major*1_000_000 + $minor*1_000 + $release;
        }
        return $this->versionNumber;
    }
}

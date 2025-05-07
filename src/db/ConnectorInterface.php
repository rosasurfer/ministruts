<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;

use rosasurfer\ministruts\db\ResultInterface as IResult;


/**
 * Interface for storage mechanism adapters.
 */
interface ConnectorInterface {


    /**
     * Constructor.
     *
     * @param  array<string, mixed> $options - connector-specific configuration options
     */
    public function __construct(array $options);


    /**
     * Connect the adapter to the database.
     *
     * @return $this
     */
    public function connect(): self;


    /**
     * Disconnect the adapter from the database.
     *
     * @return $this
     */
    public function disconnect(): self;


    /**
     * Whether the adapter currently is connected to the database.
     *
     * @return bool
     */
    public function isConnected(): bool;


    /**
     * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
     * can be used in queries "as-is" and doesn't need additional quoting.
     *
     * @param  string $name - identifier to escape
     *
     * @return string - escaped and quoted identifier
     */
    public function escapeIdentifier(string $name): string;


    /**
     * Escape a DBMS literal, i.e. a column's value. The resulting string can be used in queries "as-is" and doesn't need
     * additional quoting.
     *
     * @param  ?scalar $value - value to escape
     *
     * @return string - escaped and quoted string or stringified scalar value if the value was not a string
     */
    public function escapeLiteral($value): string;


    /**
     * Escape a string. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  ?string $value - the value to escape
     *
     * @return ?string - escaped and unquoted string or NULL if the passed value was NULL
     */
    public function escapeString(?string $value): ?string;


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return IResult
     */
    public function query(string $sql): IResult;


    /**
     * Execute a SQL statement and skip potential result set processing. This method should be used for SQL statements not
     * returning rows. If the database driver does not support it the statement is forwarded to {@link ConnectorInterface::query()}.
     *
     * @param  string $sql - SQL statement
     *
     * @return $this
     */
    public function execute(string $sql): self;


    /**
     * Execute a SQL statement and return the internal driver's raw response.
     *
     * @param  string $sql - SQL statement
     *
     * @return mixed - raw driver response
     */
    public function executeRaw(string $sql);


    /**
     * Start a new transaction.
     *
     * @return $this
     */
    public function begin(): self;


    /**
     * Commit an active transaction.
     *
     * @return $this
     */
    public function commit(): self;


    /**
     * Roll back an active transaction.
     *
     * @return $this
     */
    public function rollback(): self;


    /**
     * Execute a task in a transactional way. The transaction is automatically committed or rolled back.
     * A nested transaction is executed in the context of the nesting transaction.
     *
     * @param  callable $task - task to execute
     *
     * @return mixed - the task's return value (if any)
     */
    public function transaction(callable $task);


    /**
     * Whether the connection currently is in a transaction.
     *
     * @return bool
     */
    public function isInTransaction(): bool;


    /**
     * Return the last ID generated for an AUTO_INCREMENT column by a SQL statement (connector specific, see the db README).
     *
     * @return int - last generated ID or 0 (zero) if no ID was generated yet in the current session; <br>
     *               -1 if the DBMS doesn't support this functionality
     */
    public function lastInsertId(): int;


    /**
     * Return the number of rows affected by the last row modifying statement (connector specific, see the db README).
     *
     * @return int - last number of affected rows or 0 (zero) if no rows were affected yet in the current session; <br>
     *               -1 if the DBMS doesn't support this functionality
     */
    public function lastAffectedRows(): int;


    /**
     * Whether the DBMS's SQL dialect supports 'insert into ... returning ...' syntax.
     *
     * @return bool
     */
    public function supportsInsertReturn(): bool;


    /**
     * Return the connector's internal connection object.
     *
     * @return resource|object - connection handle or handler instance
     */
    public function getInternalHandler();


    /**
     * Return the type of the DBMS the connector is used for.
     *
     * @return string
     */
    public function getType(): string;


    /**
     * Return the version of the DBMS the connector is used for as a string.
     *
     * @return string
     */
    public function getVersionString(): string;


    /**
     * Return the version ID of the DBMS the connector is used for as an integer.
     *
     * @return int
     */
    public function getVersionNumber(): int;
}

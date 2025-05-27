<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use Closure;
use Throwable;

use rosasurfer\ministruts\core\Singleton;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;
use rosasurfer\ministruts\db\MultipleRecordsException;
use rosasurfer\ministruts\db\NoSuchRecordException;
use rosasurfer\ministruts\db\ResultInterface as IResult;
use rosasurfer\ministruts\db\orm\meta\EntityMapping;
use rosasurfer\ministruts\phpstan\ArrayShapes;

/**
 * Abstract DAO base class.
 *
 * @phpstan-import-type ORM_ENTITY from ArrayShapes
 */
abstract class DAO extends Singleton {

    /** @var ?IConnector - the db connector for this DAO */
    private ?IConnector $connector = null;

    /** @var ?Worker - the worker this DAO uses */
    private ?Worker $worker = null;

    /** @var ?EntityMapping - the mapping of the DAO's entity */
    private ?EntityMapping $entityMapping = null;

    /** @var string - the PHP class name of the DAO's entity */
    protected string $entityClass;


    /**
     * Constructor
     *
     * Create a new DAO.
     */
    protected function __construct() {
        parent::__construct();
        $this->entityClass = substr(static::class, 0, -3);
    }


    /**
     * Get the specified DAO implementation.
     *
     * @param  class-string<DAO> $class - concrete DAO class name
     *
     * @return DAO
     */
    final public static function getImplementation(string $class): self {
        if (!is_subclass_of($class, __CLASS__, true)) {     // @phpstan-ignore function.alreadyNarrowedType ("class-string" is not a native type)
            throw new InvalidValueException("Invalid parameter \$class: $class (not a subclass of ".__CLASS__.')');
        }
        /** @var self $dao */
        $dao = self::getInstance($class);
        return $dao;
    }


    /**
     * Find a single matching record and convert it to an instance of the entity class.
     *
     * @param  string $query                - SQL query with optional ORM syntax
     * @param  bool   $allowMany [optional] - whether the query is allowed to return a multi-row result (default: no)
     *
     * @return ?PersistableObject
     *
     * @throws MultipleRecordsException if the query returned multiple rows and $allowMany was not set to TRUE.
     */
    public function find(string $query, bool $allowMany = false): ?PersistableObject {
        return $this->getWorker()->find($query, $allowMany);
    }


    /**
     * Find all matching records and convert them to instances of the entity class.
     *
     * @param  ?string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
     *
     * @return PersistableObject[]
     */
    public function findAll(?string $query = null): array {
        if ($query === null) {
            $mapping = $this->getMapping();
            $table = $this->escapeIdentifier($mapping['table']);
            $query = "select * from $table";
        }
        return $this->getWorker()->findAll($query);
    }


    /**
     * Get a single matching record and convert it to an instance of the entity class.
     *
     * @param  string $query                - SQL query with optional ORM syntax
     * @param  bool   $allowMany [optional] - whether the query is allowed to return a multi-row result (default: no)
     *
     * @return PersistableObject
     *
     * @throws NoSuchRecordException    if the query returned no rows
     * @throws MultipleRecordsException if the query returned multiple rows and $allowMany was not set to TRUE
     */
    public function get(string $query, bool $allowMany = false): PersistableObject {
        $result = $this->find($query, $allowMany);
        if (!$result) throw new NoSuchRecordException($query);
        return $result;
    }


    /**
     * Get all matching records (at least one) and convert them to instances of the entity class.
     *
     * @param  ?string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
     *
     * @return         PersistableObject[] - at least one instance
     * @phpstan-return non-empty-array<PersistableObject>
     *
     * @throws NoSuchRecordException  if the query returned no rows
     */
    public function getAll(?string $query = null): array {
        $results = $this->findAll($query);
        if (!$results) throw new NoSuchRecordException((string)$query);
        return $results;
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement with optional ORM syntax
     *
     * @return IResult
     */
    public function query(string $sql): IResult {
        return $this->getWorker()->query($sql);
    }


    /**
     * Execute a SQL statement and skip potential result set processing. This method should be used for SQL statements not
     * returning rows.
     *
     * @param  string $sql - SQL statement with optional ORM syntax
     *
     * @return $this
     */
    public function execute(string $sql): self {
        $this->getWorker()->execute($sql);
        return $this;
    }


    /**
     * Execute a task in a transactional way. The transaction is automatically committed or rolled back.
     * A nested transaction is executed in the context of the nesting transaction.
     *
     * @param  Closure $task - task to execute (an anonymous function is implicitly casted)
     *
     * @return mixed - the task's return value (if any)
     */
    public function transaction(Closure $task) {
        try {
            $this->db()->begin();
            $result = $task();
            $this->db()->commit();
            return $result;
        }
        catch (Throwable $ex) {
            $this->db()->rollback();
            throw $ex;
        }
    }


    /**
     * Return the mapping configuration of the DAO's entity.
     *
     * @return         array<string, mixed>
     * @phpstan-return ORM_ENTITY
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_ENTITY
     */
    abstract public function getMapping(): array;


    /**
     * Parse and validate the DAO's data mapping.
     *
     * @param  array<mixed> $mapping - user provided mapping data
     *
     * @return         array<string, mixed> - validated full entity mapping
     * @phpstan-return ORM_ENTITY
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_ENTITY
     */
    protected function parseMapping(array $mapping): array {
        $entityClass = '?';
        $configError = static function(string $message) use (&$entityClass): void {
            ORM::configError("$message in mapping of $entityClass");
        };
        $entity = [];

        // [class]
        $class = $mapping['class'] ?? $configError('missing element "class"');
        Assert::string($class, 'invalid type of element "class" (string expected)');
        $class = trim($class);
        if (!is_subclass_of($class, PersistableObject::class)) {
            $configError("invalid element \"class\": \"$class\" (not a subclass of ".PersistableObject::class.')');
        }
        $entity['class'] = $entityClass = $class;

        // [connection]
        $connection = $mapping['connection'] ?? $configError('missing element "connection"');
        is_string($connection) && $connection = trim($connection);
        Assert::stringNotEmpty($connection, 'invalid element "connection" (non-empty-string expected)');
        $entity['connection'] = $connection;

        // [table]
        $table = $mapping['table'] ?? $configError('missing element "table"');
        is_string($table) && $table = trim($table);
        Assert::stringNotEmpty($table, 'invalid element "table" (non-empty-string expected)');
        $entity['table'] = $table;

        // [properties]
        $properties = $mapping['properties'] ?? $configError('missing element "properties"');
        Assert::isArray($properties, 'invalid element "properties" (array expected)');
        Assert::notEmpty($properties, 'invalid element "properties" (non-empty-array expected)');

        foreach ($properties as $i => $userdata) {
            $property = [];

            $name = $userdata['name'] ?? $configError("missing attribute [properties][$i][\"name\"]");
            is_string($name) && $name = trim($name);
            Assert::stringNotEmpty($name, "invalid attribute [properties][$i][\"name\"] (non-empty-string expected)");
            $property['name'] = $name;

            $type = $userdata['type'] ?? $configError("missing attribute [properties][$i][@name=$name \"type\"]");
            is_string($type) && $type = trim($type);
            Assert::stringNotEmpty($type, "invalid attribute [properties][$i][@name=$name \"type\"] (non-empty-string expected)");
            // TODO: validate "type" (can also be a custom type)
            $property['type'] = $type;

            $column = $userdata['column'] ?? $name;
            is_string($column) && $column = trim($column);
            Assert::stringNotEmpty($column, "invalid attribute [properties][$i][@name=$name \"column\"] (non-empty-string expected)");
            $property['column'] = $column;

            $columnType = $userdata['column-type'] ?? $type;
            is_string($columnType) && $columnType = trim($columnType);
            Assert::stringNotEmpty($columnType, "invalid attribute [properties][$i][@name=$name \"column-type\"] (non-empty-string expected)");
            // TODO: validate "column-type" (can also be a custom type)
            $property['column-type'] = $columnType;

            if ($userdata['primary-key'] ?? 0) {
                if (isset($entity['identity'])) $configError('only one property must be marked with attribute "primary-key"');
                $property['primary-key'] = true;
                $entity['identity'] = &$property;                   // by reference to be able to update all instances at once when needed
            }                                                       // @see  PersistableObject::getPhysicalValue()
            if ($userdata['version'] ?? 0) {
                if (isset($entity['version'])) $configError('only one property can be marked with attribute "version"');
                $property['version'] = true;
                $entity['version'] = &$property;
            }
            if ($userdata['soft-delete'] ?? 0) {
                if (isset($entity['soft-delete'])) $configError('only one property can be marked with attribute "soft-delete"');
                $property['soft-delete'] = true;
                $entity['soft-delete'] = &$property;
            }

            if (isset($entity['properties'][$name])) $configError("multiple property names \"$name\" found (names must be unique)");
            $entity['properties'][$name] = &$property;              // add all properties to collection "properties"

            $columnL = strtolower($column);
            if (isset($entity['columns'][$columnL])) $configError("multiple columns \"$columnL\" found (columns must be unique)");
            $entity['columns'][$columnL] = &$property;              // add all properties to collection "columns"

            if ($type == ORM::BOOL) {                               // register virtual getters for all properties
                $entity['getters']["is$name" ] = &$property;
                $entity['getters']["has$name"] = &$property;
            }
            else {
                $entity['getters']["get$name"] = &$property;
            }
            unset($property);                                       // reset the reference
        }
        if (!isset($entity['identity'])) $configError('missing property marked with attribute "primary-key" (tables without primary key are not supported)');

        // [relations]
        $relations = $mapping['relations'] ?? [];
        Assert::isArray($relations, 'invalid element "relations" (array expected)');
        $relationTypes = ['one-to-one', 'one-to-many', 'many-to-one', 'many-to-many'];

        foreach ($relations as $i => $userdata) {
            $relation = [];

            $name = $userdata['name'] ?? $configError("missing attribute [relations][$i][\"name\"]");
            is_string($name) && $name = trim($name);
            Assert::stringNotEmpty($name, "invalid attribute [relations][$i][\"name\"] (non-empty-string expected)");
            $relation['name'] = $name;

            $type = $userdata['type'] ?? $configError("missing attribute [relations][$i][@name=$name \"type\"]");
            Assert::string($type, "invalid attribute [relations][$i][@name=$name \"type\"] (string expected)");
            $type = trim($type);
            if (!\in_array($type, $relationTypes, true)) {
                $configError("invalid attribute [relations][$i][@name=$name \"type\"] (one of \"".join('|', $relationTypes)."\" expected)");
            }
            $relation['type'] = $type;

            $class = $userdata['class'] ?? $configError("missing attribute [relations][$i][@name=$name \"class\"]");
            Assert::string($class, "invalid attribute [relations][$i][@name=$name \"class\"] (string expected)");
            $class = trim($class);
            if (!is_subclass_of($class, PersistableObject::class)) {
                $configError("invalid attribute [relations][$i][@name=$name \"class\"] (not a subclass of ".PersistableObject::class.')');
            }
            $relation['class'] = $class;

            switch ($type) {
                // -------------------------------------------------------------------------------------------------------------------------
                case 'one-to-one':
                    if (isset($userdata['column'])) {
                        // variant 1: local foreign-key column, optional ref-column, no join table
                        $column = $userdata['column'];
                        is_string($column) && $column = trim($column);
                        Assert::stringNotEmpty($column, "invalid attribute [relations][$i][@name=$name \"column\"] (non-empty-string expected)");
                        $relation['column'] = $column;

                        if (isset($userdata['ref-column'])) {
                            $refColumn = $userdata['ref-column'];
                            is_string($refColumn) && $refColumn = trim($refColumn);
                            Assert::stringNotEmpty($refColumn, "invalid attribute [relations][$i][@name=$name \"ref-column\"] (non-empty-string expected)");
                            $relation['ref-column'] = $refColumn;
                        }
                        if (isset($userdata['join-table'])) $configError("invalid relation [relations][$i][@name=$name] (attribute \"column\" cannot be combined with attribute \"join-table\")");
                    }
                    else {
                        // variant 2: no local foreign-key column, required ref-column, optional join table
                        $refColumn = $userdata['ref-column'] ?? $configError("missing attribute [relations][$i][@name=$name \"ref-column\"]");
                        is_string($refColumn) && $refColumn = trim($refColumn);
                        Assert::stringNotEmpty($refColumn, "invalid attribute [relations][$i][@name=$name \"ref-column\"] (non-empty-string expected)");
                        $relation['ref-column'] = $refColumn;
                    }
                    $relation = $this->validateJoinTableSettings($userdata, $relation, true, $i, $entityClass);
                    break;

                // -------------------------------------------------------------------------------------------------------------------------
                case 'one-to-many':
                    // no local foreign-key column, required ref-column, optional join table
                    if (isset($userdata['column'])) $configError("invalid relation [relations][$i][@name=$name] (type \"one-to-many\" cannot have attribute \"column\")");

                    $refColumn = $userdata['ref-column'] ?? $configError("missing attribute [relations][$i][@name=$name \"ref-column\"]");
                    is_string($refColumn) && $refColumn = trim($refColumn);
                    Assert::stringNotEmpty($refColumn, "invalid attribute [relations][$i][@name=$name \"ref-column\"] (non-empty-string expected)");
                    $relation['ref-column'] = $refColumn;

                    $relation = $this->validateJoinTableSettings($userdata, $relation, true, $i, $entityClass);
                    break;

                // -------------------------------------------------------------------------------------------------------------------------
                case 'many-to-one':
                    // required local foreign-key column, optional ref-column, no join table
                    $column = $userdata['column'] ?? $configError("missing attribute [relations][$i][@name=$name \"column\"]");
                    is_string($column) && $column = trim($column);
                    Assert::stringNotEmpty($column, "invalid attribute [relations][$i][@name=$name \"column\"] (non-empty-string expected)");
                    $relation['column'] = $column;

                    if (isset($userdata['ref-column'])) {
                        $refColumn = $userdata['ref-column'];
                        is_string($refColumn) && $refColumn = trim($refColumn);
                        Assert::stringNotEmpty($refColumn, "invalid attribute [relations][$i][@name=$name \"ref-column\"] (non-empty-string expected)");
                        $relation['ref-column'] = $refColumn;
                    }

                    if (isset($userdata['join-table'])) $configError("invalid relation [relations][$i][@name=$name] (type \"many-to-one\" cannot have attribute \"join-table\")");
                    $relation = $this->validateJoinTableSettings($userdata, $relation, true, $i, $entityClass);
                    break;

                // -------------------------------------------------------------------------------------------------------------------------
                case 'many-to-many':
                    // no local foreign-key column, required ref-column, required join table
                    if (isset($userdata['column'])) $configError("invalid relation [relations][$i][@name=$name] (type \"many-to-many\" cannot have attribute \"column\")");

                    $relation = $this->validateJoinTableSettings($userdata, $relation, false, $i, $entityClass);
                    break;

                // -------------------------------------------------------------------------------------------------------------------------
                default:
                    $configError("invalid attribute [relations][$i][@name=$name \"type\"] (one of \"".join('|', $relationTypes)."\" expected)");
            }

            // ensure the local "key" property is set (default: identity)
            $key = $userdata['key'] ?? $entity['identity']['name'];

            if (!isset($entity['properties'][$key])) $configError("invalid attribute [relations][$i][@name=$name \"key\"] (property name expected)");
            $relation['key'] = $key;

            // add all relations to collection "relations"
            if (isset($entity['relations'][$name])) $configError("multiple relations with name \"$name\" found (names must be unique)");
            $entity['relations'][$name] = &$relation;                   // by reference to be able to update all instances at once if needed

            // add all relations with local column to collection "columns"
            if (isset($relation['column'])) {
                $columnL = strtolower($relation['column']);
                if (isset($entity['columns'][$columnL])) $configError("multiple mappings for column \"$columnL\" found (columns must be unique)");
                $entity['columns'][$columnL] = &$relation;
            }

            // register virtual getters for all relations
            $entity['getters']["get$name"] = &$relation;

            unset($relation);                                           // reset the reference
        }

        // for faster indexing set collected getter names to all-lower-case
        $entity['getters'] = \array_change_key_case($entity['getters'], CASE_LOWER);

        /** @phpstan-var ORM_ENTITY $entity */
        return $entity;
    }


    /**
     * Validate a relation's "join-table" settings in the user-provided mapping data.
     *
     * @param mixed[]  $userdata - user-provided mapping data
     * @param mixed[]  $relation - existing relation data
     * @param bool     $optional - whether "join-table" settings in $userdata are optional or required
     * @param int      $pos      - position of the relation in the mapping (for generation of error messages)
     * @param string   $class    - entity class the settings belong to (for generation of error messages)
     *
     * @return mixed[] - the resulting relation data
     *
     * @throws ConfigException on configuration errors
     */
    private function validateJoinTableSettings(array $userdata, array $relation, bool $optional, int $pos, string $class): array {
        $name = $relation['name'] ?? '';
        $i = $pos;
        $configError = static function(string $message) use ($class): void {
            ORM::configError("$message in mapping of $class");
        };
        $newData = [];

        if ($optional) {
            if (isset($userdata['join-table'])) {
                // validation as with "join-table" (see below)
            }
            else {
                if (isset($userdata['fk-ref-column'])) $configError("invalid relation [relations][$i][@name=$name] (attribute \"fk-ref-column\" requires a \"join-table\")");
                if (isset($userdata['foreign-key'  ])) $configError("invalid relation [relations][$i][@name=$name] (attribute \"foreign-key\" requires a \"join-table\")");
            }
        }
        else {
            // "join-table" (required)
            $joinTable = $userdata['join-table'] ?? $configError("missing attribute [relations][$i][@name=$name \"join-table\"]");
            is_string($joinTable) && $joinTable = trim($joinTable);
            Assert::stringNotEmpty($joinTable, "invalid attribute [relations][$i][@name=$name \"join-table\"] (non-empty-string expected)");
            $newData['join-table'] = $joinTable;

            // "ref-column" (required)
            if (!isset($relation['ref-column'])) {
                $refColumn = $userdata['ref-column'] ?? $configError("missing attribute [relations][$i][@name=$name \"ref-column\"]");
                is_string($refColumn) && $refColumn = trim($refColumn);
                Assert::stringNotEmpty($refColumn, "invalid attribute [relations][$i][@name=$name \"ref-column\"] (non-empty-string expected)");
                $newData['ref-column'] = $refColumn;
            }

            // "fk-ref-column" (required)
            $fkRefColumn = $userdata['fk-ref-column'] ?? $configError("missing attribute [relations][$i][@name=$name \"fk-ref-column\"]");
            is_string($fkRefColumn) && $fkRefColumn = trim($fkRefColumn);
            Assert::stringNotEmpty($fkRefColumn, "invalid attribute [relations][$i][@name=$name \"fk-ref-column\"] (non-empty-string expected)");
            $newData['fk-ref-column'] = $fkRefColumn;

            // "foreign-key" (optional)
            if (isset($userdata['foreign-key'])) {
                $foreignKey = $userdata['foreign-key'];
                is_string($foreignKey) && $foreignKey = trim($foreignKey);
                Assert::stringNotEmpty($foreignKey, "invalid attribute [relations][$i][@name=$name \"foreign-key\"] (non-empty-string expected)");
                $newData['foreign-key'] = $foreignKey;
            }
        }
        return $relation + $newData;
    }


    /**
     * Return the object-oriented data mapping of the DAO's entity.
     *
     * @return EntityMapping
     */
    public function getEntityMapping(): EntityMapping {
        return $this->entityMapping ??= new EntityMapping($this->getMapping());
    }


    /**
     * Return the name of the DAO's entity class.
     *
     * @return string
     */
    public function getEntityClass(): string {
        return $this->entityClass;
    }


    /**
     * Return the database adapter used for the DAO's entity.
     *
     * @return IConnector
     */
    final public function db(): IConnector {
        return $this->connector ??= $this->getWorker()->getConnector();
    }


    /**
     * Return the Worker the DAO uses.
     *
     * @return Worker
     */
    private function getWorker(): Worker {
        return $this->worker ??= new Worker($this);
    }


    /**
     * Escape a DBMS identifier, i.e. the name of a database object (schema, table, view, column etc.). The resulting string
     * can be used in queries "as-is" and doesn't need additional quoting.
     *
     * @param  string $name - identifier to escape
     *
     * @return string - escaped and quoted identifier
     */
    public function escapeIdentifier(string $name): string {
        return $this->db()->escapeIdentifier($name);
    }


    /**
     * Escape a DBMS literal, i.e. a column's value. The resulting string can be used in queries "as-is" and doesn't need
     * additional quoting.
     *
     * @param  ?scalar $value - value to escape
     *
     * @return string - escaped and quoted string or stringified scalar value if the value was not a string
     */
    public function escapeLiteral($value): string {
        return $this->db()->escapeLiteral($value);
    }


    /**
     * Escape a string. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  ?string $value - value to escape
     *
     * @return ?string - escaped but unquoted string or NULL if the passed value was NULL
     */
    public function escapeString(?string $value): ?string {
        return $this->db()->escapeString($value);
    }
}

<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use Throwable;

use rosasurfer\ministruts\core\Singleton;
use rosasurfer\ministruts\core\exception\ClassNotFoundException;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;
use rosasurfer\ministruts\db\MultipleRecordsException;
use rosasurfer\ministruts\db\NoSuchRecordException;
use rosasurfer\ministruts\db\ResultInterface as IResult;
use rosasurfer\ministruts\db\orm\meta\EntityMapping;

use function rosasurfer\ministruts\is_class;
use rosasurfer\ministruts\core\assert\Assert;


/**
 * Abstract DAO base class.
 *
 * @phpstan-import-type  EntityClass  from \rosasurfer\ministruts\db\orm\ORM
 * @phpstan-import-type  ORM_ENTITY   from \rosasurfer\ministruts\db\orm\ORM
 * @phpstan-import-type  ORM_PROPERTY from \rosasurfer\ministruts\db\orm\ORM
 * @phpstan-import-type  ORM_RELATION from \rosasurfer\ministruts\db\orm\ORM
 */
abstract class DAO extends Singleton {


    /** @var ?IConnector - the db connector for this DAO */
    private $connector = null;

    /** @var ?Worker - the worker this DAO uses */
    private $worker = null;

    /** @var ?EntityMapping - the mapping of the DAO's entity */
    private $entityMapping = null;

    /** @var string - the PHP class name of the DAO's entity */
    protected $entityClass;


    /**
     * Get the specified DAO implementation.
     *
     * @param  string $class - DAO class name
     *
     * @return DAO
     */
    final public static function getImplementation($class) {
        if (!is_a($class, __CLASS__, true)) {
            if (!is_class($class)) throw new ClassNotFoundException('Class not found: '.$class );
            throw new InvalidTypeException('Not a '.__CLASS__.' subclass: '.$class);
        }
        /** @var self $dao */
        $dao = self::getInstance($class);
        return $dao;
    }


    /**
     * Constructor
     *
     * Create a new DAO.
     */
    protected function __construct() {
        parent::__construct();
        $this->entityClass = substr(get_class($this), 0, -3);
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
    public function find($query, $allowMany = false) {
        return $this->getWorker()->find($query, $allowMany);
    }


    /**
     * Find all matching records and convert them to instances of the entity class.
     *
     * @param  ?string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
     *
     * @return PersistableObject[]
     */
    public function findAll($query = null) {
        if ($query === null) {
            $mapping = $this->getMapping();
            $table = $this->escapeIdentifier($mapping['table']);
            $query = 'select * from '.$table;
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
    public function get($query, $allowMany = false) {
        $result = $this->find($query, $allowMany);
        if (!$result)
            throw new NoSuchRecordException($query);
        return $result;
    }


    /**
     * Get all matching records (at least one) and convert them to instances of the entity class.
     *
     * @param  ?string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
     *
     * @return PersistableObject[] - at least one instance
     *
     * @throws NoSuchRecordException  if the query returned no rows
     */
    public function getAll($query = null) {
        $results = $this->findAll($query);
        if (!$results)
            throw new NoSuchRecordException((string)$query);
        return $results;
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement with optional ORM syntax
     *
     * @return IResult
     */
    public function query($sql) {
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
    public function execute($sql) {
        $this->getWorker()->execute($sql);
        return $this;
    }


    /**
     * Execute a task in a transactional way. The transaction is automatically committed or rolled back.
     * A nested transaction is executed in the context of the nesting transaction.
     *
     * @param  \Closure $task - task to execute (an anonymous function is implicitly casted)
     *
     * @return mixed - the task's return value (if any)
     */
    public function transaction(\Closure $task) {
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
     */
    abstract public function getMapping(): array;


    /**
     * Parse and validate the DAO's data mapping.
     *
     * @param  array<mixed> $mapping - user provided mapping data
     *
     * @return array<string, mixed> - validated full entity mapping
     *
     * @phpstan-return ORM_ENTITY
     */
    protected function parseMapping(array $mapping): array {
        $configError = function(string $message): bool {
            throw new ConfigException("ORM config error: $message");
        };
        $entity = [];

        // [class]
        $class = $mapping['class'] ?? $configError('missing field "class"');
        Assert::string($class, 'invalid type of field "class" (string expected)');
        if (!is_subclass_of($class, PersistableObject::class)) {
            $configError("invalid field \"class\": \"$class\" (not a subclass of ".PersistableObject::class.')');
        }
        $entity['class'] = $class;

        // [connection]
        $connection = $mapping['connection'] ?? $configError('missing field "connection"');
        Assert::stringNotEmpty($connection, 'invalid field "connection" (non-empty-string expected)');
        $entity['connection'] = $connection;

        // [table]
        $table = $mapping['table'] ?? $configError('missing field "table"');
        Assert::stringNotEmpty($table, 'invalid field "table" (non-empty-string expected)');
        $entity['table'] = $table;

        // [properties]
        $properties = $mapping['properties'] ?? $configError('missing field "properties"');
        Assert::isArray($properties, 'invalid field "properties" (array expected)');
        Assert::notEmpty($properties, 'invalid field "properties" (non-empty-array expected)');

        foreach ($properties as $i => $data) {
            $name = $data['name'] ?? $configError("missing field [properties][$i][name]");
            Assert::stringNotEmpty($name, "invalid field [properties][$i][name] (non-empty-string expected)");

            $type = $data['type'] ?? $configError("missing field [properties][$i][type]");
            Assert::stringNotEmpty($type, "invalid field [properties][$i][type] (non-empty-string expected)");

            $column = $data['column'] ?? $name;
            Assert::stringNotEmpty($column, "invalid field [properties][$i][column] (non-empty-string expected)");

            $columnType = $data['column-type'] ?? $type;            // TODO: $type may be a custom type
            Assert::stringNotEmpty($columnType, "invalid field [properties][$i][column-type] (non-empty-string expected)");

            $property = [
                'name'        => $name,
                'type'        => $type,
                'column'      => $column,
                'column-type' => $columnType,
            ];

            if ($data['primary'] ?? 0) {
                if (isset($entity['identity'])) $configError('only one property/column can be marked with "primary"');
                $property['primary'] = true;
                $entity['identity'] = &$property;                   // Properties are stored by reference to be able to update all instances
            }                                                       // at once from related mappings in PersistableObject::getPhysicalValue().
            if ($data['version'] ?? 0) {
                if (isset($entity['version'])) $configError('only one property/column can be marked with "version"');
                $property['version'] = true;
                $entity['version'] = &$property;
            }
            if ($data['soft-delete'] ?? 0) {
                if (isset($entity['soft-delete'])) $configError('only one property/column can be marked with "soft-delete"');
                $property['soft-delete'] = true;
                $entity['soft-delete'] = &$property;
            }

            if (isset($entity['properties'][$name])) $configError("multiple properties named \"$name\" found (names must be unique)");
            $entity['properties'][$name] = &$property;              // add all properties to collection "properties"

            if (isset($entity['columns'][$column])) $configError("multiple columns named \"$column\" found (columns must be unique)");
            $entity['columns'][$column] = &$property;               // add all properties to collection "columns"

            if ($type == ORM::BOOL) {                               // register virtual getters for all properties
                $entity['getters']["is$name" ] = &$property;
                $entity['getters']["has$name"] = &$property;
            }
            else {
                $entity['getters']["get$name"] = &$property;
            }
            unset($property);                                       // reset the reference
        }
        $entity['identity'] ?? $configError('missing property/column marked with "primary" (tables without primary key are not supported)');


        // TODO:
        // [relations]
        $relations = $mapping['relations'] ?? [];
        Assert::isArray($relations, 'invalid field $mapping[relations] (array expected)');

        foreach ($relations as $i => $relation) {
            $name  = $relation['name' ];
            $assoc = $relation['assoc'];

            // any association using a mandatory or optional join table
            if ($assoc=='many-to-many' || isset($relation['join-table'])) {
                if (!isset($relation['join-table']))                     throw new RuntimeException("Missing attribute \"join-table\"=\"{table-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                if (!isset($relation['key'])) {
                    $relation['key'] = $entity['identity']['name'];
                }
                elseif (!isset($entity['properties'][$relation['key']])) throw new RuntimeException("Illegal attribute \"key\"=\"$relation[key]\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                if (!isset($relation['ref-column']))                     throw new RuntimeException("Missing attribute \"ref-column\"=\"{table-name.column-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                if (!isset($relation['fk-ref-column']))                  throw new RuntimeException("Missing attribute \"fk-ref-column\"=\"{table-name.column-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
            }

            // one-to-one (using no optional join table)
            elseif ($assoc == 'one-to-one') {
                if (!isset($relation['column'])) {
                    if (!isset($relation['key'])) {
                        $relation['key'] = $entity['identity']['name'];
                    }
                    elseif (!isset($entity['properties'][$relation['key']])) throw new RuntimeException("Illegal attribute \"key\"=\"$relation[key]\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                    if (!isset($relation['ref-column']))                     throw new RuntimeException("Missing attribute \"ref-column\"=\"{column-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                }
            }

            // one-to-many (using no optional join table)
            elseif ($assoc == 'one-to-many') {
                if (!isset($relation['key'])) {
                    $relation['key'] = $entity['identity']['name'];
                }
                elseif (!isset($entity['properties'][$relation['key']])) throw new RuntimeException("Illegal attribute \"key\"=\"$relation[key]\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
                if (!isset($relation['ref-column']))                     throw new RuntimeException("Missing attribute \"ref-column\"=\"{column-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
            }

            // many-to-one (using no optional join table)
            elseif ($assoc == 'many-to-one') {
                if (!isset($relation['column'])) throw new RuntimeException("Missing attribute \"column\"=\"{column-name}\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");
            }
            else                                 throw new RuntimeException("Illegal attribute \"assoc\"=\"$assoc\" in relation [name=\"$name\" ...] of entity class \"$entity[class]\"");

            if (isset($relation['column'])) {
                $entity['columns'][$relation['column']] = &$relation;   // Stored by reference to be able to update all instances at once.
            }                                                           // See explanations above for referenced properties.
            $entity['getters']["get$name"] = &$relation;

            $entity['relations'][$name] = &$relation;
            unset($relation);                                           // reset the reference
        }

        // for faster indexing set collected column and getter names to all-lower-case
        $entity['columns'] = \array_change_key_case($entity['columns'], CASE_LOWER);
        $entity['getters'] = \array_change_key_case($entity['getters'], CASE_LOWER);

        //error_log(print_r($entity, true));

        /** @phpstan-var ORM_ENTITY $entity */
        return $entity = $entity;
    }


    /**
     * Return the object-oriented data mapping of the DAO's entity.
     *
     * @return EntityMapping
     */
    public function getEntityMapping() {
        return $this->entityMapping ??= new EntityMapping($this->getMapping());
    }


    /**
     * Return the name of the DAO's entity class.
     *
     * @return string
     */
    public function getEntityClass() {
        return $this->entityClass;
    }


    /**
     * Return the database adapter used for the DAO's entity.
     *
     * @return IConnector
     */
    final public function db() {
        return $this->connector ??= $this->getWorker()->getConnector();
    }


    /**
     * Return the Worker the DAO uses.
     *
     * @return Worker
     */
    private function getWorker() {
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
    public function escapeIdentifier($name) {
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
    public function escapeLiteral($value) {
        return $this->db()->escapeLiteral($value);
    }


    /**
     * Escape a scalar value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  ?scalar $value - value to escape
     *
     * @return ?string - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        return $this->db()->escapeString($value);
    }
}

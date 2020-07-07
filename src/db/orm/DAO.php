<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;
use rosasurfer\core\exception\ClassNotFoundException;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\MultipleRecordsException;
use rosasurfer\db\NoSuchRecordException;
use rosasurfer\db\ResultInterface as IResult;
use rosasurfer\db\orm\meta\EntityMapping;

use function rosasurfer\is_class;


/**
 * DAO
 *
 * Abstract DAO base class.
 */
abstract class DAO extends Singleton {


    /** @var IConnector - the db connector for this DAO */
    private $connector;

    /** @var Worker - the worker this DAO uses */
    private $worker;

    /** @var EntityMapping - the mapping of the DAO's entity */
    private $entityMapping;

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
        if (!is_a($class, __CLASS__, $allowString=true)) {
            if (!is_class($class)) throw new ClassNotFoundException('Class not found: '.$class );
            throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);
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
     * @return PersistableObject|null
     *
     * @throws MultipleRecordsException if the query returned multiple rows and $allowMany was not set to TRUE.
     */
    public function find($query, $allowMany = false) {
        return $this->getWorker()->find($query, $allowMany);
    }


    /**
     * Find all matching records and convert them to instances of the entity class.
     *
     * @param  string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
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
     * @param  string $query [optional] - SQL query with optional ORM syntax; without a query all instances are returned
     *
     * @return PersistableObject[] - at least one instance
     *
     * @throws NoSuchRecordException  if the query returned no rows
     */
    public function getAll($query = null) {
        $results = $this->findAll($query);
        if (!$results)
            throw new NoSuchRecordException($query);
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
        catch (\Throwable $ex) { $this->db()->rollback(); throw $ex; }
        catch (\Exception $ex) { $this->db()->rollback(); throw $ex; }
    }


    /**
     * Return the mapping configuration of the DAO's entity.
     *
     * @return array
     */
    abstract public function getMapping();


    /**
     * Parse and validate the DAO's data mapping.
     *
     * @param  array $mapping - data mapping
     *
     * @return array - validated and normalized mapping
     */
    protected function parseMapping(array $mapping) {
        foreach ($mapping['properties'] as $i => $property) {
            $name = $property['name'];
            $type = $property['type'];

            if (!isset($property['column'     ])) $property['column'     ] = $name;
            if (!isset($property['column-type'])) $property['column-type'] = $type;     // TODO: bug, $type may be a custom type

            if (isset($property['primary']) && $property['primary']===true)
                $mapping['identity'] = &$property;

            if (isset($property['version']) && $property['version']===true)
                $mapping['version'] = &$property;

            $column = $property['column'];
            $mapping['columns'][$column] = &$property;

            if ($type=='bool' || $type=='boolean') {
                $mapping['getters']['is'.$name ] = &$property;
                $mapping['getters']['has'.$name] = &$property;
            }
            else {
                $mapping['getters']['get'.$name] = &$property;
            }
            $mapping['properties'][$name] = &$property;
            unset($mapping['properties'][$i], $property);
        };

        if (isset($mapping['relations'])) {
            foreach ($mapping['relations'] as $i => $property) {
                $name  = $property['name' ];
                $assoc = $property['assoc'];

                // any association using a mandatory or optional join table
                if ($assoc=='many-to-many' || isset($property['join-table'])) {
                    if (!isset($property['join-table']))                       throw new RuntimeException('Missing attribute "join-table"="{table_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isset($property['key']))
                        $property['key'] = $mapping['identity']['name'];
                    else if (!isset($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isset($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{'.$property['join-table'].'.column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isset($property['fk-ref-column']))                    throw new RuntimeException('Missing attribute "fk-ref-column"="{'.$property['join-table'].'.column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }

                // one-to-one (using no optional join table)
                else if ($assoc == 'one-to-one') {
                    if (!isset($property['column'])) {
                        if (!isset($property['key']))
                            $property['key'] = $mapping['identity']['name'];
                        else if (!isset($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                        if (!isset($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    }
                }

                // one-to-many (using no optional join table)
                else if ($assoc == 'one-to-many') {
                    if (!isset($property['key']))
                        $property['key'] = $mapping['identity']['name'];
                    else if (!isset($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isset($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }

                // many-to-one (using no optional join table)
                else if ($assoc == 'many-to-one') {
                    if (!isset($property['column'])) throw new RuntimeException('Missing attribute "column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }
                else                                 throw new RuntimeException('Illegal attribute "assoc"="'.$assoc.'" in relation [name="'.$name.'"...] of entity class "'.$mapping['class'].'"');

                if (isset($property['column']))
                    $mapping['columns'][$property['column']] = &$property;
                $getter = 'get'.$name;
                $mapping['getters'][$getter] = &$property;
                $mapping['relations'][$name] = &$property;
                unset($mapping['relations'][$i], $property);
            };
        }
        else $mapping['relations'] = [];

        $mapping['columns'] = \array_change_key_case($mapping['columns'], CASE_LOWER);
        $mapping['getters'] = \array_change_key_case($mapping['getters'], CASE_LOWER);

        return $mapping;
    }


    /**
     * Return the object-oriented data mapping of the DAO's entity.
     *
     * @return EntityMapping
     */
    public function getEntityMapping() {
        if (!$this->entityMapping) {
            $this->entityMapping = new EntityMapping($this->getMapping());
        }
        return $this->entityMapping;
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
        if (!$this->connector) {
            $this->connector = $this->getWorker()->getConnector();
        }
        return $this->connector;
    }


    /**
     * Return the Worker the DAO uses.
     *
     * @return Worker
     */
    private function getWorker() {
        if (!$this->worker) {
            $this->worker = new Worker($this);
        }
        return $this->worker;
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
     * @param  scalar|null $value - value to escape
     *
     * @return string - escaped and quoted string or stringified scalar value if the value was not a string
     */
    public function escapeLiteral($value) {
        return $this->db()->escapeLiteral($value);
    }


    /**
     * Escape a scalar value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  scalar|null $value - value to escape
     *
     * @return string|null - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        return $this->db()->escapeString($value);
    }
}

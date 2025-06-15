<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\db\ConnectionPool;
use rosasurfer\ministruts\db\ConnectorInterface as Connector;
use rosasurfer\ministruts\db\MultipleRecordsException;
use rosasurfer\ministruts\db\ResultInterface as IResult;

use function rosasurfer\ministruts\preg_match_all;
use function rosasurfer\ministruts\strLeftTo;

use const rosasurfer\ministruts\ARRAY_ASSOC;

/**
 * Worker
 *
 * A Worker converts database records to PHP objects. For each entity exists a separate Worker instance.
 */
class Worker extends CObject {

    /** @var DAO - DAO of the worker's entity */
    private DAO $dao;

    /** @var string - name of the worker's entity class */
    protected string $entityClass;

    /** @var Connector|null - db adapter used for the worker's entity */
    private ?Connector $connector = null;


    /**
     * Constructor
     *
     * Create a new Worker for the specified DAO.
     *
     * @param  DAO $dao
     */
    public function __construct(DAO $dao) {
        $this->dao = $dao;
        $this->entityClass = $dao->getEntityClass();
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
        $result = $this->query($query);                             // TODO: numRows() is not available on SQLite or with PDO and the
                                                                    //       emulation is slow. The check can be improved with fetchRow()
        $object = $this->makeObject($result);                       //       when reset(-1) and internal record caching are implemented.
        if ($object && !$allowMany && $result->numRows() > 1) {
            throw new MultipleRecordsException($query);
        }
        return $object;
    }


    /**
     * Find all matching records and convert them to instances of the entity class.
     *
     * @param  string $query - SQL query with optional ORM syntax
     *
     * @return PersistableObject[]
     */
    public function findAll(string $query): array {
        $result = $this->query($query);
        return $this->makeObjects($result);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used for SQL statements returning rows.
     *
     * @param  string $sql - SQL statement with optional ORM syntax
     *
     * @return IResult
     */
    public function query(string $sql): IResult {
        $sql = $this->translateQuery($sql);
        return $this->getConnector()->query($sql);
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
        $sql = $this->translateQuery($sql);
        $this->getConnector()->execute($sql);
        return $this;
    }


    /**
     * Translate entity names in a SQL query into their DBMS table counterparts. At the moment this translation requires all
     * entity classes to be in the same namespace as the worker's entity class.
     *
     * @param  string $sql - original SQL query
     *
     * @return string - translated SQL query
     */
    private function translateQuery(string $sql): string {
        // model name pattern: ":User" => "t_user" (will also convert matching names in literals and comments)
        $pattern = '/[^:]:([a-z_]\w*)\b/i';
        $matches = null;
        if (preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE)) {
            $namespace = strLeftTo($this->entityClass, '\\', -1, true, '');

            foreach (\array_reverse($matches[1]) as $match) {
                $modelName = $match[0];
                $offset    = $match[1];
                $className = $namespace.$modelName;
                if (is_subclass_of($className, PersistableObject::class)) {
                    /** @var DAO $dao */
                    $dao = $className::dao();
                    $table = $dao->getMapping()['table'];
                    $sql = substr_replace($sql, $table, $offset-1, strlen($modelName)+1);
                }
            }
        }
        return $sql;
    }


    /**
     * Convert the next row of a result to an object of the model class.
     *
     * @param  IResult $result
     *
     * @return ?PersistableObject - instance or NULL if the result doesn't hold any more rows
     */
    protected function makeObject(IResult $result): ?PersistableObject {

        // TODO: Prefer to return existing instance from IdentityMap

        $row = $result->fetchRow(ARRAY_ASSOC);
        if ($row === null) {
            return null;
        }
        return PersistableObject::populateNew($this->entityClass, $row);
    }


    /**
     * Convert all remaining rows of a result to objects of the model class.
     *
     * @param  IResult $result
     *
     * @return PersistableObject[] - array of instances or an empty array if the result doesn't hold any more rows
     */
    protected function makeObjects(IResult $result): array {

        // TODO: Prefer to return existing instances from IdentityMap

        $instances = [];
        while ($row = $result->fetchRow(ARRAY_ASSOC)) {
            $instances[] = PersistableObject::populateNew($this->entityClass, $row);
        }
        return $instances;
    }


    /**
     * Return the database adapter of the Worker's model class.
     *
     * @return Connector
     */
    public function getConnector(): Connector {
        if (!$this->connector) {
            $mapping = $this->dao->getMapping();
            $this->connector = ConnectionPool::getConnector($mapping['connection']);
        }
        return $this->connector;
    }
}

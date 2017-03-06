<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;

use rosasurfer\db\ConnectionPool;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\MultipleRowsException;
use rosasurfer\db\ResultInterface as IResult;

use function rosasurfer\strLeftTo;

use const rosasurfer\ARRAY_ASSOC;


/**
 * Worker
 *
 * A Worker converts database records to PHP objects. For every model class exists exactly one Worker instance.
 * Only one PHP object is created for database records returned multiple times (e.g. by multiple queries).
 */
class Worker extends Object {


    /** @var DAO - DAO of the Worker's model class */
    private $dao;

    /** @var string - class name of the Worker's model */
    protected $entityClass;

    /** @var IConnector - database adapter of the Worker's model */
    private $connector;


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
     * Find a single matching record and convert it to an object of the model class.
     *
     * @param  string $query     - SQL query
     * @param  bool   $allowMany - whether or not the query is allowed to return a multi-row result (default: no)
     *
     * @return PersistableObject|null
     *
     * @throws MultipleRowsException if the query returned multiple rows and $allowMany was not set to TRUE.
     */
    public function findOne($query, $allowMany=false) {     // TODO: numRows() is not available on SQLite or with PDO, the emulation is slow.
        $result = $this->query($query);                     //       the check can be improved by fetchNext() when reset(-1) and internal record
                                                            //       caching are implemented.
        $object = $this->makeObject($result);
        if ($object && !$allowMany && $result->numRows() > 1) throw new MultipleRowsException();

        return $object;
    }


    /**
     * Find all matching records and convert them to objects of the model class.
     *
     * @param  string $query - SQL query
     *
     * @return PersistableObject[]
     */
    public function findAll($query) {
        $result = $this->query($query);
        return $this->makeObjects($result);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used if the SQL statement returns rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return IResult
     */
    public function query($sql) {
        $sql = $this->translateQuery($sql);
        return $this->getConnector()->query($sql);
    }


    /**
     * Translate model names in a SQL query into their DBMS table counterparts. At the moment this translation works only
     * for models in the same namespace as the worker's model class.
     *
     * @param  string $sql - original SQL query
     *
     * @return string - translated SQL query
     */
    private function translateQuery($sql) {
        // model name pattern: ":User" => "t_user" (will also convert matching names in literals and comments)
        $pattern = '/[^:]:([a-z_]\w*)\b/i';
        if (preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE)) {
            $namespace = strLeftTo($this->entityClass, '\\', -1, true, '');

            foreach (array_reverse($matches[1]) as $match) {
                $modelName = $match[0];
                $offset    = $match[1];
                $className = $namespace.$modelName;
                if (is_a($className, PersistableObject::class, true)) {
                    $table = $className::dao()->getMapping()['table'];
                    $sql   = substr_replace($sql, $table, $offset-1, strLen($modelName)+1);
                }
            };
        }
        return $sql;
    }


    /**
     * Convert the next row of a result to an object of the model class.
     *
     * @param  IResult $result
     *
     * @return PersistableObject|null - instance or NULL if the result doesn't hold any more rows
     */
    protected function makeObject(IResult $result) {
        // TODO: Lookup and return an existing instance instead of a copy.
        $row = $result->fetchNext(ARRAY_ASSOC);
        if ($row)
            return PersistableObject::createInstance($this->entityClass, $row);
        return null;
    }


    /**
     * Convert all remaining rows of a result to objects of the model class.
     *
     * @param  IResult $result
     *
     * @return PersistableObject[] - arry of instances or an empty array if the result doesn't hold any more rows
     */
    protected function makeObjects(IResult $result) {

        // TODO: Lookup and return existing instances instead of copies.

        $instances = [];
        while ($row = $result->fetchNext(ARRAY_ASSOC)) {
            $instances[] = PersistableObject::createInstance($this->entityClass, $row);
        }
        return $instances;
    }


    /**
     * Return the database adapter of the Worker's model class.
     *
     * @return IConnector
     */
    public function getConnector() {
        if (!$this->connector) {
            $mapping = $this->dao->getMapping();
            $this->connector = ConnectionPool::getConnector($mapping['connection']);
        }
        return $this->connector;
    }
}

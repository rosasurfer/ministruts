<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\ResultInterface    as IResult;

use rosasurfer\exception\ConcurrentModificationException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\db\ID_PRIMARY;
use const rosasurfer\db\IDX_MAPPING_COLUMN_BEHAVIOR;
use const rosasurfer\db\IDX_MAPPING_COLUMN_NAME;


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

    /** @var string - the name of the DAO's entity class */
    protected $entityClass;


    /**
     * Constructor
     *
     * Create a new DAO.
     */
    protected function __construct() {
        $this->entityClass = subStr(get_class($this), 0, -3);
    }


    /**
     * Find a single matching record and convert it to an object of the model class.
     *
     * @param  string $query     - SQL query
     * @param  bool   $allowMany - whether or not the query is allowed to return a multi-row result (default: no)
     *
     * @return PersistableObject
     *
     * @throws MultipleRowsException if the query returned multiple rows and $allowMany was not set to TRUE.
     */
    public function findOne($query, $allowMany=false) {
        return $this->getWorker()->findOne($query, $allowMany);
    }


    /**
     * Find all matching records and convert them to objects of the model class.
     *
     * @param  string $query - SQL query
     *
     * @return PersistableObject[]
     */
    public function findAll($query) {
        return $this->getWorker()->findAll($query);
    }


    /**
     * Execute a SQL statement and return the result. This method should be used if the SQL statement returns rows.
     *
     * @param  string $sql - SQL statement
     *
     * @return IResult
     */
    public function query($sql) {
        return $this->getWorker()->query($sql);
    }


    /**
     * Return the mapping of the DAO's entity class.
     *
     * @return array
     */
    final public function getMapping() {
        if (!isSet($this->mapping)) throw new UnimplementedFeatureException('You must implement '.get_class($this).'->mapping[] to work with this DAO.');
        return $this->mapping;
    }


    /**
     * Return the name of the DAO's entity class.
     *
     * @return string
     */
    final public function getEntityClass() {
        return $this->entityClass;
    }


    /**
     * Return the database adapter for the DAO's entity class.
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
     * Escape a DBMS string literal, i.e. a string value. The resulting string can be used in queries "as-is" and doesn't
     * need additional quoting.
     *
     * @param  string $value - value to escape
     *
     * @return string - escaped and quoted string value
     */
    public function escapeLiteral($value) {
        return $this->db()->escapeLiteral($value);
    }


    /**
     * Escape a string value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  string $value - value to escape
     *
     * @return string - escaped but not quoted string value
     */
    public function escapeString($value) {
        return $this->db()->escapeString($value);
    }


    /**
     * Reload and return a fresh version of the specified object.
     *
     * @param  PersistableObject $object
     *
     * @return PersistableObject - refreshed version (a new and different instance)
     */
    public function refresh(PersistableObject $object) {
        $class = $this->getEntityClass();
        if (!$object instanceof $class) throw new InvalidArgumentException('Cannot refresh instances of '.get_class($object));
        if (!$object->isPersistent())   throw new InvalidArgumentException('Cannot refresh non-persistent '.get_class($object));

        // TODO: This method cannot yet handle composite primary keys.
        // TODO: Get PK value via __get() if the getter is not defined (causes crash otherwise).

        $mapping   = $this->getMapping();
        $tablename = $mapping['table'];

        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_PRIMARY) {
                $columnName = $column[IDX_MAPPING_COLUMN_NAME];
                break;
            }
        }
        $id = $object->{'get'.$phpName}();

        $sql = "select *
                 from $tablename
                 where $columnName = $id";
        $instance = $this->findOne($sql);

        if (!$instance) throw new ConcurrentModificationException('Error refreshing '.get_class($object).' ('.$id.'), data record not found');
        return $instance;
    }
}

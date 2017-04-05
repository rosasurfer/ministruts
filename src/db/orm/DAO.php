<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\ResultInterface    as IResult;

use rosasurfer\db\orm\meta\EntityMapping;

use rosasurfer\exception\ConcurrentModificationException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use function rosasurfer\strLeft;


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

    /** @var EntityMapping - the mapping of the DAO's entity class */
    private $entityMapping;

    /** @var string - the name of the DAO's entity class */
    protected $entityClass;


    /**
     * Constructor
     *
     * Create a new DAO.
     */
    protected function __construct() {
        parent::__construct();

        $this->entityClass = subStr(get_class($this), 0, -3);
    }


    /**
     * Find a single matching record and convert it to an object of the model class.
     *
     * @param  string $query     - SQL query with optional ORM syntax
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
     * @param  string $query - SQL query with optional ORM syntax
     *
     * @return PersistableObject[]
     */
    public function findAll($query) {
        return $this->getWorker()->findAll($query);
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
     * Return the mapping of the DAO's entity class.
     *
     * @return array
     */
    public function getMapping() {
        if (!isSet($this->mapping)) throw new UnimplementedFeatureException('Define '.get_class($this).'->mapping[] to work with this DAO!');
        return $this->mapping;
    }


    /**
     * Return the mapping of the DAO's entity class.
     *
     * @return EntityMapping
     */
    public function getEntityMapping() {
        if (!$this->entityMapping) {
            $this->entityMapping = new EntityMapping($this->getEntityClass(), $this->getMapping());
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
     * Escape a string value. The resulting string must be quoted according to the DBMS before it can be used in queries.
     *
     * @param  scalar|null $value - value to escape
     *
     * @return string|null - escaped but unquoted string or NULL if the value was NULL
     */
    public function escapeString($value) {
        return $this->db()->escapeString($value);
    }


    /**
     * Perform the insertion of a data record representing a {@link PersistableObject} instance.
     *
     * @param  array $values - record values
     *
     * @return mixed - the inserted record's identity value
     */
    public function doInsert(array $values) {
        $db       = $this->db();
        $entity   = $this->getEntityMapping();
        $table    = $entity->getTableName();
        $identity = $entity->getIdentity();
        $idName   = $identity->getPhpName();
        $idValue  = null;
        if (isSet($values[$idName])) $idValue = $values[$idName];
        else                         unset($values[$idName]);

        // convert values to their SQL representation
        $columns = [];
        foreach ($values as $name => &$value) {
            $property  = $entity->getProperty($name);
            $columns[] = $property->getColumnName();
            $value     = $property->convertToSQL($value, $db);
        }; unset($value);

        // create SQL statement
        $sql = 'insert into '.$table.' ('.join(', ', $columns).')
                   values ('.join(', ', $values).')';

        // execute SQL statement
        if (isSet($idValue)) {
            $db->execute($sql);
        }
        else if ($db->supportsInsertReturn()) {
            $idName  = $identity->getColumnName();
            $idValue = $db->query($sql.' returning '.$idName)->fetchInt();
        }
        else {
            $idValue = $db->execute($sql)->lastInsertId();
        }
        return $idValue;
    }


    /**
     * Perform the actual update and write modifications of a {@link PersistableObject} to the storage mechanism.
     *
     * @param  PersistableObject $object  - modified object
     * @param  array             $changes - modifications
     *
     * @return mixed - The new PHP version value after writing the changes if the object's entity class is versioned or TRUE
     *                 if the entity class is not versioned. FALSE in case of an error.
     */
    public function doUpdate(PersistableObject $object, array $changes) {
        $db     = $this->db();
        $entity = $this->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumnName();
        $idValue  = $identity->convertToSQL($object->getOid(), $db);

        // collect version infos
        $versionName = $versionColumn = $oldVersion = $newVersion = null;
        $version     = $entity->getVersion();
        if ($version) {
            $versionName   = $version->getPhpName();
            $versionColumn = $version->getColumnName();
            unset($changes[$versionName], $changes[$identity->getPhpName()]);

            if (isSet($changes[$versionName])) {
                $version = null;                                    // TODO: throw exception
            }
            else {
                $oldVersion = $version->convertToSQL($changes['old.version'], $db);
                $newVersion = $changes['new.version'];
                unset($changes['old.version'], $changes['new.version']);
                $changes[$versionName] = $newVersion;
            }
        }

        // create SQL
        $sql = 'update '.$table.' set';                             // update table
        foreach ($changes as $name => $value) {                     //    set ...
            $property    = $entity->getProperty($name);             //        ...
            $columnName  = $property->getColumnName();              //        ...
            $columnValue = $property->convertToSQL($value, $db);    //        column1 = value1,
            $sql .= ' '.$columnName.' = '.$columnValue.',';         //        column2 = value2,
        }                                                           //        ...
        $sql  = strLeft($sql, -1);                                  //        ...
        $sql .= ' where '.$idColumn.' = '.$idValue;                 //    where id = value
        if ($version) {                                             //        ...
            $op   = $oldVersion=='null' ? 'is':'=';                 //        ...
            $sql .= ' and '.$versionColumn.' '.$op.' '.$oldVersion; //      and version = oldVersion
        }

        // execute SQL and check for concurrent modifications
        if ($db->execute($sql)->lastAffectedRows() != 1) {
            $found = $this->refresh($object);
            throw new ConcurrentModificationException('Error updating '.get_class($object).' (oid='.$object->getOid().'), expected version: '.$oldVersion.', found version: '.$found->getOversion());
        }
        return $newVersion;
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

        $mapping  = $this->getEntityMapping();
        $table    = $mapping->getTableName();
        $identity = $mapping->getIdentity();
        $column   = $identity->getColumnName();
        $id       = $identity->convertToSQL($object->getOid(), $this->db());

        $sql = 'select *
                 from '.$table.'
                 where '.$column.' = '.$id;
        $instance = $this->findOne($sql);

        if (!$instance) throw new ConcurrentModificationException('Error refreshing '.get_class($object).' ('.$object->getOid().'), data record not found');
        return $instance;
    }
}

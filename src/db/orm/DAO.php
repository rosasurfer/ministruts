<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Singleton;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\MultipleRecordsException;
use rosasurfer\db\ResultInterface as IResult;
use rosasurfer\db\orm\meta\EntityMapping;
use rosasurfer\exception\ConcurrentModificationException;

use function rosasurfer\strLeft;
use rosasurfer\exception\RuntimeException;


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
     * Constructor
     *
     * Create a new DAO.
     */
    protected function __construct() {
        parent::__construct();

        $this->entityClass = subStr(get_class($this), 0, -3);
    }


    /**
     * Find a single matching record and convert it to an instance of the entity class.
     *
     * @param  string $query     - SQL query with optional ORM syntax
     * @param  bool   $allowMany - whether or not the query is allowed to return a multi-row result (default: no)
     *
     * @return PersistableObject|null
     *
     * @throws MultipleRecordsException if the query returned multiple rows and $allowMany was not set to TRUE.
     */
    public function find($query, $allowMany=false) {
        return $this->getWorker()->find($query, $allowMany);
    }


    /**
     * Find all matching records and convert them to instances of the entity class.
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

            if (!isSet($property['column'     ])) $property['column'     ] = $name;
            if (!isSet($property['column-type'])) $property['column-type'] = $type; // TODO: bug, $type can be a custom type

            if (isSet($property['primary']) && $property['primary']===true)
                $mapping['identity'] = &$property;

            if (isSet($property['version']) && $property['version']===true)
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

        if (isSet($mapping['relations'])) {
            foreach ($mapping['relations'] as $i => $property) {
                $name  = $property['name' ];
                $type  = $property['type' ];
                $assoc = $property['assoc'];

                // any association using a mandatory or optional join table
                if ($assoc=='many-to-many' || isSet($property['join-table'])) {
                    if (!isSet($property['join-table']))                       throw new RuntimeException('Missing attribute "join-table"="{table_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isSet($property['key']))
                        $property['key'] = $mapping['identity']['name'];
                    else if (!isSet($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isSet($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{'.$property['join-table'].'.column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isSet($property['fk-ref-column']))                    throw new RuntimeException('Missing attribute "fk-ref-column"="{'.$property['join-table'].'.column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }

                // one-to-one (using no optional join table)
                else if ($assoc == 'one-to-one') {
                    if (!isSet($property['column'])) {
                        if (!isSet($property['key']))
                            $property['key'] = $mapping['identity']['name'];
                        else if (!isSet($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                        if (!isSet($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    }
                }

                // one-to-many (using no optional join table)
                else if ($assoc == 'one-to-many') {
                    if (!isSet($property['key']))
                        $property['key'] = $mapping['identity']['name'];
                    else if (!isSet($mapping['properties'][$property['key']])) throw new RuntimeException('Illegal attribute "key"="'.$property['key'].'" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                    if (!isSet($property['ref-column']))                       throw new RuntimeException('Missing attribute "ref-column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }

                // many-to-one (using no optional join table)
                else if ($assoc == 'many-to-one') {
                    if (!isSet($property['column'])) throw new RuntimeException('Missing attribute "column"="{column_name}" in relation [name="'.$name.'"  assoc="'.$assoc.'"...] of entity class "'.$mapping['class'].'"');
                }
                else                                 throw new RuntimeException('Illegal attribute "assoc"="'.$assoc.'" in relation [name="'.$name.'"...] of entity class "'.$mapping['class'].'"');

                if (isSet($property['column']))
                    $mapping['columns'][$property['column']] = &$property;
                $getter = 'get'.$name;
                $mapping['getters'][$getter] = &$property;
                $mapping['relations'][$name] = &$property;
                unset($mapping['relations'][$i], $property);
            };
        }
        else $mapping['relations'] = [];

        $mapping['columns'] = array_change_key_case($mapping['columns'], CASE_LOWER);
        $mapping['getters'] = array_change_key_case($mapping['getters'], CASE_LOWER);

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
        $mapping  = $this->getMapping();
        $table    = $mapping['table'];
        $idColumn = $mapping['identity']['column'];
        $id       = null;
        if  (isSet($values[$idColumn])) $id = $values[$idColumn];
        else unset($values[$idColumn]);

        // translate column values
        foreach ($values as &$value) {
            $value = $db->escapeLiteral($value);
        }; unset($value);

        // create SQL statement
        $sql = 'insert into '.$table.' ('.join(', ', array_keys($values)).')
                   values ('.join(', ', $values).')';

        // execute SQL statement
        if ($id) {
            $db->execute($sql);
        }
        else if ($db->supportsInsertReturn()) {
            $id = $db->query($sql.' returning '.$idColumn)->fetchInt();
        }
        else {
            $id = $db->execute($sql)->lastInsertId();
        }
        return $id;
    }


    /**
     * Perform the actual update and write modifications of a {@link PersistableObject} to the storage mechanism.
     *
     * @param  PersistableObject $object  - modified instance
     * @param  array             $changes - modifications
     *
     * @return bool - success status
     */
    public function doUpdate(PersistableObject $object, array $changes) {
        $db     = $this->db();
        $entity = $this->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumn();
        $idValue  = $identity->convertToDBValue($object->getObjectId(), $db);

        // collect version infos
        $versionMapping = $versionName = $versionColumn = $oldVersion = null;
        //if ($versionMapping = $entity->getVersion()) {
        //    $versionName   = $versionMapping->getName();
        //    $versionColumn = $versionMapping->getColumn();
        //    $oldVersion    = $object->getSnapshot()->$versionName;        // TODO: implement dirty check via snapshot
        //    $oldVersion    = $versionMapping->convertToDBValue($oldVersion, $db);
        //}

        // create SQL
        $sql = 'update '.$table.' set';                                     // update table
        foreach ($changes as $name => $value) {                             //    set ...
            $mapping     = $entity->getProperty($name);                     //        ...
            $columnName  = $mapping->getColumn();                           //        ...
            $columnValue = $mapping->convertToDBValue($value, $db);         //        column1 = value1,
            $sql .= ' '.$columnName.' = '.$columnValue.',';                 //        column2 = value2,
        }                                                                   //        ...
        $sql  = strLeft($sql, -1);                                          //        ...
        $sql .= ' where '.$idColumn.' = '.$idValue;                         //    where id = value
        if ($versionMapping) {                                              //        ...
            $op   = $oldVersion=='null' ? 'is':'=';                         //        ...
            $sql .= ' and '.$versionColumn.' '.$op.' '.$oldVersion;         //      and version = oldVersion
        }

        // execute SQL and check for concurrent modifications
        if ($db->execute($sql)->lastAffectedRows() != 1) {
            if ($versionMapping) {
                $object->reload();
                $msg = 'expected version: '.$oldVersion.', found version: '.$object->$versionName;
            }
            else $msg = 'record not found';
            throw new ConcurrentModificationException('Error updating '.get_class($object).' (oid='.$object->getObjectId().'), '.$msg);
        }
        return true;
    }


    /**
     * Perform the actual deletion of a {@link PersistableObject}.
     *
     * @param  PersistableObject $object
     *
     * @return bool - success status
     */
    public function doDelete(PersistableObject $object) {
        $db     = $this->db();
        $entity = $this->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumn();
        $idValue  = $identity->convertToDBValue($object->getObjectId(), $db);

        // create SQL
        $sql = 'delete from '.$table.'
                   where '.$idColumn.' = '.$idValue;

        // execute SQL and check for concurrent modifications
        if ($db->execute($sql)->lastAffectedRows() != 1)
            throw new ConcurrentModificationException('Error deleting '.get_class($object).' (oid='.$object->getObjectId().'): record not found');
        return true;
    }
}

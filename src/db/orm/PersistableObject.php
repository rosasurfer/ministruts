<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\ConcurrentModificationException;
use rosasurfer\core\exception\IllegalAccessException;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\exception\InvalidTypeException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\db\ConnectorInterface as IConnector;

use function rosasurfer\is_class;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;


/**
 * PersistableObject
 *
 * Abstract base class for stored objects.
 */
abstract class PersistableObject extends CObject {


    /** @var bool - dirty checking status */
    private $__modified = false;

    /** @var bool - flag to detect and handle recursive $this->save() calls */
    private $__inSave = false;

    /** @var bool - flag to detect and handle recursive $this->delete() calls */
    private $__inDelete = false;


    /**
     * Constructor.
     *
     * Create a new instance.
     */
    protected function __construct() {
        // post-event hook
        $this->afterCreate();
    }


    /**
     * Magic method providing default get/set implementations for mapped properties.
     *
     * @param  string $method - name of the called and undefined method
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed - return value of the intercepted virtual method
     */
    public function __call($method, array $args) {
        $dao     = $this->dao();
        $mapping = $dao->getMapping();
        $methodL = strtolower($method);

        // calls to getters of mapped properties are intercepted
        if (isset($mapping['getters'][$methodL])) {
            $property = $mapping['getters'][$methodL]['name'];
            return $this->get($property);
        }

        // calls to setters of mapped properties are intercepted
        //if (isset($mapping['setters'][$methodL])) {               // TODO: implement default setters
        //    $property = $mapping['getters'][$methodL]['name'];
        //    $this->$property = $args;
        //    return $this;
        //}

        // all other calls are passed on
        parent::__call($method, $args);
    }


    /**
     * Prevent serialization of related objects (transient behavior). Instead store the physical property value.
     * After __wakeup() relations will be re-fetched on access.
     *
     * @return string[] - array of property names to serialize
     */
    public function __sleep() {
        $mapping = $this->dao()->getMapping();
        $array   = (array) $this;

        foreach (\array_keys($mapping['relations']) as $name) {
            if (is_object($this->$name)) {
                /** @var PersistableObject $object */
                $object = $this->$name;
                $this->$name = $object->getObjectId();
            }
            else if (is_array($this->$name)) {                      // property access level encoding
                $protected = "\0*\0".$name;                         // ------------------------------
                $public    = $name;                                 // private:   "\0{className}\0{propertyName}"
                unset($array[$protected], $array[$public]);         // protected: "\0*\0{propertyName}"
            }                                                       // public:    "{propertyName}"
        }
        return \array_keys($array);
    }


    /**
     * Return the logical value of a mapped property.
     *
     * @param  string $property - property name
     *
     * @return mixed - property value
     */
    protected function get($property) {
        $mapping = $this->dao()->getMapping();

        if (isset($mapping['properties'][$property]))
            return $this->getNonRelationValue($property);

        if (isset($mapping['relations'][$property]))
            return $this->getRelationValue($property);

        throw new RuntimeException('Not a mapped property "'.$property.'"');
    }


    /**
     * Return the logical value of a mapped non-relation property.
     *
     * @param  string $property - property name
     *
     * @return mixed - property value
     */
    private function getNonRelationValue($property) {
        return $this->$property;
    }


    /**
     * Return the logical value of a mapped relation property. If the related objects have not yet been fetched before they
     * are fetched now.
     *
     * @param  string $property - property name
     *
     * @return PersistableObject|PersistableObject[]? - property value
     */
    private function getRelationValue($property) {
        $propertyName = $property;
        /** @var ?PersistableObject|PersistableObject[]|scalar $value */
        $value = &$this->$propertyName;                                 // existing property value

        if (is_object($value)) return $value;                           // relation is fetched and is an object or an array
        if (is_array ($value)) return $value;                           // (Collections are not yet implemented)

        $dao          =  $this->dao();
        $mapping      =  $dao->getMapping();
        $relation     = &$mapping['relations'][$propertyName];
        $isCollection = strEndsWith($relation['assoc'], 'many');
        /** @var PersistableObject[]? $emptyResult */
        $emptyResult  = $isCollection ? [] : null;

        if ($value === null) {
            if (!$this->isPersistent())
                return $emptyResult;
        }
        else if ($value === false) {                                    // relation is fetched and marked as empty
            return $emptyResult;
        }

        // The relation is not yet fetched, the property is NULL or holds a physical foreign-key value.
        $type           = $relation['type'];                            // related class name
        /** @var DAO $relatedDao */
        $relatedDao     = $type::dao();
        $relatedMapping = $relatedDao->getMapping();
        $relatedTable   = $relatedMapping['table'];

        if ($value === null) {
            if (isset($relation['column'])) {                           // a local column with a foreign-key value of NULL
                $value = false;
                return $emptyResult;                                    // the relation is marked as empty
            }

            // a local key is used
            if (!isset($relation['key']))
                $relation['key'] = $mapping['identity']['name'];        // default local key is identity
            $keyName = $relation['key'];                                // the used local key property
            if ($this->$keyName === null) {
                $value = false;                                         // the relation is marked as empty
                return $emptyResult;
            }
            $keyColumn = $mapping['properties'][$keyName]['column'];    // the used local key column
            $refColumn = $relation['ref-column'];                       // the referencing column

            if (!isset($relation['join-table'])) {
                // the referencing column is part of the related table
                $refColumnType = $relatedMapping['columns'][$refColumn]['type'];
                $refValue      = $relatedDao->escapeLiteral($this->getPhysicalValue($keyColumn, $refColumnType));
                $sql = 'select r.*
                            from '.$relatedTable.' r
                            where r.'.$refColumn.' = '.$refValue;
            }
            else {
                // the referenced column is part of a join table
                $joinTable = $relation['join-table'];
                $keyValue  = $dao->escapeLiteral($this->getPhysicalValue($keyColumn));  // the physical local key value

                if (!isset($relation['foreign-key']))
                    $relation['foreign-key'] = $relatedMapping['identity']['name'];     // default foreign-key is identity
                $fkName      = $relation['foreign-key'];                                // the used foreign-key property
                $fkColumn    = $relatedMapping['properties'][$fkName]['column'];        // the used foreign-key column
                $fkRefColumn = $relation['fk-ref-column'];                              // join column referencing the foreign-key
                $sql = 'select r.*
                            from '.$relatedTable.' r
                            join '.$joinTable.'    j on r.'.$fkColumn.' = j.'.$fkRefColumn.'
                            where j.'.$refColumn.' = '.$keyValue;
            }
            if ($isCollection) {                                                        // default result sorting
                $relatedIdColumn = $relatedMapping['identity']['column'];               // the related identity column
                $sql .= ' order by r.'.$relatedIdColumn;                                // sort by identity
            }
        }
        else {
            // $value holds a non-NULL column-type foreign-key value pointing to a single related record
            if (isset($relation['join-table'])) {
                if (!isset($relation['foreign-key']))
                    $relation['foreign-key'] = $relatedMapping['identity']['name'];     // default foreign-key is identity
                $fkName   = $relation['foreign-key'];                                   // the used foreign-key property
                $fkColumn = $relatedMapping['properties'][$fkName]['column'];           // the used foreign-key column
            }
            else if (isset($relation['column'])) {                      // a local column referencing the foreign key
                if (!isset($relation['ref-column']))                    // default foreign-key is identity
                    $relation['ref-column'] = $relatedMapping['identity']['column'];
                $fkColumn = $relation['ref-column'];                    // the used foreign-key column
            }
            else {
                $fkColumn = $relatedMapping['identity']['column'];      // the used foreign-key column is identity
            }
            $fkValue = $relatedDao->escapeLiteral($value);
            $sql = 'select r.*
                        from '.$relatedTable.' r
                        where r.'.$fkColumn.' = '.$fkValue;
        }

        if (!$isCollection) $value = $relatedDao->find($sql);           // => PersistableObject
        else                $value = $relatedDao->findAll($sql);        // => PersistableObject[]

        return $value;
    }


    /**
     * Return the value of a mapped column.
     *
     * @param  string $column          - column name
     * @param  string $type [optional] - column type (default: type as configured in the entity mapping)
     *
     * @return mixed - column value
     */
    private function getPhysicalValue($column, $type = null) {
        $mapping = $this->dao()->getMapping();
        $column  = strtolower($column);
        if (!isset($mapping['columns'][$column])) throw new RuntimeException('Not a mapped column "'.func_get_arg(0).'"');

        $property      = &$mapping['columns'][$column];
        $propertyName  =  $property['name'];
        $propertyValue =  $this->$propertyName;             // the logical or physical column value

        if ($propertyValue === null)
            return null;

        if (isset($property['assoc'])) {
            if ($propertyValue === false)
                return null;
            if (!is_object($propertyValue)) {               // a foreign-key value of a not-yet-fetched relation
                if ($type !== null) throw new RuntimeException('Unexpected parameter $type="'.$type.'" (not null) for relation [name="'.$propertyName.'", column="'.$column.'", ...] of entity "'.$mapping['class'].'"');
                return $propertyValue;
            }

            /** @var PersistableObject $object */
            $object = $propertyValue;                       // a single instance of "one-to-one"|"many-to-one" relation, no join table
            if (!isset($property['ref-column']))
                $property['ref-column'] = $object->dao()->getMapping()['identity']['column'];
            $fkColumn = $property['ref-column'];
            return $object->getPhysicalValue($fkColumn);
        }

        $columnType = $property['column-type'];

        switch ($columnType) {
            case 'bool'   :
            case 'boolean': return (bool)(int) $propertyValue;

            case 'int'    :
            case 'integer': return (int) $propertyValue;

            case 'real'   :
            case 'float'  :
            case 'double' :
            case 'decimal': return (float) $propertyValue;

            case 'text'   :
            case 'string' : return (string) $propertyValue;

            default:
                // TODO: convert custom types (e.g. Enum|DateTime) to physical values
                //if (is_class($propertyType)) {
                //    $object->$propertyName = new $propertyType($row[$column]);
                //    break;
                //}
        }
        throw new RuntimeException('Unsupported attribute "column-type"="'.$columnType.'" in property [name="'.$propertyName.'", ...] of entity "'.$mapping['class'].'"');
    }


    /**
     * Return the instance's identity value.
     *
     * @return mixed - identity value
     */
    final public function getObjectId() {
        $mapping  = $this->dao()->getMapping();
        $property = $mapping['identity']['name'];
        return $this->$property;
    }


    /**
     * Whether the instance was already saved and has a value assigned to it's id property.
     *
     * @return bool
     */
    final public function isPersistent() {
        // TODO: this check cannot yet handle composite primary keys
        $id = $this->getObjectId();
        return ($id !== null);
    }


    /**
     * Whether the instance is marked as "soft deleted".
     *
     * @return bool
     */
    final public function isDeleted() {
        foreach ($this->dao()->getMapping()['properties'] as $name => $property) {
            if (isset($property['soft-delete']) && $property['soft-delete']===true) {
                return ($this->$name !== null);
            }
        }
        return false;
    }


    /**
     * Whether the instance status is "modified".
     *
     * @return bool
     */
    final public function isModified() {
        return (bool) $this->__modified;
    }


    /**
     * Set the instance status to "modified".
     *
     * @return $this
     */
    final protected function modified() {
        $this->__modified = true;
        return $this;
    }


    /**
     * Save the instance in the storage mechanism.
     *
     * @return $this
     */
    public function save() {
        if ($this->__inSave)                                        // skip recursive calls from pre/post-processing hooks
            return $this;

        try {
            $this->__inSave = true;

            if (!$this->isPersistent()) {
                $this->dao()->transaction(function() {
                    if ($this->beforeSave() !== true)               // pre-processing hook
                        return $this;
                    $this->insert();
                    $this->afterSave();                             // post-processing hook
                });
            }
            elseif ($this->isModified()) {
                $this->dao()->transaction(function() {
                    if ($this->beforeSave() !== true)               // pre-processing hook
                        return $this;
                    $this->update();
                    $this->afterSave();                             // post-processing hook
                });
            }
            else {
                // persistent but not modified
            }
        }
        finally {
            $this->__inSave = false;
        }
        return $this;
    }


    /**
     * Insert this instance into the storage mechanism.
     *
     * @return $this
     */
    private function insert() {
        if ($this->isPersistent()) throw new RuntimeException('Cannot insert already persistent '.$this);

        // pre-processing hook
        if ($this->beforeInsert() !== true)
            return $this;

        $mapping = $this->dao()->getMapping();

        // collect column values
        $values = [];
        foreach (\array_keys($mapping['columns']) as $column) {
            $values[$column] = $this->getPhysicalValue($column);
        };

        // perform insertion
        $id = $this->doInsert($values);
        $this->__modified = false;

        // assign the returned identity value
        $idName = $mapping['identity']['name'];
        if ($this->$idName === null)
            $this->$idName = $id;

        // post-processing hook
        $this->afterInsert();
        return $this;
    }


    /**
     * Update the instance.
     *
     * @return $this
     */
    private function update() {
        // pre-processing hook
        if ($this->beforeUpdate() !== true)
            return $this;

        $mapping = $this->dao()->getMapping();
        $changes = [];

        // collect modified properties and their values
        foreach ($mapping['properties'] as $property) {                 // TODO: Until the dirty check is implemented all
            $propertyName = $property['name'];                          //       properties are assumed dirty.
            $changes[$propertyName] = $this->$propertyName;
        }

        // perform update
        if ($this->doUpdate($changes)) {
            $this->__modified = false;

            // post-processing hook
            $this->afterUpdate();
        }
        return $this;
    }


    /**
     * Delete the instance from the storage mechanism.
     *
     * @return $this
     */
    public function delete() {
        if ($this->__inDelete)                                                  // skip recursive calls from pre/post-processing hooks
            return $this;

        try {
            $this->__inDelete = true;

            if (!$this->isPersistent()) throw new IllegalStateException('Cannot delete non-persistent '.get_class($this));

            $this->dao()->transaction(function() {
                if ($this->beforeDelete() !== true)                             // pre-processing hook
                    return $this;

                if ($this->doDelete()) {                                        // perform deletion
                    // reset identity property
                    $idName = $this->dao()->getMapping()['identity']['name'];
                    $this->$idName = null;

                    $this->afterDelete();                                       // post-processing hook
                }
            });
        }
        finally {
            $this->__inDelete = false;
        }
        return $this;
    }


    /**
     * Perform the actual insertion of a data record representing the instance.
     *
     * @param  array $values - record values
     *
     * @return mixed - the inserted record's identity value
     */
    private function doInsert(array $values) {
        $db       = $this->db();
        $mapping  = $this->dao()->getMapping();
        $table    = $mapping['table'];
        $idColumn = $mapping['identity']['column'];
        $id       = null;
        if  (isset($values[$idColumn])) $id = $values[$idColumn];
        else unset($values[$idColumn]);

        // translate column values
        foreach ($values as &$value) {
            $value = $db->escapeLiteral($value);
        }
        unset($value);

        // create SQL statement
        $sql = 'insert into '.$table.' ('.join(', ', \array_keys($values)).')
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
     * Perform the actual update and write modifications of the instance to the storage mechanism.
     *
     * @param  array $changes - modifications
     *
     * @return bool - success status
     */
    private function doUpdate(array $changes) {
        $db     = $this->db();
        $entity = $this->dao()->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumn();
        $idValue  = $identity->convertToDBValue($this->getObjectId(), $db);

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
                $this->reload();
                $msg = 'expected version: '.$oldVersion.', found version: '.$this->$versionName;
            }
            else $msg = 'record not found';
            throw new ConcurrentModificationException('Error updating '.get_class($this).' (oid='.$this->getObjectId().'), '.$msg);
        }
        return true;
    }


    /**
     * Perform the actual deletion of the instance.
     *
     * @return bool - success status
     */
    private function doDelete() {
        $db     = $this->db();
        $entity = $this->dao()->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumn();
        $idValue  = $identity->convertToDBValue($this->getObjectId(), $db);

        // create SQL
        $sql = 'delete from '.$table.'
                   where '.$idColumn.' = '.$idValue;

        // execute SQL and check for concurrent modifications
        if ($db->execute($sql)->lastAffectedRows() != 1)
            throw new ConcurrentModificationException('Error deleting '.get_class($this).' (oid='.$this->getObjectId().'): record not found');
        return true;
    }


    /**
     * Populate this instance with the specified record values. Called during execution of {@link Worker::makeObject()} and
     * {@link PersistableObject::reload()}.
     *
     * @param  array $row - array with column values (typically a database record)
     *
     * @return $this
     */
    private function populate(array $row) {
        $row     = \array_change_key_case($row, CASE_LOWER);
        $mapping = $this->dao()->getMapping();
        $dbType  = $this->dao()->db()->getType();

        foreach ($mapping['columns'] as $column => &$property) {
            $propertyName = $property['name'];

            if ($row[$column] === null) {
                $this->$propertyName = null;
            }
            else {
                $propertyType = $property['type'];

                if (isset($property['assoc'])) {                // $property[type] is a PersistableObject class
                    //if (!isset($property['column-type'])) {
                    //    $relatedMapping = $propertyType::dao()->getMapping();
                    //    if (!isset($property['ref-column']))
                    //        $property['ref-column'] = $relatedMapping['identity']['column'];
                    //    $refColumn = $property['ref-column'];
                    //    $property['column-type'] = $relatedMapping['columns'][$refColumn]['type'];
                    //}
                    //$propertyType = $property['column-type'];
                    $propertyType = 'origin';                   // the foreign-key column is stored as provided
                }

                switch ($propertyType) {
                    case 'origin' : $this->$propertyName =             $row[$column]; break;

                    case 'bool'   :
                    case 'boolean':
                        if ($dbType == 'pgsql') {
                            if      ($row[$column] == 't') $row[$column] = 1;
                            else if ($row[$column] == 'f') $row[$column] = 0;
                        }
                                    $this->$propertyName = (bool)(int) $row[$column]; break;
                    case 'int'    :
                    case 'integer': $this->$propertyName =       (int) $row[$column]; break;
                    case 'float'  :
                    case 'double' : $this->$propertyName =     (float) $row[$column]; break;
                    case 'string' : $this->$propertyName =    (string) $row[$column]; break;
                  //case 'array'  : $this->$propertyName =   strlen($row[$column]) ? explode(',', $row[$column]):[]; break;
                  //case DateTime::class: $this->$propertyName = new DateTime($row[$column]); break;

                    default:
                        // TODO: handle custom types
                        //if (is_class($propertyType)) {
                        //    $object->$propertyName = new $propertyType($row[$column]);
                        //    break;
                        //}
                        throw new RuntimeException('Unsupported PHP type "'.$propertyType.'" for mapping of database column '.$mapping['table'].'.'.$column);
                }
            }
        }
        return $this;
    }


    /**
     * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
     * database query result records to instances of the respective entity class.
     *
     * @param  string $class - entity class name
     * @param  array  $row   - array with property values (a result row from a database query)
     *
     * @return PersistableObject
     */
    public static function populateNew($class, array $row) {
        if (static::class != __CLASS__)     throw new IllegalAccessException('Cannot access method '.__METHOD__.'() on a derived class.');
        if (!is_a($class, __CLASS__, true)) throw new InvalidTypeException('Not a '.__CLASS__.' subclass: '.$class);

        /** @var self $object */
        $object = new $class();
        $object->populate($row);
        return $object;
    }


    /**
     * Reload this instance from the database and optionally reset relations.
     *
     * @param  bool $resetRelations [optional] - NOT YET IMPLEMENTED
     *                                           Whether to reset relations and re-fetch on next access.
     *                                           (default: no)
     * @return $this
     */
    public function reload($resetRelations = false) {   // TODO: implement and set default=TRUE
        if (!$this->isPersistent()) throw new IllegalStateException('Cannot reload non-persistent '.get_class($this));

        // TODO: This method cannot yet handle composite primary keys.

        $db     = $this->db();
        $dao    = $this->dao();
        $entity = $dao->getEntityMapping();
        $table  = $entity->getTableName();

        // collect identity infos
        $identity = $entity->getIdentity();
        $idColumn = $identity->getColumn();
        $idName   = $identity->getName();
        $idValue  = $identity->convertToDBValue($this->$idName, $db);

        // create and execute SQL
        $sql = 'select *
                   from '.$table.'
                   where '.$idColumn.' = '.$idValue;
        $row = $db->query($sql)->fetchRow();
        if ($row === null) throw new ConcurrentModificationException('Error reloading '.get_class($this).' ('.$this->getObjectId().'), record not found');

        // apply record values
        return $this->populate($row);
    }


    /**
     * "Create" post-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code.
     *
     * @return void
     */
    protected function afterCreate() {
    }


    /**
     * "Save" pre-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return bool - If the method does not return boolean TRUE the "save" operation is canceled.
     */
    protected function beforeSave() {
        return true;
    }


    /**
     * "Save" post-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return void
     */
    protected function afterSave() {
    }


    /**
     * "Insert" pre-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return bool - If the method does not return boolean TRUE the "insert" operation is canceled.
     */
    protected function beforeInsert() {
        return true;
    }


    /**
     * "Insert" post-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return void
     */
    protected function afterInsert() {
    }


    /**
     * "Update" pre-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return bool - If the method does not return boolean TRUE the "update" operation is canceled.
     */
    protected function beforeUpdate() {
        return true;
    }


    /**
     * "Update" post-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "save" transaction.
     *
     * @return void
     */
    protected function afterUpdate() {
    }


    /**
     * "Delete" pre-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "delete" transaction.
     *
     * @return bool - If the method does not return boolean TRUE the "delete" operation is canceled.
     */
    protected function beforeDelete() {
        return true;
    }


    /**
     * "Delete" post-processing hook.
     *
     * Application-side ORM trigger to execute arbitrary code. Part of the implicite "delete" transaction.
     *
     * @return void
     */
    protected function afterDelete() {
    }


    /**
     * Return the {@link DAO} for the calling class.
     *
     * @return DAO
     */
    public static function dao() {
        if (static::class == __CLASS__) throw new IllegalAccessException('Use an entity class to access method '.__METHOD__.'()');
        return DAO::getImplementation(static::class.'DAO');
        // TODO: The calling class may be a derived class with the entity class being one of its parents.
    }


    /**
     * Return the database adapter for the calling class.
     *
     * @return IConnector
     */
    public static function db() {
        if (static::class == __CLASS__) throw new IllegalAccessException('Use an entity class to access method '.__METHOD__.'()');
        return self::dao()->db();
    }
}

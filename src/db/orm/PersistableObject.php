<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\ConcurrentModificationException;
use rosasurfer\ministruts\core\exception\IllegalAccessException;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;
use rosasurfer\ministruts\db\orm\meta\PropertyMapping;

use function rosasurfer\ministruts\is_class;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strLeft;


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
     * @param  string  $method - name of the undefined method
     * @param  mixed[] $args   - arguments passed to the method call
     *
     * @return mixed - return value of the intercepted call
     */
    public function __call($method, array $args) {
        $dao = $this->dao();
        $mapping = $dao->getMapping();
        $methodL = strtolower($method);

        // calls to getters of mapped properties are intercepted
        if (isset($mapping['getters'][$methodL])) {
            $propertyName = $mapping['getters'][$methodL]['name'];
            return $this->get($propertyName);
        }

        // calls to setters of mapped properties are intercepted
        //if (isset($mapping['setters'][$methodL])) {               // TODO: implement default setters
        //    $propertyName = $mapping['getters'][$methodL]['name'];
        //    $this->$propertyName = $args;
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
        $array = (array) $this;

        foreach ($mapping['relations'] as $name => $v) {
            if (is_object($this->$name)) {
                /** @var PersistableObject $object */
                $object = $this->$name;
                $this->$name = $object->getObjectId();
            }
            elseif (is_array($this->$name)) {                       // property access level encoding
                $protected = "\0*\0$name";                          // ------------------------------
                $public = $name;                                    // private:   "\0{className}\0{propertyName}"
                unset($array[$protected], $array[$public]);         // protected: "\0*\0{propertyName}"
            }                                                       // public:    "{propertyName}"
        }
        return \array_keys($array);
    }


    /**
     * Return the logical PHP value of a mapped property.
     *
     * @param  string $property - property name
     *
     * @return mixed - property value
     */
    protected function get(string $property) {
        $mapping = $this->dao()->getMapping();

        if (isset($mapping['properties'][$property])) {
            return $this->getNonRelationValue($property);
        }
        if (isset($mapping['relations'][$property])) {
            return $this->getRelationValue($property);
        }
        throw new RuntimeException("Not a mapped property \"$property\"");
    }


    /**
     * Return the logical PHP value of a mapped non-relation property.
     *
     * @param  string $property - property name
     *
     * @return mixed - property value
     */
    private function getNonRelationValue($property) {
        return $this->$property;
    }


    /**
     * Return the logical PHP value of a mapped relation property. If the related objects have not yet
     * been fetched they are fetched now.
     *
     * @param  string $property - property name
     *
     * @return PersistableObject|PersistableObject[]|null - property value
     */
    private function getRelationValue($property) {
        $propertyName = $property;
        /** @var PersistableObject|PersistableObject[]|scalar|null $value */
        $value = &$this->$propertyName;                                 // existing property value

        if (is_object($value)) return $value;                           // relation was already fetched and is object or array
        if (is_array ($value)) return $value;                           // (collections are not yet implemented)

        $dao = $this->dao();
        $mapping = $dao->getMapping();
        $relation = &$mapping['relations'][$propertyName];
        $isCollection = strEndsWith($relation['assoc'], 'many');
        $emptyResult = $isCollection ? [] : null;

        // scalar|null $value
        if ($value === null) {
            if (!$this->isPersistent()) {
                return $emptyResult;
            }
        }
        elseif ($value === false) {                                     // relation was already fetched and marked as empty
            return $emptyResult;
        }

        // The relation is not yet fetched, the property is NULL or holds a physical foreign-key value.
        $type = $relation['type'];                                      // related class name
        /** @var DAO $relatedDao */
        $relatedDao = $type::dao();
        $relatedMapping = $relatedDao->getMapping();
        $relatedTable = $relatedMapping['table'];

        if ($value === null) {
            if (isset($relation['column'])) {                           // a local column with a foreign-key value of NULL
                $value = false;                                         // mark relation as empty
                return $emptyResult;
            }

            // a local key is used
            if (!isset($relation['key'])) {
                $relation['key'] = $mapping['identity']['name'];        // default local key is identity
            }
            $keyName = $relation['key'];                                // the used local key property
            if ($this->$keyName === null) {
                $value = false;                                         // mark relation as empty
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
                /** @var string $relatedIdColumn */
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
            elseif (isset($relation['column'])) {                       // a local column referencing the foreign key
                if (!isset($relation['ref-column']))                    // default foreign-key is identity
                    $relation['ref-column'] = $relatedMapping['identity']['column'];
                $fkColumn = $relation['ref-column'];                    // the used foreign-key column
            }
            else {
                $fkColumn = $relatedMapping['identity']['column'];      // the used foreign-key column is identity
            }
            $fkValue = $relatedDao->escapeLiteral($value);
            $sql = "select r.*
                        from $relatedTable r
                        where r.$fkColumn = $fkValue";
        }

        if (!$isCollection) $value = $relatedDao->find($sql);           // => PersistableObject
        else                $value = $relatedDao->findAll($sql);        // => PersistableObject[]

        return $value;
    }


    /**
     * Return the value of a mapped column.
     *
     * @param  string  $column          - column name
     * @param  ?string $type [optional] - column type (default: type as configured in the entity mapping)
     *
     * @return mixed - column value
     */
    private function getPhysicalValue($column, $type = null) {
        $mapping = $this->dao()->getMapping();
        $column  = strtolower($column);
        if (!isset($mapping['columns'][$column])) throw new RuntimeException("Not a mapped column \"$column\"");

        $property = &$mapping['columns'][$column];          // by reference to be able to update all properties at once below (1)
        $propertyName = $property['name'];
        $propertyValue = $this->$propertyName;              // this is the logical or physical column value

        if ($propertyValue === null) {
            return null;
        }
        if (isset($property['assoc'])) {
            if ($propertyValue === false) {
                return null;
            }
            if (!is_object($propertyValue)) {               // a foreign-key value of a not-yet-fetched relation
                if ($type !== null) throw new RuntimeException('Unexpected parameter $type="'.$type.'" (not null) for relation [name="'.$propertyName.'", column="'.$column.'", ...] of entity "'.$mapping['class'].'"');
                return $propertyValue;
            }
            /** @var PersistableObject $object */
            $object = $propertyValue;                       // a single instance of "one-to-one"|"many-to-one" relation, no join table
            if (!isset($property['ref-column'])) {          // (1) use reference and update all properties with related entity settings
                $property['ref-column'] = $object->dao()->getMapping()['identity']['column'];
            }
            $fkColumn = $property['ref-column'];
            return $object->getPhysicalValue($fkColumn);
        }

        $columnType = $property['column-type'];

        switch ($columnType) {
            case ORM::BOOL  : return (bool)(int) $propertyValue;
            case ORM::INT   : return       (int) $propertyValue;
            case ORM::FLOAT : return     (float) $propertyValue;
            case ORM::STRING: return    (string) $propertyValue;

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
     * Return the instance's identity value (i.e. the value of the primary key).
     *
     * @return int|string - identity value
     */
    final public function getObjectId() {
        $mapping = $this->dao()->getMapping();
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
     * Whether the instance is marked as "soft deleted". Can be overwritten by custom classes.
     *
     * @return bool
     */
    public function isDeleted() {
        $mapping = $this->dao()->getMapping();
        $property = $mapping['soft-delete'] ?? null;
        if ($property) {
            $name = $property['name'];
            return ($this->$name !== null);     // any db value != NULL is considered as "deleted" flag
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
        if ($this->__inSave) {
            return $this;                                           // skip recursive calls from pre/post-processing hooks
        }

        try {
            $this->__inSave = true;

            if (!$this->isPersistent()) {
                $this->dao()->transaction(function() {
                    if ($this->beforeSave() !== true) {             // pre-processing hook
                        return $this;
                    }
                    $this->insert();
                    $this->afterSave();                             // post-processing hook
                });
            }
            elseif ($this->isModified()) {
                $this->dao()->transaction(function() {
                    if ($this->beforeSave() !== true) {             // pre-processing hook
                        return $this;
                    }
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
        if ($this->beforeInsert() !== true) {
            return $this;
        }

        $mapping = $this->dao()->getMapping();

        // collect column values
        $values = [];
        foreach ($mapping['columns'] as $column => $v) {
            $values[$column] = $this->getPhysicalValue($column);
        };

        // perform insertion
        $id = $this->doInsert($values);
        $this->__modified = false;

        // assign the returned identity value
        $idName = $mapping['identity']['name'];
        if ($this->$idName === null) {
            $this->$idName = $id;
        }

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
        if ($this->beforeUpdate() !== true) {
            return $this;
        }

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
        if ($this->__inDelete) {
            return $this;                                               // skip recursive calls from pre/post-processing hooks
        }

        try {
            $this->__inDelete = true;

            if (!$this->isPersistent()) throw new IllegalStateException('Cannot delete non-persistent '.get_class($this));

            $this->dao()->transaction(function() {
                if ($this->beforeDelete() !== true) {                   // pre-processing hook
                    return $this;
                }

                if ($this->doDelete()) {                                // perform deletion
                    // reset identity property
                    $idName = $this->dao()->getMapping()['identity']['name'];
                    $this->$idName = null;

                    $this->afterDelete();                               // post-processing hook
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
     * @param  array<string, ?scalar> $values - record values
     *
     * @return int - the inserted record's identity value
     */
    private function doInsert(array $values) {
        $db       = $this->db();
        $mapping  = $this->dao()->getMapping();
        $table    = $mapping['table'];
        $idColumn = $mapping['identity']['column'];

        /** @var ?int $id */
        $id = $values[$idColumn] ?? null;
        unset($values[$idColumn]);

        // translate column values
        foreach ($values as $i => $value) {
            $values[$i] = $db->escapeLiteral($value);
        }

        // create SQL statement
        $sql = "insert into $table (".join(', ', \array_keys($values)).')
                   values ('.join(', ', $values).')';

        // execute SQL statement
        if ($id) {
            $db->execute($sql);
        }
        elseif ($db->supportsInsertReturn()) {
            $id = $db->query("$sql returning $idColumn")->fetchInt();
        }
        else {
            $id = $db->execute($sql)->lastInsertId();
        }
        return $id;
    }


    /**
     * Perform the actual update and write modifications of the instance to the storage mechanism.
     *
     * @param  array<string, ?scalar> $changes - modifications
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
        $sql = "update $table set";                                         // update table
        foreach ($changes as $name => $value) {                             //    set ...
            /** @var PropertyMapping $mapping */
            $mapping     = $entity->getProperty($name);                     //        ...
            $columnName  = $mapping->getColumn();                           //        ...
            $columnValue = $mapping->convertToDBValue($value, $db);         //        column1 = value1,
            $sql .= " $columnName = $columnValue,";                         //        column2 = value2,
        }                                                                   //        ...
        $sql  = strLeft($sql, -1);                                          //        ...
        $sql .= " where $idColumn = $idValue";                              //    where id = value
        if ($versionMapping) {                                              //        ...                           @phpstan-ignore if.alwaysFalse      (keep for further development)
            $op = $oldVersion=='null' ? 'is':'=';                           //        ...                           @phpstan-ignore ternary.alwaysFalse (keep for further development)
            $sql .= " and $versionColumn $op $oldVersion";                  //      and version = oldVersion
        }

        // execute SQL and check for concurrent modifications
        if ($db->execute($sql)->lastAffectedRows() != 1) {
            $msg = 'record not found';
            if ($versionMapping) {                                                                               // @phpstan-ignore if.alwaysFalse      (keep for further development)
                $this->reload();
                $msg = "expected version: $oldVersion, found version: $this->$versionName";
            }
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
     * @param  array<string, ?scalar> $row - array with column values (typically a database record)
     *
     * @return $this
     */
    private function populate(array $row) {
        $row = \array_change_key_case($row, CASE_LOWER);
        $mapping = $this->dao()->getMapping();
        $dbType = $this->dao()->db()->getType();

        // ORM_PROPERTY|ORM_RELATION $property
        foreach ($mapping['columns'] as $column => $property) {
            $propertyName = $property['name'];

            if ($row[$column] === null) {
                $this->$propertyName = null;
            }
            else {
                if (isset($property['assoc'])) {                // $property[type] is a PersistableObject class
                    // ORM_RELATION $property
                    $this->$propertyName = $row[$column];       // the foreign-key column is stored as is

                    //if (!isset($property['column-type'])) {
                    //    $relatedMapping = $propertyType::dao()->getMapping();
                    //    if (!isset($property['ref-column'])) {
                    //        $property['ref-column'] = $relatedMapping['identity']['column'];
                    //    }
                    //    $refColumn = $property['ref-column'];
                    //    $property['column-type'] = $relatedMapping['columns'][$refColumn]['type'];
                    //}
                    //$propertyType = $property['column-type'];
                }
                else {
                    // ORM_PROPERTY $property
                    switch ($property['type']) {
                        case ORM::BOOL:
                            if ($dbType == 'pgsql') {
                                if     ($row[$column] == 't') $row[$column] = 1;
                                elseif ($row[$column] == 'f') $row[$column] = 0;
                            };
                            $this->$propertyName = (bool)(int) $row[$column];
                            break;

                        case ORM::INT    : $this->$propertyName =    (int) $row[$column]; break;
                        case ORM::FLOAT  : $this->$propertyName =  (float) $row[$column]; break;
                        case ORM::STRING : $this->$propertyName = (string) $row[$column]; break;
                      //case 'array': $this->$propertyName = strlen($row[$column]) ? explode(',', $row[$column]):[]; break;
                      //case DateTime::class: $this->$propertyName = new DateTime($row[$column]); break;

                        default:
                            // TODO: handle custom types
                            //if (is_class($propertyType)) {
                            //    $object->$propertyName = new $propertyType($row[$column]);
                            //    break;
                            //}
                            throw new RuntimeException("Unsupported property type \"$property[type]\" for mapping of column $mapping[table].$column");
                    }
                }
            }
        }
        return $this;
    }


    /**
     * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
     * database query result records to instances of the respective entity class.
     *
     * @param  string                 $class - entity class name
     * @param  array<string, ?scalar> $row   - array with property values (a result row from a database query)
     *
     * @return PersistableObject
     */
    public static function populateNew($class, array $row) {
        if (static::class != __CLASS__)         throw new IllegalAccessException('Cannot access method '.__METHOD__.'() on a derived class.');
        if (!is_subclass_of($class, __CLASS__)) throw new InvalidValueException("Invalid parameter \$class: $class (not a subclass of ".__CLASS__.')');

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

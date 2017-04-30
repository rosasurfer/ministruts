<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\ConcurrentModificationException;
use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\is_class;
use function rosasurfer\strEndsWith;


/**
 * PersistableObject
 *
 * Abstract base class for stored objects.
 */
abstract class PersistableObject extends Object {


    /** @var bool - dirty checking status */
    private $_modified;


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
     * Magic method. Provides default get/set implementations for mapped properties.
     *
     * @param  string $method - name of the called and undefined method
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed - return value of the intercepted virtual method
     */
    public function __call($method, array $args) {
        $dao     = $this->dao();
        $mapping = $dao->getMapping();
        $methodL = strToLower($method);

        // calls to getters of mapped properties are intercepted
        if (isSet($mapping['getters'][$methodL])) {
            $property = $mapping['getters'][$methodL]['name'];
            return $this->get($property);
        }

        // calls to setters of mapped properties are intercepted
        //if (isSet($mapping['setters'][$methodL])) {               // TODO: implement default setters
        //    $property = $mapping['getters'][$methodL]['name'];
        //    $this->$property = $args;
        //    return $this;
        //}

        // all other calls are passed on
        parent::__call($method, $args);
    }


    /**
     * Return the value of a mapped property.
     *
     * @param  string $name - property name
     *
     * @return mixed - property value
     */
    protected function get($name) {
        $mapping = $this->dao()->getMapping();

        if (isSet($mapping['properties'][$name]))
            return $this->getNonRelationValue($name);

        if (isSet($mapping['relations'][$name]))
            return $this->getRelationValue($name);

        throw new RuntimeException('Not a mapped property "'.$name.'"');
    }


    /**
     * Return the value of a mapped non-relation property.
     *
     * @param  string $name - property name
     *
     * @return mixed - property value
     */
    private function getNonRelationValue($name) {
        return $this->$name;
    }


    /**
     * Return the value of a mapped relation property.
     *
     * @param  string $name - property name
     *
     * @return PersistableObject|PersistableObject[]|null - property value
     */
    private function getRelationValue($name) {
        $value = &$this->$name;                                         // existing property value

        if (is_object($value)) return $value;                           // relation is fetched and is an object or an array
        if (is_array ($value)) return $value;                           // (Collections are not yet implemented)

        $dao          =  $this->dao();
        $mapping      =  $dao->getMapping();
        $relation     = &$mapping['relations'][$name];
        $isCollection = strEndsWith($relation['assoc'], 'many');
        /** @var PersistableObject[]|null $emptyResult */
        $emptyResult  = $isCollection ? [] : null;

        if ($value === null) {
            if (!$this->isPersistent())
                return $emptyResult;
        }
        else if ($value === false) {                                    // relation is fetched and marked as empty
            return $emptyResult;
        }

        // The relation is not yet fetched, the property is NULL or holds a column-type foreign-key value.
        $type           = $relation['type'];                            // related class name
        /** @var DAO $relatedDao */
        $relatedDao     = $type::dao();
        $relatedMapping = $relatedDao->getMapping();
        $relatedTable   = $relatedMapping['table'];

        if ($value === null) {
            if (isSet($relation['column'])) {                           // a local column with a foreign-key value of NULL
                $value = false;
                return $emptyResult;                                    // the relation is marked as empty
            }

            // a local key is used
            if (!isSet($relation['key']))
                $relation['key'] = $mapping['identity']['name'];        // default local key is identity
            $keyName = $relation['key'];                                // the used local key property
            if ($this->$keyName === null) {
                $value = false;                                         // the relation is marked as empty
                return $emptyResult;
            }
            $refColumn = $relation['ref-column'];                       // the referenced column

            if (!isSet($relation['join-table'])) {
                // the referenced column is part of the related table
                $refColumnType = $relatedMapping['columns'][$refColumn]['type'];
                $refValue      = $dao->escapeLiteral($this->getColumnValue($keyName, $refColumnType));
                $sql = 'select r.*
                            from '.$relatedTable.' r
                            where r.'.$refColumn.' = '.$refValue;
            }
            else {
                // the referenced column is part of a join table
                $joinTable = $relation['join-table'];
                $keyColumn = $mapping['properties'][$keyName]['column'];                // the used local key column
                $keyValue  = $dao->escapeLiteral($this->getColumnValue($keyColumn));    // the local key db value

                if (!isSet($relation['foreign-key']))
                    $relation['foreign-key'] = $relatedMapping['identity']['name'];     // default foreign-key is identity
                $fkColumn    = $relation['foreign-key'];                                // the used foreign-key column
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
            if (isSet($relation['join-table'])) {
                if (!isSet($relation['foreign-key']))
                    $relation['foreign-key'] = $relatedMapping['identity']['name'];     // default foreign-key is identity
                $fkColumn = $relation['foreign-key'];                                   // the used foreign-key column
            }
            else if (isSet($relation['column'])) {                      // a local column referencing the foreign key
                if (!isSet($relation['ref-column']))                    // default foreign-key is identity
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
        else                $value = $relatedDao->findAll($sql);        // => Collection<PersistableObject>

        return $value;
    }


    /**
     * Return the value of a mapped column.
     *
     * @param  string $name - column name
     * @param  string $type - column type (default: type as configured in the entity mapping)
     *
     * @return mixed - column value
     */
    private function getColumnValue($name, $type = null) {
        $mapping = $this->dao()->getMapping();
        $column  = strToLower($name);
        if (!isSet($mapping['columns'][$column])) throw new RuntimeException('Not a mapped column "'.$name.'"');

        $property      = &$mapping['columns'][$column];
        $propertyName  =  $property['name'];
        $propertyValue =  $this->$propertyName;

        if ($propertyValue === null)
            return null;

        if (isSet($property['assoc'])) {
            if ($propertyValue === false)
                return null;
            if (!is_object($propertyValue))
                return $propertyValue;                      // a foreign-key value of a not-yet-fetched relation

            /** @var PersistableObject $object */
            $object = $propertyValue;                       // a single instance from "one-to-one"|"many-to-one"
            if (!isSet($property['ref-column']))
                $property['ref-column'] = $object->dao()->getMapping()['identity']['column'];
            $fkColumn = $property['ref-column'];
            return $object->getColumnValue($fkColumn);
        }

        $columnType = $type ?: $property['column-type'];
        switch ($columnType) {
            case 'bool'   :
            case 'boolean': return (bool) $propertyValue;

            case 'int'    :
            case 'integer': return (int) $propertyValue;

            case 'real'   :
            case 'float'  :
            case 'double' :
            case 'decimal': return (float) $propertyValue;

            case 'text'   :
            case 'string' : return (string) $propertyValue;
        }
        throw new RuntimeException('Unsupported column type "'.$columnType.'"');
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
     * Whether or not the instance was already saved and has a value assigned to it's id property.
     *
     * @return bool
     */
    final public function isPersistent() {
        // TODO: this check cannot yet handle composite primary keys
        $id = $this->getObjectId();
        return ($id !== null);
    }


    /**
     * Whether or not the instance is marked as "soft deleted".
     *
     * @return bool
     */
    final public function isDeleted() {
        foreach ($this->dao()->getMapping()['properties'] as $name => $property) {
            if (isSet($property['soft-delete']) && $property['soft-delete']===true) {
                return ($this->$name !== null);
            }
        }
        return false;
    }


    /**
     * Whether or not the instance status is "modified".
     *
     * @return bool
     */
    final public function isModified() {
        return (bool) $this->_modified;
    }


    /**
     * Set the instance status to "modified".
     *
     * @return bool - the previous state
     */
    final protected function modified() {
        $previous = $this->isModified();
        $this->_modified = true;
        return $previous;
    }


    /**
     * Save the instance in the storage mechanism.
     *
     * @return $this
     */
    public function save() {
        // pre-processing hook
        if ($this->beforeSave() === false)
            return $this;

        if (!$this->isPersistent()) {
            $this->insert();
        }
        elseif ($this->isModified()) {
            $this->update();
        }

        // post-processing hook
        $this->afterSave();
        return $this;
    }


    /**
     * Insert this instance into the storage mechanism.
     *
     * @return $this
     */
    protected function insert() {
        if ($this->isPersistent()) throw new RuntimeException('Cannot insert already persistent '.$this);

        // pre-processing hook
        if ($this->beforeInsert() === false)
            return $this;

        $dao     = $this->dao();
        $mapping = $dao->getMapping();

        // collect column values
        $values = [];
        foreach ($mapping['columns'] as $column => $property) {
            $values[$column] = $this->getColumnValue($column);
        };

        // perform insertion
        $id = $dao->doInsert($values);

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
    protected function update() {
        // pre-processing hook
        if ($this->beforeUpdate() === false)
            return $this;

        $dao = $this->dao();

        // collect modified properties and their values
        $changes = [];
        foreach ($dao->getEntityMapping() as $property => $mapping) {   // TODO: Until the dirty check is implemented all
            $changes[$property] = $this->$property;                     //       properties are assumed dirty.
        }

        // perform update
        if ($dao->doUpdate($this, $changes)) {
            $this->_modified = false;

            // post-processing hook
            $this->afterUpdate();
        }
        return $this;
    }


    /**
     * Delete the instance from the storage mechanism. Must be overridden by the entity instance.
     *
     * @return $this
     */
    public function delete() {
        if (!$this->isPersistent()) throw new InvalidArgumentException('Cannot delete non-persistent '.get_class($this));

        // pre-processing hook
        if ($this->beforeDelete() === false)
            return $this;

        $dao = $this->dao();

        // perform deletion
        if ($dao->doDelete($this)) {
            // reset identity property
            $idName = $dao->getEntityMapping()->getIdentity()->getName();
            $this->$idName = null;

            // post-processing hook
            $this->afterDelete();
        }
        return $this;
    }


    /**
     * Creation post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterCreate() {
    }


    /**
     * Save pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool|null - if FALSE saving will be skipped
     */
    protected function beforeSave() {
    }


    /**
     * Save post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterSave() {
    }


    /**
     * Insert pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool|null - if FALSE the insertion will be skipped
     */
    protected function beforeInsert() {
    }


    /**
     * Insert post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterInsert() {
    }


    /**
     * Update pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool|null - if FALSE the update will be skipped
     */
    protected function beforeUpdate() {
    }


    /**
     * Update post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterUpdate() {
    }


    /**
     * Delete pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool|null - if FALSE the deletion will be skipped
     */
    protected function beforeDelete() {
    }


    /**
     * Delete post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterDelete() {
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
        $row     = array_change_key_case($row, CASE_LOWER);
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['columns'] as $column => &$property) {
            $propertyName = $property['name'];

            if ($row[$column] === null) {
                $this->$propertyName = null;
            }
            else {
                $propertyType = $property['type'];
                $relatedMapping = null;

                if (isSet($property['assoc'])) {                    // $propertyType is a PersistableObject class
                    //if (!isSet($property['column-type'])) {
                    //    $relatedMapping = $propertyType::dao()->getMapping();
                    //    if (!isSet($property['ref-column']))
                    //        $property['ref-column'] = $relatedMapping['identity']['column'];
                    //    $refColumn = $property['ref-column'];
                    //    $property['column-type'] = $relatedMapping['columns'][$refColumn]['type'];
                    //}
                    //$propertyType = $property['column-type'];
                    $propertyType = 'column-type';
                }

                switch ($propertyType) {
                    case 'column-type': $this->$propertyName =             $row[$column]; break;

                    case 'bool'       :
                    case 'boolean'    : $this->$propertyName = (bool)(int) $row[$column]; break;
                    case 'int'        :
                    case 'integer'    : $this->$propertyName =       (int) $row[$column]; break;
                    case 'float'      :
                    case 'double'     : $this->$propertyName =     (float) $row[$column]; break;
                    case 'string'     : $this->$propertyName =    (string) $row[$column]; break;
                  //case 'array'      : $this->$propertyName =   strLen($row[$column]) ? explode(',', $row[$column]):[]; break;
                  //case DateTime::class: $this->$propertyName = new DateTime($row[$column]); break;
                    default:
                        if (!isSet($property['assoc'])) {
                            // TODO: handle custom types
                            //if (is_class($propertyType)) {
                            //    $object->$propertyName = new $propertyType($row[$column]);
                            //    break;
                            //}
                        }
                        else {
                            $mapping = $relatedMapping;
                            $column  = $property['ref-column'];
                        }
                        throw new RuntimeException('Unsupported PHP type "'.$propertyType.'" for mapping of database column '.$mapping['table'].'.'.$column);
                }
            }
        }
        return $this;
    }


    /**
     * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
     * records originating from database queries to instances of the respective entity class.
     *
     * @param  string $class - entity class name
     * @param  array  $row   - array with property values (typically a row from a database table)
     *
     * @return self|null
     */
    public static function populateNew($class, array $row) {
        if (static::class != __CLASS__) throw new IllegalAccessException('Cannot access method '.__METHOD__.'() from an entity class.');

        /** @var self $object */
        $object = new $class();
        if (!$object instanceof self) throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

        return $object->populate($row);
    }


    /**
     * Reload this instance from the datastore and optionally reset relations.
     *
     * @param  bool $resetRelations - NOT YET IMPLEMENTED: Whether or not to reset relations and re-fetch on next access.
     *                                                     (default: no)
     * @return $this
     */
    public function reload($resetRelations = false) {   // TODO: implement and set default=TRUE
        if (!$this->isPersistent()) throw new InvalidArgumentException('Cannot reload non-persistent '.get_class($this));

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
     * Return the {@link DAO} for the calling class.
     *
     * @return DAO
     */
    public static function dao() {
        if (static::class == __CLASS__) throw new IllegalAccessException('Use an entity class to access method '.__METHOD__.'()');
        return Singleton::getInstance(static::class.'DAO');
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

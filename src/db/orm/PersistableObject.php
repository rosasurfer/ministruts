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
    private function get($name) {
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
     * @return PersistableObject|PersistableObject[] - property value
     */
    private function getRelationValue($name) {
        $value = &$this->$name;                                 // existing property value

        if (is_object($value)) return $value;                   // relation is fetched and an object
        if (is_array ($value)) return $value;                   // relation is fetched and an array (Collections not yet supported)

        $mapping      =  $this->dao()->getMapping();
        $relation     = &$mapping['relations'][$name];
        $isCollection = strEndsWith($relation['assoc'], 'many');
        $emptyResult  = $isCollection ? [] : null;

        if ($value === null) {
            if (!$this->isPersistent())
                return $emptyResult;
        }
        else if ($value === false) {
            return $emptyResult;                                // relation is fetched and marked as empty
        }

        // The relation is not yet fetched, the property is NULL or holds a scalar (a related key value).
        $type     = $relation['type'];                          // related class name
        $baseType = baseName($type);                            // related class base name
        /** @var DAO $relatedDao */
        $relatedDao     =  $type::dao();                        // related DAO
        $relatedMapping = $relatedDao->getMapping();

        /*
        Scenarios
        ---------
        - without local foreign-key column: property holds local key value (scalar or NULL) or is NULL
        - with local foreign-key column:    property holds foreign-key value (scalar or NULL)
        - TODO: a join table is not yet considered

        ================ property - column =========================== property - column ===========================
        One-To-Many:    Group: g.users, no FK column                  User: u.group, FK column u.group_id
        Many-To-Many:   Group: g.users, no FK column                  User: u.groups, no FK column
        ------------------------------------------------------------------------------------------------------------
        - after select: g.users=NULL
        - after fetch:  g.users=Collection
                   or:  g.users=FALSE
        - on create:    g.users=FALSE (by ctor)
        - on reload:    ???


        ================ property - column =========================== property - column ===========================
        Many-To-One:    Group: g.user, FK column g.user_id            User: u.groups, no FK column
        ------------------------------------------------------------------------------------------------------------
        - after select: g.user=id       fk-column=id
                    or: g.user=NULL     fk-column=NULL
        - after fetch:  g.user=User  => possible constraint violation
                   or:  g.user=FALSE
        - on create:    g.user=FALSE    fk-column=NULL (by ctor)
        - on reload:    check if FK column is modified


        ================ property - column =========================== property - column ===========================
        One-To-One: (1) Group: g.user, no FK column
        One-To-One: (2)                                               User: u.group, FK column u.group_id
        ------------------------------------------------------------------------------------------------------------
        - after select: g.user=NULL                                   u.group=id       fk-column=id
                    or:                                               u.group=NULL     fk-column=NULL
        - after fetch:  g.user=User                                   u.group=Group => possible constraint violation
                   or:  g.user=FALSE                                  u.group=FALSE
        - on create:    g.user=FALSE (by ctor)                        u.group=FALSE    fk-column=NULL (by ctor)
        - on reload:    ???                                           check if FK column is modified
        ------------------------------------------------------------------------------------------------------------
        */

        if ($value === null) {
            if (isSet($relation['column'])) {                       // a foreign key column which is NULL
                $value = false;
                return $emptyResult;                                // the relation is marked as empty
            }

            // a local key is used: it's not yet resolved
            if (!isSet($relation['key']))
                $relation['key'] = $mapping['identity']['name'];    // default local key is identity
            $keyName  = $relation['key'];                           // the resolved local key property
            $keyValue = $this->$keyName;
            if ($keyValue === null) {
                $value = false;                                     // the relation is marked as empty
                return $emptyResult;
            }
            $refColumn   = $relation['ref-column'];                 // the referenced column
            $refIdColumn = $relatedMapping['identity']['column'];   // the related id column
            $sql = 'select *
                        from :'.$baseType.'
                        where '.$refColumn.' = '.$keyValue.'
                        order by '.$refIdColumn;                    // default sort order by id
        }
        else {
            // key is resolved: a column with local or foreign key value != NULL => one-to-one|many-to-one
            if (!isSet($relation['ref-column']))
                $relation['ref-column'] = $relatedMapping['identity']['column'];
            $refColumn = $relation['ref-column'];
            $sql = 'select *
                        from :'.$baseType.'
                        where '.$refColumn.' = '.$value;
        }

        if ($isCollection) $value = $relatedDao->findAll($sql);     // => Collection
        else               $value = $relatedDao->find($sql);        // => PersistableObject

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
            /** @var PersistableObject $object */
            $object     = $propertyValue;
            $columnType = null;
            if (!isSet($property['ref-column']))
                $property['ref-column'] = $object->dao()->getMapping()['identity']['column'];
            if (isSet($property['column-type']))
                $columnType = $property['column-type'];
            return $propertyValue->getColumnValue($property['ref-column'], $columnType);
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
     * @return bool - if FALSE saving will be skipped
     */
    protected function beforeSave() {
        return true;
    }


    /**
     * Save post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterSave() {
    }


    /**
     * Insert pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool - if FALSE the insertion will be skipped
     */
    protected function beforeInsert() {
        return true;
    }


    /**
     * Insert post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterInsert() {
    }


    /**
     * Update pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool - if FALSE the update will be skipped
     */
    protected function beforeUpdate() {
        return true;
    }


    /**
     * Update post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterUpdate() {
    }


    /**
     * Delete pre-processing hook (application-side ORM trigger). Can be overridden by the instance.
     *
     * @return bool - if FALSE the deletion will be skipped
     */
    protected function beforeDelete() {
        return true;
    }


    /**
     * Delete post-processing hook (application-side ORM trigger). Can be overridden by the instance.
     */
    protected function afterDelete() {
    }


    /**
     * Populate this instance with the specified record values. Called during execution of
     * {@link Worker::makeObject()} and {@link PersistableObject::reload()}.
     *
     * @param  array $row - array with column values (typically a database record)
     *
     * @return $this
     */
    private function populate(array $row) {
        $row     = array_change_key_case($row, CASE_LOWER);
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['properties'] as $propertyName => $property) {
            $column = strToLower($property['column']);

            if ($row[$column] === null) {
                $this->$propertyName = null;
            }
            else {
                $propertyType = $property['type'];

                switch ($propertyType) {
                    case 'bool'   :
                    case 'boolean': $this->$propertyName =   (bool) $row[$column]; break;

                    case 'int'    :
                    case 'integer': $this->$propertyName =    (int) $row[$column]; break;

                    case 'float'  :
                    case 'double' : $this->$propertyName =  (float) $row[$column]; break;

                    case 'string' : $this->$propertyName = (string) $row[$column]; break;

                  //case 'array'  : $this->$propertyName =   strLen($row[$column]) ? explode(',', $row[$column]):[]; break;
                  //case DateTime::class: $this->$propertyName = new DateTime($row[$column]); break;

                    default:
                        // TODO: handle custom types
                        //if (is_class($propertyType)) {
                        //    $object->$propertyName = new $propertyType($row[$column]);
                        //    break;
                        //}
                        throw new RuntimeException('Unsupported type "'.$propertyType.'" for database mapping of '.get_class($this).'::'.$propertyName);
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

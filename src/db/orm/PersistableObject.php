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

use const rosasurfer\PHP_TYPE_ARRAY;
use const rosasurfer\PHP_TYPE_BOOL;
use const rosasurfer\PHP_TYPE_FLOAT;
use const rosasurfer\PHP_TYPE_INT;
use const rosasurfer\PHP_TYPE_STRING;


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
     * Magic method. Provides default get/set implementations for the mapped properties.
     *
     * @param  string $method - name of the called and undefined method
     * @param  array  $args   - arguments passed to the method call
     *
     * @return mixed - return value of the intercepted virtual method
     */
    public function __call($method, array $args) {
        $mapping = $this->dao()->getMapping();
        $methodL = strToLower($method);

        // calls to getters of mapped properties are intercepted
        if (isSet($mapping['getters'][$methodL])) {
            $property = $mapping['getters'][$methodL]['name'];
            return $this->$property;
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
     * Return the instance's identity value.
     *
     * @return mixed - identity value
     */
    final public function getObjectId() {
        $entity = $this->dao()->getEntityMapping();
        $idName = $entity->getIdentity()->getName();
        return $this->$idName;
    }


    /**
     * Whether or not the instance was already saved and has a value assigned to it's id property.
     *
     * @return bool
     */
    final public function isPersistent() {
        // TODO: this check cannot yet handle composite primary keys
        foreach ($this->dao()->getMapping()['properties'] as $name => $property) {
            if (isSet($property['primary']) && $property['primary']===true) {
                return ($this->$name !== null);
            }
        }
        return false;
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
        if (!$this->beforeSave())
            return $this;

        if (!$this->isPersistent()) {
            $this->insert();
        }
        elseif ($this->isModified()) {
            $this->update();
        }

        $this->updateRelations();

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
        if (!$this->beforeInsert())
            return $this;

        $dao    = $this->dao();
        $entity = $dao->getEntityMapping();

        // collect properties
        $values = [];
        foreach ($entity as $name => $property) {
            $values[$name] = $this->$name;
        }

        // perform insertion
        $id = $dao->doInsert($values);

        // assign the returned identity value
        $idName = $entity->getIdentity()->getName();
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
        if (!$this->beforeUpdate())
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
     * Update the relations of the instance. Must be overridden by the entity instance.
     *
     * @return $this
     */
    protected function updateRelations() {
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
        if (!$this->beforeDelete())
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
     * @return bool - TRUE  if saving is to be continued;
     *                FALSE if the saving is to be skipped
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
     * @return bool - TRUE  if insertion is to be continued;
     *                FALSE if the insertion is to be skipped
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
     * @return bool - TRUE  if the update is to be continued;
     *                FALSE if the update is to be skipped
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
     * @return bool - TRUE  if deletion is to be continued;
     *                FALSE if the deletion is to be skipped
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
     * @param  array $row - array with column values (typically a single database record)
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
                $type = $property['type'];

                switch ($type) {
                    case PHP_TYPE_BOOL  : $this->$propertyName =       (bool) $row[$column];  break;
                    case PHP_TYPE_INT   : $this->$propertyName =        (int) $row[$column];  break;
                    case PHP_TYPE_FLOAT : $this->$propertyName =      (float) $row[$column];  break;
                    case PHP_TYPE_STRING: $this->$propertyName =     (string) $row[$column];  break;
                  //case PHP_TYPE_ARRAY : $this->$propertyName =       strLen($row[$column]) ? explode(',', $row[$column]):[]; break;
                  //case DateTime::class: $this->$propertyName = new DateTime($row[$column]); break;

                    default:
                        // TODO: handle custom types
                        //if (is_class($type)) {
                        //    $object->$propertyName = new $type($row[$column]);
                        //    break;
                        //}
                        throw new RuntimeException('Unsupported type "'.$type.'" for database mapping of '.get_class($this).'::'.$propertyName);
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

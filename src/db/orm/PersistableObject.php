<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\is_class;

use const rosasurfer\PHP_TYPE_ARRAY;
use const rosasurfer\PHP_TYPE_BOOL;
use const rosasurfer\PHP_TYPE_FLOAT;
use const rosasurfer\PHP_TYPE_INT;
use const rosasurfer\PHP_TYPE_STRING;
use rosasurfer\exception\ConcurrentModificationException;


/**
 * PersistableObject
 *
 * Abstract base class for stored objects.
 */
abstract class PersistableObject extends Object {


    /** @var bool - dirty checking status */
    protected $_modified;

    /** @var string[] - modified and unsaved properties */
    protected $_modifications;


    /**
     * Constructor.
     *
     * Create a new instance.
     */
    protected function __construct() {
        $created = $touched = null;

        foreach ($this->dao()->getMapping()['columns'] as $phpName => $column) {
            $behavior = $column[IDX_MAPPING_COLUMN_BEHAVIOR];
            if ($behavior & ID_CREATE) {
                $created        = $touched ?: date('Y-m-d H:i:s');
                $this->$phpName = $created;
            }
            else if ($behavior & ID_VERSION && $behavior & F_NOT_NULLABLE) {
                $touched = $this->touch($created);
            }
        }
    }


    /**
     * Return the instance's identity value.
     *
     * @return mixed - identity value
     */
    final public function getObjectId() {
        $entity = $this->dao()->getEntityMapping();
        $idName = $entity->getIdentityMapping()->getPhpName();
        return $this->$idName;
    }


    /**
     * Return the instance's version value (if any).
     *
     * @return mixed|null - version value or NULL if the entity class is not versioned
     */
    final public function getObjectVersion() {
        $entity  = $this->dao()->getEntityMapping();
        $version = $entity->getVersionMapping();
        if ($version) {
            $versionName = $version->getPhpName();
            return $this->$versionName;
        }
        return null;
    }


    /**
     * Generate a new version value for the instance. The generated value is not assigned to the instance's
     * version property.
     *
     * @return mixed|null - version value or NULL if the entity class is not versioned
     */
    protected function generateVersion() {
        if ($this->dao()->getEntityMapping()->isVersioned()) {
            return date('Y-m-d H:i:s');
        }
        return null;
    }


    /**
     * Update the version string of the instance and return it.
     *
     * @param  string - version string to assign (default: current local datetime)
     *
     * @return string|null - assigned version string or NULL if the entity has no version field
     */
    protected function touch($version = null) {
        foreach ($this->dao()->getMapping()['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_VERSION) {
                return $this->$phpName = $version ?: date('Y-m-d H:i:s');
            }
        }
        return null;
    }


    /**
     * Whether or not the instance is marked as "soft deleted".
     *
     * @return bool
     */
    public function isDeleted() {
        foreach ($mapping = $this->dao()->getMapping()['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_DELETE) {
                return ($this->$phpName !== null);
            }
        }
        return false;
    }


    /**
     * Whether or not the instance was already saved and has a unique id assigned to it (the primary key).
     *
     * @return bool
     */
    public function isPersistent() {
        // TODO: this check cannot yet handle composite primary keys
        foreach ($mapping = $this->dao()->getMapping()['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_PRIMARY)
                return ($this->$phpName !== null);
        }
        return false;
    }


    /**
     * Whether or not the instance contains unsaved  modifications.
     *
     * @return bool
     */
    public function isModified() {
        return (bool)$this->_modified;
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
        $idName = $entity->getIdentityMapping()->getPhpName();
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

        $dao         = $this->dao();
        $entity      = $dao->getEntityMapping();
        $versioned   = $entity->isVersioned();
        $versionName = null;

        // collect the modified properties
        $changes = [];
        foreach ($entity as $name => $property) {
            $changes[$name] = $this->$name;             // TODO: implement dirty check
        }

        // check versioning and add old/new version values
        if ($changes && $versioned) {
            $versionName = $entity->getVersionMapping()->getPhpName();
            $changes['old.version'] = $this->$versionName;
            $changes['new.version'] = $this->generateVersion();
        }

        // perform update
        $version = $dao->doUpdate($this, $changes);

        if ($version !== false) {
            // update version property if the class is versioned and reset modification flags
            if ($versioned) {
                $this->$versionName = $version;
            }
            $this->_modifications = null;
            $this->_modified      = false;

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

        // perform deletion
        if ($this->dao()->doDelete($this)) {
            // post-processing hook
            $this->afterDelete();
        }
        return $this;
    }


    /**
     * Save pre-processing hook. Can be overridden by the entity instance.
     *
     * @return bool - TRUE if saving is to be continued; FALSE otherwise
     */
    protected function beforeSave() {
        return true;
    }


    /**
     * Save post-processing hook. Can be overridden by the entity instance.
     */
    protected function afterSave() {
    }


    /**
     * Insert pre-processing hook. Can be overridden by the entity instance.
     *
     * @return bool - TRUE if insertion is to be continued; FALSE otherwise
     */
    protected function beforeInsert() {
        return true;
    }


    /**
     * Insert post-processing hook. Can be overridden by the entity instance.
     */
    protected function afterInsert() {
    }


    /**
     * Update pre-processing hook. Can be overridden by the entity instance.
     *
     * @return bool - TRUE if updating is to be continued; FALSE otherwise
     */
    protected function beforeUpdate() {
        return true;
    }


    /**
     * Update post-processing hook. Can be overridden by the entity instance.
     */
    protected function afterUpdate() {
    }


    /**
     * Delete pre-processing hook. Can be overridden by the entity instance.
     *
     * @return bool - TRUE if deletion is to be continued; FALSE otherwise
     */
    protected function beforeDelete() {
        return true;
    }


    /**
     * Delete post-processing hook. Can be overridden by the entity instance.
     */
    protected function afterDelete() {
    }


    /**
     * Populate this instance with the specified record values. Called by the ORM during execution of
     * {@link Worker::makeObject()} and {@link PersistableObject::reload()}.
     *
     * @param  array $row - array with property values (typically a single database record)
     *
     * @return $this
     */
    private function populate(array $row) {
        $row     = array_change_key_case($row, CASE_LOWER);
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['columns'] as $phpName => $column) {
            $columnName = strToLower($column[IDX_MAPPING_COLUMN_NAME]);

            if ($row[$columnName] === null) {
                $this->$phpName = null;
            }
            else {
                $phpType = $column[IDX_MAPPING_PHP_TYPE];

                switch ($phpType) {
                    case PHP_TYPE_BOOL  : $this->$phpName =       (bool) $row[$columnName];  break;
                    case PHP_TYPE_INT   : $this->$phpName =        (int) $row[$columnName];  break;
                    case PHP_TYPE_FLOAT : $this->$phpName =      (float) $row[$columnName];  break;
                    case PHP_TYPE_STRING: $this->$phpName =     (string) $row[$columnName];  break;
                  //case PHP_TYPE_ARRAY : $this->$phpName =       strLen($row[$columnName]) ? explode(',', $row[$columnName]):[]; break;
                  //case DateTime::class: $this->$phpName = new DateTime($row[$columnName]); break;

                    default:
                        // TODO: handle custom types
                        //if (is_class($phpType)) {
                        //    $object->$phpName = new $phpType($row[$column]);
                        //    break;
                        //}
                        throw new RuntimeException('Unsupported PHP type "'.$phpType.'" for database mapping of '.get_class($this).'::'.$phpName);
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
        $identity   = $entity->getIdentityMapping();
        $idColumn   = $identity->getColumnName();
        $idProperty = $identity->getPhpName();
        $idValue    = $identity->convertToSQLValue($this->$idProperty, $db);

        // create and execute SQL
        $sql = 'select *
                   from '.$table.'
                   where '.$idColumn.' = '.$idValue;
        $row = $db->query($sql)->fetchRow();
        if ($row === null) throw new ConcurrentModificationException('Error reloading '.get_class($this).' ('.$this->getObjectId().'), data record not found');

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
        // TODO: The calling class may be a derived class with the DAO being one of its parents.
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

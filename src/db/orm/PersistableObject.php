<?php
namespace rosasurfer\db\orm;

use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\UnimplementedFeatureException;

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
    protected $_modified;

    /** @var string[]|null - modified and unsaved properties */
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
     * @return string|null - assigned version string or NULL if the model has no version field
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
        return (bool) $this->_modified;
    }


    /**
     * Save the instance in the storage mechanism.
     *
     * @return $this
     */
    public function save() {
        // execute an existing pre-processing hook
        method_exists($this, $method='beforeSave') && $this->$method();

        if (!$this->isPersistent()) {
            $this->insert();
        }
        elseif ($this->isModified()) {
            $this->update();
        }

        $this->updateRelations();

        // execute an existing post-processing hook
        method_exists($this, $method='afterSave') && $this->$method();
        return $this;
    }


    /**
     * Insert this instance into the storage mechanism.
     *
     * @return $this
     */
    protected function insert() {
        // execute an existing pre-processing hook
        method_exists($this, $method='beforeInsert') && $this->$method();

        if ($this->isPersistent()) throw new RuntimeException('Cannot insert already persistent '.$this);
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

        // execute an existing post-processing hook
        method_exists($this, $method='afterInsert') && $this->$method();
        return $this;
    }


    /**
     * Update the instance.
     *
     * @return $this
     */
    protected function update() {
        // execute an existing pre-processing hook
        method_exists($this, $method='beforeUpdate') && $this->$method();

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

        // update version property if the class is versioned and reset modification flags
        if ($versioned) {
            $this->$versionName = $version;
        }
        $this->_modifications = null;
        $this->_modified      = false;

        // execute an existing post-processing hook
        method_exists($this, $method='afterUpdate') && $this->$method();
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
     * @return NULL
     */
    public function delete() {
        // execute an existing pre-processing hook
        method_exists($this, $method='beforeDelete') && $this->$method();

        throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to delete a '.get_class($this).'.');

        // execute an existing post-processing hook
        method_exists($this, $method='afterDelete') && $this->$method();
    }


    /**
     * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
     * rows originating from database queries to objects of the respective model class.
     *
     * @param  string $class - class name of the model
     * @param  array  $row   - array with property values (typically a row from a database table)
     *
     * @return self|null
     */
    public static function createInstance($class, array $row) {
        if (static::class != __CLASS__) throw new IllegalAccessException('Cannot access method '.__METHOD__.'() from a model class.');
        $object = new $class();
        if (!$object instanceof self)   throw new InvalidArgumentException('Not a '.__CLASS__.' subclass: '.$class);

        $row      = array_change_key_case($row, CASE_LOWER);
        $mappings = $object->dao()->getMapping();

        foreach ($mappings['columns'] as $phpName => $mapping) {
            $column = strToLower($mapping[IDX_MAPPING_COLUMN_NAME]);

            if ($row[$column] === null) {
                if ($mapping[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_PRIMARY) {   // if the column identity is NULL it's an empty row
                    return null;
                }
            }
            else {
                $phpType = $mapping[IDX_MAPPING_PHP_TYPE];

                switch ($phpType) {
                    case PHP_TYPE_BOOL  : $object->$phpName =       (bool) $row[$column];  break;
                    case PHP_TYPE_INT   : $object->$phpName =        (int) $row[$column];  break;
                    case PHP_TYPE_FLOAT : $object->$phpName =      (float) $row[$column];  break;
                    case PHP_TYPE_STRING: $object->$phpName =     (string) $row[$column];  break;
                  //case PHP_TYPE_ARRAY : $object->$phpName =       strLen($row[$column]) ? explode(',', $row[$column]) : []; break;
                  //case DateTime::class: $object->$phpName = new DateTime($row[$column]); break;
                    default:
                        //if (is_class($phpType)) {
                        //    $object->$phpName = new $phpType($row[$column]);
                        //    break;
                        //}
                        throw new RuntimeException('Unsupported PHP type "'.$phpType.'" for database mapping of '.$class.'::'.$phpName);
                }
            }
        }
        return $object;
    }


    /**
     * Return the {@link DAO} for the calling class.
     *
     * @return DAO
     */
    public static function dao() {
        if (static::class == __CLASS__) throw new IllegalAccessException('Use a model class to access method '.__METHOD__.'()');
        return Singleton::getInstance(static::class.'DAO');
        // TODO: The calling class may be a derived class with the DAO being one of its parents.
    }


    /**
     * Return the database adapter for the calling class.
     *
     * @return IConnector
     */
    public static function db() {
        if (static::class == __CLASS__) throw new IllegalAccessException('Use a model class to access method '.__METHOD__.'()');
        return self::dao()->db();
    }
}

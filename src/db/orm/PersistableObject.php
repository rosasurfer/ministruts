<?php
namespace rosasurfer\db\orm;


use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use rosasurfer\log\Logger;

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


    /** @var bool - current modification status (dirty checking) */
    protected $modified = false;

    /** @var string[] - modified and unsaved properties */
    protected $modifications;


    /**
     * Default constructor. Used only by the ORM. To create new instances define and use static helper methods.
     *
     * @example
     *
     *  class Foo extends PersistableObject {
     *
     *     public static function create($bar, ...) {
     *        $instance = new self();
     *        $instance->setBar($bar);
     *        return $instance;
     *     }
     *  }
     *
     *  $foo = Foo::create('bar');
     *  $foo->save();
     */
    final protected function __construct() {
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_CREATE) {
                $this->$phpName = gmDate('Y-m-d H:i:s');
                break;
            }
        }
    }


    /**
     * Update the version string of the instance and return it.
     *
     * @return string|null - version string or NULL if the model has no version field
     */
    protected function touch() {
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_VERSION) {
                return $this->$phpName = gmDate('Y-m-d H:i:s');
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
        $mapping = $this->dao()->getMapping();

        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_DELETE) {
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
        $mapping = $this->dao()->getMapping();

        // TODO: this check cannot yet handle composite primary keys
        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_PRIMARY)
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
        return ($this->modified);
    }


    /**
     * Save the instance in the storage mechanism.
     *
     * @return self
     */
    final public function save() {
        if (!$this->isPersistent()) {
            $this->insert();
        }
        elseif ($this->modified) {
            $this->update();
        }
        else {
            //Logger::log('Nothing to save, '.get_class($this).' instance is in sync with the database.', L_NOTICE);
        }
        $this->updateLinks();
        $this->modified = false;

        return $this;
    }


    /**
     * Insert this instance into the storage mechanism. Must be implemented by the actual class.
     *
     * @return self
     */
    protected function insert() {
        throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to insert a '.get_class($this).'.');
    }


    /**
     * Update the instance in the storage mechanism. Must be implemented by the actual class.
     *
     * @return self
     */
    protected function update() {
        throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to update a '.get_class($this).'.');
    }


    /**
     * Update the relational cross-links of the instance. Must be implemented by the actual class.
     *
     * @return self
     */
    protected function updateLinks() {
        return $this;
    }


    /**
     * Delete the instance from the storage mechanism. Must be implemented by the actual class.
     *
     * @return NULL
     */
    public function delete() {
        throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to delete a '.get_class($this).'.');
    }


    /**
     * Create a new instance and populate it with the specified properties. This method is called by the ORM to transform
     * rows originating from database queries to objects of the respective model class.
     *
     * @param  string $class - class name of the model
     * @param  array  $row   - array with property values (typically a row from a database table);
     *                         NULL for a row where the identity field is NULL
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
                if ($mapping[IDX_MAPPING_COLUMN_BEHAVIOR] == ID_PRIMARY) {  // if the column identity is NULL it's an empty row
                    return null;
                }
            }
            else {
                $phpType = $mapping[IDX_MAPPING_PHP_TYPE];

                switch ($phpType) {
                    case PHP_TYPE_BOOL  : $object->$phpName =   (bool) $row[$column]; break;
                    case PHP_TYPE_INT   : $object->$phpName =    (int) $row[$column]; break;
                    case PHP_TYPE_FLOAT : $object->$phpName =  (float) $row[$column]; break;
                    case PHP_TYPE_STRING: $object->$phpName = (string) $row[$column]; break;
                    case PHP_TYPE_ARRAY : $object->$phpName =   strLen($row[$column]) ? explode(',', $row[$column]) : []; break;

                    default: throw new InvalidArgumentException('Unsupported PHP type "'.$phpType.'" in database mapping of '.$class.'::'.$phpName);
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

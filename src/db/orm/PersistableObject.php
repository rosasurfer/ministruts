<?php
namespace rosasurfer\db\orm;


use rosasurfer\core\Object;
use rosasurfer\core\Singleton;

use rosasurfer\db\ConnectorInterface as IConnector;

use rosasurfer\exception\IllegalAccessException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\UnimplementedFeatureException;

use function rosasurfer\is_class;

use const rosasurfer\PHP_TYPE_ARRAY;
use const rosasurfer\PHP_TYPE_BOOL;
use const rosasurfer\PHP_TYPE_FLOAT;
use const rosasurfer\PHP_TYPE_INT;
use const rosasurfer\PHP_TYPE_STRING;
use rosasurfer\exception\RuntimeException;


/**
 * PersistableObject
 *
 * Abstract base class for stored objects.
 */
abstract class PersistableObject extends Object {


    /** @var bool - dirty checking status */
    protected $modified = false;

    /** @var string[] - modified and unsaved properties */
    protected $modifications;


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
        return ($this->modified);
    }


    /**
     * Save the instance in the storage mechanism.
     *
     * @return self
     */
    public function save() {
        if (!$this->isPersistent()) {
            $this->insert();
        }
        elseif ($this->modified) {
            $this->update();
        }
        else {
            // nothing to save, the instance should be in sync with the database
        }
        $this->updateRelations();
        $this->modified = false;

        return $this;
    }


    /**
     * Insert this instance into the storage mechanism.
     *
     * @return self
     */
    protected function insert() {
        if ($this->isPersistent()) throw new RuntimeException('Cannot insert already persistent '.$this);

        $db         = $this->db();
        $mapping    = $this->dao()->getMapping();
        $table      = $mapping['table'];
        $columns    = [];
        $values     = [];
        $idProperty = null;
        $idColumn   = null;

        // collect columns and values
        foreach ($mapping['columns'] as $phpName => $column) {
            if ($column[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_PRIMARY) {
                $idProperty = $phpName;
                $idColumn   = $column[IDX_MAPPING_COLUMN_NAME];
                continue;                                               // skip the auto-generated identity column
            }
            $columns[] = $column[IDX_MAPPING_COLUMN_NAME];
            $bindType  = $column[IDX_MAPPING_BIND_TYPE] ?: $column[IDX_MAPPING_PHP_TYPE];

            switch ($bindType) {
                case BIND_TYPE_BOOL   : $values[] = $db->escapeLiteral(is_null($this->$phpName) ? null : (int)(bool) $this->$phpName);  break;
                case BIND_TYPE_INT    : $values[] = $db->escapeLiteral(is_null($this->$phpName) ? null :       (int) $this->$phpName);  break;
                case BIND_TYPE_DECIMAL: $values[] = $db->escapeLiteral(is_null($this->$phpName) ? null :     (float) $this->$phpName);  break;
                case BIND_TYPE_STRING : $values[] = $db->escapeLiteral(        $this->$phpName);                                        break;
                default:
                    if (is_class($bindType)) {
                        $value    = (new $bindType())->convertToSql($this->$phpName, $column, $db);
                        $values[] = $db->escapeLiteral($value);
                        break;
                    }
                    throw new RuntimeException('Unsupported SQL bind type "'.$bindType.'" for database mapping of '.get_class($this).'::'.$phpName);
            }
        }

        // create and execute INSERT statement
        $sql = 'insert into '.$table.' ('.join(', ', $columns).') values ('.join(', ', $values).')';
        if ($db->supportsInsertReturn()) $id = $db->query($sql.' returning '.$idColumn)->fetchInt();
        else                             $id = $db->execute($sql)->lastInsertId();

        // assign returned identity value
        $this->$idProperty = $id;

        return $this;
    }


    /**
     * Update the instance. To be implemented by the actual class.
     *
     * @return self
     */
    protected function update() {
        throw new UnimplementedFeatureException('You must implement '.get_class($this).'->'.__FUNCTION__.'() to update a '.get_class($this).'.');
    }


    /**
     * Update the relations of the instance. To be implemented by the actual class.
     *
     * @return self
     */
    protected function updateRelations() {
        return $this;
    }


    /**
     * Delete the instance from the storage mechanism. To be implemented by the actual class.
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
                if ($mapping[IDX_MAPPING_COLUMN_BEHAVIOR] & ID_PRIMARY) {   // if the column identity is NULL it's an empty row
                    return null;
                }
            }
            else {
                $phpType = $mapping[IDX_MAPPING_PHP_TYPE];

                switch ($phpType) {
                    case PHP_TYPE_BOOL   : $object->$phpName =        (bool) $row[$column];  break;
                    case PHP_TYPE_INT    : $object->$phpName =         (int) $row[$column];  break;
                    case PHP_TYPE_FLOAT  : $object->$phpName =       (float) $row[$column];  break;
                    case PHP_TYPE_STRING : $object->$phpName =      (string) $row[$column];  break;
                  //case PHP_TYPE_ARRAY  : $object->$phpName =        strLen($row[$column]) ? explode(',', $row[$column]) : []; break;
                  //case \DateTime::class: $object->$phpName = new \DateTime($row[$column]); break;
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

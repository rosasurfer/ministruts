<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;


/**
 * An EntityMapping is an object encapsulating meta information about how to map a database record to a PHP object.
 */
class EntityMapping extends Object implements \Iterator {


    /** @var string - the entity's class name */
    protected $className;

    /** @var array - legacy mapping information (TODO: migrate to XML) */
    protected $legacyMapping;

    /** @var PropertyMapping[] - property mapping information */
    protected $properties;

    /** @var PropertyMapping - identity mapping of the entity */
    protected $identity;

    /** @var PropertyMapping|bool - version mapping of the entity or FALSE for non-versioned entities */
    protected $version;

    /** @var int - current iterator position when used as an Iterator */
    private $iteratorPosition = 0;

    /** @var string[] - keys of the elements to iterate over when used as an Iterator */
    private $iteratorKeys;


    /**
     * Constructor
     *
     * Create a new EntityMapping.
     *
     * @param  string $class   - entity class name
     * @param  array  $mapping - legacy mapping information
     */
    public function __construct($class, array $mapping) {
        if (!is_string($class)) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

        $this->className     = $class;
        $this->legacyMapping = $mapping;
    }


    /**
     * Return the entity's class name.
     *
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }


    /**
     * Return the entity's table name.
     *
     * @return string
     */
    public function getTableName() {
        return $this->legacyMapping['table'];
    }


    /**
     * Return the mapping of the property with the specified name.
     *
     * @param  string $name - a property's PHP name
     *
     * @return PropertyMapping|null - mapping or NULL if no such property exists
     */
    public function getPropertyMapping($name) {
        if (!$this->properties) {
            $keys = array_keys($this->legacyMapping['columns']);
            $this->properties = array_flip($keys);
        }

        if (!array_key_exists($name, $this->properties))
            return null;

        if (!is_object($this->properties[$name])) {
            foreach ($this->legacyMapping['columns'] as $phpName => $column) {
                if ($name == $phpName) {
                    $this->properties[$name] = new PropertyMapping($this, $name, $column);
                    break;
                }
            }
        }
        return $this->properties[$name];
    }


    /**
     * Return the identity property of the mapping.
     *
     * @return PropertyMapping
     */
    public function getIdentityMapping() {
        if ($this->identity === null) {
            foreach ($this->legacyMapping['columns'] as $name => $column) {
                if (isSet($column['primary']) && $column['primary']===true) {
                    return $this->identity = new PropertyMapping($this, $name, $column);
                }
            }
            throw new RuntimeException('Invalid mapping for entity "'.$this->getClassName().'" (missing primary key mapping)');
        }
        return $this->identity;
    }


    /**
     * Return the version property of the mapping.
     *
     * @return PropertyMapping|null - property mapping or NULL if no versioning property is configured
     */
    public function getVersionMapping() {
        if ($this->version === null) {
            foreach ($this->legacyMapping['columns'] as $name => $column) {
                if (isSet($column['version']) && $column['version']===true) {
                    return $this->version = new PropertyMapping($this, $name, $column);
                }
            }
            $this->version = false;
        }
        return $this->version ?: null;
    }


    /**
     * Reset the current iterator position.
     */
    public function rewind() {
        if ($this->iteratorKeys === null) {
            $this->iteratorKeys = array_keys($this->legacyMapping['columns']);
        }
        $this->iteratorPosition = 0;
    }


    /**
     * Return the {@link PropertyMapping} at the current iterator position.
     *
     * @return PropertyMapping
     */
    public function current() {
        $key = $this->iteratorKeys[$this->iteratorPosition];
        return $this->legacyMapping['columns'][$key];
    }


    /**
     * Return the key of the {@link PropertyMapping} at the current iterator position.
     *
     * @return string - key
     */
    public function key() {
        return $this->iteratorKeys[$this->iteratorPosition];
    }


    /**
     * Set the current iterator position to the next element index.
     */
    public function next() {
        ++$this->iteratorPosition;
    }


    /**
     * Whether or not the element index at the current iterator position is valid.
     *
     * @return bool
     */
    public function valid() {
        return isSet($this->iteratorKeys[$this->iteratorPosition]);
    }
}

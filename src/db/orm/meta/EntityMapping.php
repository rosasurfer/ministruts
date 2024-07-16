<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\CObject;
use rosasurfer\core\exception\RuntimeException;


/**
 * An EntityMapping is an object encapsulating meta information about how to map a PHP class to a database table.
 */
class EntityMapping extends CObject implements \Iterator {


    /** @var string - the entity's class name */
    protected $className;

    /** @var array - mapping information */
    protected $mapping;

    /** @var PropertyMapping[] - property mapping instances */
    protected $properties;

    /** @var PropertyMapping - identity mapping of the entity */
    protected $identity;

    /** @var PropertyMapping|bool - version mapping of the entity or FALSE for non-versioned entities */
    protected $version;

    /** @var string[] - keys of the elements to iterate over when used as an Iterator */
    private $iteratorKeys;

    /** @var int - current iterator position when used as an Iterator */
    private $iteratorPosition = 0;


    /**
     * Constructor
     *
     * Create a new EntityMapping.
     *
     * @param  array $mapping - mapping information
     */
    public function __construct(array $mapping) {
        $this->mapping   = $mapping;
        $this->className = $mapping['class'];
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
        return $this->mapping['table'];
    }


    /**
     * Return the mapping instance of the property with the specified name.
     *
     * @param  string $name - a property's PHP name
     *
     * @return PropertyMapping? - mapping or NULL if no such property exists
     */
    public function getProperty($name) {
        if (!isset($this->mapping['properties'][$name]))
            return null;

        if (!isset($this->properties[$name]))
            $this->properties[$name] = new PropertyMapping($this, $this->mapping['properties'][$name]);

        return $this->properties[$name];
    }


    /**
     * Return the mapping instance of the entity's identity property.
     *
     * @return PropertyMapping
     */
    public function getIdentity() {
        if ($this->identity === null) {
            foreach ($this->mapping['properties'] as $property) {
                if (isset($property['primary']) && $property['primary']===true) {
                    return $this->identity = new PropertyMapping($this, $property);
                }
            }
            throw new RuntimeException('Invalid mapping for entity "'.$this->getClassName().'" (missing primary key mapping)');
        }
        return $this->identity;
    }


    /**
     * Return the mapping instance of the entity's versioning property.
     *
     * @return PropertyMapping? - mapping or NULL if versioning is not configured
     */
    public function getVersion() {
        if ($this->version === null) {
            foreach ($this->mapping['properties'] as $property) {
                if (isset($property['version']) && $property['version']===true) {
                    return $this->version = new PropertyMapping($this, $property);
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
            $this->iteratorKeys = \array_keys($this->mapping['properties']);
        }
        $this->iteratorPosition = 0;
    }


    /**
     * Return the property mapping at the current iterator position.
     *
     * @return array
     */
    public function current() {
        $key = $this->iteratorKeys[$this->iteratorPosition];
        return $this->mapping['properties'][$key];
    }


    /**
     * Return the key of the property mapping at the current iterator position.
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
     * Whether the element index at the current iterator position is valid.
     *
     * @return bool
     */
    public function valid() {
        return isset($this->iteratorKeys[$this->iteratorPosition]);
    }
}

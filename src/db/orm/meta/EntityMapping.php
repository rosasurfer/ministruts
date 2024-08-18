<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm\meta;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\RuntimeException;


/**
 * An EntityMapping is an object encapsulating meta information about how to map a PHP class to a database table.
 */
class EntityMapping extends CObject {


    /** @var string - the entity's class name */
    protected $className;

    /** @var array<string, mixed>> - mapping information */
    protected $mapping;

    /** @var PropertyMapping[] - property mapping instances */
    protected $properties;

    /** @var PropertyMapping - identity mapping of the entity */
    protected $identity;

    /** @var PropertyMapping|false - version mapping of the entity or FALSE for non-versioned entities */
    protected $version;


    /**
     * Constructor
     *
     * @param  array<string, mixed> $mapping - mapping information
     */
    public function __construct(array $mapping) {
        $this->mapping = $mapping;
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
     * Return the mapping of the property with the specified name.
     *
     * @param  string $name - a property's PHP name
     *
     * @return ?PropertyMapping - mapping or NULL if no such property exists
     */
    public function getProperty($name) {
        if (isset($this->mapping['properties'][$name])) {
            return $this->properties[$name] ??= new PropertyMapping($this, $this->mapping['properties'][$name]);
        }
        return null;
    }


    /**
     * Return the mapping of the entity's identity property (i.e. the primary key).
     *
     * @return PropertyMapping
     */
    public function getIdentity() {
        if ($this->identity === null) {
            foreach ($this->mapping['properties'] as $property) {
                if (($property['primary'] ?? 0) === true) {
                    return $this->identity = new PropertyMapping($this, $property);
                }
            }
            throw new RuntimeException('Invalid mapping for "'.$this->getClassName().'" (primary key not found)');
        }
        return $this->identity;
    }


    /**
     * Return the mapping of the entity's versioning property.
     *
     * @return ?PropertyMapping - mapping or NULL if versioning is not configured
     */
    public function getVersion() {
        if ($this->version === null) {
            foreach ($this->mapping['properties'] as $property) {
                if (($property['version'] ?? 0) === true) {
                    return $this->version = new PropertyMapping($this, $property);
                }
            }
            $this->version = false;
        }
        return $this->version ?: null;
    }
}

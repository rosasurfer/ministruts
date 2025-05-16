<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm\meta;

use rosasurfer\ministruts\core\CObject;


/**
 * An EntityMapping is an object encapsulating meta information about how to map a PHP class to a database table.
 *
 * @phpstan-import-type ORM_ENTITY from \rosasurfer\ministruts\phpstan\CustomTypes
 */
class EntityMapping extends CObject {


    /** @var string - the entity's class name */
    protected string $className;

    /**
     * @var         array<string, mixed> - mapping information
     * @phpstan-var ORM_ENTITY
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_ENTITY
     */
    protected array $mapping;

    /** @var PropertyMapping[] - property mapping instances */
    protected array $properties = [];

    /** @var ?PropertyMapping - identity mapping of the entity */
    protected ?PropertyMapping $identity = null;

    /** @var PropertyMapping|false - version mapping of the entity or FALSE for non-versioned entities */
    protected $version;


    /**
     * Constructor
     *
     * @param         array<string, mixed> $mapping - mapping information
     * @phpstan-param ORM_ENTITY $mapping
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_ENTITY
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
    public function getClassName(): string {
        return $this->className;
    }


    /**
     * Return the entity's table name.
     *
     * @return string
     */
    public function getTableName(): string {
        return $this->mapping['table'];
    }


    /**
     * Return the mapping of the property with the specified name.
     *
     * @param  string $name - a property's PHP name
     *
     * @return ?PropertyMapping - mapping or NULL if no such property exists
     */
    public function getProperty(string $name): ?PropertyMapping {
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
    public function getIdentity(): PropertyMapping {
        return $this->identity ??= new PropertyMapping($this, $this->mapping['identity']);
    }


    /**
     * Return the mapping of the entity's versioning property.
     *
     * @return ?PropertyMapping - mapping or NULL if versioning is not configured
     */
    public function getVersion(): ?PropertyMapping {
        if ($this->version === null) {
            $version = $this->mapping['version'] ?? null;
            $this->version = $version ? new PropertyMapping($this, $version) : false;
        }
        return $this->version ?: null;
    }
}

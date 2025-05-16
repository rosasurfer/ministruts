<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm\meta;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;


/**
 * A PropertyMapping is an object encapsulating meta information about how to map a PHP class property to a database column.
 *
 * @phpstan-import-type ORM_PROPERTY from \rosasurfer\ministruts\phpstan\CustomTypes
 */
class PropertyMapping extends CObject {


    /** @var EntityMapping - the entity mapping this mapping is a part of */
    protected EntityMapping $entityMapping;

    /**
     * @var         scalar[] - mapping information
     * @phpstan-var ORM_PROPERTY
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_PROPERTY
     */
    protected array $mapping;

    /** @var string - the property's PHP name */
    protected string $name;


    /**
     * Constructor
     *
     * @param         EntityMapping $entity - the entity this property belongs to
     * @param         scalar[]      $data   - raw property mapping information
     * @phpstan-param ORM_PROPERTY  $data
     *
     * @see \rosasurfer\ministruts\phpstan\ORM_PROPERTY
     */
    public function __construct(EntityMapping $entity, array $data) {
        $this->entityMapping = $entity;
        $this->mapping = $data;
        $this->name = $data['name'];
    }


    /**
     * Return the property's PHP name.
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Return the property's column name.
     *
     * @return string
     */
    public function getColumn(): string {
        return $this->mapping['column'];
    }


    /**
     * Convert a query result value to its PHP representation.
     *
     * @param  mixed      $value     - result set representation of a property value as returned by the DBMS
     * @param  IConnector $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    public function convertToPHPValue($value, IConnector $connector) {
    }


    /**
     * Convert a PHP value to its SQL representation.
     *
     * @param  mixed      $value     - PHP representation of a property value
     * @param  IConnector $connector - the used database connector
     *
     * @return string - database representation
     */
    public function convertToDBValue($value, IConnector $connector): string {
        if ($value === null) {
            $value = 'null';
        }
        else {
            $type = $this->mapping['type'];
            switch ($type) {
                case 'bool'  : $value = $connector->escapeLiteral((bool) $value);   break;
                case 'int'   : $value = (string)(int) $value;                       break;
                case 'float' : $value = (string)(float) $value;                     break;
                case 'string': $value = $connector->escapeLiteral((string) $value); break;

                default:
                    if (is_subclass_of($type, Type::class)) {
                        $value = (new $type())->convertToDBValue($value, $this, $connector);
                        break;
                    }
                    throw new InvalidValueException("Unsupported type \"$type\" for db mapping of ".$this->entityMapping->getClassName().'::'.$this->getName());
            }
        }
        return $value;
    }
}

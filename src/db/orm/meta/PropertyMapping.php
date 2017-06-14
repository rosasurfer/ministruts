<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\Object;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\is_class;


/**
 * A PropertyMapping is an object encapsulating meta information about how to map a PHP class property to a database column.
 */
class PropertyMapping extends Object {


    /** @var EntityMapping - the entity mapping this mapping is a part of */
    protected $entityMapping;

    /** @var array - mapping information */
    protected $mapping;

    /** @var string - the property's PHP name */
    protected $name;


    /**
     * Constructor
     *
     * Create a new PropertyMapping.
     *
     * @param  EntityMapping $entity  - the entity mapping this mapping is a part of
     * @param  array         $mapping - property mapping information
     */
    public function __construct(EntityMapping $entity, array $mapping) {
        $this->entityMapping = $entity;
        $this->mapping       = $mapping;
        $this->name          = $mapping['name'];
    }


    /**
     * Return the property's PHP name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }


    /**
     * Return the property's column name (if any).
     *
     * @return string|null
     */
    public function getColumn() {
        return isSet($this->mapping['column']) ? $this->mapping['column'] : null;
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
    public function convertToDBValue($value, IConnector $connector) {
        if ($value === null) {
            $value = 'null';
        }
        else {
            $type = $this->mapping['type'];
            switch ($type) {
                case 'bool'   :
                case 'boolean': $value = $connector->escapeLiteral((bool) $value); break;

                case 'int'    :
                case 'integer': $value = (string)(int) $value; break;

                case 'real'   :
                case 'float'  :
                case 'double' : $value = (string)(float) $value; break;

                case 'string' : $value = $connector->escapeLiteral((string) $value); break;

                default:
                    if (is_class($type)) {
                        $value = (new $type())->convertToDBValue($value, $this, $connector);
                        break;
                    }
                    throw new RuntimeException('Unsupported type "'.$type.'" for database mapping of '.$this->entityMapping->getClassName().'::'.$this->getName());
            }
        }
        return $value;
    }
}

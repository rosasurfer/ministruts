<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\Object;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\exception\IllegalTypeException;

use const rosasurfer\db\orm\BIND_TYPE_BOOL;
use const rosasurfer\db\orm\BIND_TYPE_DECIMAL;
use const rosasurfer\db\orm\BIND_TYPE_INT;
use const rosasurfer\db\orm\BIND_TYPE_STRING;

use const rosasurfer\db\orm\IDX_MAPPING_BIND_TYPE;
use const rosasurfer\db\orm\IDX_MAPPING_COLUMN_NAME;
use const rosasurfer\db\orm\IDX_MAPPING_PHP_TYPE;


/**
 * A PropertyMapping is an object encapsulating meta information about how to map a database column to a PHP class member.
 */
class PropertyMapping extends Object {


    /** @var EntityMapping - the entity mapping this instance belongs to */
    protected $entity;

    /** @var string - the property's PHP name */
    protected $phpName;

    /** @var array - legacy mapping information (TODO: migrate to XML) */
    protected $legacyMapping;


    /**
     * Constructor
     *
     * Create a new PropertyMapping.
     *
     * @param  EntityMapping $entity  - the entity mapping this instance belongs to
     * @param  string        $phpName - the property's PHP name
     * @param  array         $mapping - legacy mapping information
     */
    public function __construct(EntityMapping $entity, $phpName, array $mapping) {
        if (!is_string($phpName)) throw new IllegalTypeException('Illegal type of parameter $phpName: '.getType($phpName));

        $this->phpName       = $phpName;
        $this->legacyMapping = $mapping;
    }


    /**
     * Return the property's PHP name.
     *
     * @return string
     */
    public function getPhpName() {
        return $this->phpName;
    }


    /**
     * Return the property's column name.
     *
     * @return string
     */
    public function getColumnName() {
        return $this->legacyMapping[IDX_MAPPING_COLUMN_NAME];
    }


    /**
     * Convert a query result value to its PHP representation.
     *
     * @param  mixed      $value     - result set representation of a property value as returned by the DBMS
     * @param  IConnector $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    public function convertToPhp($value, IConnector $connector) {
    }


    /**
     * Convert a PHP value to its SQL representation.
     *
     * @param  mixed      $value     - PHP representation of a property value
     * @param  IConnector $connector - the used database connector
     *
     * @return string - SQL representation
     */
    public function convertToSql($value, IConnector $connector) {
        $bindType = $this->legacyMapping[IDX_MAPPING_BIND_TYPE] ?: $this->legacyMapping[IDX_MAPPING_PHP_TYPE];

        switch ($bindType) {
            case BIND_TYPE_BOOL   : $value = $connector->escapeLiteral(is_null($value) ? null : (int)(bool) $value); break;
            case BIND_TYPE_INT    : $value = $connector->escapeLiteral(is_null($value) ? null :       (int) $value); break;
            case BIND_TYPE_DECIMAL: $value = $connector->escapeLiteral(is_null($value) ? null :     (float) $value); break;
            case BIND_TYPE_STRING : $value = $connector->escapeLiteral(        $value);                              break;
            default:
                if (is_class($bindType)) {
                    $value = (new $bindType())->convertToSql($value, $this, $connector);
                    break;
                }
                throw new RuntimeException('Unsupported SQL bind type "'.$bindType.'" for database mapping of '.$this->entity->getClassName().'::'.$this->getPhpName());
        }
        return $value;
    }
}

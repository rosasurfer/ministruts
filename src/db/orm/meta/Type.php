<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\CObject;
use rosasurfer\db\ConnectorInterface as IConnector;


/**
 * Type
 *
 * Defines conversion methods between a property's PHP and SQL representation.
 */
abstract class Type extends CObject {


    /**
     * @param  mixed           $value     - result set representation of a property value as returned by the RDBMS
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    abstract public function convertToPHPValue($value, PropertyMapping $mapping, IConnector $connector);


    /**
     * @param  mixed           $value     - a property's PHP value
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return string - database representation
     */
    abstract public function convertToDBValue($value, PropertyMapping $mapping, IConnector $connector);
}

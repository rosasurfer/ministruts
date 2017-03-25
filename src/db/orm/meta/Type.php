<?php
namespace rosasurfer\db\orm\meta;

use rosasurfer\core\Object;
use rosasurfer\db\ConnectorInterface as IConnector;


/**
 * Type
 *
 * Defines conversion methods between a {@link PropertyMapping}'s PHP and SQL representation.
 */
abstract class Type extends Object {


    /**
     *
     * @param  mixed           $value     - result set representation of a property value as returned by the RDBMS
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    abstract public function convertToPhp($value, PropertyMapping $mapping, IConnector $connector);


    /**
     *
     * @param  mixed           $value     - a property's PHP value
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return string - SQL representation
     */
    abstract public function convertToSql($value, PropertyMapping $mapping, IConnector $connector);
}

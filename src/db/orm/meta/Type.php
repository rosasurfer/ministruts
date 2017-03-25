<?php
namespace rosasurfer\db\orm;

use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\orm\meta\PropertyMapping;


/**
 * Type
 *
 * Defines conversion methods between a {@link Property}'s PHP and SQL representation.
 */
abstract class Type {


    /**
     *
     * @param  mixed           $value     - result set representation of a property value as returned by the RDBMS
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    public function convertToPhp($value, PropertyMapping $mapping, IConnector $connector);


    /**
     *
     * @param  mixed           $value     - a property's PHP value
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  IConnector      $connector - the used database connector
     *
     * @return string - SQL representation
     */
    public function convertToSql($value, PropertyMapping $mapping, IConnector $connector);
}

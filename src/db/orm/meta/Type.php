<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm\meta;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\db\ConnectorInterface as Connector;


/**
 * Type
 *
 * Defines custom conversion methods between a property's PHP and SQL representation.
 */
abstract class Type extends CObject {


    /**
     * @param  mixed           $value     - result set representation of a property value as returned by the RDBMS
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  Connector       $connector - the used database connector
     *
     * @return mixed - PHP value
     */
    abstract public function convertToPHPValue($value, PropertyMapping $mapping, Connector $connector);


    /**
     * @param  mixed           $value     - a property's PHP value
     * @param  PropertyMapping $mapping   - the property mapping
     * @param  Connector       $connector - the used database connector
     *
     * @return string - database representation
     */
    abstract public function convertToDBValue($value, PropertyMapping $mapping, Connector $connector): string;
}

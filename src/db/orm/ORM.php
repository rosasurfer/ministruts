<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use rosasurfer\ministruts\core\StaticClass;


/**
 * A class holding ORM related helper functions and constants.
 */
final class ORM extends StaticClass {

    // Standard types for PHP properties and database columns.

    /** @var string */
    const BOOL = 'bool';

    /** @var string */
    const INT = 'int';

    /** @var string - synonym for db types "real", "double", "decimal" */
    const FLOAT = 'float';

    /** @var string - synonym for db type "text" */
    const STRING = 'string';


    /**
     * Helper to throw an ORM configuration exception.
     *
     * @param  string $message
     *
     * @return never
     */
    public static function configError(string $message) {
        throw new ConfigException("ORM config error: $message");
    }
}

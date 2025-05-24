<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use rosasurfer\ministruts\core\StaticClass;

/**
 * A class holding ORM related helper functions and constants.
 */
final class ORM extends StaticClass {

    // Standard types for PHP properties and database columns.

    public const BOOL = 'bool';

    public const INT = 'int';

    /** synonym for db types "real", "double", "decimal" */
    public const FLOAT = 'float';

    /** synonym for db type "text" */
    public const STRING = 'string';


    /**
     * Helper to throw an ORM configuration exception.
     *
     * @param  string $message
     *
     * @return never
     */
    public static function configError(string $message): void {
        throw new ConfigException("ORM config error: $message");
    }
}

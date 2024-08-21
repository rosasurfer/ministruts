<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db\orm;

use rosasurfer\ministruts\core\StaticClass;


/**
 * A class holding ORM related helper functions and constants.
 *
 *
 * @phpstan-type  EntityClass = \rosasurfer\ministruts\db\orm\PersistableObject
 *
 * @phpstan-type  ORM_ENTITY = array{
 *     class       : class-string<EntityClass>,
 *     connection  : non-empty-string,
 *     table       : non-empty-string,
 *     properties  : array<ORM_PROPERTY>,
 *     relations   : array<ORM_RELATION>,
 *     columns     : array<ORM_PROPERTY|ORM_RELATION>,
 *     getters     : array<ORM_PROPERTY|ORM_RELATION>,
 *     identity    : ORM_PROPERTY,
 *     version?    : ORM_PROPERTY,
 *     soft-delete?: ORM_PROPERTY,
 * }
 *
 * @phpstan-type  ORM_PROPERTY = array{
 *     name        : non-empty-string,
 *     type        : non-empty-string,
 *     column      : non-empty-string,
 *     column-type : non-empty-string,
 *     primary-key?: true,
 *     version?    : true,
 *     soft-delete?: true,
 * }
 *
 * @phpstan-type  ORM_RELATION = array{
 *     name          : non-empty-string,
 *     type          : 'one-to-one'|'one-to-many'|'many-to-one'|'many-to-many',
 *     class         : class-string<EntityClass>,
 *     key           : non-empty-string,
 *     column?       : non-empty-string,
 *     ref-column?   : non-empty-string,
 *     join-table?   : non-empty-string,
 *     fk-ref-column?: non-empty-string,
 *     foreign-key?  : non-empty-string,
 * }
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

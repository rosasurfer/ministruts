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
 * @phpstan-type  ORM_PROPERTY = array{
 *     name        : string,
 *     type        : string,
 *     column      : string,
 *     column-type : string,
 *     primary?    : bool,
 *     version?    : bool,
 *     soft-delete?: bool,
 * }
 *
 * @phpstan-type  ORM_RELATION = array{
 *     name          : string,
 *     assoc         : string,
 *     type          : class-string<EntityClass>,
 *     key?          : string,
 *     column?       : string,
 *     ref-column?   : string,
 *     join-table?   : string,
 *     foreign-key?  : string,
 *     fk-ref-column?: string,
 * }
 *
 * @phpstan-type  ORM_ENTITY = array{
 *     class       : class-string<EntityClass>,
 *     connection  : string,
 *     table       : string,
 *     properties  : array<ORM_PROPERTY>,
 *     relations   : array<ORM_RELATION>,
 *     columns     : array<ORM_PROPERTY|ORM_RELATION>,
 *     getters     : array<ORM_PROPERTY|ORM_RELATION>,
 *     identity    : ORM_PROPERTY,
 *     version?    : ORM_PROPERTY,
 *     soft-delete?: ORM_PROPERTY,
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
}

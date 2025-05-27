<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use rosasurfer\ministruts\db\orm\PersistableObject;

/**
 * Custom PHPStan type definitions and matching classes to enable IntelliSense and code completion.
 * Add this file to the project's library path and use the types in PHPStan annotations only.
 *
 * @phpstan-type  ORM_ENTITY = array{
 *     class       : class-string<PersistableObject>,
 *     connection  : non-empty-string,
 *     table       : non-empty-string,
 *     properties  : array<string, ORM_PROPERTY>,
 *     relations   : array<string, ORM_RELATION>,
 *     columns     : array<string, ORM_PROPERTY|ORM_RELATION>,
 *     getters     : array<string, ORM_PROPERTY|ORM_RELATION>,
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
 *     class         : class-string<PersistableObject>,
 *     key           : non-empty-string,
 *     column?       : non-empty-string,
 *     ref-column?   : non-empty-string,
 *     join-table?   : non-empty-string,
 *     fk-ref-column?: non-empty-string,
 *     foreign-key?  : non-empty-string,
 * }
 *
 * @phpstan-type  STACKFRAME = array{
 *     file?    : string,
 *     line?    : int,
 *     class?   : string,
 *     type?    : '->'|'::',
 *     function?: string,
 *     object?  : object,
 *     args?    : mixed[],
 *   __adjusted?: int,
 * }
 */
final class ArrayShapes {
}


/**
 * Custom PHPStan type for an array holding the mapping of an ORM entity.
 *
 * <pre>
 * Array(
 *     class       : class-string&lt;PersistableObject&gt;,
 *     connection  : non-empty-string,
 *     table       : non-empty-string,
 *     properties  : array&lt;string, ORM_PROPERTY&gt;,
 *     relations   : array&lt;string, ORM_RELATION&gt;,
 *     columns     : array&lt;string, ORM_PROPERTY|ORM_RELATION&gt;,
 *     getters     : array&lt;string, ORM_PROPERTY|ORM_RELATION&gt;,
 *     identity    : ORM_PROPERTY,
 *     version?    : ORM_PROPERTY,
 *     soft-delete?: ORM_PROPERTY,
 * )
 * </pre>
 *
 * @see \rosasurfer\ministruts\phpstan\PersistableObject
 * @see \rosasurfer\ministruts\phpstan\ORM_PROPERTY
 * @see \rosasurfer\ministruts\phpstan\ORM_RELATION
 */
final class ORM_ENTITY {
}


/**
 * Custom PHPStan type for an array holding the mapping of an ORM property.
 *
 * <pre>
 * Array(
 *     name        : non-empty-string,
 *     type        : non-empty-string,
 *     column      : non-empty-string,
 *     column-type : non-empty-string,
 *     primary-key?: true,
 *     version?    : true,
 *     soft-delete?: true,
 * )
 * </pre>
 */
final class ORM_PROPERTY {
}


/**
 * Custom PHPStan type for an array holding the mapping of an ORM relation.
 *
 * <pre>
 * Array(
 *     name          : non-empty-string,
 *     type          : 'one-to-one'|'one-to-many'|'many-to-one'|'many-to-many',
 *     class         : class-string&lt;PersistableObject&gt;,
 *     key           : non-empty-string,
 *     column?       : non-empty-string,
 *     ref-column?   : non-empty-string,
 *     join-table?   : non-empty-string,
 *     fk-ref-column?: non-empty-string,
 *     foreign-key?  : non-empty-string,
 * )
 * </pre>
 *
 * @see \rosasurfer\ministruts\phpstan\PersistableObject
 */
final class ORM_RELATION {
}


/**
 * Custom PHPStan type for an array holding a single frame of a stacktrace.
 * All fields are optional and may not exist
 *
 * <pre>
 * Array(
 *     file?    : non-empty-string,
 *     line?    : int,
 *     class?   : class-string,
 *     type?    : '->'|'::',
 *     function?: non-empty-string,
 *     object?  : object,
 *     args?    : mixed[],
 * )
 * </pre>
 */
final class STACKFRAME {
}

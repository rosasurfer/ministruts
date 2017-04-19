<?php
/**
 * Package constants
 */
namespace rosasurfer\db\orm;


// PHP to SQL serialization types (PHP value representation in a SQL statement)

/**
 * Bind type "default". The used bind type is derived from the entities property type.
 */
const BIND_TYPE_DEFAULT = 0;

/**
 * Bind type "boolean"
 */
const BIND_TYPE_BOOL = 1;

/**
 * Bind type "integer"
 */
const BIND_TYPE_INT = 2;

/**
 * Bind type "decimal"
 */
const BIND_TYPE_DECIMAL = 3;

/**
 * Bind type "string"
 */
const BIND_TYPE_STRING = 4;

/**
 * Bind type "blob"
 */
//const BIND_TYPE_BLOB = 5;         // TODO

/**
 * Bind type "array"
 */
//const BIND_TYPE_ARRAY = 6;        // TODO

/**
 * Bind type "set"
 */
//const BIND_TYPE_SET = 7;          // TODO


// Meta column ids and flags. Ids are implemented as flags but exclusive.

/**
 * Identifier for a column being part of the primary key. Not nullable.
 */
const ID_PRIMARY = 1;

/**
 * Identifier for a column holding the record's "creation" time. Not nullable.
 */
const ID_CREATE = 2;

/**
 * Identifier for a column holding the record's "version" value, usually (but not necessarily) the "last-modified" time.
 * May or may not be NULL (nullable).
 */
const ID_VERSION = 4;

/**
 * Identifier for a column holding the record's "soft-deletion" time. Nullable.
 */
const ID_DELETE = 8;

/**
 * Flag for a column being never NULL (not nullable).
 */
const F_NOT_NULLABLE = 16;


// Mapping indexes

/**
 * Mapping index of a field's PHP type.
 */
const IDX_MAPPING_PHP_TYPE = 1;

/**
 * Mapping index of a field's SQL bind type.
 */
const IDX_MAPPING_BIND_TYPE = 2;

/**
 * Mapping index of a field's DBMS column name.
 */
const IDX_MAPPING_COLUMN_NAME = 0;

/**
 * Mapping index of a field's DBMS column behavior (ids and flags).
 */
const IDX_MAPPING_COLUMN_BEHAVIOR = 3;

<?php
/**
 * Package constants
 */
namespace rosasurfer\db;


// PHP to SQL serialization types (PHP value representation in a SQL statement)

/**
 * Bind type "default". The bind type to use is derived from the model's property type.
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
const BIND_TYPE_BLOB = 5;

/**
 * Bind type "array"
 *
const BIND_TYPE_ARRAY = 6;        // TODO
 */

/**
 * Bind type "set"
 *
const BIND_TYPE_SET = 7;          // TODO
 */


// Column behaviour identifiers

/**
 * Identifier for a column being part of the primary key.
 */
const ID_PRIMARY = 1;

/**
 * Identifier for a column holding the "creation" time of the record.
 */
const ID_CREATE  = 2;

/**
 * Identifier for a column holding the "version" value of the record (typically the "last-updated" time).
 */
const ID_VERSION = 3;

/**
 * Identifier for a column holding the "soft-deletion" time of the record.
 */
const ID_DELETE  = 4;


// Mapping field indexes

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
 * Mapping index of a field's DBMS column behavior.
 */
const IDX_MAPPING_COLUMN_BEHAVIOR = 3;

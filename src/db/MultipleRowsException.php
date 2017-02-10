<?php
namespace rosasurfer\db;


/**
 * MultipleRowsException
 *
 * Thrown if a query expecting exactly one (more) row encounters multiple rows.
 */
class MultipleRowsException extends DatabaseException {
}

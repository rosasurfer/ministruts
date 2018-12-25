<?php
namespace rosasurfer\db;


/**
 * NoMoreRecordsException
 *
 * Thrown if a query expecting at least one (more) result row can not find one.
 */
class NoMoreRecordsException extends DatabaseException {
}

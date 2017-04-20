<?php
namespace rosasurfer\db;


/**
 * NoMoreRecordsException
 *
 * Thrown if a query expecting at least one (more) record can not fetch one.
 */
class NoMoreRecordsException extends DatabaseException {
}

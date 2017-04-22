<?php
namespace rosasurfer\db;


/**
 * NoMoreRecordsException
 *
 * Thrown if a query expecting at least one (more) record can not find one.
 */
class NoMoreRecordsException extends DatabaseException {
}

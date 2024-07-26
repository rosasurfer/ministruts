<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;


/**
 * NoSuchRecordException
 *
 * Thrown if a query expecting a result row can not find one.
 */
class NoSuchRecordException extends DatabaseException {
}

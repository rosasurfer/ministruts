<?php
namespace rosasurfer\db;


/**
 * NoMoreRowsException
 *
 * Thrown if a query expecting at least one (more) row can not fetch one.
 */
class NoMoreRowsException extends DatabaseException {
}

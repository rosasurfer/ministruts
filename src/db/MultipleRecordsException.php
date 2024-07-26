<?php
namespace rosasurfer\ministruts\db;


/**
 * MultipleRecordsException
 *
 * Thrown if a query expecting exactly one (more) record encounters multiple ones.
 */
class MultipleRecordsException extends DatabaseException {
}

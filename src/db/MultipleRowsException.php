<?php
namespace rosasurfer\db;


/**
 * MultipleRecordsException
 *
 * Thrown if a query expecting exactly one (more) record encounters multiple records.
 */
class MultipleRecordsException extends DatabaseException {
}

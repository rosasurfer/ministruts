<?php
namespace rosasurfer\console\docopt\exception;

use rosasurfer\core\exception\RuntimeException;


/**
 * DocoptFormatError
 *
 * An exception marking errors made by the developer of the CLI application. The help message provided to Docopt doesn't
 * follow valid Docopt language syntax.
 */
class DocoptFormatError extends RuntimeException {
}

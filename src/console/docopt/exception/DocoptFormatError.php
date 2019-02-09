<?php
namespace rosasurfer\console\docopt\exception;

use rosasurfer\exception\RuntimeException;


/**
 * DocoptFormatError
 *
 * An exception marking errors made by the developer of the CLI application. The usage message doesn't follow valid Docopt
 * syntax.
 */
class DocoptFormatError extends RuntimeException {
}

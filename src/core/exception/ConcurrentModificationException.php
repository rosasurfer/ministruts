<?php
namespace rosasurfer\core\exception;


/**
 * Exception to mark errors caused by non-synchronized modifications of shared resources.
 */
class ConcurrentModificationException extends RuntimeException {
}

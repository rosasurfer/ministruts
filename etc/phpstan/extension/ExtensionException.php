<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\phpstan;

use Throwable;

use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RosasurferException;

use const rosasurfer\ministruts\NL;

/**
 * An exception to enforce meaningful output during a PHPStan analysis.
 */
class ExtensionException extends RosasurferException {

    /**
     * Constructor
     *
	 * @param string     $message
	 * @param int        $code     [optional]
	 * @param ?Throwable $previous [optional]
     */
    public function __construct(string $message, int $code=0, ?Throwable $previous=null) {
        $msg = NL.NL.trim($message).NL.NL;
        parent::__construct($msg, $code, $previous);

        // also log the exception to the phpstan.log
        $indent = ' ';
        $msg  = trim(ErrorHandler::getVerboseMessage($this, $indent));
        $msg  = $indent.'[FATAL] Unhandled '.$msg.NL;
        $msg .= $indent.'in '.$this->getFile().' on line '.$this->getLine().NL;
        $msg .= NL;
        $msg .= $indent.'Stacktrace:'.NL;
        $msg .= $indent.'-----------'.NL;
        $msg .= ErrorHandler::getAdjustedStackTraceAsString($this, $indent);

        Extension::log($msg);
    }
}

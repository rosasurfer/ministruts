<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log;

use ErrorException;
use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\log\detail\Request;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\phpstan\ArrayShapes;

use function rosasurfer\ministruts\hsc;
use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\print_p;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\NL;

/**
 * LogMessage
 *
 * @phpstan-import-type STACKFRAME from ArrayShapes
 */
class LogMessage extends CObject {

    /** @var string - a logged string message */
    protected string $message = '';

    /** @var Throwable|null - a logged exception, if any */
    protected ?Throwable $exception = null;

    /** @var int - loglevel */
    protected int $logLevel;

    /** @var string - filename of the log statement causing this message */
    protected string $file;

    /** @var int - file position of the log statement causing this message */
    protected int $line;

    /** @var array<string, mixed> - logging context */
    protected array $context;


    /**
     * Constructor
     *
     * @param  string|object        $loggable - a string or an object implementing <tt>__toString()</tt>
     * @param  int                  $level    - loglevel
     * @param  array<string, mixed> $context  - logging context with additional data
     */
    public function __construct($loggable, int $level, array $context) {
        if (!is_string($loggable)) {
            if (!method_exists($loggable, '__toString')) throw new InvalidTypeException('Illegal type of parameter $loggable: '.get_class($loggable).' (expected object implementing "__toString()")');
            if (!$loggable instanceof Throwable) {
                $loggable = (string)$loggable;
            }
        }
        if (is_string($loggable)) $this->message = $loggable;
        else                      $this->exception = $loggable;

        if (!Logger::isLogLevel($level)) throw new InvalidValueException("Invalid parameter \$level: $level (not a loglevel)");

        $this->logLevel = $level;
        $this->context = $context;

        $this->resolveCallLocation();
    }


    /**
     * Whether the message is marked as sent by the registered error handler.
     *
     * @return bool
     */
    public function isSentByErrorHandler(): bool {
        return \key_exists('error-handler', $this->context);
    }


    /**
     * Get the plain text message of the instance.
     *
     * @return string
     */
    public function getMessage(): string {
        return $this->exception ? $this->exception->getMessage() : $this->message;
    }


    /**
     * Get the exception of the instance (if any).
     *
     * @return ?Throwable
     */
    public function getException(): ?Throwable {
        return $this->exception;
    }


    /**
     * Get the loglevel of the instance.
     *
     * @return int
     */
    public function getLogLevel(): int {
        return $this->logLevel;
    }


    /**
     * Return the filename of the log statement causing this message.
     *
     * @return string
     */
    public function getFile(): string {
        return $this->file;
    }


    /**
     * Return the file position of the log statement causing this message.
     *
     * @return int
     */
    public function getLine(): int {
        return $this->line;
    }


    /**
     * Return a string representation of the message details.
     *
     * @param  bool           $html              - whether to get an HTML (true) or a plain text (false) representation
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - message details (ending with a line break) or an empty string if not applicable
     */
    public function getMessageDetails(bool $html, ?ContentFilter $filter = null): string {
        $key = 'messageDetails.'.($html ? 'web':'cli') . ($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $msg */
            $msg = $this->context[$key];
            return $msg;
        }

        $file = $this->getFile();
        $line = $this->getLine();
        $indent = ' ';

        if ($this->exception) {
            $msg = trim(ErrorHandler::getVerboseMessage($this->exception, $indent, $filter));
            if ($this->isSentByErrorHandler() && $this->logLevel==L_FATAL) {
                if (!$this->exception instanceof ErrorException ||
                    !($this->exception->getSeverity() & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))) {
                    $msg = "Unhandled $msg";
                }
            }
        }
        else {
            $msg = $filter ? $filter->filterString($this->message) : $this->message;
            $msg = str_replace(NL, NL.$indent, normalizeEOL(rtrim($msg)));
        }
        $sLogLevel = Logger::logLevelDescription($this->logLevel);

        if ($html) {
            $msg  = '<span style="white-space:nowrap">
                        <span style="font-weight:bold">['.strtoupper($sLogLevel).']</span> <span style="white-space:pre; line-height:8px">'.nl2br(hsc($msg)).'</span>
                     </span>
                     <br/>
                     <br/>
                     in <span style="font-weight:bold">'.$file.'</span> on line <span style="font-weight:bold">'.$line.'</span><br/>'.NL;
        }
        else {
            $msg = $indent.'['.strtoupper($sLogLevel).'] '.$msg.NL. $indent.'in '.$file.' on line '.$line.NL;
        }
        return $this->context[$key] = $msg;
    }


    /**
     * Return a string representation of the stacktrace.
     *
     * @param  bool           $html              - whether to get an HTML (true) or a plain text (false) representation
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - stacktrace details (ending with a line break) or an empty string if not applicable
     */
    public function getTraceDetails(bool $html, ?ContentFilter $filter = null): string {
        $key = 'traceDetails.'.($html ? 'web':'cli') . ($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }

        $indent = ' ';

        if ($this->exception) {
            // process the exception's stacktrace
            $detail  = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
            $detail .= ErrorHandler::getAdjustedStackTraceAsString($this->exception, $indent, $filter);
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
            }
        }
        else {
            // process an existing context exception
            if (isset($this->context['exception'])) {
                /** @var Throwable $exception */
                $exception = $this->context['exception'];
                $msg = $indent.trim(ErrorHandler::getVerboseMessage($exception, $indent, $filter)).NL.NL;
                if ($html) {
                    $msg = '<br/>'.nl2br(hsc($msg));
                }

                $detail  = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
                $detail .= ErrorHandler::getAdjustedStackTraceAsString($exception, $indent, $filter);
                if ($html) {
                    $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
                }
                $detail = $msg.$detail;
            }
            else {
                // otherwise process the internal stacktrace
                if (!isset($this->context['stacktrace'])) {
                    $this->generateStackTrace();
                }
                /** @phpstan-var STACKFRAME[] $stacktrace */
                $stacktrace = $this->context['stacktrace'];
                $detail  = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
                $detail .= ErrorHandler::formatStackTrace($stacktrace, $indent, $filter);
                if ($html) {
                    $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
                }
            }
        }
        return $this->context[$key] = $detail;
    }


    /**
     * Return a string representation of the current HTTP request.
     *
     * @param  bool           $html              - whether to get an HTML (true) or a plain text (false) representation
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - request details (ending with a line break) or an empty string if not applicable
     */
    public function getRequestDetails(bool $html, ?ContentFilter $filter = null): string {
        $key = 'requestDetails.'.($html ? 'web':'cli') . ($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }

        $detail = '';

        if (!CLI) {
            $indent = ' ';
            $detail = 'Request:'.NL .'--------'.NL .trim(Request::stringify($filter));
            $detail = $indent.str_replace(NL, NL.$indent, normalizeEOL($detail)).NL;
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
            }
        }
        return $this->context[$key] = $detail;
    }


    /**
     * Return a string representation of the current HTTP session.
     *
     * @param  bool           $html              - whether to get an HTML (true) or a plain text (false) representation
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - session details (ending with a line break) or an empty string if not applicable
     */
    public function getSessionDetails(bool $html, ?ContentFilter $filter = null): string {
        $key = 'sessionDetails.'.($html ? 'web':'cli') . ($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }

        $detail = '';

        if (isset($_SESSION)) {
            $session = $filter ? $filter->filterValues($_SESSION) : $_SESSION;
            ksort($session);
            $detail = trim(print_r($session, true));

            $indent = ' ';
            $header = 'Session:'.NL . '--------'.NL;
            $detail = $indent.str_replace(NL, NL.$indent, normalizeEOL($header.$detail)).NL;
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
            }
        }
        return $this->context[$key] = $detail;
    }


    /**
     * Return a string representation of the current $_SERVER environment.
     *
     * @param  bool           $html              - whether to get an HTML (true) or a plain text (false) representation
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - server details (ending with a line break) or an empty string if not applicable
     */
    public function getServerDetails(bool $html, ?ContentFilter $filter = null): string {
        $key = 'serverDetails.'.($html ? 'web':'cli') . ($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }

        $values = $filter ? $filter->filterValues($_SERVER) : $_SERVER;
        ksort($values);

        $indent = ' ';
        $detail = 'Server:'.NL .'-------'.NL .print_r($values, true);
        $detail = $indent.str_replace(NL, NL.$indent, normalizeEOL(trim($detail))).NL;
        if ($html) {
            $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
        }
        return $this->context[$key] = $detail;
    }


    /**
     * Return a string representation of the interface calling context.
     *
     * @param  bool $html                   - whether to get an HTML (true) or a plain text (false) representation
     * @param  bool $requestTime [optional] - whether to add an additional request timestamp (default: yes)
     *
     * @return string - calling context details (ending with a line break) or an empty string if not applicable
     */
    public function getCallDetails(bool $html, bool $requestTime = true): string {
        $key = 'callDetails.'.($html ? 'web':'cli') .'.'. (int)$requestTime;

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }

        $values = [];

        if (CLI) {
            $values['Command:'] = join(' ', $_SERVER['argv']);
        }
        else {
            // @see https://httpd.apache.org/docs/2.4/mod/mod_unique_id.html
            if (isset($_SERVER['UNIQUE_ID'])) {
                $values['Request id:'] = $_SERVER['UNIQUE_ID'];
            }

            $ip = Request::getRemoteIP();
            $host = Request::getRemoteHost();
            $values['IP:'] = $ip . ($host ? " ($host)" : '');

            if ($requestTime) {
                $time = $_SERVER['REQUEST_TIME'] ?? time();
                $values['Time:'] = gmdate('Y-m-d H:i:s T', (int)$time);
            }
        }

        /** @var int $maxLen */
        $maxLen = max(array_map('strlen', array_keys($values)));
        foreach ($values as $name => $_) {
            $values[$name] = str_pad($name, $maxLen).' '.$values[$name];
        }
        $indent = ' ';
        $detail = join(NL, $values);
        $detail = $indent.str_replace(NL, NL.$indent, normalizeEOL(trim($detail))).NL;

        if ($html) {
            $detail = '<br style="clear:both"/><br/>'.print_p($detail, true, false).NL;
        }
        return $this->context[$key] = $detail;
    }


    /**
     * Resolve the file location the log statement was triggered or made from.
     *
     * @return void
     */
    protected function resolveCallLocation(): void {
        if ($this->exception) {
            $this->file = $this->exception->getFile();
            $this->line = $this->exception->getLine();
            return;
        }

        if (!isset($this->context['stacktrace'])) {
            $this->generateStackTrace();
        }
        /** @phpstan-var STACKFRAME[] $stacktrace */
        $stacktrace = $this->context['stacktrace'];

        foreach ($stacktrace as $frame) {
            // find the first frame with 'file' (skips internal PHP functions)
            if (isset($frame['file'])) {
                $this->file = $frame['file'];
                $this->line = $frame['line'] ?? 0;
                return;
            }
        }

        $this->file = 'Unknown';
        $this->line = 0;
    }


    /**
     * Generate a stacktrace pointing to the log statement and store it under $context['stacktrace'].
     *
     * @return void
     */
    protected function generateStackTrace(): void {
        if (isset($this->context['stacktrace'])) {
            return;
        }

        $result = $stacktrace = ErrorHandler::adjustStackTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), __FILE__, __LINE__);
        $logMethod = strtolower(Logger::class.'::log');

        foreach ($stacktrace as $i => $frame) {
            if (isset($frame['class'])) {
                $method = strtolower($frame['class'].'::'.($frame['function'] ?? ''));
                if ($method == $logMethod) {
                    $result = array_slice($stacktrace, $i + 1);
                    break;
                }
            }
        }
        $this->context['stacktrace'] = $result;
    }
}

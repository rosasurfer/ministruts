<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log;

use ErrorException;
use Throwable;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\error\PHPError;
use rosasurfer\ministruts\core\exception\Exception;
use rosasurfer\ministruts\core\exception\InvalidTypeException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\detail\Request;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\phpstan\UserTypes as PHPStanUserTypes;
use rosasurfer\ministruts\util\Trace;

use function rosasurfer\ministruts\hsc;
use function rosasurfer\ministruts\normalizeEOL;
use function rosasurfer\ministruts\print_p;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\L_FATAL;
use const rosasurfer\ministruts\NL;

/**
 * LogMessage
 *
 * @phpstan-import-type STACKFRAME from PHPStanUserTypes
 */
class LogMessage extends CObject {

    /** @var string - a logged message */
    protected string $message = '';

    /** @var ?Throwable - a logged exception */
    protected ?Throwable $exception = null;

    /** @var int - loglevel */
    protected int $logLevel;

    /** @var string - filename of the location causing this log message */
    protected string $file;

    /** @var int - file position of the location causing this log message */
    protected int $line;

    /** @phpstan-var array{file:string, line:int, trace:list<STACKFRAME>} - internal call location infos */
    protected array $internalLocation;

    /** @var array<string, mixed> - logging context with additional infos (if any) */
    protected array $context;


    /**
     * Constructor
     *
     * @param  string|object        $loggable - a string or an object implementing <tt>__toString()</tt>
     * @param  int                  $level    - loglevel
     * @param  array<string, mixed> $context  - logging context with additional infos
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
        $this->internalLocation = $this->getInternalLocation();
        $this->file = $this->getFile();
        $this->line = $this->getLine();
    }


    /**
     * Whether the message was sent by the framework's error handler.
     *
     * @return bool
     */
    public function fromErrorHandler(): bool {
        return $this->exception instanceof PHPError || isset($this->context['error-handler']);
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
     * Get the logged exception of the instance (if any).
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
     * Return the filename of the location causing this log message.
     *
     * @return string
     */
    public function getFile(): string {
        if (!isset($this->file)) {
            if ($this->fromErrorHandler()) {                        // use location infos from the error handler
                /** @var Throwable $exception */
                $exception = $this->exception;
                $this->file = $exception->getFile();
            }
            elseif (isset($this->context['file'])) {                // use custom location infos
                $this->file = $this->context['file'];
            }
            else {                                                  // no external location infos
                ['file' => $file] = $this->getInternalLocation();
                $this->file = $file;
            }
        }
        return $this->file;
    }


    /**
     * Return the file position of the location causing this log message.
     *
     * @return int
     */
    public function getLine(): int {
        if (!isset($this->line)) {
            if ($this->fromErrorHandler()) {                        // use location infos from the error handler
                /** @var Throwable $exception */
                $exception = $this->exception;
                $this->line = $exception->getLine();
            }
            elseif (isset($this->context['line'])) {                // use custom location infos
                $this->line = $this->context['line'];
            }
            else {                                                  // no external location infos
                ['line' => $line] = $this->getInternalLocation();
                $this->line = $line;
            }
        }
        return $this->line;
    }


    /**
     * Internally resolves the location infos causing this log message.
     *
     * @return         mixed[] - file, line and stacktrace of the causing statement
     * @phpstan-return array{file:string, line:int, trace:list<STACKFRAME>}
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    protected function getInternalLocation(): array {
        if (!isset($this->internalLocation)) {
            $fullTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $partialTrace = null;
            $file = '(unknown)';
            $line = 0;

            if (isset($this->context['file'], $this->context['line'])) {
                // custom location: look-up a call at file + line
                $file = $this->context['file'];
                $line = $this->context['line'];

                foreach ($fullTrace as $i => $frame) {
                    if (isset($frame['file'], $frame['line'])) {
                        if ($frame['file']==$file && $frame['line']==$line) {
                            $partialTrace = array_slice($fullTrace, $i+1);
                            break;
                        }
                    }
                }
            }
            else {
                // default: look-up a call to Logger::log()
                $logMethod = strtolower(Logger::class.'::log');

                foreach ($fullTrace as $i => $frame) {
                    if (isset($frame['class'])) {
                        $method = "$frame[class]::$frame[function]";
                        if (strtolower($method) == $logMethod) {
                            $file = $frame['file'] ?? '(unknown)';
                            $line = $frame['line'] ?? 0;
                            $partialTrace = array_slice($fullTrace, $i+1);
                            break;
                        }
                    }
                }
            }

            $this->internalLocation = [
                'file'  => $file,
                'line'  => $line,
                'trace' => $partialTrace ?? $fullTrace,
            ];
        }
        return $this->internalLocation;
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
        $key = 'messageDetails.'.($html ? 'web':'cli').($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $msg */
            $msg = $this->context[$key];
            return $msg;
        }

        $file = $this->getFile();
        $line = $this->getLine();
        $indent = ' ';

        if ($this->exception) {
            $msg = trim(Exception::getVerboseMessage($this->exception, $indent, $filter));
            if ($this->fromErrorHandler() && $this->logLevel==L_FATAL) {
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
        $key = 'traceDetails.'.($html ? 'web':'cli').($filter ? get_class($filter) : '');

        if (isset($this->context[$key])) {
            /** @var string $detail */
            $detail = $this->context[$key];
            return $detail;
        }
        $indent = ' ';

        if ($this->exception) {
            // use stacktrace from an explicitly passed exception (will include nested exceptions)
            $detail  = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
            $detail .= Trace::convertTraceToString($this->exception, $indent, $filter);
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
            }
        }
        elseif (isset($this->context['exception'])) {
            // use stacktrace from a context exception (will include nested exceptions)
            /** @var Throwable $exception */
            $exception = $this->context['exception'];
            $msg = Exception::getVerboseMessage($exception, $indent, $filter).NL.NL;
            if ($html) {
                $msg = '<br/>'.nl2br(hsc($msg));
            }

            $detail  = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
            $detail .= Trace::convertTraceToString($exception, $indent, $filter);
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
            }
            $detail = $msg.$detail;
        }
        else {
            // use our own stacktrace (points to the causing log statement)
            $detail = $indent.'Stacktrace:'.NL .$indent.'-----------'.NL;
            ['trace' => $stacktrace] = $this->getInternalLocation();
            $stacktrace = Trace::convertTrace($stacktrace, $this->getFile(), $this->getLine());
            $detail .= Trace::toString($stacktrace, $indent);
            if ($html) {
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
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
        $key = 'requestDetails.'.($html ? 'web':'cli').($filter ? get_class($filter) : '');

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
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
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
        $key = 'sessionDetails.'.($html ? 'web':'cli').($filter ? get_class($filter) : '');

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
                $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
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
        $key = 'serverDetails.'.($html ? 'web':'cli').($filter ? get_class($filter) : '');

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
            $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
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
            $detail = '<br style="clear:both"/><br/>'.print_p($detail, true).NL;
        }
        return $this->context[$key] = $detail;
    }
}

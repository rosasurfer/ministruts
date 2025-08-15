<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\log\LogMessage;
use rosasurfer\ministruts\log\detail\Request;

use function rosasurfer\ministruts\ini_get_bool;
use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\stdout;
use function rosasurfer\ministruts\strCompareI;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;

/**
 * PrintAppender
 *
 * A log appender displaying log messages either on STDOUT/STDERR (CLI) or as part of the HTTP response (web interface)
 * The appender is configured via config key "log.appender.print". All configuration options are optional. For defaults
 * see the getDefault*() methods.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $options = $config['log.appender.print'];
 *  $appender = new PrintAppender($options);
 *
 *  Option fields:
 *  --------------
 *  'enabled'         = (bool)        // whether the appender is enabled (default: see self::getDefaultEnabled())
 *  'loglevel'        = (int|string)  // appender loglevel (default: application loglevel)
 *  'details.trace'   = (bool)        // whether a stacktrace is attached to log messages (default: yes)
 *  'details.request' = (bool)        // whether HTTP request details are attached to log messages from the web interface (default: yes)
 *  'details.session' = (bool)        // whether HTTP session details are attached to log messages from the web interface (default: no)
 *  'details.server'  = (bool)        // whether server details are attached to log messages from the CLI interface (default: no)
 *  'filter'          = {classname}   // content filter to apply to the resulting output (default: none)
 * </pre>
 */
class PrintAppender extends BaseAppender {

    /** @var int - counter for all displayed messages */
    protected int $msgCounter = 0;

    /** @var int - counter for displayed HTML messages */
    protected int $msgCounterHtml = 0;


    /**
     * Print a log message to the screen.
     *
     * @param  LogMessage $message
     *
     * @return bool - Whether logging should continue with the next registered appender. Returning FALSE interrupts the chain.
     */
    public function appendMessage(LogMessage $message): bool {
        // filter messages below the active loglevel
        if ($message->getLogLevel() < $this->logLevel) {
            return true;
        }

        // detect "headers already sent" errors triggered by the PrintAppender itself and terminate processing of the error
        if ($message->isSentByErrorHandler() && $this->msgCounter > 0) {
            if (preg_match('/- headers already sent (by )?\(output started at /', $message->getMessage())) {
                return false;
            }
        }

        if (CLI) {
            $html = false;
        }
        else {
            $ui = Request::instance()->getHeaderValue('x-ministruts-ui') ?? 'web';
            $html = !strCompareI($ui, 'cli');
        }

        $msg = $message->getMessageDetails($html, $this->filter);
        if ($this->traceDetails   && $detail = $message->getTraceDetails  ($html, $this->filter)) $msg .= NL.$detail;
        if ($this->requestDetails && $detail = $message->getRequestDetails($html, $this->filter)) $msg .= NL.$detail;
        if ($this->sessionDetails && $detail = $message->getSessionDetails($html, $this->filter)) $msg .= NL.$detail;
        if ($this->serverDetails  && $detail = $message->getServerDetails ($html, $this->filter)) $msg .= NL.$detail;
        $msg .= NL.$message->getCallDetails($html, false);
        $msg = trim($msg);

        if (!$html) {
            if ($this->msgCounter > 0) {
                $msg = str_repeat('-', 120).NL.$msg;
            }
            $msg .= NL.NL;                                                  // EOL + one empty line

            if (!CLI)                                 echo $msg;            // web UI with forced plain text format
            elseif ($message->isSentByErrorHandler()) stderr($msg);
            else                                      stdout($msg);
        }
        else {
            // break out of unfortunate HTML tags
            if ($this->msgCounterHtml == 0) {
                echo '<a attr1="" attr2=\'\'></a></meta></title></head></script></img></input></select></textarea></label></li></ul></font></pre></tt></code></small></i></b></span></div>'.NL;
            }
            // the id of each message DIV is unique
            $divId = md5('ministruts').'-'.++$this->msgCounterHtml;

            echo <<<HTML
            <div id="$divId" align="left" 
                 style="display:initial; visibility:initial; clear:both;
                 position:relative; top:initial; left:initial; z-index:4294967295;
                 float:left; width:initial; height:initial
                 margin:0; padding:4px; border-width:0;
                 font:normal normal 12px/normal arial,helvetica,sans-serif; line-height:12px;
                 color:black; background-color:lightgray">
               $msg
            </div>
            HTML;

            // some JavaScript to move messages to the top of the page (if JS is not available messages show up inline)
            echo <<<JAVASCRIPT
            <script>
                var container = window.ministrutsContainer;
                if (!container) {
                    container = window.ministrutsContainer = document.createElement('div');
                    container.setAttribute('id', 'ministruts.print-appender');
                    container.style.position        = 'absolute';
                    container.style.top             = '6px';
                    container.style.left            = '6px';
                    container.style.zIndex          = '4294967295';
                    container.style.padding         = '6px';
                    container.style.textAlign       = 'left';
                    container.style.font            = 'normal normal 12px/1.1em arial,helvetica,sans-serif';
                    container.style.color           = 'black';
                    container.style.backgroundColor = 'lightgray';
                    //
                    var bodies = document.getElementsByTagName('body');
                    if (bodies && bodies.length) {
                        bodies[0].appendChild(container);
                    }
                    else {
                        container = window.ministrutsContainer = null; 
                    }
                }
                if (container) {
                    var logMsg = document.getElementById('$divId');
                    if (logMsg) container.appendChild(logMsg);
                }
            </script>
            JAVASCRIPT;
        }
        $this->msgCounter++;

        ob_get_level() && ob_flush();
        return true;
    }


    /**
     * Return the default "enabled" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultEnabled(): bool {
        // default: enabled on CLI, php.ini setting "display_errors=On" or white-listed web access
        static $result;
        $result ??= CLI || ini_get_bool('display_errors') || Application::isAdminIP();
        return $result;
    }


    /**
     * Return the default "details.request" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultRequestDetails(): bool {
        return true;
    }
}

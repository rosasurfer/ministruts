<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\log\LogMessage;

use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\stdout;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;


/**
 * PrintAppender
 *
 * A log appender displaying log messages either on STDOUT/STDERR (CLI) or as part of the HTTP response (web interface)
 * The appender is configured via config key "log.appender.print". All configuration options are optional.
 *
 * @example
 * <pre>
 *  $config = $this->di('config');
 *  $options = $config['log.appender.print'];
 *  $appender = new PrintAppender($options);
 *
 *  Option fields:
 *  --------------
 *  'enabled'         = (bool)          // whether the appender is enabled (default: FALSE)
 *  'loglevel'        = (int|string)    // appender loglevel if different from application loglevel (default: application loglevel)
 *  'details.trace'   = (bool)          // whether a stacktrace is attached to log messages (default: FALSE)
 *  'details.request' = (bool)          // whether HTTP request details are attached to log messages from the web interface (default: FALSE)
 *  'details.session' = (bool)          // whether HTTP session details are attached to log messages from the web interface (default: FALSE)
 *  'details.server'  = (bool)          // whether server details are attached to log messages from the CLI interface (default: FALSE)
 *  'filter'          = <class-name>    // content filter to apply to the resulting output (default: no filter)
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

        // detect "headers already sent" errors triggered by a previous message and cancel further processing
        if ($message->isSentByErrorHandler() && $this->msgCounterHtml) {
            if (preg_match('/- headers already sent (by )?\(output started at /', $message->getMessage())) {
                return false;
            }
        }

        $msg = $message->getMessageDetails(!CLI, $this->filter);
        if ($this->traceDetails   && $detail = $message->getTraceDetails  (!CLI, $this->filter)) $msg .= NL.$detail;
        if ($this->requestDetails && $detail = $message->getRequestDetails(!CLI, $this->filter)) $msg .= NL.$detail;
        if ($this->sessionDetails && $detail = $message->getSessionDetails(!CLI, $this->filter)) $msg .= NL.$detail;
        if ($this->serverDetails  && $detail = $message->getServerDetails (!CLI, $this->filter)) $msg .= NL.$detail;
        $msg .= NL.$message->getCallDetails(!CLI, false);
        $msg = trim($msg);

        if (CLI) {
            if ($this->msgCounter > 0) {
                $msg = str_repeat('-', 120).NL.$msg;
            }
            if ($message->isSentByErrorHandler()) stderr($msg.NL.NL);
            else                                  stdout($msg.NL.NL);
        }
        else {
            // break out of unfortunate HTML tags
            $divId = md5('yoummday').'-'.++$this->msgCounterHtml;       // always a unique id
            $html  = '<a attr1="" attr2=\'\'></a></meta></title></head></script></img></input></select></textarea></label></li></ul></font></pre></tt></code></small></i></b></span></div>';
            $html .= '<div id="'.$divId.'"
                            align="left"
                            style="display:initial; visibility:initial; clear:both;
                            position:relative; z-index:4294967295; top:initial; left:initial;
                            float:left; width:initial; height:initial
                            margin:0; padding:4px; border-width:0;
                            font:normal normal 10px/normal arial,helvetica,sans-serif; line-height:12px;
                            color:black; background-color:#ccc">';
            $html .= $msg;
            // some JavaScript to make sure multiple log messages are not covered by other (probably dynamic) content
            $html .= '</div>
                      <script>
                          var bodies = document.getElementsByTagName("body");
                          bodies && bodies.length && bodies[0].appendChild(document.getElementById("'.$divId.'"));
                      </script>';
            echo $html.NL;
        }
        $this->msgCounter++;

        ob_get_level() && ob_flush();
        return true;
    }
}

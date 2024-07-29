#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Scans the application's PHP error logfile for new entries and mails them to the configured receivers. If no receivers
 * are configured mail is sent to the system user running the script. Processed entries are removed from the logfile.
 *
 * @see `logwatch.php -h` for command line options when integrating with CRON
 */
namespace rosasurfer\ministruts\bin\logwatch;

use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\config\ConfigInterface;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\net\mail\Mailer;

use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;

require(dirname(realpath(__FILE__)).'/../app/init.php');    // TODO: adjust to your project

if (!CLI) {
    stderr('error: This script must be executed via CLI.'.NL);
    exit(1);
}


// --- configuration --------------------------------------------------------------------------------------------------------


set_time_limit(0);                                          // no time limit for CLI
$quiet = false;                                             // whether "quiet" mode is enabled (e.g. for CRON)


// --- parse and validate command line arguments ----------------------------------------------------------------------------


/** @var string[] $args */
$args = \array_slice($_SERVER['argv'], 1);

foreach ($args as $i => $arg) {
    if ($arg == '-h') { help(); exit(0);                           }    // help
    if ($arg == '-q') { $quiet = true; unset($args[$i]); continue; }    // quiet mode

    stderr('invalid argument: '.$arg.NL);
    !$quiet && help();
    exit(1);
}


// --- start ----------------------------------------------------------------------------------------------------------------


// (1) define the location of the error log
$errorLog = ini_get('error_log');
if (empty($errorLog) || $errorLog=='syslog') {              // errors are logged elsewhere
    if (empty($errorLog)) $quiet || echof('errors are logged elsewhere (stderr)');
    else                  $quiet || echof('errors are logged elsewhere ('.(WINDOWS ? 'event log':'syslog').')');
    exit(0);
}


// (2) check log file for existence and process it
if (!is_file    ($errorLog)) { $quiet || echof('error log empty: '       .$errorLog);    exit(0); }
if (!is_writable($errorLog)) {          stderr('cannot access log file: '.$errorLog.NL); exit(1); }
$errorLog = realpath($errorLog);

// rename the file; we don't want to lock it as doing so could block the main app
$tempName = tempnam(dirname($errorLog), basename($errorLog).'.');
if (!rename($errorLog, $tempName)) {
    stderr('cannot rename log file: '  .$errorLog.NL);
    exit(1);
}

// read the log file line by line
$hFile = fopen($tempName, 'rb');
$line  = $entry = '';
$i = 0;
while (($line=fgets($hFile)) !== false) {
    $i++;
    $line = trim($line, "\r\n");                // PHP doesn't correctly handle EOL_NETSCAPE which is error_log() standard on Windows
    if (strStartsWith($line, '[')) {            // lines starting with a bracket "[" are considered the start of an entry
        processEntry($entry);
        $entry = '';
    }
    $entry .= $line.NL;
}
processEntry($entry);                           // process the last entry (if any)

// delete the processed file
fclose($hFile);
unlink($tempName);

exit(0);


// --- functions ------------------------------------------------------------------------------------------------------------


/**
 * Send a single log entry to the defined error log receivers. The first line is sent as the mail subject and the full
 * log entry as the mail body.
 *
 * @param  string $entry - a single log entry
 *
 * @return void
 */
function processEntry($entry) {
    Assert::string($entry);
    $entry = trim($entry);
    if (!strlen($entry)) return;

    /** @var ConfigInterface $config */
    $config = Application::getConfig();

    $receivers = [];
    foreach (explode(',', $config->get('log.mail.receiver', '')) as $receiver) {
        if ($receiver = trim($receiver)) {
            if (filter_var($receiver, FILTER_VALIDATE_EMAIL)) {     // silently skip invalid receiver addresses
                $receivers[] = $receiver;
            }
        }
    }                                                               // without receivers mail is sent to the system user
    !$receivers && $receivers[] = strtolower(get_current_user().'@localhost');

    $subject = strtok($entry, "\r\n");                              // that's CR or LF, not CRLF
    $message = $entry;
    $sender  = null;
    $headers = [];

    static $mailer; if (!$mailer) {
        $options = [];
        if (strlen($name = $config->get('log.mail.profile', ''))) {
            $options = $config->get('mail.profile.'.$name, []);
            $sender  = $config->get('mail.profile.'.$name.'.from', null);
            $headers = $config->get('mail.profile.'.$name.'.headers', []);
        }
        $mailer = Mailer::create($options);
    }

    global $quiet;
    $quiet || echof(substr($subject, 0, 80).'...');

    foreach ($receivers as $receiver) {
        $mailer->sendMail($sender, $receiver, $subject, $message, $headers);
    }
}


/**
 * Show the call syntax.
 *
 * @param  string $message [optional] - additional message to display (default: none)
 *
 * @return void
 */
function help($message = null) {
    if (isset($message))
        echo $message.NL;

    $self = basename($_SERVER['PHP_SELF']);

echo <<<HELP

 Syntax:  $self [options]

 Options:  -q   Quiet mode. Suppress status messages but not errors (for scripted execution, e.g. by CRON).
           -h   This help screen.


HELP;
}

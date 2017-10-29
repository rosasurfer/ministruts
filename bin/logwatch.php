#!/usr/bin/env php
<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                    //
//  This file is a template script, it cannot run by itself. Copy this file to your project's CRON directory and      //
//  adjust the path in line 36 to your application's init script.  Then setup a cron job to run the script by CRON.   //
//                                                                                                                    //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/**
 * Scans the application's PHP error log file for entries and notifies by email. Mail is sent to the configured log message
 * receivers. If no receivers are configured mail is sent to the system user running the script. Processed log entries are
 * removed from the log file.
 *
 * TODO: Error messages must not be printed to STDOUT but to STDERR.
 * TODO: Add parameter for not suppressing regular output to get status messages when not executed by CRON.
 */
namespace rosasurfer\bin\logwatch;

use rosasurfer\config\Config;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\util\PHP;

use function rosasurfer\echoPre;
use function rosasurfer\normalizeEOL;
use function rosasurfer\stderror;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\EOL_UNIX;
use const rosasurfer\EOL_WINDOWS;
use const rosasurfer\NL;
use const rosasurfer\WINDOWS;


require(dirName(realPath(__FILE__)).'/{application-path-to-init}/init.php');
set_time_limit(0);                                       // no time limit for CLI


// --- configuration --------------------------------------------------------------------------------------------------------


$quiet = false;                                          // whether or not "quiet" mode is enabled (for CRON)


// --- parse/validate command line arguments --------------------------------------------------------------------------------


$args = array_slice($_SERVER['argv'], 1);

foreach ($args as $i => $arg) {
    if ($arg == '-h') { help(); exit(0);                           }     // help
    if ($arg == '-q') { $quiet = true; unset($args[$i]); continue; }     // quiet mode

    stderror('invalid argument: '.$arg);
    !$quiet && help();
    exit(1);
}


// --- start ----------------------------------------------------------------------------------------------------------------


// (1) define error log sender and receivers
// read the regularily configured receivers
$config = Config::getDefault();
$sender = $config->get('mail.from', get_current_user().'@localhost');
$receivers = [];
foreach (explode(',', $config->get('log.mail.receiver', '')) as $receiver) {
    if ($receiver=trim($receiver))
        $receivers[] = $receiver;       // TODO: validate address format
}

// check setting "mail.forced-receiver" (may be set in development)
if ($receivers && $forcedReceivers=$config->get('mail.forced-receiver', false)) {
    $receivers = [];
    foreach (explode(',', $forcedReceivers) as $receiver) {
        if ($receiver=trim($receiver))
            $receivers[] = $receiver;
    }
}

// without receiver mail is sent to the current system user
!$receivers && $receivers[]=get_current_user().'@localhost';


// (2) define the location of the error log
$errorLog = ini_get('error_log');
if (empty($errorLog) || $errorLog=='syslog') {           // errors are logged elsewhere
    if (empty($errorLog)) $quiet || echoPre('errors are logged elsewhere ('.(CLI     ?    'stderr':'sapi'  ).')');
    else                  $quiet || echoPre('errors are logged elsewhere ('.(WINDOWS ? 'event log':'syslog').')');
    exit(0);
}


// (3) check log file for existence and process it
if (!is_file    ($errorLog)) { $quiet || echoPre('error log does not exist: '.$errorLog); exit(0); }
if (!is_writable($errorLog)) {          stderror('cannot access log file: '  .$errorLog); exit(1); }
$errorLog = realPath($errorLog);

// rename the file; we don't want to lock it cause doing so could block the main app
$tempName = tempNam(dirName($errorLog), baseName($errorLog).'.');
if (!rename($errorLog, $tempName)) {
    stderror('cannot rename log file: '  .$errorLog);
    exit(1);
}

// read the log file line by line
PHP::ini_set('auto_detect_line_endings', 1);
$hFile = fOpen($tempName, 'rb');
$line  = $entry = '';
$i = 0;
while (($line=fGets($hFile)) !== false) {
    $i++;
    if (strStartsWith($line, '[')) {            // lines starting with a bracket "[" are considered the start of an entry
        processEntry($entry);
        $entry = '';
    }
    $entry .= $line;
}
processEntry($entry);                           // process the last entry (if any)

// delete the processed file
fClose($hFile);
unlink($tempName);


// (4) the ugly end
exit(0);


// --- function definitions -------------------------------------------------------------------------------------------------


/**
 * Send a single log entry to the defined error log receivers. The first line is sent as the mail subject and the full
 * log entry as the mail body.
 *
 * @param  string $entry - a single or multi line log entry
 */
function processEntry($entry) {
    if (!is_string($entry)) throw new IllegalTypeException('Illegal type of parameter $entry: '.getType($entry));
    $entry = trim($entry);
    if (!strLen($entry)) return;

    global $quiet, $sender, $receivers;

    $entry = normalizeEOL($entry, WINDOWS ? EOL_WINDOWS:EOL_UNIX);  // normalize line-breaks for email
    $entry = str_replace(chr(0), '?', $entry);                      // replace NUL bytes which destroy the mail

    $subject = strTok($entry, "\r\n");                              // that's CR or LF, not CRLF
    $message = $entry;

    $quiet || echoPre('sending log entry: '.subStr($subject, 0, 50).'...');

    // send log entry to receivers
    foreach ($receivers as $receiver) {
        // Linux:   "From:" header is not reqired but may be set
        // Windows: mail() fails if "sendmail_from" is not set and "From:" header is missing
        mail($receiver, $subject, $message, $headers='From: '.$sender);
    }
}


/**
 * Help. Display script syntax.
 *
 * @param  string $message [optional] - additional message to display (default: none)
 */
function help($message = null) {
    if (!is_null($message))
        echo($message.NL);

    $self = baseName($_SERVER['PHP_SELF']);

echo <<<HELP

 Syntax:  $self [options]

 Options:  -q   Quiet mode. Suppress status messages but not errors (for execution by CRON).
           -h   This help screen.


HELP;
}

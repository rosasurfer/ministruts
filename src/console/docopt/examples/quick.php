#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echoPre;

require(dirname(realpath(__FILE__)).'/../../../../etc/vendor/autoload.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<HELP
Usage:
  $self tcp <host> <port> [--timeout=<seconds>]
  $self serial <port> [--baud=9600] [--timeout=<seconds>]
  $self -h | --help | --version

HELP;

$result = docopt($doc, null, ['version'=>'0.1.1rc']);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
echoPre($result);
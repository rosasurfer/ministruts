#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echoPre;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<DOCOPT

Example of a program which uses the [options] shortcut.

Usage: $self  [options] <port>

Options:
  -h --help              show this help message and exit
  --version              show version and exit
  -n, --number N         use N as a number
  -t, --timeout TIMEOUT  set timeout TIMEOUT seconds
  --apply                apply changes to database
  -q                     operate in quiet mode

DOCOPT;

$result = docopt($doc, null, ['version'=>'1.0.0rc2']);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}

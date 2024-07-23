#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echof;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$doc = <<<DOCOPT
Example of a program which uses the [options] shortcut.

Usage: {:cmd:}  [options] <port>

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
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

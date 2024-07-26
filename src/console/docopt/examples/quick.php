#!/usr/bin/env php
<?php
use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$doc = <<<DOCOPT
Usage:
  {:cmd:}  tcp <host> <port> [--timeout=<seconds>]
  {:cmd:}  serial <port> [--baud=9600] [--timeout=<seconds>]
  {:cmd:}  -h | --help | --version

DOCOPT;

$result = docopt($doc, null, ['version'=>'0.1.1rc']);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

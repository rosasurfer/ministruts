#!/usr/bin/env php
<?php
use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$doc = <<<DOCOPT
Usage: {:cmd:}  [-h | --help] (ODD EVEN)...

Try:   {:cmd:}  1 2 3 4

Options:
  -h, --help

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

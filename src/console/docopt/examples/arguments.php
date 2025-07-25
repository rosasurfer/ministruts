#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\json_encode;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$doc = <<<DOCOPT
Process FILE and optionally apply correction to either left-hand or right-hand side.

Usage: {:cmd:}  [-vqrh] [FILE]...
       {:cmd:}  (--left | --right) CORRECTION FILE

Arguments:
  FILE        optional input file
  CORRECTION  correction angle, needs FILE, --left or --right to be present

Options:
  -h --help
  -v          verbose mode
  -q          quiet mode
  -r          make report
  --left      use left-hand side
  --right     use right-hand side

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

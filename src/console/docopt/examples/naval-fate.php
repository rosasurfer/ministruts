#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$doc = <<<DOCOPT
Naval Fate.

Usage:
  {:cmd:}  ship new <name>...
  {:cmd:}  ship <name> move <x> <y> [--speed=<kn>]
  {:cmd:}  ship shoot <x> <y>
  {:cmd:}  mine (set|remove) <x> <y> [--moored|--drifting]
  {:cmd:}  -h | --help
  {:cmd:}  --version

Options:
  -h --help     Show this screen.
  --version     Show version.
  --speed=<kn>  Speed in knots [default: 10].
  --moored      Moored (anchored) mine.
  --drifting    Drifting mine.

DOCOPT;

$result = docopt($doc, null, ['version'=>'Naval Fate 2.0']);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value, JSON_THROW_ON_ERROR).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

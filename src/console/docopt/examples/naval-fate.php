#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;
use function rosasurfer\json_encode;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<DOCOPT

Naval Fate.

Usage:
  $self  ship new <name>...
  $self  ship <name> move <x> <y> [--speed=<kn>]
  $self  ship shoot <x> <y>
  $self  mine (set|remove) <x> <y> [--moored|--drifting]
  $self  -h | --help
  $self  --version

Options:
  -h --help     Show this screen.
  --version     Show version.
  --speed=<kn>  Speed in knots [default: 10].
  --moored      Moored (anchored) mine.
  --drifting    Drifting mine.

DOCOPT;

$result = docopt($doc, null, ['version'=>'Naval Fate 2.0']);

foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

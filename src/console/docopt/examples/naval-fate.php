#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echof;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

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
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

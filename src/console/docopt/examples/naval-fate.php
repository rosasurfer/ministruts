#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
require $autoload;

$doc = <<<'DOCOPT'
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

$docoptResult = docopt($doc, ['version'=>'Naval Fate 2.0']);
echof(toString(json_encode($docoptResult)));

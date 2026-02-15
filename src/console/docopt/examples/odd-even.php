#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
require $autoload;

$doc = <<<'DOCOPT'
Usage: {:cmd:}  [-h | --help] (ODD EVEN)...

Try:   {:cmd:}  1 2 3 4

Options:
  -h, --help

DOCOPT;

$docoptResult = docopt($doc);
echof(toString(json_encode($docoptResult)));

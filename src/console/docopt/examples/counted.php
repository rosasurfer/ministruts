#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
require $autoload;

$doc = <<<'DOCOPT'
Usage: {:cmd:}  --help
       {:cmd:}  -v...
       {:cmd:}  go [go]
       {:cmd:}  (--path=<path>)...
       {:cmd:}  <file> <file>

Try:   {:cmd:}  -vvvvvvvvvv
       {:cmd:}  go go
       {:cmd:}  --path ./here --path ./there
       {:cmd:}  this.txt that.txt

DOCOPT;

$docoptResult = docopt($doc);
echof(toString(json_encode($docoptResult)));

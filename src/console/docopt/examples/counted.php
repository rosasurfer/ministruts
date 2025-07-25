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

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

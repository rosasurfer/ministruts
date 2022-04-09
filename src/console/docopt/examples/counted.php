#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$doc = <<<DOCOPT
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
    echoPre($key.': '.json_encode($value));
}

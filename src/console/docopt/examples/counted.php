#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echof;

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
    echof($key.': '.json_encode($value));
}

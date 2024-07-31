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

Usage: $self  --help
       $self  -v...
       $self  go [go]
       $self  (--path=<path>)...
       $self  <file> <file>

Try:   $self  -vvvvvvvvvv
       $self  go go
       $self  --path ./here --path ./there
       $self  this.txt that.txt

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}

#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../etc/vendor/autoload.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<HELP
Usage: $self --help
       $self -v...
       $self go [go]
       $self (--path=<path>)...
       $self <file> <file>

Try:   $self -vvvvvvvvvv
       $self go go
       $self --path ./here --path ./there
       $self this.txt that.txt

HELP;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
echoPre($result->getArgs());
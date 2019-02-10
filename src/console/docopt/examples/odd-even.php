#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echoPre;

require(dirname(realpath(__FILE__)).'/../../../../etc/vendor/autoload.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<HELP
Usage: $self [-h | --help] (ODD EVEN)...

Try:   $self 1 2 3 4

Options:
  -h, --help

HELP;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
echoPre($result);

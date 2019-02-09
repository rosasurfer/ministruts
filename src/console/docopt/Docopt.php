<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\StaticClass;


/**
 * Docopt
 */
class Docopt extends StaticClass {


    /**
     * API compatibility with python docopt
     *
     * @param  string       $doc
     * @param  string|array $params [optional]
     *
     * @return Result
     */
    public static function handle($doc, $params = []) {
        $argv = null;
        if (isset($params['argv'])) {
            $argv = $params['argv'];
            unset($params['argv']);
        }
        elseif (is_string($params)) {
            $argv = $params;
            $params = [];
        }
        /** @var array */
        $options = $params;
        $parser = new Parser($options);
        return $parser->parse($doc, $argv);
    }
}

<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\StaticClass;


/**
 * Docopt
 *
 * Command-line argument parser that will make you smile.
 */
class Docopt extends StaticClass {


    /**
     * API compatibility with python docopt
     *
     * @param  string       $doc
     * @param  string|array $params
     *
     * @return Response
     */
    public static function handle($doc, $params = []) {
        $argv = null;
        if (isSet($params['argv'])) {
            $argv = $params['argv'];
            unset($params['argv']);
        }
        elseif (is_string($params)) {
            $argv = $params;
            $params = [];
        }
        /** @var array */
        $options = $params;
        $handler = new Handler($options);
        return $handler->handle($doc, $argv);
    }
}

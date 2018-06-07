<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\IllegalTypeException;


/**
 * Windows constants
 */
class Windows extends StaticClass {


    /** @var int - for example the maximum path on drive D is "D:\some-256-character-path-string<NUL>" */
    const MAX_PATH = 260;

    /** @var array - Win32 errors and descriptions */
    private static $win32Errors = [
         0 => ['0',                    'The system is out of memory or resources.'],
         2 => ['ERROR_FILE_NOT_FOUND', 'The specified file was not found.'        ],
         3 => ['ERROR_PATH_NOT_FOUND', 'The specified path was not found.'        ],
        11 => ['ERROR_BAD_FORMAT',     'The .exe file is invalid.'                ],
    ];


    /**
     * Return a human-readable version of a Win32 error code.
     *
     * @param  int error
     *
     * @return string
     */
    public static function errorToString($error) {
        if (!is_int($error)) throw new IllegalTypeException('Illegal type of parameter $error: '.getType($error));

        if (key_exists($error, self::$win32Errors))
            return self::$win32Errors[$error][0];
        return (string) $error;
    }


    /**
     * Return a description of a Win32 error code.
     *
     * @param  int error
     *
     * @return string
     */
    public static function errorDescription($error) {
        if (!is_int($error)) throw new IllegalTypeException('Illegal type of parameter $error: '.getType($error));

        if (key_exists($error, self::$win32Errors))
            return self::$win32Errors[$error][1];
        return (string) $error;
    }
}

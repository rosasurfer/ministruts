<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;

/**
 * Windows constants
 */
class Windows extends StaticClass {

    /** for example the maximum path on drive D is "D:\some-256-character-path-string<NUL>" */
    public const MAX_PATH = 260;

    /** @var array<string[]> - Win32 errors and descriptions */
    private static array $win32Errors = [
          0 => ['0',                    'The system is out of memory or resources.'                      ],
          2 => ['ERROR_FILE_NOT_FOUND', 'The system cannot find the file specified.'                     ],
          3 => ['ERROR_PATH_NOT_FOUND', 'The system cannot find the path specified.'                     ],
         11 => ['ERROR_BAD_FORMAT',     'An attempt was made to load a program with an incorrect format.'],
        193 => ['ERROR_BAD_EXE_FORMAT', 'The command is not a valid Win32 application.'                  ],
    ];


    /**
     * Return a human-readable version of a Win32 error code.
     *
     * @param  int $error
     *
     * @return string
     */
    public static function errorToString(int $error): string {
        if (\key_exists($error, self::$win32Errors)) {
            return self::$win32Errors[$error][0];
        }
        return (string) $error;
    }


    /**
     * Return a description of a Win32 error code.
     *
     * @param  int $error
     *
     * @return string
     */
    public static function errorDescription(int $error): string {
        if (\key_exists($error, self::$win32Errors)) {
            return self::$win32Errors[$error][1];
        }
        return (string) $error;
    }
}

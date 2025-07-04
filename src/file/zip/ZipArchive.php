<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\file\zip;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\log\Logger;

use const rosasurfer\ministruts\L_WARN;

/**
 * ZipArchive
 *
 * Drop-in replacement for the built-in PHP class with improved error handling.
 */
class ZipArchive extends \ZipArchive {

    use ObjectTrait, DiAwareTrait;

    /** @var string[][] */
    protected static array $errors = [
        parent::ER_OK          => ['ER_OK'             , 'no error'                             ],      //  0
        parent::ER_MULTIDISK   => ['ER_MULTIDISK'      , 'multi-disk zip archives not supported'],      //  1
        parent::ER_RENAME      => ['ER_RENAME'         , 'renaming temporary file failed'       ],      //  2
        parent::ER_CLOSE       => ['ER_CLOSE'          , 'closing zip archive failed'           ],      //  3
        parent::ER_SEEK        => ['ER_SEEK'           , 'seek error'                           ],      //  4
        parent::ER_READ        => ['ER_READ'           , 'read error'                           ],      //  5
        parent::ER_WRITE       => ['ER_WRITE'          , 'write error'                          ],      //  6
        parent::ER_CRC         => ['ER_CRC'            , 'CRC error'                            ],      //  7
        parent::ER_ZIPCLOSED   => ['ER_ZIPCLOSED'      , 'containing zip archive was closed'    ],      //  8
        parent::ER_NOENT       => ['ER_NOENT'          , 'no such file'                         ],      //  9
        parent::ER_EXISTS      => ['ER_EXISTS'         , 'file already exists'                  ],      // 10
        parent::ER_OPEN        => ['ER_OPEN'           , 'can\'t open file'                     ],      // 11
        parent::ER_TMPOPEN     => ['ER_TMPOPEN'        , 'failure to create temporary file'     ],      // 12
        parent::ER_ZLIB        => ['ER_ZLIB'           , 'zlib error'                           ],      // 13
        parent::ER_MEMORY      => ['ER_MEMORY'         , 'memory allocation failed'             ],      // 14
        parent::ER_CHANGED     => ['ER_CHANGED'        , 'entry has been changed'               ],      // 15
        parent::ER_COMPNOTSUPP => ['ER_COMPNOTSUPP'    , 'compression method not supported'     ],      // 16
        parent::ER_EOF         => ['ER_EOF'            , 'premature EOF'                        ],      // 17
        parent::ER_INVAL       => ['ER_INVAL'          , 'invalid argument'                     ],      // 18
        parent::ER_NOZIP       => ['ER_NOZIP'          , 'not a zip archive'                    ],      // 19
        parent::ER_INTERNAL    => ['ER_INTERNAL'       , 'internal error'                       ],      // 20
        parent::ER_INCONS      => ['ER_INCONS'         , 'zip archive inconsistent'             ],      // 21
        parent::ER_REMOVE      => ['ER_REMOVE'         , 'can\'t remove file'                   ],      // 22
        parent::ER_DELETED     => ['ER_DELETED'        , 'entry has been deleted'               ],      // 23
        24                     => ['ER_ENCRNOTSUPP'    , 'encryption method not supported'      ],      // 24 (since PHP 7.4.3)
        25                     => ['ER_RDONLY'         , 'read-only archive'                    ],      // 25 (since PHP 7.4.3)
        26                     => ['ER_NOPASSWD'       , 'no password provided'                 ],      // 26 (since PHP 7.4.3)
        27                     => ['ER_WRONGPASSWD'    , 'wrong password provided'              ],      // 27 (since PHP 7.4.3)
        28                     => ['ER_OPNOTSUPP'      , 'operation not supported'              ],      // 28 (since PHP 7.4.3)
        29                     => ['ER_INUSE'          , 'resource still in use'                ],      // 29 (since PHP 7.4.3)
        30                     => ['ER_TELL'           , 'tell error'                           ],      // 30 (since PHP 7.4.3)
        31                     => ['ER_COMPRESSED_DATA', 'compressed data invalid'              ],      // 31 (since PHP 7.4.3)
        32                     => ['ER_CANCELLED'      , 'operation cancelled'                  ],      // 32 (since PHP 7.4.3)
        33                     => ['ER_DATA_LENGTH'    , 'unexpected length of data'            ],      // 33 (since PHP 8.3.0)
        34                     => ['ER_NOT_ALLOWED'    , 'not allowed in torrentzip'            ],      // 34 (since PHP 8.3.0)
    ];


    /**
     * {@inheritDoc}
     *
     * @param  string $filename
     * @param  int    $flags [optional]
     *
     * @return int - Returns always an error status and not a mixed value. On success it returns ER_OK.
     */
    public function open($filename, $flags = 0): int {
        /** @var bool|int $result */
        $result = parent::open($filename, $flags);

        if ($result === true) {
            return parent::ER_OK;
        }
        else if ($result === false) {
            return parent::ER_OPEN;
        }
        if (!isset(static::$errors[$result])) {
            Logger::log("ZipArchive::open() returned an unknown error: $result", L_WARN);
        }
        return $result;
    }


    /**
     * Return a human-readable version of a ZipArchive error code.
     *
     * @param  int $error
     *
     * @return string
     */
    public static function errorToStr(int $error): string {
        return static::$errors[$error][0] ?? (string)$error;
    }


    /**
     * Return a description of a ZipArchive error code.
     *
     * @param  int $error
     *
     * @return string
     */
    public static function errorDescription(int $error): string {
        return static::$errors[$error][1] ?? 'unknown error';
    }
}

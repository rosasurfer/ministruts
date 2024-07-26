<?php
namespace rosasurfer\ministruts\file;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;


/**
 * FileSystem
 *
 * File system related functionality
 */
class FileSystem extends StaticClass {


    /**
     * Drop-in replacement for the built-in PHP function {@link \mkdir()}.
     *
     * Make sure a directory exists. If the directory does not exist try to create it. By default this method will create
     * all specified sub-directories. It will set default permissions a bit more restrictive and will *not* emit a warning if a
     * directory already exists.
     *
     * @param  string   $path                 - directory name                                                              <br>
     * @param  int      $mode      [optional] - permission mode to set if the directory is created on non-Windows systems   <br>
     *                                          (default: 0775 = rwxrwxr x)                                                 <br>
     * @param  bool     $recursive [optional] - whether to automatically create specified sub-directories                   <br>
     *                                          (default: yes)                                                              <br>
     * @param  resource $context   [optional]
     *
     * @return bool - success status
     */
    public static function mkDir($path, $mode=0775, $recursive=true, $context=null) {
        if (!is_dir($path)) {
            if (is_file($path)) throw new RuntimeException('Cannot create directory "'.$path.'" (existing file of the same name)');

            $args = [$path, $mode, $recursive];
            func_num_args() > 3 && $args[] = $context;

            $ex = null;
            try {
                return \mkdir(...$args);                // unpack arguments as mkdir() will not accept $context = null
            }
            catch (IRosasurferException $ex) {}
            catch (\Throwable           $ex) { $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex); }

            throw $ex->appendMessage('Cannot create directory "'.$path.'"');
        }
        return true;
    }


    /**
     * Drop-in replacement for the built-in PHP function {@link \copy()}.
     *
     * Copies a file. If the target directory does not exist try to create it. If the destination file already exists,
     * it will be overwritten.
     *
     * @param  string   $source             - path to the source file
     * @param  string   $destination        - destination file
     * @param  resource $context [optional]
     *
     * @return bool - success status
     */
    public static function copy($source, $destination, $context = null) {
        $dir = dirname($destination);
        !is_dir($dir) && static::mkDir($dir);

        return copy(...func_get_args());                // unpack arguments as copy() will not accept $context = null
    }
}

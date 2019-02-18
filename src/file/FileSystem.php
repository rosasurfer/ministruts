<?php
namespace rosasurfer\file;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\RosasurferExceptionInterface;
use rosasurfer\exception\RuntimeException;


/**
 * FileSystem
 *
 * File system related functionality
 */
class FileSystem extends StaticClass {


    /**
     * Make sure a directory exists. If the directory does not exist try to create it. By default this method will create
     * specified sub-directories. It will *not* emit a warning if a directory already exists.
     *
     * @param  string   $path                 - directory name                                                              <br>
     * @param  int      $mode      [optional] - permission mode to set if the directory is created on non-Windows systems   <br>
     *                                          (default: 0777 = rwxrwxrwx)                                                 <br>
     * @param  bool     $recursive [optional] - whether to automatically create specified sub-directories                   <br>
     *                                          (default: yes)                                                              <br>
     * @param  resource $context   [optional]
     *
     * @return bool - success status
     *
     * @throws RuntimeException if the directory cannot be created
     */
    public static function mkDir($path, $mode=null, $recursive=true, $context=null) {
        if (!is_dir($path)) {
            if (is_file($path)) throw new RuntimeException('Cannot create directory "'.$path.'" (existing file of the same name)');
            $args = func_get_args();
            $argc = func_num_args();

            if ($argc < 3) {
                if ($argc == 1)
                    $args[] = null;                             // use default $mode = null
                $args[] = true;                                 // use default $recursive = true
            }
            try {
                if (!mkdir(...$args)) throw new \Exception();   // unpack new arguments as mkdir() will not accept $context = null
            }
            catch (\Exception $ex) {
                if (!$ex instanceof RosasurferExceptionInterface)
                    $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
                throw $ex->addMessage('Cannot create directory "'.$path.'"');
            }
        }
        return true;
    }
}

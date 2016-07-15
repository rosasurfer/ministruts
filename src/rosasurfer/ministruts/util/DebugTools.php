<?php
use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\IRosasurferException;

use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\NL;


/**
 * Debugging related functionality
 */
class DebugTools extends StaticClass {


   /**
    * Take a regular PHP stacktrace and create a fixed and more readable Java-like one.
    *
    * @param  array  $trace - regular PHP stacktrace
    * @param  string $file  - name of the file where the stacktrace was generated (optional)
    * @param  int    $line  - line of the file where the stacktrace was generated (optional)
    *
    * @return array - new fixed stacktrace
    *
    * @example
    * original stacktrace:
    * <pre>
    *   require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
    *   include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
    *   include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
    *   {main}
    * </pre>
    *
    * new stacktrace:
    * <pre>
    *   require_once()             [php]
    *   include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
    *   include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
    *   {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
    * </pre>
    */
   public static function fixTrace(array $trace, $file='unknown', $line=0) {
      // check if the stacktrace is already fixed
      if ($trace) {
         $lastFrame = $trace[sizeOf($trace)-1];
         if (isSet($lastFrame['function']) && $lastFrame['function']=='{main}')
            return $trace;                               // already fixed
      }

      // add a frame for the main script to the bottom (end of array)
      $trace[] = ['function' => '{main}'];

      // move FILE and LINE fields down (to the end) by one position
      for ($i=sizeOf($trace); $i--;) {
         if (isSet($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
         else                       unset($trace[$i]['file']);

         if (isSet($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
         else                       unset($trace[$i]['line']);

         /*
         if (isSet($trace[$i]['args'])) {             // skip content of large vars
            foreach ($trace[$i]['args'] as &$arg) {
               if     (is_object($arg)) $arg = get_class($arg);
               elseif (is_array ($arg)) $arg = 'Array()';
            } unset($arg);
         }
         */
      }

      // add location details to the top (beginning of array)
      $trace[0]['file'] = $file;
      $trace[0]['line'] = $line;

      return $trace;

      /**
       * @TODO: fix wrong stacktrace frames originating from calls to virtual static functions
       *
       * phalcon\mvc\Model::__callStatic()                  [php-phalcon]
       * vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
       * vokuro\auth\Auth->check()               # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
       */
   }


   /**
    * Return a formatted and human-readable version of a stacktrace.
    *
    * @param  array  $trace  - stacktrace
    * @param  string $indent - indent the formatted lines by this value (default: empty string)
    *
    * @return string
    *
    * @example
    *
    * original stacktrace format:
    * <pre>
    *   #0 [internal function]: PDO->__construct('mysql:host=127....', 'root', '', Array)
    *   #1 [internal function]: Phalcon\Db\Adapter\Pdo->connect(Array)
    *   #2 /var/www/phalcon/vokuro/app/config/services.php(72): Phalcon/Db/Adapter/Pdo->__construct(Array)
    *   #3 [internal function]: {closure}()
    *   #4 [internal function]: Phalcon\Di\Service->resolve(NULL, Object(Phalcon\Di\FactoryDefault))
    *   #5 [internal function]: Phalcon\Di->get('db', NULL)
    *   #6 [internal function]: Phalcon\Di->getShared('db')
    *   #7 [internal function]: Phalcon\Mvc\Model\Manager->_getConnection(Object(Vokuro\Models\Users), NULL)
    *   #8 [internal function]: Phalcon\Mvc\Model\Manager->getReadConnection(Object(Vokuro\Models\Users))
    *   #9 [internal function]: Phalcon\Mvc\Model->getReadConnection()
    *   #10 [internal function]: Phalcon\Mvc\Model\MetaData\Strategy\Introspection->getMetaData(Object(Vokuro\Models\Users), Object(Phalcon\Di\FactoryDefault))
    *   #11 [internal function]: Phalcon\Mvc\Model\MetaData->_initialize(Object(Vokuro\Models\Users), 'vokuro\\models\\u...', 'users', '')
    *   #12 [internal function]: Phalcon\Mvc\Model\MetaData->readMetaDataIndex(Object(Vokuro\Models\Users), 4)
    *   #13 [internal function]: Phalcon\Mvc\Model\MetaData->getDataTypes(Object(Vokuro\Models\Users))
    *   #14 [internal function]: Phalcon\Mvc\Model::_invokeFinder('findFirstByEmai...', Array)
    *   #15 /var/www/phalcon/vokuro/app/library/Auth/Auth.php(27): Phalcon/Mvc/Model::__callStatic('findFirstByEmai...', Array)
    *   #16 /var/www/phalcon/vokuro/app/library/Auth/Auth.php(27): Vokuro/Models/Users::findFirstByEmail('demo@phalconphp...')
    *   #17 /var/www/phalcon/vokuro/app/controllers/SessionController.php(90): Vokuro/Auth/Auth->check(Array)
    *   #18 [internal function]: Vokuro\Controllers\SessionController->loginAction()
    *   #19 [internal function]: Phalcon\Dispatcher->dispatch()
    *   #20 /var/www/phalcon/vokuro/public/index.php(36): Phalcon/Mvc/Application->handle()
    *   #21 {main}
    * </pre>
    *
    * new stacktrace format:
    * <pre>
    *   PDO->__construct()                                                # line 72, file: /var/www/phalcon/vokuro/app/config/services.php
    *   phalcon\db\adapter\Pdo->connect()                                            [php-phalcon]
    *   phalcon\db\adapter\Pdo->__construct()                                        [php-phalcon]
    *   {closure}                                                         # line 72, file: /var/www/phalcon/vokuro/app/config/services.php
    *   phalcon\di\Service->resolve()                                                [php-phalcon]
    *   phalcon\Di->get()                                                            [php-phalcon]
    *   phalcon\Di->getShared()                                                      [php-phalcon]
    *   phalcon\mvc\model\Manager->_getConnection()                                  [php-phalcon]
    *   phalcon\mvc\model\Manager->getReadConnection()                               [php-phalcon]
    *   phalcon\mvc\Model->getReadConnection()                                       [php-phalcon]
    *   phalcon\mvc\model\metadata\strategy\Introspection->getMetaData()             [php-phalcon]
    *   phalcon\mvc\model\MetaData->_initialize()                                    [php-phalcon]
    *   phalcon\mvc\model\MetaData->readMetaDataIndex()                              [php-phalcon]
    *   phalcon\mvc\model\MetaData->getDataTypes()                                   [php-phalcon]
    *   phalcon\mvc\Model::_invokeFinder()                                           [php-phalcon]
    *   phalcon\mvc\Model::__callStatic()                                            [php-phalcon]
    *   vokuro\auth\Auth->check()                                         # line 27, file: /var/www/phalcon/vokuro/app/library/Auth/Auth.php
    *   vokuro\controllers\SessionController->loginAction()               # line 90, file: /var/www/phalcon/vokuro/app/controllers/SessionController.php
    *   phalcon\Dispatcher->dispatch()                                               [php-phalcon]
    *   phalcon\mvc\Application->handle()                                            [php-phalcon]
    *   {main}                                                            # line 36, file: /var/www/phalcon/vokuro/public/index.php
    * </pre>
    */
   public static function formatTrace(array $trace, $indent='') {
      $result = null;

      $size = sizeOf($trace);
      $callLen = $lineLen = 0;

      for ($i=0; $i < $size; $i++) {               // align FILE and LINE
         $frame =& $trace[$i];

         $call = self::getFQFunctionName($frame);
         $call!='{main}' && $call!='{closure}' && $call.='()';
         $callLen = max($callLen, strLen($call));
         $frame['call'] = $call;

         $frame['line'] = isSet($frame['line']) ? " # line $frame[line]," : '';
         $lineLen = max($lineLen, strLen($frame['line']));

         if (isSet($frame['file']))                 $frame['file'] = ' file: '.$frame['file'];
         elseif (strStartsWith($call, 'phalcon\\')) $frame['file'] = ' [php-phalcon]';
         else                                       $frame['file'] = ' [php]';
      }

      for ($i=0; $i < $size; $i++) {
         $result .= $indent.str_pad($trace[$i]['call'], $callLen).' '.str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file'].NL;
      }

      return $result;
   }


   /**
    * Return the fully qualified function or method name of a stacktrace's frame.
    *
    * @param  array $frame - frame
    *
    * @return string - fully qualified name (namespace part all lower-case)
    */
   public static function getFQFunctionName(array $frame) {
      $class = $function = '';

      if (isSet($frame['function'])) {
         $function = $frame['function'];

         if (isSet($frame['class'])) {
            $class = $frame['class'];
            if (is_int($pos=strRPos($class, '\\'))) {
               $class = strToLower(subStr($class, 0, $pos)).subStr($class, $pos);
            }
            $class = $class.$frame['type'];
         }
         else if (is_int($pos=strRPos($function, '\\'))) {
            $function = strToLower(subStr($function, 0, $pos)).subStr($function, $pos);
         }
      }
      return $class.$function;
   }


   /**
    * Return a more readable version of an exception's message.
    *
    * @param  Exception $exception - any exception (not only RosasurferExceptions)
    *
    * @return string - message
    */
   public static function getBetterMessage(\Exception $exception) {
      $class     = get_class($exception);
      $namespace = strToLower(strLeftTo($class, '\\', -1, true, ''));
      $name      = strRightFrom($class, '\\', -1, false, $class);

      if ($namespace != 'rosasurfer\\ministruts\\exceptions\\') $result = $namespace.$name;
      else                                                      $result = $name;    // just the base name for improved readability

      if ($exception instanceof \ErrorException)
         $result .= '('.self::errorLevelToStr($exception->getSeverity()).')';
      $result .= (strLen($message=$exception->getMessage()) ? ': ':'').$message;

      return $result;
   }


   /**
    * Return a more readable version of an exception's stacktrace. The representation also contains informations about
    * nested exceptions.
    *
    * @param  Exception $exception - any exception (not only RosasurferExceptions)
    * @param  string    $indent    - indent the resulting lines by this value (default: empty string)
    *
    * @return string - readable stacktrace
    */
   public static function getBetterTraceAsString(\Exception $exception, $indent='') {
      if ($exception instanceof IRosasurferException) $trace = $exception->getBetterTrace();
      else                                            $trace = self::fixTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
      $result = self::formatTrace($trace, $indent);

      if ($cause=$exception->getPrevious()) {
         // recursively add stacktraces of all nested exceptions
         $message = self::getBetterMessage($cause);
         $result .= NL.$indent.'caused by'.NL.$indent.$message.NL;
         $result .= self::{__FUNCTION__}($cause, $indent);              // recursion
      }
      return $result;
   }


   /**
    * Return a human-readable form of the specified error reporting level.
    *
    * @param  int $level - reporting level (default: the currently active reporting level)
    *
    * @return string
    */
   public static function errorLevelToStr($level=null) {
      if (!func_num_args()) $level = error_reporting();
      if (!is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

      $levels = array();

      if (!$level                     ) $levels[] = '0';                      //  zero
      if ($level & E_ERROR            ) $levels[] = 'E_ERROR';                //     1
      if ($level & E_WARNING          ) $levels[] = 'E_WARNING';              //     2
      if ($level & E_PARSE            ) $levels[] = 'E_PARSE';                //     4
      if ($level & E_NOTICE           ) $levels[] = 'E_NOTICE';               //     8
      if ($level & E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';           //    16
      if ($level & E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';         //    32
      if ($level & E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';        //    64
      if ($level & E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';      //   128
      if ($level & E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';           //   256
      if ($level & E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';         //   512
      if ($level & E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';          //  1024
      if ($level & E_STRICT           ) $levels[] = 'E_STRICT';               //  2048
      if ($level & E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';    //  4096
      if ($level & E_DEPRECATED       ) $levels[] = 'E_DEPRECATED';           //  8192
      if ($level & E_USER_DEPRECATED  ) $levels[] = 'E_USER_DEPRECATED';      // 16384
      if ($level & E_ALL == E_ALL     ) $levels   = ['E_ALL'];                // 32767

      return join('|', $levels);
   }
}

<?php
namespace rosasurfer;

use rosasurfer\config\AutoConfig;
use rosasurfer\config\Config;
use rosasurfer\config\ConfigInterface as IConfig;

use rosasurfer\core\StaticClass;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\util\PHP;


/**
 * Framework initialization
 */
class MiniStruts extends StaticClass {


    /** @var int - error handling mode in which regular PHP errors are logged */
    const LOG_ERRORS       = ErrorHandler::LOG_ERRORS;

    /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
    const THROW_EXCEPTIONS = ErrorHandler::THROW_EXCEPTIONS;


    /**
     * Initialize the framework. This method expects an array with any of the following options:
     *
     * "config"            - IConfig: Configuration instance. <br>
     *                     - string:  Configuration location, can point to a directory or to a .properties file. <br>
     *
     * "handle-errors"     - string:  How to handle regular PHP errors. If set to 'strict' errors are converted to PHP
     *                                ErrorExceptions and thrown. If set to 'weak' errors are logged and execution continues.
     *                                If set to 'ignore' you have to setup your own error handling mechanism. <br>
     *                                default: 'strict' <br>
     *
     * "handle-exceptions" - bool:    If set to TRUE exceptions are handled by the built-in exception handler. If set to
     *                                FALSE you have to setup your own exception handling mechanism. <br>
     *                                default: TRUE <br>
     *
     * "globals"           - bool:    If set to TRUE, the helper functions and constants defined in "rosasurfer/helpers.php"
     *                                are mapped to and available in the global namespace. This can simplify HTML views as
     *                                no additional PHP "use" declarations are needed. <br>
     *                                default: FALSE <br>
     *
     * @param  array $options
     */
    public static function init(array $options = []) {
        // set default values
        if (!isSet($options['handle-errors'    ])) $options['handle-errors'    ] = 'strict';
        if (!isSet($options['handle-exceptions'])) $options['handle-exceptions'] = true;
        if (!isSet($options['globals'          ])) $options['globals'          ] = false;
        if (!isSet($options['config'           ])) throw new InvalidArgumentException('Invalid argument $options (option "config" not set)');

        self::setupErrorHandling    ($options['handle-errors'    ]);
        self::setupExceptionHandling($options['handle-exceptions']);
        self::loadGlobalHelpers     ($options['globals'          ]);
        self::setConfiguration      ($options['config'           ]);

        // (1) check application settings                              // TODO: remove APPLICATION_ROOT dependency
        if (!defined('\APPLICATION_ROOT')) {
            $errorMsg = 'Error: The global constant APPLICATION_ROOT must be defined.';
            echoPre(CLI ? $errorMsg : 'application error (see error log)');
            error_log($errorMsg);
            exit(1);
        }
        !defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));

        // (2) check for admin tasks if on localhost
        // __phpinfo__               : show PHP config at start of script
        // __config__                : show application config (may contain further PHP config settings)
        // __config__   + __phpinfo__: show PHP config after application configuration
        // __shutdown__ + __phpinfo__: show PHP config at shutdown (end of script)
        // __cache__                 : show cache admin interface
        $phpInfoTaskAfterConfig = $configInfoTask = $cacheInfoTask = $atShutdown = false;

        if (LOCALHOST) {
            foreach ($_REQUEST as $param => $value) {
                $param = strToLower($param);
                if ($param == '__phpinfo__') {
                    if ($atShutdown) {
                        register_shutdown_function(function() {
                            PHP::phpInfo();
                            exit(0);
                        });
                    }
                    else if ($configInfoTask) {
                        $configInfoTask         = false;       // cancel config-info task
                        $phpInfoTaskAfterConfig = true;
                    }
                    else {
                        PHP::phpInfo();
                        exit(0);
                    }
                    break;                                    // stop parsing after "__phpinfo__"
                }
                else if ($param == '__config__') {
                    $configInfoTask = true;
                }
                else if ($param == '__cache__') {
                    $cacheInfoTask = true;
                    break;                                    // stop parsing after "__cache__"
                }
                else if ($param == '__shutdown__') {
                    $atShutdown = true;
                }
            }
        }

        // (3) load any further PHP config settings from the application's main configuration
        self::configurePhp();

        // (4) execute "config-info" task if enabled
        if ($configInfoTask) {
            echoPre(Config::getDefault()->info());
            exit(0);
        }

        // (5) execute "phpinfo" after-config task if enabled
        if ($phpInfoTaskAfterConfig) {
            PHP::phpInfo();
            exit(0);
        }

        // (6) execute "cache-info" task if enabled
        if ($cacheInfoTask) {
            //include(MINISTRUTS_ROOT.'/src/debug/apc.php'); // TODO: not yet implemented
            exit(0);
        }

        // (7) enforce PHP requirements (last step to be able to run admin tasks with erroneous PHP settings)
        !PHP::ini_get_bool('short_open_tag') && exit(1|echoPre('application error (see error log)')|error_log('Error: The PHP configuration value "short_open_tag" must be enabled (security).'));
        ini_get('request_order') != 'GP'     && exit(1|echoPre('application error (see error log)')|error_log('Error: The PHP configuration value "request_order" must be "GP".'));
    }


    /**
     * Update the PHP configuration with user defined settings.
     */
    private static function configurePhp() {
        /*
        ini_set('arg_separator.output'    , '&amp;'                );
        ini_set('auto_detect_line_endings',  1                     );
        ini_set('default_mimetype'        , 'text/html'            );
        ini_set('default_charset'         , 'UTF-8'                );
        ini_set('ignore_repeated_errors'  ,  0                     );
        ini_set('ignore_repeated_source'  ,  0                     );
        ini_set('ignore_user_abort'       ,  1                     );
        ini_set('display_errors'          , (int)(CLI || LOCALHOST));
        ini_set('display_startup_errors'  , (int)(CLI || LOCALHOST));
        ini_set('log_errors'              ,  1                     );
        ini_set('log_errors_max_len'      ,  0                     );
        ini_set('track_errors'            ,  1                     );
        ini_set('html_errors'             ,  0                     );
        ini_set('session.use_cookies'     ,  1                     );
        ini_set('session.use_trans_sid'   ,  0                     );
        ini_set('session.cookie_httponly' ,  1                     );
        ini_set('session.referer_check'   , ''                     );
        ini_set('zend.detect_unicode'     ,  1                     );     // BOM header recognition
      */
    }


    /**
     * Set the specified configuration as the application's main configuration or create one.
     *
     * @param  IConfig|string $config - configuration or config location as passed to the framework loader
     */
    private static function setConfiguration($config) {
        if (is_string($config))
            $config = new AutoConfig($config);
        Config::setDefault($config);
    }


    /**
     * Setup the application's error handling.
     *
     * @param  int|string $value - configuration value as passed to the framework loader
     */
    private static function setupErrorHandling($value) {
        $flag = self::THROW_EXCEPTIONS;
        if (is_string($value)) {
            $value = trim(strToUpper($value));
            if      ($value == 'weak'  ) $flag = self::LOG_ERRORS;  // default: THROW_EXCEPTIONS
            else if ($value == 'ignore') $flag = null;
        }
        if ($flag) {
            ErrorHandler::setupErrorHandling($flag);
        }
    }


    /**
     * Setup the application's exception handling.
     *
     * @param  bool|int|string $value - configuration value as passed to the framework loader
     */
    private static function setupExceptionHandling($value) {
        $enabled = true;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value = trim(strToLower($value));
            if ($value=='0' || $value=='off' || $value=='false')
                $enabled = false;                                   // default: true
        }

        if ($enabled) {
            ErrorHandler::setupExceptionHandling();
        }
    }


    /**
     * Map the helper constants and functions in namespace \rosasurfer to the global namespace.
     *
     * @param  mixed $value - configuration value as passed to the framework loader
     */
    private static function loadGlobalHelpers($value) {
        $enabled = false;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value   = trim(strToLower($value));
            $enabled = ($value=='1' || $value=='on' || $value=='true');
        }

        if ($enabled) {
            include(MINISTRUTS_ROOT.'/src/globals.php');
        }
    }


    /**
     * Replace an existing Composer class loader.
     *
     * @param  mixed $value - configuration value as passed to the framework loader
     */
    private static function replaceComposer($value) {
        $enabled = false;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value   = trim(strToLower($value));
            $enabled = ($value=='1' || $value=='on' || $value=='true');
        }

        if ($enabled) {
            // replace Composer
        }
    }
}

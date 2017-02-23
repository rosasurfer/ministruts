<?php
namespace rosasurfer;

use rosasurfer\config\Config;
use rosasurfer\config\ConfigInterface as IConfig;
use rosasurfer\config\StdConfig;

use rosasurfer\core\StaticClass;
use rosasurfer\debug\ErrorHandler;
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
     * "config"            - IConfig: configuration instance
     *                     - string:  configuration location, can either be a directory or a file.
     *
     * "global-helpers"    - bool: If set to TRUE, the helper functions and constants defined in namespace \rosasurfer
     *                             are additionally mapped to the global namespace.
     *                             default: FALSE (no global helpers)
     *                             see  {@link ./globals.php}
     *
     * "handle-errors"     - int:  Flag specifying how to handle regular PHP errors. Possible values:
     *                       LOG_ERRORS:       PHP errors are sent to the configured default logger.<br>
     *                       THROW_EXCEPTIONS: PHP errors are converted to PHP ErrorExceptions and thrown back. If this
     *                             option is used it is required to either configure the framework's exception handler or
     *                             to register your own exception handling mechanism. Without an exception handler PHP
     *                             will terminate a script with a FATAL error after such an exception.
     *                             default: NULL (no error handling)
     * strict|ignore
     *
     *
     * "handle-exceptions" - bool: If set to TRUE exceptions are handled by the built-in exception handler.<br>
     *                             Enabling this option is required if the option "handle-errors" is set to
     *                             THROW_EXCEPTIONS and you don't provide your own exception handling mechanism.
     *                             default: FALSE (no exception handling)
     * true|false
     *
     * @param  array $options
     */
    public static function init(array $options = []) {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'config'           : self::setConfiguration      ($value); continue;
                case 'global-helpers'   : self::loadGlobalHelpers     ($value); continue;
                case 'handle-errors'    : self::setupErrorHandling    ($value); continue;
                case 'handle-exceptions': self::setupExceptionHandling($value); continue;
              //case 'replace-composer' : self::replaceComposer       ($value); continue;     // TODO
            }
        }

        // (1) check application settings                              // TODO: remove APPLICATION_ROOT dependency
        !defined('\APPLICATION_ROOT') && exit(1|echoPre('application error (see error log)')|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
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
            $config = new StdConfig($config);
        Config::setDefault($config);
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
     * Setup the application's error handling.
     *
     * @param  int|string $value - configuration value as passed to the framework loader
     */
    private static function setupErrorHandling($value) {
        $flag = null;
        if (is_int($value)) {
            if     ($value == self::LOG_ERRORS      ) $flag = self::LOG_ERRORS;
            elseif ($value == self::THROW_EXCEPTIONS) $flag = self::THROW_EXCEPTIONS;
        }
        elseif (is_string($value)) {
            $value = trim(strToUpper($value));
            if     ($value=='LOG_ERRORS'       || $value=='LOG'  ) $flag = self::LOG_ERRORS;
            elseif ($value=='THROW_EXCEPTIONS' || $value=='THROW') $flag = self::THROW_EXCEPTIONS;
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
        $enabled = false;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value   = trim(strToLower($value));
            $enabled = ($value=='1' || $value=='on' || $value=='true');
        }

        if ($enabled) {
            ErrorHandler::setupExceptionHandling();
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

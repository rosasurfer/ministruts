<?php
namespace rosasurfer;

use rosasurfer\core\StaticClass;


/**
 * Framework initialization
 */
class MiniStruts extends StaticClass {


   /**
    * Initialize the framework. This method expects an array with the following case-insensitive options:
    *
    * • 'config'           - String: Full path to a custom application configuration file to use. The default is to use
    *                        the configuration found in "APPLICATION_ROOT/app/config/config.properties". If the application
    *                        structure follows its own standards use this setting to provide a custom configuration file.
    *
    * • 'replaceComposer'  - Boolean: If this option is set to TRUE, the framework replaces an existing Composer class
    *                        loader (non-standard compliant) with it's own standard compliant version. Use this option if
    *                        the case-sensitivity of Composer's class loader causes errors.
    *                        Default: FALSE
    *
    * • 'handleErrors'     - Integer: Flag specifying how to handle regular PHP errors. Possible values:
    *                        ERROR_HANDLER_LOG:   PHP errors are logged by the built-in default logger.
    *
    *                        ERROR_HANDLER_THROW: PHP errors are converted to PHP ErrorExceptions and thrown back. If this
    *                                             option is used it is required to either configure the frameworks exception
    *                                             handler or to register your own exception handling mechanism. Without an
    *                                             exception handler PHP will terminate a script with a FATAL error after
    *                                             such an exception.
    *                        Default: NULL (no error handling)
    *
    * • 'handleExceptions' - Boolean: If this option is set to TRUE, the framework will send otherwise unhandled exceptions
    *                        to the built-in default logger before PHP will terminate the script. Enabling this option is
    *                        required if the option 'handleErrors' is set to ERROR_HANDLER_THROW and you don't provide your
    *                        own exception handling mechanism.
    *                        Default: FALSE (no exception handling)
    *
    * • 'globalHelpers'    - Boolean: If this option is set to TRUE, the helper functions and constants defined in the
    *                        namespace "rosasurfer\" are additionally mapped to the global namespace.
    *                        (see "src/rosasurfer/helpers.php")
    *                        Default: FALSE (no global helpers)
    *
    * @param  array $options - An options array supporting the following settings:
    */
   public static function init(array $options = []) {
   }
}


<?
/**
 * Template for an index.php file
 * ------------------------------
 * Usually you don't need anything more in this file and yes, this directory is visible by the web.
 * It's the application's root directory.  You can virtually link it to whatever subdirectory you like.
 * (via WEB-INF/etc/httpd-conf/httpd-include.conf)
 */

include(dirName(__FILE__).'/WEB-INF/classes/classes.php');


define('APPLICATION_NAME'   ,  'myAppName');
define('APPLICATION_CONTEXT', '/'         ); // means the application resides in the document root
                                             // directory of the web server

FrontController ::me()->processRequest();
?>

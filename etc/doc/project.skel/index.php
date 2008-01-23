<?
/**
 * Template for an index.php file
 * ------------------------------
 * Usually you don't need anything more in this file and yes, this directory is visible by the web.
 * It is the application's root directory.  You can virtually link it to whatever web directory you
 * like, you may even nest one project into another (see "WEB-INF/etc/httpd/httpd-include.conf").
 * Make sure to modify this file's line "define('APPLICATION_CONTEXT', '...')" appropriately.
 */

include(dirName(__FILE__).'/WEB-INF/classes/classes.php');


define('APPLICATION_NAME'   ,  'myAppName');
define('APPLICATION_CONTEXT', '/'         ); // '/' means the application resides in the document root
                                             // directory of the web server nut you can use any directory

FrontController ::me()->processRequest();
?>

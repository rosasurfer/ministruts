<?
/**
 * Template for an index.php file
 * ------------------------------
 * Usually you don't need anything more in this file. This directory is visible by the web and it's
 * the application's root directory.  You can virtually link it to whatever web server directory
 * you like, you may even nest one project into another (see "WEB-INF/etc/httpd/httpd-include.conf").
 */

include(dirName(__FILE__).'/WEB-INF/classes/classes.php');

define('APPLICATION_NAME', 'myAppName');

FrontController ::processRequest();
?>

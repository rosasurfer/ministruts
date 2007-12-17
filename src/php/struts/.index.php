<?
// Vorlage für einfache index.php eines Projektes
// ----------------------------------------------
include(dirName(__FILE__).'/WEB-INF/classes/classes.php');

define('APPLICATION_NAME'   ,  'myAppName'           );
define('APPLICATION_CONTEXT', '/application_base_url');

FrontController ::me()->processRequest();
?>

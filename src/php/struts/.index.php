<?
define('APPLICATION_ROOT_DIRECTORY', dirName(__FILE__));                      $url = subStr($_SERVER['SCRIPT_FILENAME'], strLen($_SERVER['DOCUMENT_ROOT']));
define('APPLICATION_ROOT_URL'      , subStr($url, 0, strRPos($url, '/')));    unset($url);

FrontController ::me()->processRequest();
?>

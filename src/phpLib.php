<?
// Systemvoraussetzung: PHP 5.2+
// -----------------------------
define('START_TIME', microTime(true));


// Klassendefinitionen
// -------------------
$dir = dirName(__FILE__).DIRECTORY_SEPARATOR;

$__classes['AbstractCachePeer'              ] = $dir.'php/cache/AbstractCachePeer';
$__classes['ApcCache'                       ] = $dir.'php/cache/ApcCache';
$__classes['Cache'                          ] = $dir.'php/cache/Cache';
$__classes['RuntimeMemoryCache'             ] = $dir.'php/cache/RuntimeMemoryCache';

$__classes['ISingle'                        ] = $dir.'php/core/ISingle';
$__classes['Object'                         ] = $dir.'php/core/Object';
$__classes['Singleton'                      ] = $dir.'php/core/Singleton';
$__classes['StaticClass'                    ] = $dir.'php/core/StaticClass';

$__classes['IDAOConnected'                  ] = $dir.'php/dao/IDAOConnected';

$__classes['DB'                             ] = $dir.'php/db/DB';
$__classes['DBPool'                         ] = $dir.'php/db/DBPool';
$__classes['MySQLConnector'                 ] = $dir.'php/db/MySQLConnector';
$__classes['PersistableObject'              ] = $dir.'php/db/PersistableObject';

$__classes['BusinessRuleException'          ] = $dir.'php/exceptions/BusinessRuleException';
$__classes['ClassNotFoundException'         ] = $dir.'php/exceptions/ClassNotFoundException';
$__classes['ConcurrentModificationException'] = $dir.'php/exceptions/ConcurrentModificationException';
$__classes['DatabaseException'              ] = $dir.'php/exceptions/DatabaseException';
$__classes['FileNotFoundException'          ] = $dir.'php/exceptions/FileNotFoundException';
$__classes['IOException'                    ] = $dir.'php/exceptions/IOException';
$__classes['IllegalStateException'          ] = $dir.'php/exceptions/IllegalStateException';
$__classes['IllegalTypeException'           ] = $dir.'php/exceptions/IllegalTypeException';
$__classes['InfrastructureException'        ] = $dir.'php/exceptions/InfrastructureException';
$__classes['InvalidArgumentException'       ] = $dir.'php/exceptions/InvalidArgumentException';
$__classes['NestableException'              ] = $dir.'php/exceptions/NestableException';
$__classes['NoPermissionException'          ] = $dir.'php/exceptions/NoPermissionException';
$__classes['PHPErrorException'              ] = $dir.'php/exceptions/PHPErrorException';
$__classes['RuntimeException'               ] = $dir.'php/exceptions/RuntimeException';

$__classes['TorHelper'                      ] = $dir.'php/net/TorHelper';

$__classes['CurlHttpClient'                 ] = $dir.'php/net/http/CurlHttpClient';
$__classes['CurlHttpResponse'               ] = $dir.'php/net/http/CurlHttpResponse';
$__classes['HeaderParser'                   ] = $dir.'php/net/http/HeaderParser';
$__classes['HeaderUtils'                    ] = $dir.'php/net/http/HeaderUtils';
$__classes['HttpClient'                     ] = $dir.'php/net/http/HttpClient';
$__classes['HttpRequest'                    ] = $dir.'php/net/http/HttpRequest';
$__classes['HttpResponse'                   ] = $dir.'php/net/http/HttpResponse';

$__classes['Mailer'                         ] = $dir.'php/net/mail/Mailer';

$__classes['BasePdfDocument'                ] = $dir.'php/pdf/BasePdfDocument';
$__classes['SimplePdfDocument'              ] = $dir.'php/pdf/SimplePdfDocument';

$__classes['Action'                         ] = $dir.'php/struts/Action';
$__classes['ActionForm'                     ] = $dir.'php/struts/ActionForm';
$__classes['ActionForward'                  ] = $dir.'php/struts/ActionForward';
$__classes['ActionMapping'                  ] = $dir.'php/struts/ActionMapping';
$__classes['FrontController'                ] = $dir.'php/struts/FrontController';
$__classes['HttpSession'                    ] = $dir.'php/struts/HttpSession';
$__classes['Module'                         ] = $dir.'php/struts/Module';
$__classes['PageContext'                    ] = $dir.'php/struts/PageContext';
$__classes['Request'                        ] = $dir.'php/struts/Request';
$__classes['RequestProcessor'               ] = $dir.'php/struts/RequestProcessor';
$__classes['Response'                       ] = $dir.'php/struts/Response';
$__classes['RoleProcessor'                  ] = $dir.'php/struts/RoleProcessor';
$__classes['Struts'                         ] = $dir.'php/struts/Struts';
$__classes['Tile'                           ] = $dir.'php/struts/Tile';

$__classes['CommonValidator'                ] = $dir.'php/util/CommonValidator';
$__classes['Config'                         ] = $dir.'php/util/Config';
$__classes['Logger'                         ] = $dir.'php/util/Logger';
$__classes['String'                         ] = $dir.'php/util/String';

unset($dir);



// Konstanten
// ----------
// die einzelnen Loglevel
define('L_DEBUG' ,  1);
define('L_INFO'  ,  2);
define('L_NOTICE',  4);
define('L_WARN'  ,  8);
define('L_ERROR' , 16);
define('L_FATAL' , 32);
$__logLevels[''] = L_WARN;   // der Default-Loglevel


// Zeitkonstanten
define('MINUTE' ,  60         );
define('HOUR'   ,  60 * MINUTE);
define('DAY'    ,  24 * HOUR  );
define('WEEK'   ,   7 * DAY   );
define('MONTH'  ,  30 * DAY   );
define('YEAR'   , 365 * DAY   );


// ob wir unter Windows laufen
define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3))==='WIN'));


// Errorhandler registrieren
// -------------------------
set_error_handler    (array('Logger', 'handleError'    ));
set_exception_handler(array('Logger', 'handleException'));
/*
function wrapErrorHandler    ($level, $message, $file, $line, array $vars) { Logger ::handleError    ($level, $message, $file, $line, $vars); }
function wrapExceptionHandler(Exception $exception)                        { Logger ::handleException($exception)                           ; }
set_error_handler    ('wrapErrorHandler'    );
set_exception_handler('wrapExceptionHandler');
*/


/**
 * Lädt die angegebene Klasse.
 *
 * @param string  $className - Klassenname
 * @param boolean $throw     - ob Exceptions zurückgeworfen werfen dürfen (bei manuellem Aufruf)
 */
function __autoload($className /*, $throw */) {
   try {
      if (isSet($GLOBALS['__classes'][$className])) {
         $className = $GLOBALS['__classes'][$className];

         // TODO: ::autoload(): Warnen bei relativen Klassenpfaden
         /*
         $relative = WINDOWS ? !preg_match('/^[a-zA-Z]:/', $className) : (strPos($className, '/') !== 0);
         if ($relative)
            Logger ::log('Not an absolute class name definition: '.$className, L_WARN, __CLASS__);
         */

         include($className.'.php');
         return true;
      }
      throw new ClassNotFoundException("Undefined class '$className'");
   }
   catch (Exception $ex) {
      if (func_num_args() > 1)         // Exceptions nur weiterrreichen, wenn wir nicht vom PHP-Kernel aufgerufen wurden
         throw $ex;
      Logger ::handleException($ex);   // PHP-Kernel: manuell verarbeiten (__autoload darf keine Exceptions werfen)
   }
   return false;
}


/**
 * Ob der angegebene Klassenname definiert ist.  Diese Funktion ist notwendig, weil eine einfache
 * Abfrage der Art "if (class_exist($className, true))" __autoload() aufruft und dabei im Fehlerfall
 * das Script mit einem fatalen Fehler beendet (__autoload darf keine Exceptions werfen).
 * Wird __autoload aus dieser Funktion und nicht aus dem PHP-Kernel aufgerufen, werden Exceptions
 * weitergereicht und der folgende Code kann entsprechend reagieren.
 *
 * @param string $className - Klassenname
 *
 * @return boolean
 *
 * @see __autoload()
 */
function is_class($className) {
   if (class_exists($className, false))
      return true;

   try {
      return (bool) __autoload($className, true);
   }
   catch (ClassNotFoundException $ex) { /* yes, we eat it */ }

   return false;
}


/**
 * Gibt den Wert des 'Forwarded-IP'-Headers des aktuellen Request zurück.
 *
 * @return string - IP-Adresse oder NULL, wenn der entsprechende Header nicht gesetzt ist
 */
function getForwardedIP() {
   static $ip = false;

   if ($ip === false) {
      if (isSet($_SERVER['HTTP_X_FORWARDED_FOR'])) {
         $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_HTTP_X_FORWARDED_FOR'])) {
         $ip = $_SERVER['HTTP_HTTP_X_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_X_UP_FORWARDED_FOR'])) {       // mobile device
         $ip = $_SERVER['HTTP_X_UP_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'])) {  // mobile device
         $ip = $_SERVER['HTTP_HTTP_X_UP_FORWARDED_FOR'];
      }
      elseif (isSet($_SERVER[''])) {
         $ip = $_SERVER[''];
      }
      else {
         $ip = null;
      }
   }
   return $ip;
}


/**
 * Erzeugt eine zufällige ID (wegen Verwechselungsgefahr ohne die Zeichen 0 O 1 l I).
 *
 * @param int $length - Länge der ID
 *
 * @return string - ID
 */
function getRandomID($length) {
   if (!isSet($length) || !is_int($length) || $length < 1)
      throw new RuntimeException('Invalid argument length: '.$length);

   $id = crypt(uniqId(rand(), true));              // zufällige ID erzeugen
   $id = strip_tags(stripSlashes($id));            // Sonder- und leicht zu verwechselnde Zeichen entfernen
   $id = strRev(str_replace('/', '', str_replace('.', '', str_replace('$', '', str_replace('0', '', str_replace('O', '', str_replace('1', '', str_replace('l', '', str_replace('I', '', $id)))))))));
   $len = strLen($id);
   if ($len < $length) {
      $id .= getRandomID($length-$len);            // bis zur gewünschten Länge vergrößern ...
   }
   else {
      $id = subStr($id, 0, $length);               // oder auf die gewünschte Länge kürzen
   }
   return $id;
}


/**
 * Hilfsfunktion zur HTML-formatierten Ausgabe einer Variablen.
 *
 * @param mixed $var    - die auszugebende Variable
 * @param bool  $return - Ob die Ausgabe auf STDOUT erfolgen soll (FALSE) oder als Rückgabewert der Funktion (TRUE),
 *                        Default ist FALSE
 *
 * @return string - Rückgabewert, wenn $return TRUE ist, NULL andererseits.
 */
function printFormatted($var, $return = false) {
   if (is_object($var) && method_exists($var, '__toString')) {
      $str = $var->__toString();
   }
   elseif (is_object($var) || is_array($var)) {
      $str = print_r($var, true);
   }
   else {
      $str = (string) $var;
   }

   if (isSet($_SERVER['REQUEST_METHOD'])) {
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.$str.'</pre></div>';
   }
   $str .= "\n";

   if ($return)
      return $str;

   ob_get_level() ? ob_flush() : flush();
   echo $str;
   return null;
}


/**
 * Alias für printFormatted($var, false).
 *
 * @param mixed $var - die auszugebende Variable
 */
function echoPre($var) {
   printFormatted($var, false);
}


/**
 * Gibt einen String als JavaScript aus.
 *
 * @param string $snippet - Code
 */
function javaScript($snippet) {
   echo '<script language="JavaScript">'.$snippet.'</script>';
}


/**
 * Dekodiert einen HTML-String nach ISO-8859-15.
 *
 * @param string $html - der zu dekodierende String
 *
 * @return string
 */
function decodeHtml($html) {
   if ($html === null || $html === '')
      return $html;

   static $table = null;
   if ($table === null) {
      $table = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES));
      $table['&nbsp;'] = ' ';
      $table['&euro;'] = '€';
   }
   $string = strTr($html, $table);
   return preg_replace('/&#(\d+);/me', "chr('\\1')", $string);
}


/**
 * Dekodiert einen UTF-8-kodierten String nach ISO-8859-1.
 *
 * @param string $string - der zu dekodierende String
 *
 * @return string
 */
function decodeUtf8($string) {
   if ($string === null || $string === '')
      return $string;

   return html_entity_decode(htmlEntities($string, ENT_NOQUOTES, 'UTF-8'));
}


/**
 * Addiert zu einem Datum eine Anzahl von Tagen.
 *
 * @param string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param int    $days - Tagesanzahl
 *
 * @return string - resultierendes Datum
 */
function addDate($date, $days) {
   if (!CommonValidator ::isDate($date)) throw new InvalidArgumentException('Invalid argument $date: '.$date);
   if (!is_int($days))                   throw new InvalidArgumentException('Invalid argument $days: '.$days);

   $parts = explode('-', $date);
   $year  = (int) $parts[0];
   $month = (int) $parts[1];
   $day   = (int) $parts[2];

   return date('Y-m-d', mkTime(0, 0, 0, $month, $day+$days, $year));
}


/**
 * Subtrahiert von einem Datum eine Anzahl von Tagen.
 *
 * @param string $date - Ausgangsdatum (Format: yyyy-mm-dd)
 * @param int    $days - Tagesanzahl
 *
 * @return string
 */
function subDate($date, $days) {
   if (!is_int($days)) throw new InvalidArgumentException('Invalid argument $days: '.$days);
   return addDate($date, -$days);
}


/**
 * Formatiert einen Date- oder DateTime-Wert mit dem angegebenen Format.
 *
 * @param string $format   - Formatstring (siehe PHP Manual zu date())
 * @param string $datetime - Datum oder Zeit
 *
 * @return string
 */
function formatDate($format, $datetime) {
   if ($datetime === null)
      return null;

   if ($datetime < '1970-01-01 00:00:00') {
      if ($format != 'd.m.Y') {
         Logger ::log(new RuntimeException('Cannot format early datetime '.$datetime.' with format: '.$format), L_INFO, __CLASS__);
         return preg_replace('/[1-9]/', '0', date($format, time()));
      }

      $parts = explode('-', substr($datetime, 0, 10));
      return $parts[2].'.'.$parts[1].'.'.$parts[0];
   }

   $timestamp = strToTime($datetime);
   if (!is_int($timestamp))
      throw new InvalidArgumentException('Invalid argument $datetime: '.$datetime);

   return date($format, $timestamp);
}


/**
 * Formatiert einen Zahlenwert im Währungsformat.
 *
 * @param mixed  $value            - Zahlenwert (integer oder float)
 * @param int    $decimals         - Anzahl der Nachkommastellen
 * @param string $decimalSeparator - Dezimaltrennzeichen (entweder '.' oder ',')
 *
 * @return string
 */
function formatMoney($value, $decimals = 2, $decimalSeparator = ',') {
   if (!is_int($value) && !is_float($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
   if (!is_int($decimals))                   throw new IllegalTypeException('Illegal type of parameter $decimals: '.getType($decimals));

   if ($decimalSeparator == '.')
      return number_format($value, $decimals, '.', ',');

   if ($decimalSeparator == ',')
      return number_format($value, $decimals, ',', '.');

   throw new InvalidArgumentException('Invalid argument $decimalSeparator: '.$decimalSeparator);
}

/**
 * date_mysql2german
 * wandelt ein MySQL-DATE (ISO-Date)
 * in ein traditionelles deutsches Datum um.
function date_mysql2german($datum) {
    list($jahr, $monat, $tag) = explode("-", $datum);

    return sprintf("%02d.%02d.%04d", $tag, $monat, $jahr);
}
 */

/**
 * date_german2mysql
 * wandelt ein traditionelles deutsches Datum
 * nach MySQL (ISO-Date).
function date_german2mysql($datum) {
    list($tag, $monat, $jahr) = explode(".", $datum);

    return sprintf("%04d-%02d-%02d", $jahr, $monat, $tag);
}
 */

/**
 * timestamp_mysql2german
 * wandelt ein MySQL-Timestamp
 * in ein traditionelles deutsches Datum um.
function timestamp_mysql2german($t) {
    return sprintf("%02d.%02d.%04d",
                    substr($t, 6, 2),
                    substr($t, 4, 2),
                    substr($t, 0, 4));
}
*/


/**
 * Ist $value nicht NULL, gibt die Funktion $value zurück, ansonsten die Alternative $altValue.
 *
 * @return mixed
 */
function ifNull($value, $altValue) {
   return ($value === null) ? $altValue : $value;
}


/*
Indicate that script is being called by CLI (vielleicht besser für $console)
----------------------------------------------------------------------------
if (php_sapi_name() == 'cli') {
   $console = true ;
}
*/
?>

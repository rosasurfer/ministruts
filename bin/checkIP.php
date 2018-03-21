<?php
namespace rosasurfer\bin\check_ip;

use rosasurfer\Application;
use rosasurfer\net\NetTools;

use function rosasurfer\echoPre;

isSet($_SERVER['REQUEST_METHOD']) && exit(1);                           // in case we are running on CLI

// php.ini settings
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('error_log', __DIR__.'/../php-error.log');

// load the framework and create a new application
require(__DIR__.'/../src/load.php');
$app = new Application([
    'app.global-helpers' => true,
]);


// --- start ----------------------------------------------------------------------------------------------------------------


$address = getRemoteAddress();
$name    = NetTools::getHostByAddress($address);

if ($address == $name) echoPre('current IP address: '.$address);
else                   echoPre('current IP address: '.$address.'  ('.$name.')');


$value = getForwardedRemoteAddress();
if ($value) {
    if (!isIPAddress($value) || ($value==($name=NetTools::getHostByAddress($value)))) {
        echoPre('forwarded for:      '.$value);
    }
    else {
        echoPre('forwarded for:      '.$value.'  ('.$name.')');
    }
}

exit(0);


// --- functions ------------------------------------------------------------------------------------------------------------


/**
 * Gibt die IP-Adresse zurueck, von der aus der Request ausgeloest wurde.
 *
 * @return string - IP-Adresse
 */
function getRemoteAddress() {
    return $_SERVER['REMOTE_ADDR'];
}


/**
 * Gibt den Wert des 'X-Forwarded-For'-Headers des aktuellen Requests zurueck.
 *
 * @return string - Wert (ein oder mehrere IP-Adressen oder Hostnamen) oder NULL, wenn der Header nicht gesetzt ist
 */
function getForwardedRemoteAddress() {
    return getHeaderValue(array('X-Forwarded-For', 'X-UP-Forwarded-For'));
}


/**
 * Gibt den Wert des angegebenen Headers als String zurueck. Wird ein Array mit mehreren Namen angegeben oder wurden
 * mehrere Header des angegebenen Namens uebertragen, werden alle Werte dieser Header als eine komma-getrennte Liste
 * zurueckgegeben (in der uebertragenen Reihenfolge).
 *
 * @param  string|string[] $names - ein oder mehrere Headernamen
 *
 * @return string|null - Wert oder NULL, wenn die angegebenen Header nicht gesetzt sind
 */
function getHeaderValue($names) {
    if (is_string($names)) {
        $names = [$names];
    }
    elseif (is_array($names)) {
        foreach ($names as $name) {
            if (!is_string($name)) throw new \Exception('Illegal argument type in argument $names: '.getType($name));
        }
    }
    else throw new \Exception('Illegal type of parameter $names: '.getType($names));

    $headers = getHeaders($names);
    if ($headers)
        return join(',', $headers);

    return null;
}


/**
 * Gibt die angegebenen Header als Array von Name-Wert-Paaren zurueck (in der uebertragenen Reihenfolge).
 *
 * @param  string|array $names [optional] - ein oder mehrere Namen; ohne Angabe werden alle Header zurueckgegeben
 *
 * @return array - Name-Wert-Paare
 */
function getHeaders($names = null) {
    if     ($names === null)   $names = array();
    elseif (is_string($names)) $names = array($names);
    elseif (is_array($names)) {
        foreach ($names as $name) {
            if (!is_string($name)) throw new \Exception('Illegal argument type in argument $names: '.getType($name));
        }
    }
    else throw new \Exception('Illegal type of parameter $names: '.getType($names));

    // einmal alle Header einlesen
    static $headers = null;
    if ($headers === null) {
        if (function_exists('getAllHeaders')) {
            $headers = apache_request_headers();
        }
        else {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if(subStr($key, 0, 5) == 'HTTP_') {
                    $key = strToLower(subStr($key, 5));
                    $key = str_replace(' ', '-', ucWords(str_replace('_', ' ', $key)));
                    $headers[$key] = $value;
                }
            }
        }
    }

    // alle oder nur die gewuenschten Header zurueckgeben
    if (!$names)
        return $headers;

    return array_intersect_ukey($headers, array_flip($names), 'strCaseCmp');
}


/**
 * Ob der uebergebene String eine syntaktisch gueltige IP-Adresse ist.
 *
 * @param  string $string                 - der zu ueberpruefende String
 * @param  bool   $returnBytes [optional] - Typ des Rueckgabewertes
 *                                          FALSE: Boolean (default)
 *                                          TRUE:  Array mit den Adressbytes oder FALSE, wenn der String keine gueltige IP-Adresse darstellt
 * @return bool|array
 */
function isIPAddress($string, $returnBytes = false) {
    static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';

    $result = is_string($string) && strLen($string) && preg_match($pattern, $string, $bytes);

    if ($result) {
        array_shift($bytes);

        foreach ($bytes as $i => $byte) {
            $b = (int) $byte;
            if (!is_string($byte) || $b > 255)
                return false;
            $bytes[$i] = $b;
        }

        if ($bytes[0] == 0)
            return false;

        return $returnBytes ? $bytes : true;
    }
    return false;
}

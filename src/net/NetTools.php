<?php
namespace rosasurfer\net;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidValueException;

use function rosasurfer\strEndsWithI;
use function rosasurfer\strStartsWith;


/**
 * NetTools - Internet related utilities
 */
final class NetTools extends StaticClass {


    /**
     * Return the host name of the internet host specified by a given IP address&#46;  Additionally checks the result
     * returned by the built-in PHP function for plausibility.
     *
     * @param  string $ipAddress - the host IP address
     *
     * @return string|bool - the host name on success, the unmodified IP address on failure, or FALSE on malformed input
     */
    public static function getHostByAddress($ipAddress) {
        Assert::string($ipAddress);
        if ($ipAddress == '') throw new InvalidValueException('Invalid parameter $ipAddress: "'.$ipAddress.'"');

        $result = gethostbyaddr($ipAddress);

        if ($result==='localhost' && !strStartsWith($ipAddress, '127.'))
            $result = $ipAddress;

        return $result;
    }


    /**
     * Gibt die IP-Adresse eines Hostnamens zurueck.
     *
     * @param  string $name - Hostname
     *
     * @return string - IP-Adresse oder der originale Hostname, wenn dieser nicht aufgeloest werden kann
     */
    public static function getHostByName($name) {
        Assert::string($name);
        if ($name == '') throw new InvalidValueException('Invalid parameter $name: "'.$name.'"');

        return \gethostbyname($name);
    }
}

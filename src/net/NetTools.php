<?php
namespace rosasurfer\net;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\exception\InvalidArgumentException;

use function rosasurfer\strEndsWithI;
use function rosasurfer\strStartsWith;


/**
 * NetTools - Internet related utilities
 */
final class NetTools extends StaticClass {


    /**
     * Return the host name of the Internet host specified by a given IP address. Additionally checks the result returned
     * by the built-in PHP function for plausibility.
     *
     * @param  string $ipAddress - the host IP address
     *
     * @return string|bool - the host name on success, the unmodified IP address on failure, or FALSE on malformed input
     */
    public static function getHostByAddress($ipAddress) {
        Assert::string($ipAddress);
        if ($ipAddress == '') throw new InvalidArgumentException('Invalid argument $ipAddress: "'.$ipAddress.'"');

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
        if ($name == '') throw new InvalidArgumentException('Invalid argument $name: "'.$name.'"');

        return \gethostbyname($name);
    }


    /**
     * Ob die IP-Adresse auf einen bekannten Proxy-Server weist.
     *
     * @param  string $address                   - IP-Adresse
     * @param  bool   $reverseResolve [optional] - ob die IP-Adresse rueck-aufgeloest und ueberprueft werden soll (default: false)
     *
     * @return bool
     */
    public static function isProxyAddress($address, $reverseResolve = false) {
        Assert::string($address,      'Illegal type of parameter $address: %s');
        if (!strlen($address))         throw new InvalidArgumentException('Invalid argument $address: '.$address);
        Assert::bool($reverseResolve, 'Illegal type of parameter $reverseResolve: %s');

        static $proxys = null;

        if ($proxys === null) {
            $proxys = array();

            /** @var ConfigInterface $config */
            $config = self::di('config');

            // Config einlesen
            $value = $config->get('proxys', null);
            foreach (explode(',', $value) as $value) {
                $value = trim($value);
                if (strlen($value)) {
                    if ($value != '194.8.74.0/23') {
                        $proxys[$value] = $value;
                    }
                    else {
                        // TODO: Unterstuetzung fuer CIDR-Notation integrieren
                        $proxys['194.8.74.0'  ] = '194.8.74.0';
                        $proxys['194.8.74.1'  ] = '194.8.74.1';
                        $proxys['194.8.74.2'  ] = '194.8.74.2';
                        $proxys['194.8.74.3'  ] = '194.8.74.3';
                        $proxys['194.8.74.4'  ] = '194.8.74.4';
                        $proxys['194.8.74.5'  ] = '194.8.74.5';
                        $proxys['194.8.74.6'  ] = '194.8.74.6';
                        $proxys['194.8.74.7'  ] = '194.8.74.7';
                        $proxys['194.8.74.8'  ] = '194.8.74.8';
                        $proxys['194.8.74.9'  ] = '194.8.74.9';

                        $proxys['194.8.74.10' ] = '194.8.74.10';
                        $proxys['194.8.74.11' ] = '194.8.74.11';
                        $proxys['194.8.74.12' ] = '194.8.74.12';
                        $proxys['194.8.74.13' ] = '194.8.74.13';
                        $proxys['194.8.74.14' ] = '194.8.74.14';
                        $proxys['194.8.74.15' ] = '194.8.74.15';
                        $proxys['194.8.74.16' ] = '194.8.74.16';
                        $proxys['194.8.74.17' ] = '194.8.74.17';
                        $proxys['194.8.74.18' ] = '194.8.74.18';
                        $proxys['194.8.74.19' ] = '194.8.74.19';

                        $proxys['194.8.74.20' ] = '194.8.74.20';
                        $proxys['194.8.74.21' ] = '194.8.74.21';
                        $proxys['194.8.74.22' ] = '194.8.74.22';
                        $proxys['194.8.74.23' ] = '194.8.74.23';
                        $proxys['194.8.74.24' ] = '194.8.74.24';
                        $proxys['194.8.74.25' ] = '194.8.74.25';
                        $proxys['194.8.74.26' ] = '194.8.74.26';
                        $proxys['194.8.74.27' ] = '194.8.74.27';
                        $proxys['194.8.74.28' ] = '194.8.74.28';
                        $proxys['194.8.74.29' ] = '194.8.74.29';

                        $proxys['194.8.74.30' ] = '194.8.74.30';
                        $proxys['194.8.74.31' ] = '194.8.74.31';
                        $proxys['194.8.74.32' ] = '194.8.74.32';
                        $proxys['194.8.74.33' ] = '194.8.74.33';
                        $proxys['194.8.74.34' ] = '194.8.74.34';
                        $proxys['194.8.74.35' ] = '194.8.74.35';
                        $proxys['194.8.74.36' ] = '194.8.74.36';
                        $proxys['194.8.74.37' ] = '194.8.74.37';
                        $proxys['194.8.74.38' ] = '194.8.74.38';
                        $proxys['194.8.74.39' ] = '194.8.74.39';

                        $proxys['194.8.74.40' ] = '194.8.74.40';
                        $proxys['194.8.74.41' ] = '194.8.74.41';
                        $proxys['194.8.74.42' ] = '194.8.74.42';
                        $proxys['194.8.74.43' ] = '194.8.74.43';
                        $proxys['194.8.74.44' ] = '194.8.74.44';
                        $proxys['194.8.74.45' ] = '194.8.74.45';
                        $proxys['194.8.74.46' ] = '194.8.74.46';
                        $proxys['194.8.74.47' ] = '194.8.74.47';
                        $proxys['194.8.74.48' ] = '194.8.74.48';
                        $proxys['194.8.74.49' ] = '194.8.74.49';

                        $proxys['194.8.74.50' ] = '194.8.74.50';
                        $proxys['194.8.74.51' ] = '194.8.74.51';
                        $proxys['194.8.74.52' ] = '194.8.74.52';
                        $proxys['194.8.74.53' ] = '194.8.74.53';
                        $proxys['194.8.74.54' ] = '194.8.74.54';
                        $proxys['194.8.74.55' ] = '194.8.74.55';
                        $proxys['194.8.74.56' ] = '194.8.74.56';
                        $proxys['194.8.74.57' ] = '194.8.74.57';
                        $proxys['194.8.74.58' ] = '194.8.74.58';
                        $proxys['194.8.74.59' ] = '194.8.74.59';

                        $proxys['194.8.74.60' ] = '194.8.74.60';
                        $proxys['194.8.74.61' ] = '194.8.74.61';
                        $proxys['194.8.74.62' ] = '194.8.74.62';
                        $proxys['194.8.74.63' ] = '194.8.74.63';
                        $proxys['194.8.74.64' ] = '194.8.74.64';
                        $proxys['194.8.74.65' ] = '194.8.74.65';
                        $proxys['194.8.74.66' ] = '194.8.74.66';
                        $proxys['194.8.74.67' ] = '194.8.74.67';
                        $proxys['194.8.74.68' ] = '194.8.74.68';
                        $proxys['194.8.74.69' ] = '194.8.74.69';

                        $proxys['194.8.74.70' ] = '194.8.74.70';
                        $proxys['194.8.74.71' ] = '194.8.74.71';
                        $proxys['194.8.74.72' ] = '194.8.74.72';
                        $proxys['194.8.74.73' ] = '194.8.74.73';
                        $proxys['194.8.74.74' ] = '194.8.74.74';
                        $proxys['194.8.74.75' ] = '194.8.74.75';
                        $proxys['194.8.74.76' ] = '194.8.74.76';
                        $proxys['194.8.74.77' ] = '194.8.74.77';
                        $proxys['194.8.74.78' ] = '194.8.74.78';
                        $proxys['194.8.74.79' ] = '194.8.74.79';

                        $proxys['194.8.74.80' ] = '194.8.74.80';
                        $proxys['194.8.74.81' ] = '194.8.74.81';
                        $proxys['194.8.74.82' ] = '194.8.74.82';
                        $proxys['194.8.74.83' ] = '194.8.74.83';
                        $proxys['194.8.74.84' ] = '194.8.74.84';
                        $proxys['194.8.74.85' ] = '194.8.74.85';
                        $proxys['194.8.74.86' ] = '194.8.74.86';
                        $proxys['194.8.74.87' ] = '194.8.74.87';
                        $proxys['194.8.74.88' ] = '194.8.74.88';
                        $proxys['194.8.74.89' ] = '194.8.74.89';

                        $proxys['194.8.74.90' ] = '194.8.74.90';
                        $proxys['194.8.74.91' ] = '194.8.74.91';
                        $proxys['194.8.74.92' ] = '194.8.74.92';
                        $proxys['194.8.74.93' ] = '194.8.74.93';
                        $proxys['194.8.74.94' ] = '194.8.74.94';
                        $proxys['194.8.74.95' ] = '194.8.74.95';
                        $proxys['194.8.74.96' ] = '194.8.74.96';
                        $proxys['194.8.74.97' ] = '194.8.74.97';
                        $proxys['194.8.74.98' ] = '194.8.74.98';
                        $proxys['194.8.74.99' ] = '194.8.74.99';

                        $proxys['194.8.74.100'] = '194.8.74.100';
                        $proxys['194.8.74.101'] = '194.8.74.101';
                        $proxys['194.8.74.102'] = '194.8.74.102';
                        $proxys['194.8.74.103'] = '194.8.74.103';
                        $proxys['194.8.74.104'] = '194.8.74.104';
                        $proxys['194.8.74.105'] = '194.8.74.105';
                        $proxys['194.8.74.106'] = '194.8.74.106';
                        $proxys['194.8.74.107'] = '194.8.74.107';
                        $proxys['194.8.74.108'] = '194.8.74.108';
                        $proxys['194.8.74.109'] = '194.8.74.109';

                        $proxys['194.8.74.110'] = '194.8.74.110';
                        $proxys['194.8.74.111'] = '194.8.74.111';
                        $proxys['194.8.74.112'] = '194.8.74.112';
                        $proxys['194.8.74.113'] = '194.8.74.113';
                        $proxys['194.8.74.114'] = '194.8.74.114';
                        $proxys['194.8.74.115'] = '194.8.74.115';
                        $proxys['194.8.74.116'] = '194.8.74.116';
                        $proxys['194.8.74.117'] = '194.8.74.117';
                        $proxys['194.8.74.118'] = '194.8.74.118';
                        $proxys['194.8.74.119'] = '194.8.74.119';

                        $proxys['194.8.74.120'] = '194.8.74.120';
                        $proxys['194.8.74.121'] = '194.8.74.121';
                        $proxys['194.8.74.122'] = '194.8.74.122';
                        $proxys['194.8.74.123'] = '194.8.74.123';
                        $proxys['194.8.74.124'] = '194.8.74.124';
                        $proxys['194.8.74.125'] = '194.8.74.125';
                        $proxys['194.8.74.126'] = '194.8.74.126';
                        $proxys['194.8.74.127'] = '194.8.74.127';
                        $proxys['194.8.74.128'] = '194.8.74.128';
                        $proxys['194.8.74.129'] = '194.8.74.129';

                        $proxys['194.8.74.130'] = '194.8.74.130';
                        $proxys['194.8.74.131'] = '194.8.74.131';
                        $proxys['194.8.74.132'] = '194.8.74.132';
                        $proxys['194.8.74.133'] = '194.8.74.133';
                        $proxys['194.8.74.134'] = '194.8.74.134';
                        $proxys['194.8.74.135'] = '194.8.74.135';
                        $proxys['194.8.74.136'] = '194.8.74.136';
                        $proxys['194.8.74.137'] = '194.8.74.137';
                        $proxys['194.8.74.138'] = '194.8.74.138';
                        $proxys['194.8.74.139'] = '194.8.74.139';

                        $proxys['194.8.74.140'] = '194.8.74.140';
                        $proxys['194.8.74.141'] = '194.8.74.141';
                        $proxys['194.8.74.142'] = '194.8.74.142';
                        $proxys['194.8.74.143'] = '194.8.74.143';
                        $proxys['194.8.74.144'] = '194.8.74.144';
                        $proxys['194.8.74.145'] = '194.8.74.145';
                        $proxys['194.8.74.146'] = '194.8.74.146';
                        $proxys['194.8.74.147'] = '194.8.74.147';
                        $proxys['194.8.74.148'] = '194.8.74.148';
                        $proxys['194.8.74.149'] = '194.8.74.149';

                        $proxys['194.8.74.150'] = '194.8.74.150';
                        $proxys['194.8.74.151'] = '194.8.74.151';
                        $proxys['194.8.74.152'] = '194.8.74.152';
                        $proxys['194.8.74.153'] = '194.8.74.153';
                        $proxys['194.8.74.154'] = '194.8.74.154';
                        $proxys['194.8.74.155'] = '194.8.74.155';
                        $proxys['194.8.74.156'] = '194.8.74.156';
                        $proxys['194.8.74.157'] = '194.8.74.157';
                        $proxys['194.8.74.158'] = '194.8.74.158';
                        $proxys['194.8.74.159'] = '194.8.74.159';

                        $proxys['194.8.74.160'] = '194.8.74.160';
                        $proxys['194.8.74.161'] = '194.8.74.161';
                        $proxys['194.8.74.162'] = '194.8.74.162';
                        $proxys['194.8.74.163'] = '194.8.74.163';
                        $proxys['194.8.74.164'] = '194.8.74.164';
                        $proxys['194.8.74.165'] = '194.8.74.165';
                        $proxys['194.8.74.166'] = '194.8.74.166';
                        $proxys['194.8.74.167'] = '194.8.74.167';
                        $proxys['194.8.74.168'] = '194.8.74.168';
                        $proxys['194.8.74.169'] = '194.8.74.169';

                        $proxys['194.8.74.170'] = '194.8.74.170';
                        $proxys['194.8.74.171'] = '194.8.74.171';
                        $proxys['194.8.74.172'] = '194.8.74.172';
                        $proxys['194.8.74.173'] = '194.8.74.173';
                        $proxys['194.8.74.174'] = '194.8.74.174';
                        $proxys['194.8.74.175'] = '194.8.74.175';
                        $proxys['194.8.74.176'] = '194.8.74.176';
                        $proxys['194.8.74.177'] = '194.8.74.177';
                        $proxys['194.8.74.178'] = '194.8.74.178';
                        $proxys['194.8.74.179'] = '194.8.74.179';

                        $proxys['194.8.74.180'] = '194.8.74.180';
                        $proxys['194.8.74.181'] = '194.8.74.181';
                        $proxys['194.8.74.182'] = '194.8.74.182';
                        $proxys['194.8.74.183'] = '194.8.74.183';
                        $proxys['194.8.74.184'] = '194.8.74.184';
                        $proxys['194.8.74.185'] = '194.8.74.185';
                        $proxys['194.8.74.186'] = '194.8.74.186';
                        $proxys['194.8.74.187'] = '194.8.74.187';
                        $proxys['194.8.74.188'] = '194.8.74.188';
                        $proxys['194.8.74.189'] = '194.8.74.189';

                        $proxys['194.8.74.190'] = '194.8.74.190';
                        $proxys['194.8.74.191'] = '194.8.74.191';
                        $proxys['194.8.74.192'] = '194.8.74.192';
                        $proxys['194.8.74.193'] = '194.8.74.193';
                        $proxys['194.8.74.194'] = '194.8.74.194';
                        $proxys['194.8.74.195'] = '194.8.74.195';
                        $proxys['194.8.74.196'] = '194.8.74.196';
                        $proxys['194.8.74.197'] = '194.8.74.197';
                        $proxys['194.8.74.198'] = '194.8.74.198';
                        $proxys['194.8.74.199'] = '194.8.74.199';

                        $proxys['194.8.74.200'] = '194.8.74.200';
                        $proxys['194.8.74.201'] = '194.8.74.201';
                        $proxys['194.8.74.202'] = '194.8.74.202';
                        $proxys['194.8.74.203'] = '194.8.74.203';
                        $proxys['194.8.74.204'] = '194.8.74.204';
                        $proxys['194.8.74.205'] = '194.8.74.205';
                        $proxys['194.8.74.206'] = '194.8.74.206';
                        $proxys['194.8.74.207'] = '194.8.74.207';
                        $proxys['194.8.74.208'] = '194.8.74.208';
                        $proxys['194.8.74.209'] = '194.8.74.209';

                        $proxys['194.8.74.210'] = '194.8.74.210';
                        $proxys['194.8.74.211'] = '194.8.74.211';
                        $proxys['194.8.74.212'] = '194.8.74.212';
                        $proxys['194.8.74.213'] = '194.8.74.213';
                        $proxys['194.8.74.214'] = '194.8.74.214';
                        $proxys['194.8.74.215'] = '194.8.74.215';
                        $proxys['194.8.74.216'] = '194.8.74.216';
                        $proxys['194.8.74.217'] = '194.8.74.217';
                        $proxys['194.8.74.218'] = '194.8.74.218';
                        $proxys['194.8.74.219'] = '194.8.74.219';

                        $proxys['194.8.74.220'] = '194.8.74.220';
                        $proxys['194.8.74.221'] = '194.8.74.221';
                        $proxys['194.8.74.222'] = '194.8.74.222';
                        $proxys['194.8.74.223'] = '194.8.74.223';
                        $proxys['194.8.74.224'] = '194.8.74.224';
                        $proxys['194.8.74.225'] = '194.8.74.225';
                        $proxys['194.8.74.226'] = '194.8.74.226';
                        $proxys['194.8.74.227'] = '194.8.74.227';
                        $proxys['194.8.74.228'] = '194.8.74.228';
                        $proxys['194.8.74.229'] = '194.8.74.229';

                        $proxys['194.8.74.230'] = '194.8.74.230';
                        $proxys['194.8.74.231'] = '194.8.74.231';
                        $proxys['194.8.74.232'] = '194.8.74.232';
                        $proxys['194.8.74.233'] = '194.8.74.233';
                        $proxys['194.8.74.234'] = '194.8.74.234';
                        $proxys['194.8.74.235'] = '194.8.74.235';
                        $proxys['194.8.74.236'] = '194.8.74.236';
                        $proxys['194.8.74.237'] = '194.8.74.237';
                        $proxys['194.8.74.238'] = '194.8.74.238';
                        $proxys['194.8.74.239'] = '194.8.74.239';

                        $proxys['194.8.74.240'] = '194.8.74.240';
                        $proxys['194.8.74.241'] = '194.8.74.241';
                        $proxys['194.8.74.242'] = '194.8.74.242';
                        $proxys['194.8.74.243'] = '194.8.74.243';
                        $proxys['194.8.74.244'] = '194.8.74.244';
                        $proxys['194.8.74.245'] = '194.8.74.245';
                        $proxys['194.8.74.246'] = '194.8.74.246';
                        $proxys['194.8.74.247'] = '194.8.74.247';
                        $proxys['194.8.74.248'] = '194.8.74.248';
                        $proxys['194.8.74.249'] = '194.8.74.249';

                        $proxys['194.8.74.250'] = '194.8.74.250';
                        $proxys['194.8.74.251'] = '194.8.74.251';
                        $proxys['194.8.74.252'] = '194.8.74.252';
                        $proxys['194.8.74.253'] = '194.8.74.253';
                        $proxys['194.8.74.254'] = '194.8.74.254';
                        $proxys['194.8.74.255'] = '194.8.74.255';

                        $proxys['194.8.75.0'  ] = '194.8.75.0';
                        $proxys['194.8.75.1'  ] = '194.8.75.1';
                        $proxys['194.8.75.2'  ] = '194.8.75.2';
                        $proxys['194.8.75.3'  ] = '194.8.75.3';
                        $proxys['194.8.75.4'  ] = '194.8.75.4';
                        $proxys['194.8.75.5'  ] = '194.8.75.5';
                        $proxys['194.8.75.6'  ] = '194.8.75.6';
                        $proxys['194.8.75.7'  ] = '194.8.75.7';
                        $proxys['194.8.75.8'  ] = '194.8.75.8';
                        $proxys['194.8.75.9'  ] = '194.8.75.9';

                        $proxys['194.8.75.10' ] = '194.8.75.10';
                        $proxys['194.8.75.11' ] = '194.8.75.11';
                        $proxys['194.8.75.12' ] = '194.8.75.12';
                        $proxys['194.8.75.13' ] = '194.8.75.13';
                        $proxys['194.8.75.14' ] = '194.8.75.14';
                        $proxys['194.8.75.15' ] = '194.8.75.15';
                        $proxys['194.8.75.16' ] = '194.8.75.16';
                        $proxys['194.8.75.17' ] = '194.8.75.17';
                        $proxys['194.8.75.18' ] = '194.8.75.18';
                        $proxys['194.8.75.19' ] = '194.8.75.19';

                        $proxys['194.8.75.20' ] = '194.8.75.20';
                        $proxys['194.8.75.21' ] = '194.8.75.21';
                        $proxys['194.8.75.22' ] = '194.8.75.22';
                        $proxys['194.8.75.23' ] = '194.8.75.23';
                        $proxys['194.8.75.24' ] = '194.8.75.24';
                        $proxys['194.8.75.25' ] = '194.8.75.25';
                        $proxys['194.8.75.26' ] = '194.8.75.26';
                        $proxys['194.8.75.27' ] = '194.8.75.27';
                        $proxys['194.8.75.28' ] = '194.8.75.28';
                        $proxys['194.8.75.29' ] = '194.8.75.29';

                        $proxys['194.8.75.30' ] = '194.8.75.30';
                        $proxys['194.8.75.31' ] = '194.8.75.31';
                        $proxys['194.8.75.32' ] = '194.8.75.32';
                        $proxys['194.8.75.33' ] = '194.8.75.33';
                        $proxys['194.8.75.34' ] = '194.8.75.34';
                        $proxys['194.8.75.35' ] = '194.8.75.35';
                        $proxys['194.8.75.36' ] = '194.8.75.36';
                        $proxys['194.8.75.37' ] = '194.8.75.37';
                        $proxys['194.8.75.38' ] = '194.8.75.38';
                        $proxys['194.8.75.39' ] = '194.8.75.39';

                        $proxys['194.8.75.40' ] = '194.8.75.40';
                        $proxys['194.8.75.41' ] = '194.8.75.41';
                        $proxys['194.8.75.42' ] = '194.8.75.42';
                        $proxys['194.8.75.43' ] = '194.8.75.43';
                        $proxys['194.8.75.44' ] = '194.8.75.44';
                        $proxys['194.8.75.45' ] = '194.8.75.45';
                        $proxys['194.8.75.46' ] = '194.8.75.46';
                        $proxys['194.8.75.47' ] = '194.8.75.47';
                        $proxys['194.8.75.48' ] = '194.8.75.48';
                        $proxys['194.8.75.49' ] = '194.8.75.49';

                        $proxys['194.8.75.50' ] = '194.8.75.50';
                        $proxys['194.8.75.51' ] = '194.8.75.51';
                        $proxys['194.8.75.52' ] = '194.8.75.52';
                        $proxys['194.8.75.53' ] = '194.8.75.53';
                        $proxys['194.8.75.54' ] = '194.8.75.54';
                        $proxys['194.8.75.55' ] = '194.8.75.55';
                        $proxys['194.8.75.56' ] = '194.8.75.56';
                        $proxys['194.8.75.57' ] = '194.8.75.57';
                        $proxys['194.8.75.58' ] = '194.8.75.58';
                        $proxys['194.8.75.59' ] = '194.8.75.59';

                        $proxys['194.8.75.60' ] = '194.8.75.60';
                        $proxys['194.8.75.61' ] = '194.8.75.61';
                        $proxys['194.8.75.62' ] = '194.8.75.62';
                        $proxys['194.8.75.63' ] = '194.8.75.63';
                        $proxys['194.8.75.64' ] = '194.8.75.64';
                        $proxys['194.8.75.65' ] = '194.8.75.65';
                        $proxys['194.8.75.66' ] = '194.8.75.66';
                        $proxys['194.8.75.67' ] = '194.8.75.67';
                        $proxys['194.8.75.68' ] = '194.8.75.68';
                        $proxys['194.8.75.69' ] = '194.8.75.69';

                        $proxys['194.8.75.70' ] = '194.8.75.70';
                        $proxys['194.8.75.71' ] = '194.8.75.71';
                        $proxys['194.8.75.72' ] = '194.8.75.72';
                        $proxys['194.8.75.73' ] = '194.8.75.73';
                        $proxys['194.8.75.74' ] = '194.8.75.74';
                        $proxys['194.8.75.75' ] = '194.8.75.75';
                        $proxys['194.8.75.76' ] = '194.8.75.76';
                        $proxys['194.8.75.77' ] = '194.8.75.77';
                        $proxys['194.8.75.78' ] = '194.8.75.78';
                        $proxys['194.8.75.79' ] = '194.8.75.79';

                        $proxys['194.8.75.80' ] = '194.8.75.80';
                        $proxys['194.8.75.81' ] = '194.8.75.81';
                        $proxys['194.8.75.82' ] = '194.8.75.82';
                        $proxys['194.8.75.83' ] = '194.8.75.83';
                        $proxys['194.8.75.84' ] = '194.8.75.84';
                        $proxys['194.8.75.85' ] = '194.8.75.85';
                        $proxys['194.8.75.86' ] = '194.8.75.86';
                        $proxys['194.8.75.87' ] = '194.8.75.87';
                        $proxys['194.8.75.88' ] = '194.8.75.88';
                        $proxys['194.8.75.89' ] = '194.8.75.89';

                        $proxys['194.8.75.90' ] = '194.8.75.90';
                        $proxys['194.8.75.91' ] = '194.8.75.91';
                        $proxys['194.8.75.92' ] = '194.8.75.92';
                        $proxys['194.8.75.93' ] = '194.8.75.93';
                        $proxys['194.8.75.94' ] = '194.8.75.94';
                        $proxys['194.8.75.95' ] = '194.8.75.95';
                        $proxys['194.8.75.96' ] = '194.8.75.96';
                        $proxys['194.8.75.97' ] = '194.8.75.97';
                        $proxys['194.8.75.98' ] = '194.8.75.98';
                        $proxys['194.8.75.99' ] = '194.8.75.99';

                        $proxys['194.8.75.100'] = '194.8.75.100';
                        $proxys['194.8.75.101'] = '194.8.75.101';
                        $proxys['194.8.75.102'] = '194.8.75.102';
                        $proxys['194.8.75.103'] = '194.8.75.103';
                        $proxys['194.8.75.104'] = '194.8.75.104';
                        $proxys['194.8.75.105'] = '194.8.75.105';
                        $proxys['194.8.75.106'] = '194.8.75.106';
                        $proxys['194.8.75.107'] = '194.8.75.107';
                        $proxys['194.8.75.108'] = '194.8.75.108';
                        $proxys['194.8.75.109'] = '194.8.75.109';

                        $proxys['194.8.75.110'] = '194.8.75.110';
                        $proxys['194.8.75.111'] = '194.8.75.111';
                        $proxys['194.8.75.112'] = '194.8.75.112';
                        $proxys['194.8.75.113'] = '194.8.75.113';
                        $proxys['194.8.75.114'] = '194.8.75.114';
                        $proxys['194.8.75.115'] = '194.8.75.115';
                        $proxys['194.8.75.116'] = '194.8.75.116';
                        $proxys['194.8.75.117'] = '194.8.75.117';
                        $proxys['194.8.75.118'] = '194.8.75.118';
                        $proxys['194.8.75.119'] = '194.8.75.119';

                        $proxys['194.8.75.120'] = '194.8.75.120';
                        $proxys['194.8.75.121'] = '194.8.75.121';
                        $proxys['194.8.75.122'] = '194.8.75.122';
                        $proxys['194.8.75.123'] = '194.8.75.123';
                        $proxys['194.8.75.124'] = '194.8.75.124';
                        $proxys['194.8.75.125'] = '194.8.75.125';
                        $proxys['194.8.75.126'] = '194.8.75.126';
                        $proxys['194.8.75.127'] = '194.8.75.127';
                        $proxys['194.8.75.128'] = '194.8.75.128';
                        $proxys['194.8.75.129'] = '194.8.75.129';

                        $proxys['194.8.75.130'] = '194.8.75.130';
                        $proxys['194.8.75.131'] = '194.8.75.131';
                        $proxys['194.8.75.132'] = '194.8.75.132';
                        $proxys['194.8.75.133'] = '194.8.75.133';
                        $proxys['194.8.75.134'] = '194.8.75.134';
                        $proxys['194.8.75.135'] = '194.8.75.135';
                        $proxys['194.8.75.136'] = '194.8.75.136';
                        $proxys['194.8.75.137'] = '194.8.75.137';
                        $proxys['194.8.75.138'] = '194.8.75.138';
                        $proxys['194.8.75.139'] = '194.8.75.139';

                        $proxys['194.8.75.140'] = '194.8.75.140';
                        $proxys['194.8.75.141'] = '194.8.75.141';
                        $proxys['194.8.75.142'] = '194.8.75.142';
                        $proxys['194.8.75.143'] = '194.8.75.143';
                        $proxys['194.8.75.144'] = '194.8.75.144';
                        $proxys['194.8.75.145'] = '194.8.75.145';
                        $proxys['194.8.75.146'] = '194.8.75.146';
                        $proxys['194.8.75.147'] = '194.8.75.147';
                        $proxys['194.8.75.148'] = '194.8.75.148';
                        $proxys['194.8.75.149'] = '194.8.75.149';

                        $proxys['194.8.75.150'] = '194.8.75.150';
                        $proxys['194.8.75.151'] = '194.8.75.151';
                        $proxys['194.8.75.152'] = '194.8.75.152';
                        $proxys['194.8.75.153'] = '194.8.75.153';
                        $proxys['194.8.75.154'] = '194.8.75.154';
                        $proxys['194.8.75.155'] = '194.8.75.155';
                        $proxys['194.8.75.156'] = '194.8.75.156';
                        $proxys['194.8.75.157'] = '194.8.75.157';
                        $proxys['194.8.75.158'] = '194.8.75.158';
                        $proxys['194.8.75.159'] = '194.8.75.159';

                        $proxys['194.8.75.160'] = '194.8.75.160';
                        $proxys['194.8.75.161'] = '194.8.75.161';
                        $proxys['194.8.75.162'] = '194.8.75.162';
                        $proxys['194.8.75.163'] = '194.8.75.163';
                        $proxys['194.8.75.164'] = '194.8.75.164';
                        $proxys['194.8.75.165'] = '194.8.75.165';
                        $proxys['194.8.75.166'] = '194.8.75.166';
                        $proxys['194.8.75.167'] = '194.8.75.167';
                        $proxys['194.8.75.168'] = '194.8.75.168';
                        $proxys['194.8.75.169'] = '194.8.75.169';

                        $proxys['194.8.75.170'] = '194.8.75.170';
                        $proxys['194.8.75.171'] = '194.8.75.171';
                        $proxys['194.8.75.172'] = '194.8.75.172';
                        $proxys['194.8.75.173'] = '194.8.75.173';
                        $proxys['194.8.75.174'] = '194.8.75.174';
                        $proxys['194.8.75.175'] = '194.8.75.175';
                        $proxys['194.8.75.176'] = '194.8.75.176';
                        $proxys['194.8.75.177'] = '194.8.75.177';
                        $proxys['194.8.75.178'] = '194.8.75.178';
                        $proxys['194.8.75.179'] = '194.8.75.179';

                        $proxys['194.8.75.180'] = '194.8.75.180';
                        $proxys['194.8.75.181'] = '194.8.75.181';
                        $proxys['194.8.75.182'] = '194.8.75.182';
                        $proxys['194.8.75.183'] = '194.8.75.183';
                        $proxys['194.8.75.184'] = '194.8.75.184';
                        $proxys['194.8.75.185'] = '194.8.75.185';
                        $proxys['194.8.75.186'] = '194.8.75.186';
                        $proxys['194.8.75.187'] = '194.8.75.187';
                        $proxys['194.8.75.188'] = '194.8.75.188';
                        $proxys['194.8.75.189'] = '194.8.75.189';

                        $proxys['194.8.75.190'] = '194.8.75.190';
                        $proxys['194.8.75.191'] = '194.8.75.191';
                        $proxys['194.8.75.192'] = '194.8.75.192';
                        $proxys['194.8.75.193'] = '194.8.75.193';
                        $proxys['194.8.75.194'] = '194.8.75.194';
                        $proxys['194.8.75.195'] = '194.8.75.195';
                        $proxys['194.8.75.196'] = '194.8.75.196';
                        $proxys['194.8.75.197'] = '194.8.75.197';
                        $proxys['194.8.75.198'] = '194.8.75.198';
                        $proxys['194.8.75.199'] = '194.8.75.199';

                        $proxys['194.8.75.200'] = '194.8.75.200';
                        $proxys['194.8.75.201'] = '194.8.75.201';
                        $proxys['194.8.75.202'] = '194.8.75.202';
                        $proxys['194.8.75.203'] = '194.8.75.203';
                        $proxys['194.8.75.204'] = '194.8.75.204';
                        $proxys['194.8.75.205'] = '194.8.75.205';
                        $proxys['194.8.75.206'] = '194.8.75.206';
                        $proxys['194.8.75.207'] = '194.8.75.207';
                        $proxys['194.8.75.208'] = '194.8.75.208';
                        $proxys['194.8.75.209'] = '194.8.75.209';

                        $proxys['194.8.75.210'] = '194.8.75.210';
                        $proxys['194.8.75.211'] = '194.8.75.211';
                        $proxys['194.8.75.212'] = '194.8.75.212';
                        $proxys['194.8.75.213'] = '194.8.75.213';
                        $proxys['194.8.75.214'] = '194.8.75.214';
                        $proxys['194.8.75.215'] = '194.8.75.215';
                        $proxys['194.8.75.216'] = '194.8.75.216';
                        $proxys['194.8.75.217'] = '194.8.75.217';
                        $proxys['194.8.75.218'] = '194.8.75.218';
                        $proxys['194.8.75.219'] = '194.8.75.219';

                        $proxys['194.8.75.220'] = '194.8.75.220';
                        $proxys['194.8.75.221'] = '194.8.75.221';
                        $proxys['194.8.75.222'] = '194.8.75.222';
                        $proxys['194.8.75.223'] = '194.8.75.223';
                        $proxys['194.8.75.224'] = '194.8.75.224';
                        $proxys['194.8.75.225'] = '194.8.75.225';
                        $proxys['194.8.75.226'] = '194.8.75.226';
                        $proxys['194.8.75.227'] = '194.8.75.227';
                        $proxys['194.8.75.228'] = '194.8.75.228';
                        $proxys['194.8.75.229'] = '194.8.75.229';

                        $proxys['194.8.75.230'] = '194.8.75.230';
                        $proxys['194.8.75.231'] = '194.8.75.231';
                        $proxys['194.8.75.232'] = '194.8.75.232';
                        $proxys['194.8.75.233'] = '194.8.75.233';
                        $proxys['194.8.75.234'] = '194.8.75.234';
                        $proxys['194.8.75.235'] = '194.8.75.235';
                        $proxys['194.8.75.236'] = '194.8.75.236';
                        $proxys['194.8.75.237'] = '194.8.75.237';
                        $proxys['194.8.75.238'] = '194.8.75.238';
                        $proxys['194.8.75.239'] = '194.8.75.239';

                        $proxys['194.8.75.240'] = '194.8.75.240';
                        $proxys['194.8.75.241'] = '194.8.75.241';
                        $proxys['194.8.75.242'] = '194.8.75.242';
                        $proxys['194.8.75.243'] = '194.8.75.243';
                        $proxys['194.8.75.244'] = '194.8.75.244';
                        $proxys['194.8.75.245'] = '194.8.75.245';
                        $proxys['194.8.75.246'] = '194.8.75.246';
                        $proxys['194.8.75.247'] = '194.8.75.247';
                        $proxys['194.8.75.248'] = '194.8.75.248';
                        $proxys['194.8.75.249'] = '194.8.75.249';

                        $proxys['194.8.75.250'] = '194.8.75.250';
                        $proxys['194.8.75.251'] = '194.8.75.251';
                        $proxys['194.8.75.252'] = '194.8.75.252';
                        $proxys['194.8.75.253'] = '194.8.75.253';
                        $proxys['194.8.75.254'] = '194.8.75.254';
                        $proxys['194.8.75.255'] = '194.8.75.255';
                    }
                }
            }
        }

        if (isset($proxys[$address]))
            return true;

        if ($reverseResolve) {
            /** @var string $hostname */
            $hostname = self::getHostByAddress($address);
            return strEndsWithI($hostname, '.proxy.aol.com');
        }

        return false;
    }
}

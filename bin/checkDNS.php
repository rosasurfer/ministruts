#!/usr/bin/php
<?php
use rosasurfer\config\Config;
use rosasurfer\exception\RuntimeException;

use const rosasurfer\WINDOWS;


/**
 * TODO: Jeder Nameserver muss einzeln abgefragt werden, denn bei fehlerhafter NS-Synchronisierung koennen
 *       sich die zurueckgegebenen Werte der einzelnen Server unterscheiden.
 */
set_time_limit(0);

define('APPLICATION_ID',  'DNS-Checker');
define('APPLICATION_ROOT', __DIR__     );


// Library einbinden
require(dirName(__DIR__).'/src/load.php');

// dns_* functions are not implemented on Windows
if (WINDOWS) throw new InfrastructureException('This script cannot run on Windows.');



/**
 * Fuehrt eine DNS-Abfrage durch und gibt den ermittelten Wert zurueck
 *
 * @param  string $domain - Domain-Name, fuer den die Abfrage durchgefuehrt werden soll
 * @param  string $type   - Typ des abzufragenden Wertes (A, MX, NS, TXT, etc.)
 *
 * @return string - Wert
 */
function queryDNS($domain, $type) {
    $result = null;

    switch ($type) {
        case 'A':
            $result = dns_get_record($domain, DNS_A);
            $result = ($result && isSet($result[0]['ip'])) ? $result[0]['ip'] : null;
            break;

        case 'MX':
            $result = dns_get_record($domain, DNS_MX);
            $result = ($result && isSet($result[0]['target'])) ? $result[0]['target'] : null;
            break;

        case 'NS':
            $result = dns_get_record($domain, DNS_NS);
            $result = ($result && isSet($result[0]['target'])) ? $result[0]['target'] : null;
            break;

        case 'TXT':
            $result = dns_get_record($domain, DNS_TXT);
            $result = ($result && isSet($result[0]['txt'])) ? $result[0]['txt'] : null;
            break;

        case 'PTR':
            $result = dns_get_record($domain, DNS_PTR);
            $result = ($result && isSet($result[0]['target'])) ? $result[0]['target'] : null;
            break;

        case 'CNAME':
        case 'HINFO':
        case 'PTR'  :
        case 'SOA'  :
        case 'AAAA' :
        case 'SRV'  :
        case 'NAPTR':
        case 'A6'   :
        case 'ALL'  :
        case 'ANY'  :
            break;

        default:
            throw new InvalidArgumentException('Invalid argument $type: '.$type);
    }
    return $result;
}


if (!$config=Config::getDefault())
    throw new RuntimeException('Service locator returned invalid default config: '.getType($config));


// normale DNS-Eintraege ueberpruefen (A, MX, NS, TXT, etc.)
$domains = $config->get('dns.domain', []);
if (!is_array($domains)) throw new IllegalTypeException('Invalid config value "dns.domain": '.getType($domains).' (not array)');

foreach ($domains as $domain => $domainValues) {
    foreach ($domainValues as $type => $value) {
        if ($type != 'subdomain') {
            $result = queryDNS($domain, $type);
            if ($result != $value) {
                if ($type == 'TXT') {
                    if (strContains($value , ' ')) $value  = '"'.$value.'"';
                    if (strContains($result, ' ')) $result = '"'.$result.'"';
                }
                if ($result == '0.0.0.0')
                    $result = 'SERVFAIL';
                $ns = queryDNS($domain, 'NS');
                echoPre("DNS error for      $domain:   required $type value: $value,   found: $result,   NS: $ns");
                if ($result == 'SERVFAIL')
                    continue 2;
            }
            continue;
        }

        foreach ($value as $subdomain => $subdomainValues) {
            foreach ($subdomainValues as $type => $value) {
                $result = queryDNS("$subdomain.$domain", $type);
                if ($result != $value) {
                    if ($result == '0.0.0.0')
                        $result = 'SERVFAIL';
                    $ns = queryDNS($domain, 'NS');
                    echoPre('DNS error for '.str_pad($subdomain, 4, ' ', STR_PAD_LEFT).".$domain:   required $type value: $value,   found: $result,   NS: $ns");
                    if ($result == 'SERVFAIL')
                        continue 4;
                }
            }
        }
    }
}


// Reverse-DNS der angegebenen IP-Adressen ueberpruefen
$ips = $config->get('dns.ip', array());

foreach ($ips as $ip => $value) {
    $domain = join('.', array_reverse(explode('.', $ip))).'.in-addr.arpa';
    $result = queryDNS($domain, 'PTR');
    if ($result != $value) {
        $ns = queryDNS($domain, 'NS');
        echoPre("RDNS error for $ip:   required PTR value: $value,   found: $result,   NS: $ns");
    }
}

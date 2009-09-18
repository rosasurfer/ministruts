#!/usr/bin/php -Cq
<?
set_time_limit(0);

// Library einbinden
require(dirName(__FILE__).'/../src/phpLib.php');


define('APPLICATION_NAME', 'DNS-Checker');


/**
 * TODO:
 * -----
 * Jeder Nameserver muß einzeln abgefragt werden, denn bei fehlerhafter Synchronisierung können sich die zurückgegebenen Werte unterscheiden.
 */



/**
 * Führt eine DNS-Abfrage durch und gibt den ermitelten Wert zurück
 *
 * @param $string $domain - Domain-Name, für den die Abfrage durchgeführt werden soll
 * @param $string $type   - Typ des abzufragenden Wertes (A, MX, NS, TXT, etc.)
 *
 * @return string - Wert
 */
function queryDNS($domain, $type) {
   $result = null;

   //echoPre('query for: '.$domain.'  '.$type.'  record');

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

      case 'TXT'  :
         $result = dns_get_record($domain, DNS_TXT);
         $result = ($result && isSet($result[0]['txt'])) ? $result[0]['txt'] : null;
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

   //echoPre('result: '.$result);

   return $result;
}


// DNS-Einträge
$domains = Config::get('dns.domain', array());

foreach ($domains as $domain => $domainValues) {
   foreach ($domainValues as $type => $value) {
      if ($type != 'subdomain') {
         $result = queryDNS($domain, $type);
         if ($result != $value) {
            if ($type == 'TXT') {
               if (String ::contains($value , ' ')) $value  = "\"$value\"";
               if (String ::contains($result, ' ')) $result = "\"$result\"";
            }
            $ns = queryDNS($domain, 'NS');
            echoPre("DNS error detected for      $domain:   required $type value: $value,   found: $result,   NS: $ns");
         }
         continue;
      }

      foreach ($value as $subdomain => $subdomainValues) {
         foreach ($subdomainValues as $type => $value) {
            $result = queryDNS("$subdomain.$domain", $type);
            if ($result != $value) {
               $ns = queryDNS($domain, 'NS');
               echoPre('DNS error detected for '.str_pad($subdomain, 4, ' ', STR_PAD_LEFT).".$domain:   required $type value: $value,   found: $result,   NS: $ns");
            }
         }
      }
   }
}


// Reverse-DNS-Einträge
$ips = Config::get('dns.ip', array());

foreach ($ips as $ip => $value) {
   $result = getHostByAddr($ip);
   if ($result != $value)
      echoPre("Reverse DNS error detected for $ip:   required PTR value: $value,   found: $result");
}
?>

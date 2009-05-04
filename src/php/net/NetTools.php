<?
/**
 * NetTools - Internet related utilities
 */
final class NetTools extends StaticClass {


   /**
    * Gibt den Hostnamen einer IP-Adresse zurück.
    *
    * @param string $address - IP-Adresse
    *
    * @return boolean
    */
   public static function getHostByAddr($address) {
      if ($address !== (string)$address) throw new IllegalTypeException('Illegal type of parameter $address: '.getType($address));
      if ($address == '')                throw new InvalidArgumentException('Invalid argument $address: "'.$address.'"');

      // TODO: Format überprüfen

      return getHostByAddr($address);
   }


   /**
    * Ob die IP-Adresse auf einen Proxy-Server weist.
    *
    * @param string $address - IP-Adresse
    *
    * @return boolean
    */
   public static function isProxyAddress($address) {
      if (!is_string($address)) throw new IllegalTypeException('Illegal type of parameter $address: '.getType($address));
      if (!strLen($address))    throw new InvalidArgumentException('Invalid argument $address: '.$address);

      static $proxys = null;

      if ($proxys === null) {
         $proxys = array();

         // Config einlesen
         $value = Config ::get('proxys', null);
         foreach (explode(',', $value) as $value) {
            $value = trim($value);
            if (strLen($value))
               $proxys[$value] = $value;
         }
      }

      if (isSet($proxys[$address]))
         return true;

      if (String ::endsWith(self ::getHostByAddr($address), '.proxy.aol.com', true))
         return true;

      return false;
   }
}

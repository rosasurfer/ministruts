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
    * @return string - Hostname oder die originale IP-Adresse, wenn diese nicht aufgelöst werden kann
    */
   public static function getHostByAddress($address) {
      if ($address !== (string)$address) throw new IllegalTypeException('Illegal type of parameter $address: '.getType($address));
      if ($address == '')                throw new InvalidArgumentException('Invalid argument $address: "'.$address.'"');

      try {
         return getHostByAddr($address);
      }
      catch (Exception $ex) {
         if ($ex->getMessage() == 'gethostbyaddr(): Address is not a valid IPv4 or IPv6 address')
            throw new InvalidArgumentException('Invalid argument $address: "'.$address.'"', $ex);
         throw $ex;
      }
   }


   /**
    * Gibt die IP-Adresse eines Hostnamens zurück.
    *
    * @param string $name - Hostname
    *
    * @return string - IP-Adresse oder der originale Hostname, wenn dieser nicht aufgelöst werden kann
    */
   public static function getHostByName($name) {
      if ($name !== (string)$name) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if ($name == '')             throw new InvalidArgumentException('Invalid argument $name: "'.$name.'"');

      return getHostByName($name);
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

      if (String ::endsWith(self ::getHostByAddress($address), '.proxy.aol.com', true))
         return true;

      return false;
   }
}

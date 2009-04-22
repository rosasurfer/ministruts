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
}

<?
/**
 * DBPool
 *
 * Connector-Pool für Datenbankverbindungen zu mehreren Datenbanken.
 */
final class DBPool extends Singleton {


   /**
    * connector pool
    */
   private /*DB[]*/ $pool = array();


   /**
    * default connector
    */
   private /*DB*/ $default;


   /**
    * verschiedene Schreibweisen
    */
   private static $knownConnectors = array('mysql'          => 'MySQLConnector',
                                           'mysqlconnector' => 'MySQLConnector',
                                           );

   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Singleton
    */
   public static function me() {
      return Singleton ::getInstance(__CLASS__);
   }


   /**
    * Gibt den Connector für den angegebenen Datenbank-Aliasnamen zurück.
    *
    * @param string $alias - Datenbank-Alias
    *
    * @return DB
    */
   public static function getDB($alias = null) {
      $me = self ::me();

      if ($alias === null) {                        // single db project
         if (!$me->default)
            throw new IllegalStateException('Invalid default database configuration: null');
         $connector = $me->default;
      }
      elseif (isSet($me->pool[$alias])) {           // im Pool ?
         $connector = $me->pool[$alias];
      }
      elseif ($config=Config ::get('db.'.$alias)) { // nein, Config holen und Connector laden
         // bekannte Connectoren trotz verschiedener Schreibweisen erkennen
         $connectorName = strToLower($config['connector']);

         if (isSet(self::$knownConnectors[$connectorName])) {
            $class = self::$knownConnectors[$connectorName];
         }
         else {   // unbekannt, Fall-back: name.Connector
            $class = $config['connector'].'Connector';
         }

         $host  = $config['host'     ];
         $user  = $config['username' ];
         $pass  = $config['password' ];
         $db    = $config['database' ];

         $connector = DB ::spawn($class, $host, $user, $pass, $db);
         $me->pool[$alias] = $connector;
      }
      else {
         throw new IllegalStateException('Can not find database configuration for alias "'.$alias.'"');
      }

      return $connector;
   }

}
/*
   // @return DB
   public static function getByDao(GenericDAO $dao) {
      return self::me()->getLink($dao->getLinkName());
   }
}
*/
?>

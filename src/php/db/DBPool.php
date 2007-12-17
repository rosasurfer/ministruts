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
    * Gibt die Klasseninstanz zurück.
    *
    * @return DBPool
    */
   public static function me() {
      return Singleton ::getInstance(__CLASS__);
   }


   /**
    * Gibt den Connector mit dem jeweiligen Namen zurück.
    *
    * @param string $name - Name
    *
    * @return DB
    */
   public static function getDB($name = null) {
      $me = self ::me();

      if ($name === null) {                        // single db project
         if (!$me->default)
            throw new IllegalStateException('Invalid default database connector: null');
         $connector = $me->default;
      }
      elseif (isSet($me->pool[$name])) {           // im Pool ?
         $connector = $me->pool[$name];
      }
      elseif ($config=Config ::get('db.'.$name)) { // nein, Config holen und Connector laden
         $lowerName = strToLower($config['connector']);

         // bekannte Connectoren trotz verschiedener Schreibweisen erkennen
         if (isSet(self::$knownConnectors[$lowerName])) {
            $class = self::$knownConnectors[$lowerName];
         }
         else {   // unbekannt, Fall-back: name.Connector
            $class = $config['connector'].'Connector';
         }

         $host  = $config['host'     ];
         $user  = $config['username' ];
         $pass  = $config['password' ];
         $db    = $config['database' ];

         $connector = DB ::spawn($class, $host, $user, $pass, $db);
         $me->pool[$name] = $connector;
      }
      else {
         throw new IllegalStateException('Can not find database connector named "'.$name.'"');
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

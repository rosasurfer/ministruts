<?php
namespace rosasurfer\db;

use rosasurfer\config\Config;

use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RuntimeException;


/**
 * ConnectorPool
 *
 * Connector-Pool für Datenbankverbindungen zu mehreren Datenbanken.
 */
final class ConnectorPool extends Singleton {


   /**
    * connector pool
    */
   private /*Connector[]*/ $pool = array();


   /**
    * default connector
    */
   private /*Connector*/ $default;


   /**
    * verschiedene Schreibweisen
    */
   private static $connectorAliases = [
      'mysql'                         => MySqlConnector::class,
      __NAMESPACE__.'\mysqlconnector' => MySqlConnector::class,
   ];

   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Singleton
    */
   public static function me() {
      return Singleton::getInstance(static::class);
   }


   /**
    * Gibt den Connector für den angegebenen Datenbank-Alias zurück.
    *
    * @param  string $alias - Datenbank-Alias
    *
    * @return Connector
    */
   public static function getConnector($alias = null) {
      $me = self::me();

      if ($alias === null) {                                            // single db project
         if (!$me->default) throw new IllegalStateException('Invalid default database configuration: null');
         $connector = $me->default;
      }
      elseif (isSet($me->pool[$alias])) {                               // schon im Pool ?
         $connector = $me->pool[$alias];
      }
      else {                                                            // nein, Config holen
         if (!$config=Config::getDefault())
            throw new RuntimeException('Service locator returned invalid default config: '.getType($config));

         $config = $config->get('db.'.$alias, null);
         if (!$config) throw new IllegalStateException('No configuration found for database alias "'.$alias.'"');

         $name = $config['connector'];                                  // Connector laden
         if ($name[0]=='//') $name = substr($name, 1);

         // Aliase durch Klassennamen ersetzen
         $lName = strToLower($name);
         if (isSet(self::$connectorAliases[$lName]))
            $class = self::$connectorAliases[$lName];

         $host    =                             $config['host'    ];
         $user    =                             $config['username'];
         $pass    =                             $config['password'];
         $db      =                             $config['schema'  ];
         $options = isSet($config['options']) ? $config['options' ] : null;

         $connector = Connector::spawn($class, $host, $user, $pass, $db, $options);
         $me->pool[$alias] = $connector;
      }
      return $connector;
   }
}

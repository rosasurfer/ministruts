<?php
namespace rosasurfer\db;

use rosasurfer\config\Config;
use rosasurfer\core\Singleton;

use rosasurfer\db\mysql\MySqlConnector;
use rosasurfer\db\sqlite\SqliteConnector;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RuntimeException;


/**
 * ConnectionPool
 *
 * Connection-Pool für Datenbankverbindungen zu mehreren Datenbanken.
 */
final class ConnectionPool extends Singleton {


   /** @var Connector[] - connector pool */
   private $pool = [];

   /** @var Connector - default connector */
   private $default;

   /** @var string[] - verschiedene Connector-Schreibweisen */
   private static $aliases = [
      'mysql'                                         => MySqlConnector::class,
      __NAMESPACE__.'\\mysql\\mysqlconnector'         => MySqlConnector::class,

      'postgres'                                      => PostgreSqlConnector::class,
      'postgresql'                                    => PostgreSqlConnector::class,
      __NAMESPACE__.'\\postgres\\postgresqlconnector' => PostgreSqlConnector::class,

      'sqlite'                                        => SqliteConnector::class,
      'sqlite3'                                       => SqliteConnector::class,
      __NAMESPACE__.'\\sqlite\\sqliteconnector'       => SqliteConnector::class,
   ];


   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return self
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

         $name = $config['connector'];                                  // Connector ermitteln
         $name = str_replace('/', '\\', $name);
         if ($name[0]=='\\') $name = subStr($name, 1);

         // Aliase durch Klassennamen ersetzen
         $lName = strToLower($name);
         if (isSet(self::$aliases[$lName]))
            $class = self::$aliases[$lName];

         if ($old = 1) {
            // old
            $host    =                             $config['host'    ];
            $user    =                             $config['username'];
            $pass    =                             $config['password'];
            $db      =                             $config['schema'  ];
            $options = isSet($config['options']) ? $config['options' ] : null;
            $connector = Connector::create($class, $host, $user, $pass, $db, $options);
         }
         else {
            // new: clean-up and separate config and options
            $options = isSet($config['options']) ? $config['options'] : [];
            unset($config['connector'], $config['options']);
            $connector = Connector::create($config, $options);
         }

         $me->pool[$alias] = $connector;
      }
      return $connector;
   }
}

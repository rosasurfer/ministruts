<?php
namespace rosasurfer\db;

use rosasurfer\config\Config;
use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RuntimeException;


/**
 * ConnectionPool
 *
 * A pool for multiple database adapter instances. Each instance represents a connection.
 */
final class ConnectionPool extends Singleton {


   /** @var Connector[] - adapter pool */
   private $pool = [];

   /** @var Connector - default adapter */
   private $default;

   /** @var string[] - common adapter spellings */
   private static $aliases = [
      'mysql'                             => MysqlConnector::class,
      __NAMESPACE__.'\\mysqlconnector'    => MysqlConnector::class,

      'pgsql'                             => PostgresConnector::class,
      'postgres'                          => PostgresConnector::class,
      'postgresql'                        => PostgresConnector::class,
      __NAMESPACE__.'\\postgresconnector' => PostgresConnector::class,

      'sqlite'                            => SqliteConnector::class,
      'sqlite3'                           => SqliteConnector::class,
      __NAMESPACE__.'\\sqliteconnector'   => SqliteConnector::class,
   ];


   /**
    * Return the Singleton instance of this class.
    *
    * @return self
    */
   public static function me() {
      return Singleton::getInstance(static::class);
   }


   /**
    * Return the Connector instance for the specified connection identifier.
    *
    * @param  string $id - connection identifier
    *
    * @return Connector - database adapter for the specified identifier
    */
   public static function getConnector($id = null) {
      $me = self::me();

      if ($id === null) {                                      // a single db project
         if (!$me->default) throw new IllegalStateException('Invalid default database configuration: null');
         $connector = $me->default;
      }
      elseif (isSet($me->pool[$id])) {                         // is the connection already known?
         $connector = $me->pool[$id];
      }
      else {                                                   // no, get the connection's config
         if (!$config=Config::getDefault())
            throw new RuntimeException('Service locator returned invalid default config: '.getType($config));

         $config = $config->get('db.'.$id, null);
         if (!$config) throw new IllegalStateException('No configuration found for database alias "'.$id.'"');

         // resolve the class name to use for the Connector
         $name = $config['connector'];
         $name = str_replace('/', '\\', $name);
         if ($name[0]=='\\') $name = subStr($name, 1);

         // check known aliases for class name
         $lName = strToLower($name);
         if (isSet(self::$aliases[$lName]))
            $class = self::$aliases[$lName];

         // separate connection configuration and additional options
         $options = isSet($config['options']) ? $config['options'] : [];
         unset($config['connector'], $config['options']);

         // instantiate and save a new Connector
         $me->pool[$id] = $connector = Connector::create($class, $config, $options);
      }
      return $connector;
   }
}

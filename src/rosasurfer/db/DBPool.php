<?php
namespace rosasurfer\db;

use rosasurfer\config\Config;

use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalStateException;


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
   private static $internalConnectors = [
           'mysql'                         => __NAMESPACE__.'\MySQLConnector',
           'mysqlconnector'                => __NAMESPACE__.'\MySQLConnector',
           __NAMESPACE__.'\mysqlconnector' => __NAMESPACE__.'\MySQLConnector',
      '\\'.__NAMESPACE__.'\mysqlconnector' => __NAMESPACE__.'\MySQLConnector',
   ];

   /**
    * Gibt die Singleton-Instanz dieser Klasse zurück.
    *
    * @return Singleton
    */
   public static function me() {
      return Singleton::getInstance(__CLASS__);
   }


   /**
    * Gibt den Connector für den angegebenen Datenbank-Alias zurück.
    *
    * @param  string $alias - Datenbank-Alias
    *
    * @return DB
    */
   public static function getDB($alias = null) {
      $me = self::me();

      if ($alias === null) {                                // single db project
         if (!$me->default) throw new IllegalStateException('Invalid default database configuration: null');
         $connector = $me->default;
      }
      elseif (isSet($me->pool[$alias])) {                   // schon im Pool ?
         $connector = $me->pool[$alias];
      }
      elseif ($config=Config::getDefault()->get('db.'.$alias, null)) {   // nein, Config holen und Connector laden
         // bekannte Namen trotz unterschiedlicher Schreibweisen erkennen
         $name = strToLower($config['connector']);

         if (isSet(self::$internalConnectors[$name])) {
            $class = self::$internalConnectors[$name];
         }
         else {   // unbekannt, Fall-back zu "{$name}Connector"
            $class = $config['connector'].'Connector';
         }

         $host    =                             $config['host'    ];
         $user    =                             $config['username'];
         $pass    =                             $config['password'];
         $db      =                             $config['schema'  ];
         $options = isSet($config['options']) ? $config['options' ] : null;

         $connector = DB::spawn($class, $host, $user, $pass, $db, $options);
         $me->pool[$alias] = $connector;
      }
      else {
         throw new IllegalStateException('No database configuration found for db alias "'.$alias.'"');
      }

      return $connector;
   }


   /*
   // @return DB
   public static function getByDao(GenericDAO $dao) {
      return self:: me()->getLink($dao->getLinkName());
   }
   */
}

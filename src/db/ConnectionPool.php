<?php
namespace rosasurfer\db;

use rosasurfer\config\ConfigInterface;
use rosasurfer\core\Singleton;
use rosasurfer\core\assert\Assert;
use rosasurfer\db\ConnectorInterface as IConnector;
use rosasurfer\db\mysql\MySQLConnector;
use rosasurfer\db\pgsql\PostgresConnector;
use rosasurfer\db\sqlite\SQLiteConnector;
use rosasurfer\exception\IllegalStateException;


/**
 * ConnectionPool
 *
 * A pool for multiple database adapter instances. Each instance represents a connection.
 */
final class ConnectionPool extends Singleton {


    /** @var IConnector[] - adapter pool */
    private $pool = [];

    /** @var IConnector - default adapter */
    private $default;

    /** @var string[] - common adapter aliases */
    private static $aliases = [
        'mysql'                                     => MySQLConnector::class,
        'maria'                                     => MySQLConnector::class,
        'mariadb'                                   => MySQLConnector::class,
        'maria-db'                                  => MySQLConnector::class,
        __NAMESPACE__.'\\mysql\\ mysqlconnector'    => MySQLConnector::class,

        'pgsql'                                     => PostgresConnector::class,
        'postgres'                                  => PostgresConnector::class,
        'postgresql'                                => PostgresConnector::class,
        __NAMESPACE__.'\\pgsql\\ postgresconnector' => PostgresConnector::class,

        'sqlite'                                    => SQLiteConnector::class,
        'sqlite3'                                   => SQLiteConnector::class,
        __NAMESPACE__.'\\sqlite\\sqliteconnector'   => SQLiteConnector::class,
    ];


    /**
     * Return the Singleton instance of this class.
     *
     * @return static
     */
    public static function me() {
        return self::getInstance(static::class);
    }


    /**
     * Return the connector instance for the specified connection identifier.
     *
     * @param  string $id [optional] - connection identifier
     *
     * @return IConnector - database adapter for the specified identifier
     */
    public static function getConnector($id = null) {
        $me = self::me();

        if ($id === null) {                                      // a single db project
            if (!$me->default) throw new IllegalStateException('Invalid default database configuration: null');
            $connector = $me->default;
        }
        elseif (isset($me->pool[$id])) {                         // is the connection already known?
            $connector = $me->pool[$id];
        }
        else {                                                   // no, get the connection's config
            /** @var ConfigInterface $config */
            $config  = self::di('config');
            $options = $config->get('db.'.$id, []);
            Assert::isArray($options, 'Invalid config value "db.'.$id.'": %s');
            if (!$options) throw new IllegalStateException('No configuration found for database alias "'.$id.'"');

            // resolve the class name to use for the connector
            $className = $options['connector']; unset($options['connector']);
            $className = str_replace('/', '\\', $className);
            if ($className[0]=='\\') $className = substr($className, 1);

            // check known aliases for a match
            $lName = strtolower($className);
            if (isset(self::$aliases[$lName]))
                $className = self::$aliases[$lName];

            // instantiate and save a new connector
            $me->pool[$id] = $connector = Connector::create($className, $options);
        }
        return $connector;
    }
}

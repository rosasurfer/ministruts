<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\db;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\Singleton;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\db\ConnectorInterface as IConnector;
use rosasurfer\ministruts\db\pgsql\PostgresConnector;
use rosasurfer\ministruts\db\sqlite\SQLiteConnector;


/**
 * ConnectionPool
 *
 * A pool for multiple database adapter instances. Each instance represents a connection.
 */
final class ConnectionPool extends Singleton {


    /** @var IConnector[] - adapter pool */
    private array $pool = [];

    /** @var string[] - common adapter aliases */
    private static array $aliases = [
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
    public static function me(): self {
        /** @var static $instance */
        $instance = self::getInstance(static::class);
        return $instance;
    }


    /**
     * Return the connector instance for the specified connection identifier.
     *
     * @param  string $id - connection identifier
     *
     * @return IConnector - database adapter for the specified identifier
     */
    public static function getConnector(string $id): IConnector {
        $me = self::me();

        if (!isset($me->pool[$id])) {
            /** @var Config $config */
            $config = self::di('config');
            $options = $config->get("db.$id", []);
            Assert::isArray($options, "config value \"db.$id\"");
            if (!$options) throw new IllegalStateException("No configuration found for database alias \"$id\"");

            // resolve the class name of the connector
            $className = $options['connector'];
            unset($options['connector']);
            $className = str_replace('/', '\\', $className);
            if ($className[0] == '\\') $className = substr($className, 1);

            // check known aliases
            $lName = strtolower($className);
            $className = self::$aliases[$lName] ?? $className;

            // instantiate connector and add it to the pool
            $me->pool[$id] = $connector = Connector::create($className, $options);
        }
        return $me->pool[$id];
    }
}

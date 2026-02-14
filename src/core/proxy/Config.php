<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\proxy;

use rosasurfer\ministruts\config\Config as ConfigInterface;

/**
 * Config
 *
 * A {@link Proxy} for the application's {@link ConfigInterface} instance currently registered in the service container.
 *
 * @method static ConfigInterface instance()                                                        Get the object behind the proxy.
 * @method static mixed           get(string $key, mixed $default = null)                           Return the config setting with the specified key or the default value if no such setting is found. Throws an exception if the setting is not found and no default value was specified.
 * @method static int             int(string $key, int $default = 0)                                Return the config setting with the specified key interpreted as an integer. Throws an exception if the setting is not found or cannot be interpreted as an integer.
 * @method static float           float(string $key, float $default = 0.0)                           Return the config setting with the specified key interpreted as a float. Throws an exception if the setting is not found or cannot be interpreted as a float.
 * @method static bool            bool(string $key, bool $default = false)                           Return the config setting with the specified key interpreted as a boolean. Throws an exception if the setting is not found or cannot be interpreted as a boolean.
 * @method static string          string(string $key, string $default = '')                          Return the config setting with the specified key interpreted as a string. Throws an exception if the setting is not found or is non-scalar.
 * @method static ConfigInterface set(int|string $key, mixed $value)                                Set/modify the config setting with the specified key.
 */
class Config extends Proxy {

    /**
     * {@inheritDoc}
     */
    protected static function getServiceName(): string {
        return 'config';
        return ConfigInterface::class;      // @phpstan-ignore deadCode.unreachable (keep for testing)
    }
}

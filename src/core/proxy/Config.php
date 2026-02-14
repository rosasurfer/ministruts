<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\proxy;

use rosasurfer\ministruts\config\Config as ConfigInterface;

/**
 * Config
 *
 * A {@link Proxy} for the application's {@link ConfigInterface} instance currently registered in the service container.
 *
 * @method static ConfigInterface instance()                                                     Get the object behind the proxy.
 * @method static mixed           get(string $key, mixed $default = null)                        Return the config setting with the specified key or the default value if no such setting is found. Throws an exception if the setting is not found and no default value was specified.
 * @method static int             int(string $key, int $default = 0)                             Return the config setting with the specified key interpreted as an integer. Accepted integer representations are integer values and strings matching integer notation (e.g. "0", "42", "-7"). Not existing settings and non-integer values trigger an exception.
 * @method static float           float(string $key, float $default = 0.0)                       Return the config setting with the specified key interpreted as a float. Throws an exception if the setting is not found or cannot be interpreted as a float.
 * @method static bool            bool(string $key, bool $strict = false, bool $default = false) Return the config setting with the specified key interpreted as a boolean. Accepted strict boolean representations are "1" and "0", "true" and "false", "on" and "off", "yes" and "no" (case-insensitive), as well as native booleans. Not existing settings and non-boolean values trigger an exception.
 * @method static string          string(string $key, string $default = '')                      Return the config setting with the specified key interpreted as a string. Scalar values are casted to string. Not existing settings and non-scalar values trigger an exception.
 * @method static ConfigInterface set(int|string $key, mixed $value)                             Set/modify the config setting with the specified key.
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

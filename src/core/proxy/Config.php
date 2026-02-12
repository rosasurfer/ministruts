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
 * @method static mixed           get(string $key, mixed $default = null)                           Return the config setting with the specified key or the default value if no such setting is found.
 * @method static bool            getBool(string $key, bool $strict = false, bool $default = false) Return the config setting with the specified key as a boolean. Accepted strict boolean representations are "1" and "0", "true" and "false", "on" and "off", "yes" and "no" (case-insensitive).
 * @method static string          getString(string $key, string $default = '')                      Return the config setting with the specified key as a string. Scalar setting values are casted to string. Not existing and non-scalar settings trigger an exception.
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

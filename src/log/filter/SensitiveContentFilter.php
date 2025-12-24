<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\filter;

use Throwable;
use rosasurfer\ministruts\core\CObject;

/**
 * A filter for sensitive named values. The filter matches if the key/name of a value contains one of the configured name patterns.
 * Redacted values are replaced by asterisk "*" characters.
 */
class SensitiveContentFilter extends CObject implements ContentFilterInterface {

    /**
     * @var string[] - sensitive name patterns (case-insensitive partial matching)
     *
     * @link https://docs.sentry.io/product/data-management-settings/scrubbing/server-side-scrubbing/
     */
    protected array $patterns = [
        'api_key',
        'api-key',
        'apikey',
        'auth',
        'bearer',
        'credential',
        'mysql_pwd',
        'mysql-pwd',
        'pass',
        'private_key',
        'private-key',
        'privatekey',
        'secret',
        'token',
    ];


    /**
     * A plain string does not constitute a named value and will pass through this filter unmodified.
     *
     * {@inheritDoc}
     *
     * @example
     * <pre>
     *  $str = 'Bob sent an email which contained his Paypal password in plain text.';
     *  $str = (new SensitiveContentFilter())->filterString($str);
     *  echo $str;                  // output: Bob sent an email which contained his Paypal password in plain text.
     * </pre>
     */
    public function filterString(string $input): string {
        return $input;
    }


    /**
     * {@inheritDoc}
     *
     * @example
     * <pre>
     *  $name  = 'password';
     *  $value = 'secret';
     *  $str = (new SensitiveContentFilter())->filterValue($name, $value);
     *  echo "$name = $value";      // output: password = ******
     * </pre>
     */
    public function filterValue(string $name, $value) {
        if (is_string($value) && $this->isSensitive($name)) {
            return self::SUBSTITUTE;
        }
        return $value;
    }


    /**
     * {@inheritDoc}
     *
     * @example
     * <pre>
     *  $array = ['password' => 'secret'];
     *  $array = (new SensitiveContentFilter())->filterValues($array);
     *  print_r($array);            // output: Array([password] => ******)
     * </pre>
     */
    public function filterValues(array $values, array $skip = []): array {
        $skip = array_flip($skip);

        foreach ($values as $key => $value) {
            if (isset($skip[$key])) {
                continue;
            }
            if (is_array($value)) {
                $values[$key] = $this->filterValues($value);
            }
            elseif (is_object($value)) {
                // todo
            }
            elseif (is_string($value) && is_string($key) && $this->isSensitive($key)) {
                $values[$key] = self::SUBSTITUTE;
            }
        }
        return $values;
    }


    /**
     * {@inheritDoc}
     *
     * @example
     * <pre>
     *  $uri = 'https://user:pass@hostname:9090/path?token=secret-token&arg=value#anchor';
     *  $uri = (new SensitiveContentFilter())->filterUri($uri);
     *  echo $uri;                  // output: https://user:******@hostname:9090/path?token=******&arg=value#anchor
     * </pre>
     */
    public function filterUri(string $uri): string {
        if (!strlen($uri)) {
            return $uri;
        }

        $u = null;
        try {
            $u = parse_url($uri);       // all optional: scheme, user, pass, host, port, path, query, fragment
        }
        catch (Throwable $th) {}
        if (!$u) {
            return $uri;
        }

        // filter an existing 'pass' component
        if (isset($u['pass'])) {
            $search  = ":$u[pass]@";
            $replace = ':'.self::SUBSTITUTE.'@';
            $pos = strpos($uri, $search);
            if ($pos !== false) {
                $uri = substr_replace($uri, $replace, $pos, strlen($search));
            }
        }

        // filter an existing 'query' component
        if (isset($u['query'])) {
            $params = explode('&', $u['query']);

            foreach ($params as $i => $param) {
                $args = explode('=', $param, 2);
                if (sizeof($args) > 1 && $this->isSensitive($args[0])) {
                    $params[$i] = "$args[0]=".self::SUBSTITUTE;
                }
            }
            $filtered = join('&', $params);

            if ($filtered !== $u['query']) {
                $search  = "?$u[query]";
                $replace = "?$filtered";
                $pos = strpos($uri, $search);
                if ($pos !== false) {
                    $uri = substr_replace($uri, $replace, $pos, strlen($search));
                }
            }
        }
        return $uri;
    }


    /**
     * Whether a string contains one of the configured sensitive patterns.
     *
     * @param  string $input
     *
     * @return bool
     */
    protected function isSensitive(string $input): bool {
        if ($input == '') {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if ((stripos($input, $pattern) !== false)) {
                return true;
            }
        }
        return false;
    }
}

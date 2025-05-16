<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use Throwable;

use rosasurfer\ministruts\core\Singleton;
use rosasurfer\ministruts\core\di\proxy\Request as Request;
use rosasurfer\ministruts\util\PHP;


/**
 * HttpSession
 *
 * An object wrapping the current HTTP session.
 */
class HttpSession extends Singleton {


    /** @var bool - Whether the session is considered "new". A session is new if the client doesn't yet know the session id. */
    protected bool $new;


    /**
     * Return the {@link Singleton} instance.
     *
     * @return static
     */
    public static function me(): self {
        /** @var static $instance */
        $instance = self::getInstance(static::class);
        return $instance;
    }


    /**
     * Constructor
     */
    protected function __construct() {
        parent::__construct();
        $this->init();
    }


    /**
     * Start and initialize the session.
     *
     * @return void
     */
    protected function init(): void {
        // limit session cookie to application path to support multiple projects per domain
        $params = session_get_cookie_params();
        session_set_cookie_params($params['lifetime'],
                                  Request::getApplicationBaseUri(),
                                  $params['domain'  ],
                                  $params['secure'  ],
                                  $params['httponly']);
        PHP::ini_set('session.use_strict_mode', true);  // enforce prevention of user-defined session ids

        // start or continue the session
        try {
            session_start();                            // intentionally trigger an error if the session has already been started
        }
        catch (Throwable $ex) {                         // @phpstan-ignore catch.neverThrown (TODO: Is this check still needed?)
            if (!preg_match('/The session id contains illegal characters/i', $ex->getMessage())) throw $ex;
            session_regenerate_id();
        }

        // check session state
        if (sizeof($_SESSION) == 0) {                   // 0 means the session is new and not yet marked
            $this->reset(false);
        }
        else {
            $this->new = false;                         // continue an existing session (with markers)
        }
    }


    /**
     * Reset this session to a new and empty state.
     *
     * @param  bool $regenerateId - whether to generate a new session id and to delete an old session file
     *
     * @return void
     */
    public function reset(bool $regenerateId): void {
        if ($regenerateId) {
            session_regenerate_id(true);                                            // generate new id and delete the old file
        }
        $request = Request::instance();

        $_SESSION = [];                                                             // empty the session
        $_SESSION['__SESSION_CREATED__'  ] = microtime(true);                       // initialize the session markers
        $_SESSION['__SESSION_IP__'       ] = $request->getRemoteAddress();          // TODO: resolve/store forwarded IP
        $_SESSION['__SESSION_USERAGENT__'] = $request->getHeaderValue('User-Agent');

        $this->new = true;
    }


    /**
     * Whether the session is new. A session is considered "new" if the web user does not yet know the session id.
     *
     * @return bool
     */
    public function isNew(): bool {
        return $this->new;
    }


    /**
     * Return the current name of the session variable.
     *
     * @return string
     */
    public function getName(): string {
        return session_name();
    }


    /**
     * Return the current session id.
     *
     * @return string
     */
    public function getId(): string {
        return session_id();
    }


    /**
     * Return the session value stored under the specified key, or the passed default value if no such session value exists.
     *
     * @param  string $key                - session key
     * @param  mixed  $default [optional] - alternative default (default: NULL)
     *
     * @return mixed
     */
    public function getAttribute(string $key, $default = null) {
        if (\key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }
        return $default;
    }


    /**
     * Store a value under the specified key in the session. An already existing value under the same key is replaced.
     * If NULL is passed as a value the effect is the same as calling {@link HttpSession::removeAttribute($key)}.
     *
     * @param  string $key   - session key
     * @param  mixed  $value - value to store
     *
     * @return $this
     */
    public function setAttribute(string $key, $value): self {
        if ($value !== null) {
            $_SESSION[$key] = $value;
        }
        else {
            $this->removeAttribute($key);
        }
        return $this;
    }


    /**
     * Delete all session values stored under the specified key(s).
     *
     * @param  string|string[] $key - a single session key or an array of session keys
     *
     * @return void
     */
    public function removeAttribute($key): void {
        if (is_array($key)) {
            foreach ($key as $value) {
                unset($_SESSION[$value]);
            }
        }
        else {
            unset($_SESSION[$key]);
        }
    }


    /**
     * Whether a value is stored in the session under the specified key.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isAttribute(string $key): bool {
        return \key_exists($key, $_SESSION);
    }
}

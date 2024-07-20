<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\error\PHPError;
use rosasurfer\util\PHP;


/**
 * HttpSession
 *
 * An object wrapping the current HTTP session.
 */
class HttpSession extends Singleton {


    /** @var Request - request the session belongs to */
    protected $request;

    /** @var bool - Whether the session is considered "new". A session is new if the client doesn't yet know the session id. */
    protected $new;


    /**
     * Return the {@link \rosasurfer\core\Singleton} instance.
     *
     * @param  Request $request - request the session belongs to
     *
     * @return static
     *
     * @throws RuntimeException if not called from the web interface
     */
    public static function me(Request $request) {
        return self::getInstance(static::class, $request);
    }


    /**
     * Constructor
     *
     * @param  Request $request - request the session belongs to
     */
    protected function __construct(Request $request) {
        parent::__construct();
        $this->request = $request;
        $this->init();
    }


    /**
     * Start and initialize the session.
     */
    protected function init() {
        $request = $this->request;

        // limit session cookie to application path to support multiple projects per domain
        $params = session_get_cookie_params();
        session_set_cookie_params($params['lifetime'],
                                  $request->getApplicationBaseUri(),
                                  $params['domain'  ],
                                  $params['secure'  ],
                                  $params['httponly']);
        PHP::ini_set('session.use_strict_mode', true);  // enforce prevention of user-defined session ids

        // start or continue the session
        try {
            session_start();                            // intentionally trigger an error if the session has already been started
        }
        catch (PHPError $error) {                       // TODO: Is this check still needed?
            if (preg_match('/The session id contains illegal characters/i', $error->getMessage()))
                session_regenerate_id();
            else throw $error;
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
     */
    public function reset($regenerateId) {
        if (!is_bool($regenerateId)) throw new IllegalTypeException('Illegal type of parameter $regenerateId: '.gettype($regenerateId));

        if ($regenerateId) {
            session_regenerate_id(true);                                            // generate new id and delete the old file
        }
        $_SESSION = [];                                                             // empty the session
        $_SESSION['__SESSION_CREATED__'  ] = microtime(true);                       // initialize the session markers
        $_SESSION['__SESSION_IP__'       ] = $this->request->getRemoteAddress();    // TODO: resolve/store forwarded IP
        $_SESSION['__SESSION_USERAGENT__'] = $this->request->getHeaderValue('User-Agent');

        $this->new = true;
    }


    /**
     * Whether the session is new. A session is considered "new" if the web user does not yet know the session id.
     *
     * @return bool
     */
    public function isNew() {
        return $this->new;
    }


    /**
     * Return the current name of the session variable.
     *
     * @return string
     */
    public function getName() {
        return session_name();
    }


    /**
     * Return the current session id.
     *
     * @return string
     */
    public function getId() {
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
    public function getAttribute($key, $default = null) {
        if (\key_exists($key, $_SESSION))
            return $_SESSION[$key];
        return $default;
    }


    /**
     * Store a value under the specified key in the session. An already existing value under the same key is replaced.
     * If NULL is passed as a value the effect is the same as calling {@link HttpSession::removeAttribute($key)}.
     *
     * @param  string $key   - session key
     * @param  mixed  $value - value to store
     */
    public function setAttribute($key, $value) {
        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));

        if ($value !== null) {
            $_SESSION[$key] = $value;
        }
        else {
            $this->removeAttribute($key);
        }
    }


    /**
     * Delete all session values stored under the specified key(s).
     *
     * @param  string|string[] $key - a single session key or an array of session keys
     */
    public function removeAttribute($key) {
        if (is_array($key)) {
            foreach ($key as $i => $value) {
                if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $key['.$i.']: '.gettype($value));
                unset($_SESSION[$value]);
            }
            return;
        }

        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));
        unset($_SESSION[$key]);
    }


    /**
     * Whether a value is stored in the session under the specified key.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isAttribute($key) {
        return \key_exists($key, $_SESSION);
    }
}

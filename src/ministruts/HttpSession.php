<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\exception\error\PHPError;


/**
 * HttpSession
 *
 * An object wrapping the current HTTP session.
 */
class HttpSession extends Singleton {


    /** @var Request - request the session belongs to */
    protected $request;

    /** @var bool - Whether the session is "new". A session is new if the client doesn't yet know the session id. */
    protected $new;


    /**
     * Return the {@link Singleton} instance.
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
        /**
         * PHP laesst sich ohne weiteres manipulierte Session-IDs unterschieben, solange diese keine ungueltigen Zeichen
         * enthalten (IDs wie PHPSESSID=111 werden anstandslos akzeptiert). Wenn session_start() zurueckkehrt, gibt es mit
         * den vorhandenen PHP-Mitteln keine vernuenftige Moeglichkeit mehr, festzustellen, ob die Session-ID von PHP oder
         * kuenstlich vom User generiert wurde. Daher wird in dieser Methode jede neu erzeugte Session markiert. Fehlt diese
         * Markierung nach Rueckkehr von session_start(), wurde die Session nicht von dieser Methode erzeugt.
         * Aus Sicherheitsgruenden wird eine solche Session verworfen und eine neue initialisiert.
         */
        $request = $this->request;

        // Session-Cookie auf Application beschraenken, um mehrere Projekte je Domain zu ermoeglichen
        $params = session_get_cookie_params();
        session_set_cookie_params($params['lifetime'],
                                  $request->getApplicationBaseUri(),
                                  $params['domain'  ],
                                  $params['secure'  ],
                                  $params['httponly']);

        // Session starten bzw. fortsetzen
        try {
            session_start();                        // TODO: Handle the case when a session was already started elsewhere?
        }
        catch (PHPError $error) {
            if (!preg_match('/The session id contains illegal characters/i', $error->getMessage()))
                throw $error;
            session_regenerate_id();                // regenerate a new id
        }

        // check session state
        if (sizeof($_SESSION) == 0) {               // 0 means a new session without markers
            $sessionName = session_name();
            $sessionId = session_id();

            // check whether the session id was submitted by the user
            if     (isset($_COOKIE [$sessionName]) && $_COOKIE [$sessionName]==$sessionId) $fromUser = true;
            elseif (isset($_REQUEST[$sessionName]) && $_REQUEST[$sessionName]==$sessionId) $fromUser = true;    // GET/POST
            else                                                                           $fromUser = false;

            $this->reset($regenerateId = $fromUser);
        }
        else {
            $this->new = false;                     // continue existing session (with markers)
        }
    }


    /**
     * Reset this session to a clean and new state.
     *
     * @param  bool $regenerateId - whether to generate a new session id and to delete an old session file
     */
    public function reset($regenerateId) {
        if (!is_bool($regenerateId)) throw new IllegalTypeException('Illegal type of parameter $regenerateId: '.gettype($regenerateId));

        if ($regenerateId) {
            session_regenerate_id(true);                                        // generate new id (deletes the old file)
        }
        $this->removeAttribute(\array_keys($_SESSION));                         // empty the session

        $request = $this->request;                                              // initialize session markers
        $_SESSION['__SESSION_CREATED__'  ] = microtime(true);
        $_SESSION['__SESSION_IP__'       ] = $request->getRemoteAddress();      // TODO: resolve/store forwarded IP
        $_SESSION['__SESSION_USERAGENT__'] = $request->getHeaderValue('User-Agent');

        $this->new = true;
    }


    /**
     * Ob diese Session neu ist oder nicht. Die Session ist neu, wenn der User die aktuelle Session-ID noch nicht kennt.
     *
     * @return bool
     */
    public function isNew() {
        return $this->new;
    }


    /**
     * Gibt die Session-ID der Session zurueck.
     *
     * @return string - Session-ID
     */
    public function getId() {
        return session_id();
    }


    /**
     * Gibt den Namen der Sessionvariable zurueck.
     *
     * @return string - Name
     */
    public function getName() {
        return session_name();
    }


    /**
     * Gibt den unter dem angegebenen Schluessel in der Session gespeicherten Wert zurueck oder den
     * angegebenen Alternativwert, falls kein Wert unter diesem Schluessel existiert.
     *
     * @param  string $key                - Schluessel, unter dem der Wert gespeichert ist
     * @param  mixed  $default [optional] - Default- bzw. Alternativwert (kann selbst auch NULL sein)
     *
     * @return mixed - der gespeicherte Wert oder NULL
     */
    public function getAttribute($key, $default = null) {
        if (\key_exists($key, $_SESSION))
            return $_SESSION[$key];
        return $default;
    }


    /**
     * Speichert in der Session unter dem angegebenen Schluessel einen Wert.  Ein unter dem selben
     * Schluessel schon vorhandener Wert wird ersetzt.
     *
     * Ist der uebergebene Wert NULL, hat dies den selben Effekt wie der Aufruf von
     * HttpSession::removeAttribute($key)
     *
     * @param  string $key   - Schluessel, unter dem der Wert gespeichert wird
     * @param  mixed  $value - der zu speichernde Wert
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
     * Delete session values stored under the specified key(s).
     *
     * @param  string|string[] $key - single session key or array of session keys of values to remove
     */
    public function removeAttribute($key) {
        if (is_array($key)) {
            foreach ($key as $k => $value) {
                if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $key['.$k.']: '.gettype($value));
                unset($_SESSION[$value]);
            }
            return;
        }

        if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.gettype($key));
        unset($_SESSION[$key]);
    }


    /**
     * Ob unter dem angegebenen Schluessel ein Wert in der Session existiert.
     *
     * @param  string $key - Schluessel
     *
     * @return bool
     */
    public function isAttribute($key) {
        return \key_exists($key, $_SESSION);
    }
}

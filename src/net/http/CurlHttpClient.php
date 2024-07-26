<?php
namespace rosasurfer\ministruts\net\http;

use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\IOException;
use rosasurfer\ministruts\log\Logger;

use function rosasurfer\ministruts\ini_get_bool;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;

use const rosasurfer\ministruts\L_INFO;
use const rosasurfer\ministruts\L_WARN;
use const rosasurfer\ministruts\NL;


/**
 * CurlHttpClient
 *
 * Eine Klasse, die mit cURL HttpRequests ausfuehren kann.
 */
class CurlHttpClient extends HttpClient {


    /** @var ?resource - Curl-Handle */
    protected $hCurl = null;

    /** @var int - Zaehler fuer manuelle Redirects (falls "open_basedir" aktiv ist) */
    protected $manualRedirects = 0;

    /** @var array - zusaetzliche CURL-Optionen */
    protected $options = [];

    /** @var string[] - cURL-Fehlerbeschreibungen */
    protected static $errors = [
        CURLE_OK                          => 'CURLE_OK',
        CURLE_UNSUPPORTED_PROTOCOL        => 'CURLE_UNSUPPORTED_PROTOCOL',
        CURLE_FAILED_INIT                 => 'CURLE_FAILED_INIT',
        CURLE_URL_MALFORMAT               => 'CURLE_URL_MALFORMAT',
        CURLE_URL_MALFORMAT_USER          => 'CURLE_URL_MALFORMAT_USER',
        CURLE_COULDNT_RESOLVE_PROXY       => 'CURLE_COULDNT_RESOLVE_PROXY',
        CURLE_COULDNT_RESOLVE_HOST        => 'CURLE_COULDNT_RESOLVE_HOST',
        CURLE_COULDNT_CONNECT             => 'CURLE_COULDNT_CONNECT',
        CURLE_FTP_WEIRD_SERVER_REPLY      => 'CURLE_FTP_WEIRD_SERVER_REPLY',
        CURLE_FTP_ACCESS_DENIED           => 'CURLE_FTP_ACCESS_DENIED',
        CURLE_FTP_USER_PASSWORD_INCORRECT => 'CURLE_FTP_USER_PASSWORD_INCORRECT',
        CURLE_FTP_WEIRD_PASS_REPLY        => 'CURLE_FTP_WEIRD_PASS_REPLY',
        CURLE_FTP_WEIRD_USER_REPLY        => 'CURLE_FTP_WEIRD_USER_REPLY',
        CURLE_FTP_WEIRD_PASV_REPLY        => 'CURLE_FTP_WEIRD_PASV_REPLY',
        CURLE_FTP_WEIRD_227_FORMAT        => 'CURLE_FTP_WEIRD_227_FORMAT',
        CURLE_FTP_CANT_GET_HOST           => 'CURLE_FTP_CANT_GET_HOST',
        CURLE_FTP_CANT_RECONNECT          => 'CURLE_FTP_CANT_RECONNECT',
        CURLE_FTP_COULDNT_SET_BINARY      => 'CURLE_FTP_COULDNT_SET_BINARY',
        CURLE_FTP_PARTIAL_FILE            => 'CURLE_FTP_PARTIAL_FILE',
        CURLE_FTP_COULDNT_RETR_FILE       => 'CURLE_FTP_COULDNT_RETR_FILE',
        CURLE_FTP_WRITE_ERROR             => 'CURLE_FTP_WRITE_ERROR',
        CURLE_FTP_QUOTE_ERROR             => 'CURLE_FTP_QUOTE_ERROR',
        CURLE_HTTP_NOT_FOUND              => 'CURLE_HTTP_NOT_FOUND',
        CURLE_WRITE_ERROR                 => 'CURLE_WRITE_ERROR',
        CURLE_MALFORMAT_USER              => 'CURLE_MALFORMAT_USER',
        CURLE_FTP_COULDNT_STOR_FILE       => 'CURLE_FTP_COULDNT_STOR_FILE',
        CURLE_READ_ERROR                  => 'CURLE_READ_ERROR',
        CURLE_OUT_OF_MEMORY               => 'CURLE_OUT_OF_MEMORY',
        CURLE_OPERATION_TIMEDOUT          => 'CURLE_OPERATION_TIMEDOUT',
        CURLE_FTP_COULDNT_SET_ASCII       => 'CURLE_FTP_COULDNT_SET_ASCII',
        CURLE_FTP_PORT_FAILED             => 'CURLE_FTP_PORT_FAILED',
        CURLE_FTP_COULDNT_USE_REST        => 'CURLE_FTP_COULDNT_USE_REST',
        CURLE_FTP_COULDNT_GET_SIZE        => 'CURLE_FTP_COULDNT_GET_SIZE',
        CURLE_HTTP_RANGE_ERROR            => 'CURLE_HTTP_RANGE_ERROR',
        CURLE_HTTP_POST_ERROR             => 'CURLE_HTTP_POST_ERROR',
        CURLE_SSL_CONNECT_ERROR           => 'CURLE_SSL_CONNECT_ERROR',
        CURLE_BAD_DOWNLOAD_RESUME         => 'CURLE_BAD_DOWNLOAD_RESUME',
        CURLE_FILE_COULDNT_READ_FILE      => 'CURLE_FILE_COULDNT_READ_FILE',
        CURLE_LDAP_CANNOT_BIND            => 'CURLE_LDAP_CANNOT_BIND',
        CURLE_LDAP_SEARCH_FAILED          => 'CURLE_LDAP_SEARCH_FAILED',
        CURLE_LIBRARY_NOT_FOUND           => 'CURLE_LIBRARY_NOT_FOUND',
        CURLE_FUNCTION_NOT_FOUND          => 'CURLE_FUNCTION_NOT_FOUND',
        CURLE_ABORTED_BY_CALLBACK         => 'CURLE_ABORTED_BY_CALLBACK',
        CURLE_BAD_FUNCTION_ARGUMENT       => 'CURLE_BAD_FUNCTION_ARGUMENT',
        CURLE_BAD_CALLING_ORDER           => 'CURLE_BAD_CALLING_ORDER',
        CURLE_HTTP_PORT_FAILED            => 'CURLE_HTTP_PORT_FAILED',
        CURLE_BAD_PASSWORD_ENTERED        => 'CURLE_BAD_PASSWORD_ENTERED',
        CURLE_TOO_MANY_REDIRECTS          => 'CURLE_TOO_MANY_REDIRECTS',
        CURLE_UNKNOWN_TELNET_OPTION       => 'CURLE_UNKNOWN_TELNET_OPTION',
        CURLE_TELNET_OPTION_SYNTAX        => 'CURLE_TELNET_OPTION_SYNTAX',
        CURLE_OBSOLETE                    => 'CURLE_OBSOLETE',
        51                                => 'CURLE_SSL_PEER_CERTIFICATE', // since libcurl-7.62.0 unified with CURLE_SSL_CACERT (60)
        CURLE_GOT_NOTHING                 => 'CURLE_GOT_NOTHING',
        CURLE_SSL_ENGINE_NOTFOUND         => 'CURLE_SSL_ENGINE_NOTFOUND',
        CURLE_SSL_ENGINE_SETFAILED        => 'CURLE_SSL_ENGINE_SETFAILED',
        CURLE_SEND_ERROR                  => 'CURLE_SEND_ERROR',
        CURLE_RECV_ERROR                  => 'CURLE_RECV_ERROR',
        CURLE_SHARE_IN_USE                => 'CURLE_SHARE_IN_USE',
        CURLE_SSL_CERTPROBLEM             => 'CURLE_SSL_CERTPROBLEM',
        CURLE_SSL_CIPHER                  => 'CURLE_SSL_CIPHER',
        CURLE_SSL_CACERT                  => 'CURLE_SSL_CACERT',
        CURLE_BAD_CONTENT_ENCODING        => 'CURLE_BAD_CONTENT_ENCODING',
        CURLE_LDAP_INVALID_URL            => 'CURLE_LDAP_INVALID_URL',
        CURLE_FILESIZE_EXCEEDED           => 'CURLE_FILESIZE_EXCEEDED',
        64                                => 'CURLE_USE_SSL_FAILED',
        65                                => 'CURLE_SEND_FAIL_REWIND',
        66                                => 'CURLE_SSL_ENGINE_INITFAILED',
        67                                => 'CURLE_LOGIN_DENIED',
        68                                => 'CURLE_TFTP_NOTFOUND',
        69                                => 'CURLE_TFTP_PERM',
        70                                => 'CURLE_REMOTE_DISK_FULL',
        71                                => 'CURLE_TFTP_ILLEGAL',
        72                                => 'CURLE_TFTP_UNKNOWNID',
        73                                => 'CURLE_REMOTE_FILE_EXISTS',
        74                                => 'CURLE_TFTP_NOSUCHUSER',
        75                                => 'CURLE_CONV_FAILED',
        76                                => 'CURLE_CONV_REQD',
        77                                => 'CURLE_SSL_CACERT_BADFILE',
        78                                => 'CURLE_REMOTE_FILE_NOT_FOUND',
        79                                => 'CURLE_SSH',
        80                                => 'CURLE_SSL_SHUTDOWN_FAILED',
        81                                => 'CURLE_AGAIN',
        82                                => 'CURLE_SSL_CRL_BADFILE',
        83                                => 'CURLE_SSL_ISSUER_ERROR',
        84                                => 'CURLE_FTP_PRET_FAILED',
        85                                => 'CURLE_RTSP_CSEQ_ERROR',
        86                                => 'CURLE_RTSP_SESSION_ERROR',
        87                                => 'CURLE_FTP_BAD_FILE_LIST',
        88                                => 'CURLE_CHUNK_FAILED',
        89                                => 'CURLE_NO_CONNECTION_AVAILABLE',
        90                                => 'CURLE_SSL_PINNEDPUBKEYNOTMATCH',
        91                                => 'CURLE_SSL_INVALIDCERTSTATUS',
        92                                => 'CURLE_HTTP2_STREAM',
    ];


    /**
     * Constructor
     *
     * Create a new instance.
     *
     * @param  array $options [optional] - additional options
     *                                     (default: none)
     */
    public function __construct(array $options = []) {
        $this->options = $options;
    }


    /**
     * Destructor, schliesst ein ggf&#46; noch offenes cURL-Handle.
     */
    public function __destruct() {
        try {
            if (is_resource($this->hCurl)) {
                $hTmp = $this->hCurl;
                $this->hCurl = null;
                curl_close($hTmp);
            }
        }
        catch (\Throwable $ex) { throw ErrorHandler::handleDestructorException($ex); }
    }


    /**
     * Fuehrt den uebergebenen Request aus und gibt die empfangene Antwort zurueck.
     *
     * @param  HttpRequest $request
     *
     * @return HttpResponse
     *
     * @throws IOException wenn ein Fehler auftritt
     */
    public function send(HttpRequest $request) {
        if (!is_resource($this->hCurl))
            $this->hCurl = curl_init();

        $response = new CurlHttpResponse();
        $options  = $this->prepareCurlOptions($request, $response);

        // CURLOPT_FOLLOWLOCATION funktioniert nur bei deaktiviertem "open_basedir"-Setting
        if (!ini_get_bool('open_basedir')) {
            if ($this->isFollowRedirects()) {
                $options[CURLOPT_FOLLOWLOCATION] = true;
            }
            elseif (isset($options[CURLOPT_FOLLOWLOCATION]) && $options[CURLOPT_FOLLOWLOCATION]) {
                $this->setFollowRedirects(true);
            }
            if ($this->isFollowRedirects()) {
                !isset($options[CURLOPT_MAXREDIRS]) && $options[CURLOPT_MAXREDIRS]=$this->maxRedirects;
            }
        }

        if ($request->getMethod() != 'POST') {
            $options[CURLOPT_POST] = false;
        }
        else {
            $options[CURLOPT_POST      ] = true;
            $options[CURLOPT_URL       ] = strLeftTo($request->getUrl(), '?');
            $options[CURLOPT_POSTFIELDS] = strRightFrom($request->getUrl(), '?');   // use URL parameters as POST fields
        }
        curl_setopt_array($this->hCurl, $options);

        // Request ausfuehren
        if (curl_exec($this->hCurl) === false) throw new IOException('cURL error '.self::getError($this->hCurl).','.NL.'URL: '.$options[CURLOPT_URL]);
        $status = curl_getinfo($this->hCurl, CURLINFO_HTTP_CODE);
        $response->setStatus($status);

        // ggf. manuellen Redirect ausfuehren (falls "open_basedir" aktiviert ist)
        if (($status==301 || $status==302) && $this->isFollowRedirects() && ini_get_bool('open_basedir')) {
            if ($this->manualRedirects >= $this->maxRedirects) throw new IOException('CURL error: maxRedirects limit exceeded - '.$this->maxRedirects.', URL: '.$options[CURLOPT_URL]);
            $this->manualRedirects++;

            /** @var string $location */
            $location = $response->getHeader('Location');                       // TODO: relative Redirects abfangen
            Logger::log('Performing manual redirect to: '.$location, L_INFO);   // TODO: verschachtelte IOExceptions abfangen
            $request  = new HttpRequest($location);
            $response = $this->send($request);
        }

        return $response;
    }


    /**
     * Create a cUrl options array for the current request.
     *
     * @param  HttpRequest      $request
     * @param  CurlHttpResponse $response
     *
     * @return array - resulting options
     */
    protected function prepareCurlOptions(HttpRequest $request, CurlHttpResponse $response) {
        $options = $this->options;                                  // options passed to the constructor
        $options    [CURLOPT_URL]      =  $request->getUrl();       // set or overwrite an existing URL
        $options += [CURLOPT_TIMEOUT   => $this->timeout    ];      // set but don't overwrite these existing options
        $options += [CURLOPT_USERAGENT => $this->userAgent  ];
        $options += [CURLOPT_ENCODING  => ''                ];      // an empty string activates all supported encodings

        if (!isset($options[CURLOPT_WRITEHEADER])) {
            $options += [CURLOPT_HEADERFUNCTION => [$response, 'writeHeader']];
        }

        if (!isset($options[CURLOPT_FILE])) {                       // overrides CURLOPT_RETURNTRANSFER
            $options += [CURLOPT_WRITEFUNCTION  => [$response, 'writeContent']];
        }

        foreach ($request->getHeaders() as $key => $value) {        // add all additionally specified request headers
            $options[CURLOPT_HTTPHEADER][] = $key.': '.$value;
        }
        return $options;
    }


    /**
     * Gibt eine Beschreibung des letzten cURL-Fehlers zurueck.
     *
     * @param  resource $hCurl - cURL-Handle
     *
     * @return string
     */
    protected static function getError($hCurl) {
        $errorNo  = curl_errno($hCurl);
        $errorStr = curl_error($hCurl);

        if (isset(self::$errors[$errorNo])) {
            $errorNo = self::$errors[$errorNo];
        }
        else {
            Logger::log('Unknown CURL error code: '.$errorNo, L_WARN);
        }

        return $errorNo.' ('.$errorStr.')';
    }
}

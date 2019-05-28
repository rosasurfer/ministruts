<?php declare(strict_types=1);

namespace {

    if (!defined('SID')) {
        define('SID', 'sessionName=sessionValue');
    }


    if (!function_exists('apache_request_headers')) {

        /**
         * @return array
         */
        function apache_request_headers() {
            return [];
        }
    }


    if (!function_exists('apc_add')) {

        /**
         * @param  string|array $key
         * @param  mixed        $var
         * @param  int          $ttl [optional]
         *
         * @return bool|array
         */
        function apc_add($key, $var, $ttl = null) {
            return false;
        }

        /**
         * @param  string $key
         *
         * @return bool
         */
        function apc_delete($key) {
            return false;
        }

        /**
         * @param string|string[] $keys
         *
         * @return bool|string[]
         */
        function apc_exists($keys) {
            return false;
        }

        /**
         * @param  string|string[] $key
         * @param  bool            $success [optional]
         *
         * @return mixed
         */
        function apc_fetch($key, &$success = null) {
            return false;
        }

        /**
         * @param  string|array $key
         * @param  mixed        $value
         * @param  int          $ttl [optional]
         *
         * @return bool|array
         */
        function apc_store($key, $value, $ttl = null) {
            return false;
        }
    }


    if (!function_exists('apcu_add')) {

        /**
         * @param  string|array $keys
         * @param  mixed        $values
         * @param  int          $ttl [optional]
         *
         * @return bool|array
         */
        function apcu_add($keys, $values, $ttl = null) {
            return false;
        }

        /**
         * @param  string|string[] $key
         * @param  bool            $success [optional]
         *
         * @return mixed
         */
        function apcu_fetch($key, &$success = null) {
            return false;
        }
    }


    if (!function_exists('curl_close')) {

        define('CURLE_ABORTED_BY_CALLBACK',         42);
        define('CURLE_BAD_CALLING_ORDER',           44);
        define('CURLE_BAD_CONTENT_ENCODING',        61);
        define('CURLE_BAD_DOWNLOAD_RESUME',         36);
        define('CURLE_BAD_FUNCTION_ARGUMENT',       43);
        define('CURLE_BAD_PASSWORD_ENTERED',        46);
        define('CURLE_COULDNT_CONNECT',              7);
        define('CURLE_COULDNT_RESOLVE_HOST',         6);
        define('CURLE_COULDNT_RESOLVE_PROXY',        5);
        define('CURLE_FAILED_INIT',                  2);
        define('CURLE_FILE_COULDNT_READ_FILE',      37);
        define('CURLE_FILESIZE_EXCEEDED',           63);
        define('CURLE_FTP_ACCESS_DENIED',            9);
        define('CURLE_FTP_CANT_GET_HOST',           15);
        define('CURLE_FTP_CANT_RECONNECT',          16);
        define('CURLE_FTP_COULDNT_GET_SIZE',        32);
        define('CURLE_FTP_COULDNT_RETR_FILE',       19);
        define('CURLE_FTP_COULDNT_SET_ASCII',       29);
        define('CURLE_FTP_COULDNT_SET_BINARY',      17);
        define('CURLE_FTP_COULDNT_STOR_FILE',       25);
        define('CURLE_FTP_COULDNT_USE_REST',        31);
        define('CURLE_FTP_PARTIAL_FILE',            18);
        define('CURLE_FTP_PORT_FAILED',             30);
        define('CURLE_FTP_QUOTE_ERROR',             21);
        define('CURLE_FTP_USER_PASSWORD_INCORRECT', 10);
        define('CURLE_FTP_WEIRD_227_FORMAT',        14);
        define('CURLE_FTP_WEIRD_PASS_REPLY',        11);
        define('CURLE_FTP_WEIRD_PASV_REPLY',        13);
        define('CURLE_FTP_WEIRD_SERVER_REPLY',       8);
        define('CURLE_FTP_WEIRD_USER_REPLY',        12);
        define('CURLE_FTP_WRITE_ERROR',             20);
        define('CURLE_FUNCTION_NOT_FOUND',          41);
        define('CURLE_GOT_NOTHING',                 52);
        define('CURLE_HTTP_NOT_FOUND',              22);
        define('CURLE_HTTP_PORT_FAILED',            45);
        define('CURLE_HTTP_POST_ERROR',             34);
        define('CURLE_HTTP_RANGE_ERROR',            33);
        define('CURLE_LDAP_CANNOT_BIND',            38);
        define('CURLE_LDAP_INVALID_URL',            62);
        define('CURLE_LDAP_SEARCH_FAILED',          39);
        define('CURLE_LIBRARY_NOT_FOUND',           40);
        define('CURLE_MALFORMAT_USER',              24);
        define('CURLE_OBSOLETE',                    50);
        define('CURLE_OK',                           0);
        define('CURLE_OPERATION_TIMEDOUT',          28);
        define('CURLE_OUT_OF_MEMORY',               27);
        define('CURLE_READ_ERROR',                  26);
        define('CURLE_RECV_ERROR',                  56);
        define('CURLE_SEND_ERROR',                  55);
        define('CURLE_SHARE_IN_USE',                57);
        define('CURLE_SSL_CACERT',                  60);
        define('CURLE_SSL_CERTPROBLEM',             58);
        define('CURLE_SSL_CIPHER',                  59);
        define('CURLE_SSL_CONNECT_ERROR',           35);
        define('CURLE_SSL_ENGINE_NOTFOUND',         53);
        define('CURLE_SSL_ENGINE_SETFAILED',        54);
        define('CURLE_SSL_PEER_CERTIFICATE',        51);    // since libcurl-7.62.0 unified with CURLE_SSL_CACERT (60)
        define('CURLE_TELNET_OPTION_SYNTAX',        49);
        define('CURLE_TOO_MANY_REDIRECTS',          47);
        define('CURLE_UNKNOWN_TELNET_OPTION',       48);
        define('CURLE_UNSUPPORTED_PROTOCOL',         1);
        define('CURLE_URL_MALFORMAT',                3);
        define('CURLE_URL_MALFORMAT_USER',           4);
        define('CURLE_WRITE_ERROR',                 23);

        define('CURLINFO_HTTP_CODE',           2097154);

        define('CURLOPT_ENCODING',               10102);
        define('CURLOPT_FILE',                   10001);
        define('CURLOPT_FOLLOWLOCATION',            52);
        define('CURLOPT_HEADERFUNCTION',         20079);
        define('CURLOPT_HTTPHEADER',             10023);
        define('CURLOPT_MAXREDIRS',                 68);
        define('CURLOPT_POST',                      47);
        define('CURLOPT_POSTFIELDS',             10015);
        define('CURLOPT_SSL_VERIFYPEER',            64);
        define('CURLOPT_TIMEOUT',                   13);
        define('CURLOPT_URL',                    10002);
        define('CURLOPT_USERAGENT',              10018);
        define('CURLOPT_WRITEFUNCTION',          20011);
        define('CURLOPT_WRITEHEADER',            10029);

        /**
         * @param  resource $ch
         */
        function curl_close($ch) {
        }

        /**
         * @param  string $url [optional]
         *
         * @return resource
         */
        function curl_init($url = null) {
            /** @var resource $result */
            return $result = null;
        }

        /**
         * @param  resource $ch
         *
         * @return int
         */
        function curl_errno($ch) {
            return 0;
        }

        /**
         * @param  resource $ch
         *
         * @return string
         */
        function curl_error($ch) {
            return '';
        }

        /**
         * @param  resource $ch
         *
         * @return string|bool
         */
        function curl_exec($ch) {
            return false;
        }

        /**
         * @param  resource $ch
         * @param  int      $option [optional]
         *
         * @return mixed
         */
        function curl_getinfo($ch, $option = null) {
            return [];
        }

        /**
         * @param  resource $ch
         * @param  array    $options
         *
         * @return bool
         */
        function curl_setopt_array($ch, array $options) {
            return false;
        }
    }


    if (!function_exists('mysql_affected_rows')) {

        define('MYSQL_ASSOC', 1);
        define('MYSQL_NUM',   2);
        define('MYSQL_BOTH',  3);

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return int
         */
        function mysql_affected_rows($link_identifier = null) {
            return 0;
        }

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return bool
         */
        function mysql_close($link_identifier = null) {
            return false;
        }

        /**
         * @param  string $server       [optional]
         * @param  string $username     [optional]
         * @param  string $password     [optional]
         * @param  bool   $new_link     [optional]
         * @param  int    $client_flags [optional]
         *
         * @return resource
         */
        function mysql_connect($server = null, $username = null, $password = null, $new_link = null, $client_flags = null) {
            /** @var resource $result */
            return $result = null;
        }

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return string
         */
        function mysql_error($link_identifier = null) {
            return '';
        }

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return int
         */
        function mysql_errno($link_identifier = null) {
            return 0;
        }

        /**
         * @param  resource $result
         * @param  int      $result_type [optional]
         *
         * @return string[]|bool
         */
        function mysql_fetch_array($result, $result_type = null) {
            return false;
        }


        /**
         * @param  resource $result
         *
         * @return bool
         */
        function mysql_free_result($result) {
            return false;
        }

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return string
         */
        function mysql_get_server_info($link_identifier = null) {
            return '';
        }

        /**
         * @param  resource $link_identifier [optional]
         *
         * @return int
         */
        function mysql_insert_id($link_identifier = null) {
            return 0;
        }

        /**
         * @param  resource $result
         *
         * @return int
         */
        function mysql_num_fields($result) {
            return 0;
        }

        /**
         * @param  resource $result
         *
         * @return int
         */
        function mysql_num_rows($result) {
            return 0;
        }

        /**
         * @param  string   $query
         * @param  resource $link_identifier [optional]
         *
         * @return resource|bool
         */
        function mysql_query($query, $link_identifier = null) {
            return false;
        }

        /**
         * @param  string   $unescaped_string
         * @param  resource $link_identifier [optional]
         *
         * @return string
         */
        function mysql_real_escape_string($unescaped_string, $link_identifier = null) {
            return '';
        }

        /**
         * @param  string   $database_name
         * @param  resource $link_identifier [optional]
         *
         * @return bool
         */
        function mysql_select_db($database_name, $link_identifier = null) {
            return false;
        }

        /**
         * @param  string   $charset
         * @param  resource $link_identifier [optional]
         *
         * @return bool
         */
        function mysql_set_charset($charset, $link_identifier = null) {
            return false;
        }
    }


    if (!function_exists('pcntl_signal')) {

        define('SIGINT', 2);

        /**
         * @param  int          $signo
         * @param  callable|int $handler
         * @param  bool         $restart_syscalls [optional]
         *
         * @return bool
         */
        function pcntl_signal($signo, $handler, $restart_syscalls = null) {
            return false;
        }

        /**
         * @return bool
         */
        function pcntl_signal_dispatch() {
            return false;
        }
    }


    if (!function_exists('pg_affected_rows')) {

        define('PGSQL_ASSOC',             1);
        define('PGSQL_BAD_RESPONSE',      3);
        define('PGSQL_BOTH',              3);
        define('PGSQL_COMMAND_OK',        1);
        define('PGSQL_CONNECT_FORCE_NEW', 2);
        define('PGSQL_COPY_IN',           4);
        define('PGSQL_COPY_OUT',          3);
        define('PGSQL_EMPTY_QUERY',       0);
        define('PGSQL_FATAL_ERROR',       7);
        define('PGSQL_NONFATAL_ERROR',    6);
        define('PGSQL_NUM',               2);
        define('PGSQL_STATUS_LONG',       1);
        define('PGSQL_STATUS_STRING',     2);
        define('PGSQL_TUPLES_OK',         2);


        /**
         * @param  resource $result
         *
         * @return int
         */
        function pg_affected_rows($result) {
            return 0;
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return bool
         */
        function pg_close($connection = null) {
            return false;
        }

        /**
         * @param  string $connection_string
         * @param  int    $connect_type [optional]
         *
         * @return resource
         */
        function pg_connect($connection_string, $connect_type = null) {
            /** @var resource $result */
            return $result = null;
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return string
         */
        function pg_dbname($connection = null) {
            return '';
        }

        /**
         * @param  resource $connection [optional]
         * @param  string   $data
         *
         * @return string
         */
        function pg_escape_identifier($connection = null, $data) {
            return '';
        }

        /**
         * @param  resource $connection [optional]
         * @param  string   $data
         *
         * @return string
         */
        function pg_escape_literal($connection = null, $data) {
            return '';
        }

        /**
         * @param  resource $connection [optional]
         * @param  string   $data
         *
         * @return string
         */
        function pg_escape_string($connection = null, $data) {
            return '';
        }

        /**
         * @param  resource $result
         * @param  int      $row  [optional]
         * @param  int      $type [optional]
         *
         * @return array|bool
         */
        function pg_fetch_array($result, $row=null, $type=PGSQL_BOTH) {
            return false;
        }

        /**
         * @param  resource $result
         *
         * @return bool
         */
        function pg_free_result($result) {
            return false;
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return string
         */
        function pg_host($connection = null) {
            return '';
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return string
         */
        function pg_last_error($connection = null) {
            return '';
        }

        /**
         * @param  resource $result
         *
         * @return int
         */
        function pg_num_fields($result) {
            return 0;
        }

        /**
         * @param  resource $result
         *
         * @return int
         */
        function pg_num_rows($result) {
            return 0;
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return int
         */
        function pg_port($connection = null) {
            return 0;
        }

        /**
         * @param  resource $connection [optional]
         * @param  string   $query
         *
         * @return resource
         */
        function pg_query($connection = null, $query) {
            /** @var resource $result */
            return $result = null;
        }

        /**
         * @param  resource $result
         * @param  int      $type [optional]
         *
         * @return int|string
         */
        function pg_result_status($result, $type = PGSQL_STATUS_LONG) {
            return 0;
        }

        /**
         * @param  resource $connection [optional]
         *
         * @return array
         */
        function pg_version($connection = null) {
            return [];
        }
    }


    if (!function_exists('sem_acquire')) {

        /**
         * @param  resource $sem_identifier
         * @param  bool     $nowait [optional]
         *
         * @return bool
         */
        function sem_acquire($sem_identifier, $nowait = false) {
            return false;
        }

        /**
         * @param  int $key
         * @param  int $max_acquire   [optional]
         * @param  int $perm          [optional]
         * @param  int $auto_release  [optional]
         *
         * @return resource
         */
        function sem_get($key, $max_acquire = null, $perm = null, $auto_release = null) {
            /** @var resource $result */
            return $result = null;
        }

        /**
         * @param  resource $sem_identifier
         *
         * @return bool
         */
        function sem_remove($sem_identifier) {
            return false;
        }
    }


    if (!class_exists('SQLite3', $autoLoad=false)) {

        define('SQLITE3_ASSOC',          1);
        define('SQLITE3_BOTH',           3);
        define('SQLITE3_NUM',            2);
        define('SQLITE3_OPEN_CREATE',    4);
        define('SQLITE3_OPEN_READWRITE', 2);

        class SQLite3 {

            /**
             * @param  string $filename
             * @param  int    $flags
             * @param  string $encryption_key
             */
            public function __construct($filename, $flags=null, $encryption_key=null) {
            }

            /**
             * @return int
             */
            public function changes() {
                return 0;
            }

            /**
             * @return bool
             */
            public function close() {
                return false;
            }

            /**
             * @param  string $value
             *
             * @return string
             */
            public static function escapeString($value) {
                return '';
            }

            /**
             * @param  string $query
             *
             * @return bool
             */
            public function exec($query) {
                return false;
            }

            /**
             * @return int
             */
            public function lastErrorCode() {
                return 0;
            }

            /**
             * @return string
             */
            public function lastErrorMsg() {
                return '';
            }

            /**
             * @return int
             */
            public function lastInsertRowID() {
                return 0;
            }

            /**
             * @param  string $query
             *
             * @return SQLite3Result|bool
             */
            public function query($query) {
                return false;
            }

            /**
             * @return array
             */
            public static function version() {
                return [];
            }
        }

        class SQLite3Result {

            /**
             * @param  int $mode [optional]
             *
             * @return array|bool
             */
            public function fetchArray($mode = null) {
                return false;
            }

            /**
             * @return bool
             */
            public function finalize() {
                return false;
            }

            /**
             * @return int
             */
            public function numColumns() {
                return 0;
            }

            /**
             * @return bool
             */
            public function reset() {
                return false;
            }
        }
    }


    if (!function_exists('stats_standard_deviation')) {

        /**
         * @param  array $values
         * @param  bool  $sample [optional]
         *
         * @return float|bool
         */
        function stats_standard_deviation(array $values, $sample=false) {
            return false;
        }
    }
}


namespace rosasurfer\bin\logwatch {

    /**
     * @param  string $message [optional]
     */
    function help($message = null) {}

    /**
     * @param  string $entry
     */
    function processEntry($entry) {}
}


namespace rosasurfer\util\apc\apc {

    /**
     * @param  array $array1
     * @param  array $array2
     *
     * @return int
     */
    function block_sort($array1, $array2) {
        return 0;
    }

    /**
     * @param  int  $s
     * @param  bool $long [optional]
     *
     * @return string
     */
    function bsize($s, $long=true) {
        return '';
    }

    /**
     * @param  string $name
     * @param  mixed  $value
     */
    function defaults($name, $value) {}

    /**
     * @param  int $ts
     *
     * @return string
     */
    function duration($ts) {
        return '';
    }

    /**
     * @param  resource $im
     * @param  int      $centerX
     * @param  int      $centerY
     * @param  int      $diameter
     * @param  int      $start
     * @param  int      $end
     * @param  int      $color1
     * @param  int      $color2
     * @param  string   $text
     * @param  int      $placeindex
     */
    function fill_arc($im, $centerX, $centerY, $diameter, $start, $end, $color1, $color2, $text='', $placeindex=0) {}

    /**
     * @param  resource $im
     * @param  int      $x
     * @param  int      $y
     * @param  int      $w
     * @param  int      $h
     * @param  int      $color1
     * @param  int      $color2
     * @param  string   $text
     * @param  int      $placeindex
     */
    function fill_box($im, $x, $y, $w, $h, $color1, $color2,$text='',$placeindex=0) {}

    /**
     * @return bool
     */
    function graphics_avail() {
        return false;
    }

    /**
     * @param  string $ob
     * @param  string $text
     *
     * @return string
     */
    function menu_entry($ob, $text) {
        return '';
    }

    /**
     * @param  string $s
     */
    function put_login_link($s='Login') {}

    /**
     * @param  string $key
     * @param  string $text
     * @param  string $extra
     *
     * @return string
     */
    function sortheader($key, $text, $extra='') {
        return '';
    }

    /**
     * @param  resource $im
     * @param  int      $centerX
     * @param  int      $centerY
     * @param  int      $diameter
     * @param  int      $start
     * @param  int      $end
     * @param  int      $color1
     * @param  string   $text
     * @param  int      $placeindex
     */
    function text_arc($im, $centerX, $centerY, $diameter, $start, $end, $color1, $text, $placeindex=0) {}
}


namespace rosasurfer\ministruts\demo\view {

    /**
     * @param  string $uri
     *
     * @return \rosasurfer\ministruts\url\VersionedUrl
     */
    function asset($uri) {
        return \rosasurfer\asset($uri);
    }

    /**
     * @param  mixed $var
     * @param  bool  $flushBuffers
     */
    function echoPre($var, $flushBuffers = true) {}
}

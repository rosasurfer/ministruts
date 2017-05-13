<?php declare(strict_types=1);

namespace {

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
         * @param  int|null     $ttl
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
         * @param  bool|null       $success
         *
         * @return mixed
         */
        function apc_fetch($key, &$success = null) {
            return false;
        }

        /**
         * @param  string|array $key
         * @param  mixed        $value
         * @param  int|null     $ttl
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
         * @param  int|null     $ttl
         *
         * @return bool|array
         */
        function apcu_add($keys, $values, $ttl = null) {
            return false;
        }

        /**
         * @param  string|string[] $key
         * @param  bool|null       $success
         *
         * @return mixed
         */
        function apcu_fetch($key, &$success = null) {
            return false;
        }
    }


    if (!function_exists('mysql_affected_rows')) {
        define('MYSQL_ASSOC', 1);
        define('MYSQL_NUM'  , 2);
        define('MYSQL_BOTH' , 3);

        /**
         * @param  resource|null $link_identifier
         *
         * @return int
         */
        function mysql_affected_rows($link_identifier = null) {
            return 0;
        }

        /**
         * @param  resource|null $link_identifier
         *
         * @return bool
         */
        function mysql_close($link_identifier = null) {
            return false;
        }

        /**
         * @param  string|null $server
         * @param  string|null $username
         * @param  string|null $password
         * @param  bool|null   $new_link
         * @param  int|null    $client_flags
         *
         * @return resource
         */
        function mysql_connect($server = null, $username = null, $password = null, $new_link = null, $client_flags = null) {
            /** @var resource $resource */
            $resource = fOpen($fileName='', $mode='');
            return $resource;
        }

        /**
         * @param  resource|null $link_identifier
         *
         * @return string
         */
        function mysql_error($link_identifier = null) {
            return '';
        }

        /**
         * @param  resource|null $link_identifier
         *
         * @return int
         */
        function mysql_errno($link_identifier = null) {
            return 0;
        }

        /**
         * @param  resource $result
         * @param  int|null $result_type
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
         * @param  resource|null $link_identifier
         *
         * @return string
         */
        function mysql_get_server_info($link_identifier = null) {
            return '';
        }

        /**
         * @param  resource|null $link_identifier
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
         * @param  string        $query
         * @param  resource|null $link_identifier
         *
         * @return resource|bool
         */
        function mysql_query($query, $link_identifier = null) {
            return false;
        }

        /**
         * @param  string        $unescaped_string
         * @param  resource|null $link_identifier
         *
         * @return string
         */
        function mysql_real_escape_string($unescaped_string, $link_identifier = null) {
            return '';
        }

        /**
         * @param  string        $database_name
         * @param  resource|null $link_identifier
         *
         * @return bool
         */
        function mysql_select_db($database_name, $link_identifier = null) {
            return false;
        }

        /**
         * @param  string        $charset
         * @param  resource|null $link_identifier
         *
         * @return bool
         */
        function mysql_set_charset($charset, $link_identifier = null) {
            return false;
        }
    }


    if (!function_exists('pcntl_signal')) {
        /**
         * @param  int          $signo
         * @param  callable|int $handler
         * @param  bool|null    $restart_syscalls
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


    if (!function_exists('sem_acquire')) {
        /**
         * @param  resource  $sem_identifier
         * @param  bool|null $nowait
         *
         * @return bool
         */
        function sem_acquire($sem_identifier, $nowait = false) {
            return false;
        }

        /**
         * @param  int      $key
         * @param  int|null $max_acquire
         * @param  int|null $perm
         * @param  int|null $auto_release
         *
         * @return resource
         */
        function sem_get($key, $max_acquire = null, $perm = null, $auto_release = null) {
            /** @var resource $resource */
            $resource = fOpen($fileName='', $mode='');
            return $resource;
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

    if (!defined('SID')) {
        define('SID', 'sessionName=sessionValue');
    }
}


namespace rosasurfer\bin\check_dns {

    /**
     * @param  string $domain
     * @param  string $type
     *
     * @return string
     */
    function queryDNS($domain, $type) {
        return '';
    }
}


namespace rosasurfer\bin\check_ip {

    /**
     * @return string
     */
    function getForwardedRemoteAddress() {
        return '';
    }

    /**
     * @param  string|array|null $names
     *
     * @return array
     */
    function getHeaders($names = null) {
        return [];
    }

    /**
     * @param  string|array $names
     *
     * @return string
     */
    function getHeaderValue($names) {
        return '';
    }

    /**
     * @return string
     */
    function getRemoteAddress() {
        return '';
    }

    /**
     * @param  string    $string
     * @param  bool|null $returnBytes
     *
     * @return bool|array
     */
    function isIPAddress($string, $returnBytes=false) {
        return false;
    }
}


namespace rosasurfer\cron\logwatch {

    /**
     * @param  string $message
     */
    function error($message) {}

    /**
     * @param  string|null $message
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
     * @param  int       $s
     * @param  bool|null $long
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

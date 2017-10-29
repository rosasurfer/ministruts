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


    if (!function_exists('mysql_affected_rows')) {
        define('MYSQL_ASSOC', 1);
        define('MYSQL_NUM'  , 2);
        define('MYSQL_BOTH' , 3);

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
            /** @var resource $resource */
            $resource = fOpen($fileName='', $mode='');
            return $resource;
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
        define('SIGINT' , 2);

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
     * @param  string|array $names [optional]
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
     * @param  string $string
     * @param  bool   $returnBytes [optional]
     *
     * @return bool|array
     */
    function isIPAddress($string, $returnBytes=false) {
        return false;
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

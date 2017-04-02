<?php declare(strict_types=1);

namespace {

    if (!function_exists('apache_request_headers')) {
        /**
         * @return array|false
         */
        function apache_request_headers() {}
    }


    if (!function_exists('apc_add')) {
        /**
         * @param  string $key
         * @param  mixed  $var
         * @param  int    $ttl
         * @return bool
         */
        function apc_add($key, $var, $ttl = null) {}

        /**
         * @param  string $key
         * @return bool
         */
        function apc_delete($key) {}

        /**
         * @param string|string[] $keys
         * @return bool|string[]
         */
        function apc_exists($keys) {}

        /**
         * @param  string|string[] $key
         * @param  bool            $success
         * @return mixed
         */
        function apc_fetch($key, &$success = null) {}

        /**
         * @param  string $key
         * @param  mixed  $var
         * @param  int    $ttl
         * @return bool
         */
        function apc_store($key, $var, $ttl = null) {}
    }


    if (!function_exists('mysql_affected_rows')) {
        /**
         * @param  resource $link_identifier
         * @return int
         */
        function mysql_affected_rows($link_identifier = null) {}

        /**
         * @param  resource $link_identifier
         * @return bool
         */
        function mysql_close($link_identifier = null) {}

        /**
         * @param  string $server
         * @param  string $username
         * @param  string $password
         * @param  bool   $new_link
         * @param  int    $client_flags
         * @return resource|bool
         */
        function mysql_connect($server = null, $username = null, $password = null, $new_link = null, $client_flags = null) {}

        /**
         * @param  resource $link_identifier
         * @return string
         */
        function mysql_error($link_identifier = null) {}

        /**
         * @param  resource $link_identifier
         * @return int
         */
        function mysql_errno($link_identifier = null) {}

        /**
         * @param  resource $result
         * @param  int      $result_type
         * @return array|bool
         */
        function mysql_fetch_array($result, $result_type = null) {}

        /**
         * @param  resource $result
         * @return bool
         */
        function mysql_free_result($result) {}

        /**
         * @param  resource $link_identifier
         * @return string
         */
        function mysql_get_server_info($link_identifier = null) {}

        /**
         * @param  resource $link_identifier
         * @return int
         */
        function mysql_insert_id($link_identifier = null) {}

        /**
         * @param  resource $result
         * @return int
         */
        function mysql_num_fields($result) {}

        /**
         * @param  resource $result
         * @return int
         */
        function mysql_num_rows($result) {}

        /**
         * @param  string   $query
         * @param  resource $link_identifier
         * @return resource|bool
         */
        function mysql_query($query, $link_identifier = null) {}

        /**
         * @param  string   $unescaped_string
         * @param  resource $link_identifier
         * @return string
         */
        function mysql_real_escape_string($unescaped_string, $link_identifier = null) {}

        /**
         * @param  string   $database_name
         * @param  resource $link_identifier
         * @return bool
         */
        function mysql_select_db($database_name, $link_identifier = null) {}

        /**
         * @param  string   $charset
         * @param  resource $link_identifier
         * @return bool
         */
        function mysql_set_charset($charset, $link_identifier = null) {}
    }


    if (!function_exists('sem_acquire')) {
        /**
         * @param  resource $sem_identifier
         * @return bool
         */
        function sem_acquire($sem_identifier) {}

        /**
         * @param  int $key
         * @param  int $max_acquire
         * @param  int $perm
         * @param  int $auto_release
         * @return resource|bool
         */
        function sem_get($key, $max_acquire = null, $perm = null, $auto_release = null) {}

        /**
         * @param  resource $sem_identifier
         * @return bool
         */
        function sem_remove($sem_identifier) {}
    }
}


namespace rosasurfer\bin\check_dns {

    /**
     * @param  string $domain
     * @param  string $type
     * @return string
     */
    function queryDNS($domain, $type) {}
}


namespace rosasurfer\bin\check_ip {

    /**
     * @return string
     */
    function getForwardedRemoteAddress() {}

    /**
     * @param  string|array $names
     * @return array
     */
    function getHeaders($names = null) {}

    /**
     * @param  string|array $names
     * @return string
     */
    function getHeaderValue($names) {}

    /**
     * @return string
     */
    function getRemoteAddress() {}

    /**
     * @param  string $string
     * @param  bool   $returnBytes
     * @return bool|array
     */
    function isIPAddress($string, $returnBytes=false) {}
}


namespace rosasurfer\cron\logwatch {

    /**
     * @param  string $message
     */
    function error($message) {}

    /**
     * @param  string $message
     */
    function help($message = null) {}

    /**
     * @param  string $entry
     */
    function processEntry($entry) {}
}

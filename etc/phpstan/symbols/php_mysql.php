<?php
declare(strict_types=1);


/** @var int */
const MYSQL_ASSOC = 1;

/** @var int */
const MYSQL_NUM = 2;

/** @var int */
const MYSQL_BOTH = 3;


/**
 * @link   http://www.php.net/manual/en/function.mysql-affected-rows.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return int
 */
function mysql_affected_rows($link_identifier = null): int {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-close.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return bool
 */
function mysql_close($link_identifier = null): bool {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-connect.php
 *
 * @param  string $server       [optional] - default: ini_get('mysql.default_host')
 * @param  string $username     [optional] - default: ini_get('mysql.default_user')
 * @param  string $password     [optional] - default: ini_get('mysql.default_password')
 * @param  bool   $new_link     [optional]
 * @param  int    $client_flags [optional]
 *
 * @return resource|false
 */
function mysql_connect(string $server='', string $username='', string $password='', bool $new_link=false, int $client_flags=0) {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-error.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return string
 */
function mysql_error($link_identifier = null) {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-errno.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return int
 */
function mysql_errno($link_identifier = null): int {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-fetch-array.php
 *
 * @param  resource $result
 * @param  int      $result_type [optional]
 *
 * @return string[]
 */
function mysql_fetch_array($result, int $result_type = MYSQL_BOTH): array {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-free-result.php
 *
 * @param  resource $result
 *
 * @return bool
 */
function mysql_free_result($result): bool {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-get-server-info.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return string|false
 */
function mysql_get_server_info($link_identifier = null) {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-insert-id.php
 *
 * @param  ?resource $link_identifier [optional]
 *
 * @return int
 */
function mysql_insert_id($link_identifier = null): int {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-num-fields.php
 *
 * @param  resource $result
 *
 * @return int
 */
function mysql_num_fields($result): int {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-num-rows.php
 *
 * @param  resource $result
 *
 * @return int|false
 */
function mysql_num_rows($result) {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-query.php
 *
 * @param  string    $query
 * @param  ?resource $link_identifier [optional]
 *
 * @return resource|bool
 */
function mysql_query(string $query, $link_identifier = null) {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-real-escape-string.php
 *
 * @param  string    $unescaped_string
 * @param  ?resource $link_identifier [optional]
 *
 * @return string
 */
function mysql_real_escape_string(string $unescaped_string, $link_identifier = null): string {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-select-db.php
 *
 * @param  string    $database_name
 * @param  ?resource $link_identifier [optional]
 *
 * @return bool
 */
function mysql_select_db(string $database_name, $link_identifier = null): bool {}


/**
 * @link   http://www.php.net/manual/en/function.mysql-set-charset.php
 *
 * @param  string    $charset
 * @param  ?resource $link_identifier [optional]
 *
 * @return bool
 */
function mysql_set_charset(string $charset, $link_identifier = null): bool {}

<?php

namespace {

    /**
     * Fetches all HTTP request headers from the current request. Works in the Apache, FastCGI, CLI, and FPM webservers.
     *
     * @return array<string, string>
     *
     * @link https://www.php.net/manual/en/function.apache-request-headers.php
     */
    function apache_request_headers(): array {}

    /**
     * Fetches all HTTP request headers from the current request. Alias of apache_request_headers().
     *
     * @return array<string, string>
     *
     * @link https://www.php.net/manual/en/function.getallheaders.php
     */
    function getallheaders(): array {}
}

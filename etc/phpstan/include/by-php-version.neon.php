<?php
declare(strict_types=1);

$typeAliases = [];

if (PHP_VERSION_ID < 80000) {
    $typeAliases['CurlHandleId'   ] = 'resource';
    $typeAliases['SysvSemaphoreId'] = 'resource';
}
else {
    $typeAliases['CurlHandleId'   ] = 'CurlHandle';
    $typeAliases['SysvSemaphoreId'] = 'SysvSemaphore';
}

$config = [];
$config['parameters']['phpVersion']  = PHP_VERSION_ID;
$config['parameters']['typeAliases'] = $typeAliases;

return $config;

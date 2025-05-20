<?php
declare(strict_types=1);

$typeAliases = [];

if (PHP_VERSION_ID >= 8_01_00) {
    $typeAliases['PgSqlConnectionId'] = 'PgSql\Connection';
    $typeAliases['PgSqlResultId'    ] = 'PgSql\Result';
}
else {
    $typeAliases['PgSqlConnectionId'] = 'resource';
    $typeAliases['PgSqlResultId'    ] = 'resource';
}
if (PHP_VERSION_ID >= 8_00_00) {
    $typeAliases['CurlHandleId'     ] = 'CurlHandle';
    $typeAliases['SysvSemaphoreId'  ] = 'SysvSemaphore';
}
else {
    $typeAliases['CurlHandleId'     ] = 'resource';
    $typeAliases['SysvSemaphoreId'  ] = 'resource';
}

$config = [];
$config['parameters']['phpVersion']  = PHP_VERSION_ID;
$config['parameters']['typeAliases'] = $typeAliases;

return $config;

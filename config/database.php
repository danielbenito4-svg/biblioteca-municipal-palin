<?php
declare(strict_types=1);
$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'biblioteca_palin',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];
if (is_file(__DIR__ . '/database.local.php')) {
    $config = array_replace($config, require __DIR__ . '/database.local.php');
}
return $config;

<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'driver' => Env::string('DB_CONNECTION', 'mysql'),
    'host' => Env::string('DB_HOST', '127.0.0.1'),
    'port' => Env::string('DB_PORT', '3306'),
    'database' => Env::string('DB_DATABASE', 'stayin_db'),
    'username' => Env::string('DB_USERNAME', 'root'),
    'password' => Env::string('DB_PASSWORD', ''),
    'charset' => Env::string('DB_CHARSET', 'utf8mb4'),
    'collation' => Env::string('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'socket' => Env::string('DB_SOCKET', '/Applications/MAMP/tmp/mysql/mysql.sock'),
    'slow_query_ms' => Env::int('DB_SLOW_QUERY_MS', 400),
];
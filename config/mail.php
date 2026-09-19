<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'mailer' => Env::string('MAIL_MAILER', 'log'), // log|smtp
    'host' => Env::string('MAIL_HOST', '127.0.0.1'),
    'port' => Env::int('MAIL_PORT', 1025),
    'username' => Env::string('MAIL_USERNAME', ''),
    'password' => Env::string('MAIL_PASSWORD', ''),
    'encryption' => Env::string('MAIL_ENCRYPTION', ''), // '', 'tls', 'ssl'
    'from' => [
        'address' => Env::string('MAIL_FROM_ADDRESS', 'hello@stayin.example'),
        'name' => Env::string('MAIL_FROM_NAME', 'StayIn'),
    ],
    'reply_to' => Env::string('MAIL_REPLY_TO', ''),
    'queue_table' => 'email_queue',
    'max_attempts' => Env::int('MAIL_MAX_ATTEMPTS', 3),
    'dev_redirect_all_to' => Env::string('MAIL_DEV_REDIRECT', ''),
];
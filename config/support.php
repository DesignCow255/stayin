<?php
return [
    'support_email' => \App\Core\Env::string('MAIL_FROM_ADDRESS', 'hello@stayin.example'),
    'support_name' => \App\Core\Env::string('MAIL_FROM_NAME', \App\Core\Env::string('APP_NAME', 'StayIn')),
    'support_phone' => \App\Core\Env::string('SUPPORT_PHONE', ''),
    'support_whatsapp' => \App\Core\Env::string('WHATSAPP_SUPPORT_NUMBER', ''),
];

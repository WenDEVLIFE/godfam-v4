<?php
/**
 * Mail Configuration
 * 
 * Fill in your SMTP details below to enable email sending.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/config/env.php';

return [
    'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME') ?: '',
    'password'   => getenv('MAIL_PASSWORD') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls', // 'tls' or 'ssl'
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@godfamchurch.com',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: "God's Family United Methodist Church",
];

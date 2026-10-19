<?php
require_once __DIR__ . '/../utils/CaptchaService.php';

$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
$data = [
    'secret'   => CaptchaService::getSecretKey(),
    'response' => 'test-token',
    'remoteip' => '127.0.0.1'
];
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
        'timeout' => 8
    ]
];
$context = stream_context_create($options);
$res = @file_get_contents($verifyUrl, false, $context);
echo "Google reCAPTCHA verification response:\n";
echo $res . "\n";

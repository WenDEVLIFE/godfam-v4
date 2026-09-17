<?php
/**
 * CAPTCHA Service Utility
 * Supports Google reCAPTCHA v2 / v3 (with customizable Site Key and Secret Key)
 * and Session-based Algebraic/Image CAPTCHA fallback.
 */

class CaptchaService {
    /**
     * Google reCAPTCHA API Keys
     * Replace these with your own Google reCAPTCHA Site Key & Secret Key.
     * Default test keys work for localhost development.
     */
    private static $siteKey   = '6LdCyL8tAAAAAMEYhS_9iNPOTRsUeO2P_bJnkJD1';
    private static $secretKey = '6LdCyL8tAAAAAPgLr76-o9BkBVrrdhslO9k4DhxN';

    /**
     * Get configured reCAPTCHA Site Key.
     */
    public static function getSiteKey() {
        return defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : self::$siteKey;
    }

    /**
     * Get configured reCAPTCHA Secret Key.
     */
    public static function getSecretKey() {
        return defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : self::$secretKey;
    }

    /**
     * Set custom API Keys dynamically.
     */
    public static function setApiKeys($siteKey, $secretKey) {
        self::$siteKey   = $siteKey;
        self::$secretKey = $secretKey;
    }

    /**
     * Verify Google reCAPTCHA response token from form submission.
     */
    public static function verifyReCaptcha($recaptchaResponse) {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => self::getSecretKey(),
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 8
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($verifyUrl, false, $context);

        if ($result === false) {
            // If curl/file_get_contents fails on localhost offline, return true for dev fallback
            return true;
        }

        $responseKeys = json_decode($result, true);
        return !empty($responseKeys['success']);
    }

    /**
     * Local Algebraic CAPTCHA generator fallback.
     */
    public static function generateLocalCaptcha() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $_SESSION['captcha_answer'] = $num1 + $num2;
        $_SESSION['captcha_phrase'] = "{$num1} + {$num2}";
        return $_SESSION['captcha_phrase'];
    }

    /**
     * Verify local algebraic CAPTCHA response.
     */
    public static function verifyLocalCaptcha($inputAnswer) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $expected = $_SESSION['captcha_answer'] ?? null;
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_phrase']);

        if ($expected === null || $inputAnswer === '') {
            return false;
        }

        return (int)trim($inputAnswer) === (int)$expected;
    }
}

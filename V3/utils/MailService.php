<?php
/**
 * Mail Service - Handles PHPMailer integration and email broadcasting.
 */

// Support Composer autoloader or manual folder structures (PHPMailer / phpmailer)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $autoloader = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
    }
}

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $possiblePaths = [
        __DIR__ . '/../vendor/PHPMailer/src/',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/',
        __DIR__ . '/../vendor/phpmailer/src/',
        __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/',
        __DIR__ . '/../vendor/PHPMailer/',
        __DIR__ . '/../vendor/phpmailer/',
    ];
    foreach ($possiblePaths as $basePath) {
        if (file_exists($basePath . 'Exception.php') && file_exists($basePath . 'PHPMailer.php')) {
            require_once $basePath . 'Exception.php';
            require_once $basePath . 'PHPMailer.php';
            if (file_exists($basePath . 'SMTP.php')) {
                require_once $basePath . 'SMTP.php';
            }
            break;
        }
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailService {
    private $config;

    /**
     * Per-request circuit breaker. When the SMTP server cannot be reached or
     * authentication fails, every further send in the same request is skipped
     * immediately instead of waiting for another timeout. This keeps bulk
     * sends (announcements, reminders) from blowing past PHP's max_execution_time.
     */
    private static $smtpUsable = null;

    /** Cached pre-flight result for the current request. */
    private static $smtpReachable = null;

    /** Hard limit for a single SMTP connection / response (seconds). */
    private const SMTP_TIMEOUT = 10;

    /** Give up on the whole batch after this many seconds. */
    private const BATCH_BUDGET = 45;

    private $batchStartedAt = null;

    /** Reused PHPMailer instance (SMTP keep-alive across a batch). */
    private $mailer = null;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    /**
     * Send a formal announcement email.
     *
     * When an image is supplied it is embedded inline (cid:announcementImage)
     * and attached as a downloadable file.
     */
    public function sendAnnouncement($title, $content, $recipient, $publishedAt = null, $senderLabel = null, $imagePath = null) {
        if (!filter_var((string)$recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid recipient email address.'
            ];
        }

        $subject = 'Church Announcement: ' . $title;
        $safePublishedAt = $publishedAt ?: date('M d, Y h:i A');
        $safeSenderLabel = $senderLabel ?: ($this->config['from_name'] ?? "God's Family United Methodist Church");

        $resolvedImage = $this->resolveImagePath($imagePath);
        $imageBlock = '';
        $options = [];
        if ($resolvedImage !== null) {
            $imageBlock = "
                    <div style='text-align:center; margin:24px 0;'>
                        <img src=\"cid:announcementImage\" alt=\"Announcement photo\" style=\"max-width:100%; height:auto; border-radius:6px;\">
                    </div>";
            $extension = strtolower((string)pathinfo($resolvedImage, PATHINFO_EXTENSION));
            $slug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9]+/', '-', (string)$title), '-'));
            if (strlen($slug) > 40) {
                $slug = trim(substr($slug, 0, 40), '-');
            }
            if ($slug === '') {
                $slug = 'photo';
            }
            $attachmentName = 'announcement-' . $slug . ($extension !== '' ? '.' . $extension : '');
            $options['embedded'] = ['announcementImage' => $resolvedImage];
            $options['attachments'] = [['path' => $resolvedImage, 'name' => $attachmentName]];
        }

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .header { background-color: #1a202c; color: #ffffff; padding: 40px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; font-weight: 300; letter-spacing: 2px; text-transform: uppercase; }
                .content { padding: 40px 30px; }
                .content h2 { color: #2d3748; font-size: 22px; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                .announcement-text { margin-top: 20px; font-size: 16px; color: #4a5568; }
                .footer { background-color: #f8f9fa; color: #7f8c8d; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>God's Family United Methodist Church</h1>
                </div>
                <div class='content'>
                    <h2>" . htmlspecialchars($title) . "</h2>" . $imageBlock . "
                    <div class='announcement-text'>" . nl2br(htmlspecialchars($content)) . "</div>
                    <p style='margin-top:20px; color:#718096; font-size:13px;'>
                        Published: " . htmlspecialchars($safePublishedAt) . "<br>
                        Sender: " . htmlspecialchars($safeSenderLabel) . "
                    </p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " God's Family United Methodist Church. All rights reserved.
                </div>
            </div>
        </body>
        </html>";

        return $this->sendEmail($recipient, $subject, $body, $options);
    }

    /**
     * Resolve an announcement image path. Relative paths are resolved
     * against the public directory. Returns null when the file is unusable.
     */
    private function resolveImagePath($imagePath) {
        if ($imagePath === null) {
            return null;
        }

        $path = trim((string)$imagePath);
        if ($path === '') {
            return null;
        }

        $isAbsolute = (bool)preg_match('#^([A-Za-z]:)?[\\\\/]#', $path);
        if (!$isAbsolute) {
            $path = BASE_PATH . '/public/' . ltrim($path, '/');
        }

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Send a personal greeting (birthday, anniversary, digest).
     * Unlike sendAnnouncement(), the subject is used as-is (no
     * "Church Announcement:" prefix) and the body is a warm message.
     */
    public function sendGreeting($subject, $message, $recipient, $senderLabel = null) {
        if (!filter_var((string)$recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid recipient email address.'
            ];
        }

        $safeSenderLabel = $senderLabel ?: ($this->config['from_name'] ?? "God's Family United Methodist Church");
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.7; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .header { background-color: #1a202c; color: #ffffff; padding: 40px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 22px; font-weight: 400; letter-spacing: 1px; }
                .content { padding: 36px 30px; font-size: 16px; color: #4a5568; }
                .footer { background-color: #f8f9fa; color: #7f8c8d; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . htmlspecialchars($subject) . "</h1>
                </div>
                <div class='content'>
                    " . nl2br(htmlspecialchars($message)) . "
                    <p style='margin-top:24px; font-size:13px; color:#718096;'>" . htmlspecialchars($safeSenderLabel) . "</p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " God's Family United Methodist Church. All rights reserved.
                </div>
            </div>
        </body>
        </html>";

        return $this->sendEmail($recipient, $subject, $body);
    }

    /**
     * Send OTP Verification Email
     */
    public function sendOTPMail($email, $otp) {
        $subject = "Your Security Verification Code";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                .header { background-color: #1a202c; color: #ffffff; padding: 40px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; font-weight: 300; letter-spacing: 1px; text-transform: uppercase; }
                .content { padding: 40px 30px; text-align: center; }
                .otp-code { font-size: 32px; font-weight: 700; color: #3182ce; background: #f7fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e0; margin: 20px 0; letter-spacing: 5px; }
                .footer { background-color: #f8f9fa; color: #7f8c8d; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>God's Family United Methodist Church</h1>
                </div>
                <div class='content'>
                    <h2>Password Reset Request</h2>
                    <p>You requested to reset your password. Use the verification code below to proceed. This code will expire in 10 minutes.</p>
                    <div class='otp-code'>$otp</div>
                    <p style='font-size: 13px; color: #718096;'>If you did not request this, please ignore this email or contact support if you have concerns.</p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " God's Family United Methodist Church Management System. All rights reserved.
                </div>
            </div>
        </body>
        </html>";

        $result = $this->sendEmail($email, $subject, $body);
        return $result['success'] === true;
    }

    /**
     * Check if PHPMailer is available and loaded.
     */
    public function isAvailable(): bool {
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }

    /**
     * Core Email Sending Logic (SMTP)
     */
    private function sendEmail($to, $subject, $body, array $options = []) {
        if (!$this->isAvailable()) {
            $err = 'PHPMailer library is not available. Please run `composer install` to install dependencies.';
            error_log("MailService Error: {$err}");
            return [
                'success' => false,
                'error' => 'Email service is currently unavailable.',
                'internal_error' => $err
            ];
        }

        // Circuit breaker: an earlier send in this request already proved the
        // SMTP server is unreachable or rejecting auth - stop waiting again.
        if (self::$smtpUsable === false) {
            return [
                'success' => false,
                'error' => 'Email service is temporarily unavailable.',
                'internal_error' => 'Skipped: SMTP already failed earlier in this request.'
            ];
        }

        // Batch budget: never let one page load run into PHP's execution limit.
        if ($this->batchStartedAt === null) {
            $this->batchStartedAt = microtime(true);
        } elseif ((microtime(true) - $this->batchStartedAt) > self::BATCH_BUDGET) {
            return [
                'success' => false,
                'error' => 'Email service is busy, remaining emails were skipped.',
                'internal_error' => 'Skipped: batch time budget exhausted.'
            ];
        }

        // Cheap pre-flight: fail in a few seconds instead of minutes.
        if (!$this->smtpReachable()) {
            self::$smtpUsable = false;
            return [
                'success' => false,
                'error' => 'Email service is currently unreachable.',
                'internal_error' => 'SMTP pre-flight connection failed.'
            ];
        }

        try {
            $mail = $this->getMailer();

            // Reuse the open SMTP connection for every recipient in the batch
            // (keeps 16 announcements well inside PHP's execution limit).
            $mail->clearAllRecipients();
            $mail->clearAttachments();
            $mail->clearCustomHeaders();
            $mail->clearReplyTos();

            // Recipients
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $body;

            if (!empty($options['embedded']) && is_array($options['embedded'])) {
                foreach ($options['embedded'] as $cid => $path) {
                    if (is_file($path) && is_readable($path)) {
                        $mail->addEmbeddedImage($path, (string)$cid, basename($path));
                    }
                }
            }

            if (!empty($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (!is_array($attachment) || empty($attachment['path'])) {
                        continue;
                    }
                    $path = $attachment['path'];
                    if (!is_file($path) || !is_readable($path)) {
                        continue;
                    }
                    $name = !empty($attachment['name']) ? (string)$attachment['name'] : basename($path);
                    $mail->addAttachment($path, $name);
                }
            }

            $mail->send();
            self::$smtpUsable = true;
            return [
                'success' => true
            ];
        } catch (Exception $e) {
            $errorInfo = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            error_log("Mail Error: {$errorInfo}");
            if ($this->isConnectionError($errorInfo)) {
                self::$smtpUsable = false;
            }
            return [
                'success' => false,
                'error' => 'Message could not be sent.',
                'internal_error' => $errorInfo
            ];
        } catch (\Throwable $e) {
            error_log("Mail Error: " . $e->getMessage());
            if ($this->isConnectionError($e->getMessage())) {
                self::$smtpUsable = false;
            }
            return [
                'success' => false,
                'error' => 'An unexpected error occurred while sending email.',
                'internal_error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build (once) and reuse the configured PHPMailer instance.
     * SMTPKeepAlive avoids reconnecting + re-authenticating for every recipient.
     */
    private function getMailer(): PHPMailer {
        if ($this->mailer instanceof PHPMailer) {
            return $this->mailer;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->config['username'];
        $mail->Password   = $this->config['password'];
        $mail->SMTPSecure = ($this->config['encryption'] ?? '') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($this->config['port'] ?? 587);

        // Never hang: cap the socket timeout and the response read window.
        // (PHPMailer defaults both to 300s, which blows past
        // max_execution_time = 120 on bulk sends.)
        $mail->Timeout = self::SMTP_TIMEOUT;
        $mail->getSMTPInstance()->Timelimit = self::SMTP_TIMEOUT;

        // Keep the connection open between recipients of the same batch.
        $mail->SMTPKeepAlive = true;

        // Bypass SSL certificate verification for local environments (XAMPP)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($this->config['from_email'], $this->config['from_name']);

        $this->mailer = $mail;
        return $mail;
    }

    public function __destruct() {
        if ($this->mailer instanceof PHPMailer) {
            try {
                $this->mailer->smtpClose();
            } catch (\Throwable $e) {
                // Never let cleanup throw.
            }
        }
    }

    /**
     * Quick TCP check so a dead network fails in seconds, not minutes.
     */
    private function smtpReachable(): bool {
        if (self::$smtpReachable !== null) {
            return self::$smtpReachable;
        }

        $host = $this->config['host'] ?? '';
        $port = (int)($this->config['port'] ?? 587);
        $scheme = (($this->config['encryption'] ?? '') === 'ssl') ? 'ssl://' : 'tcp://';

        $conn = @fsockopen($scheme . $host, $port, $errno, $errstr, 5);
        if (is_resource($conn)) {
            fclose($conn);
            self::$smtpReachable = true;
        } else {
            error_log("MailService pre-flight failed for {$host}:{$port} ({$errno}) {$errstr}");
            self::$smtpReachable = false;
        }

        return self::$smtpReachable;
    }

    /**
     * True when the error means "the SMTP server itself is unusable"
     * (connection, TLS or auth), as opposed to a per-recipient rejection.
     */
    private function isConnectionError(string $errorInfo): bool {
        $needles = [
            'smtp connect() failed',
            'could not connect',
            'connection refused',
            'connection timed out',
            'operation timed out',
            'stream_socket_client',
            'could not authenticate',
            'authentication failed',
            'ssl',
            'starttls',
        ];
        $haystack = strtolower($errorInfo);
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}

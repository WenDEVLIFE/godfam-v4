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

    public function __construct() {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    /**
     * Send a formal announcement email.
     */
    public function sendAnnouncement($title, $content, $recipient, $publishedAt = null, $senderLabel = null) {
        if (!filter_var((string)$recipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid recipient email address.'
            ];
        }

        $subject = 'Church Announcement: ' . $title;
        $safePublishedAt = $publishedAt ?: date('M d, Y h:i A');
        $safeSenderLabel = $senderLabel ?: ($this->config['from_name'] ?? "God's Family United Methodist Church");
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
                    <h2>" . htmlspecialchars($title) . "</h2>
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
     * Core Email Sending Logic (SMTP)
     */
    private function sendEmail($to, $subject, $body) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];

            // Bypass SSL certificate verification for local environments (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return [
                'success' => true
            ];
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            return [
                'success' => false,
                'error' => 'Message could not be sent.',
                'internal_error' => $mail->ErrorInfo
            ];
        }
    }
}

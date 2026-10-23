<?php
/**
 * TestNotifier - Sends real test emails through the shared MailService so the
 * wording and image handling match production delivery exactly.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/utils/MailService.php';
require_once BASE_PATH . '/utils/Greetings.php';

class TestNotifier {
    private $pdo;
    private $mailService;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    /**
     * Send a test announcement, optionally with a picture.
     */
    public function sendAnnouncementEmail(string $email, ?string $imagePath, string $title, string $content): array {
        $label = $imagePath ? 'Announcement with picture' : 'Announcement';

        $result = $this->mailService->sendAnnouncement(
            $title,
            $content,
            $email,
            date('M d, Y h:i A'),
            'Test Notifications',
            $imagePath
        );

        return $this->buildResult($label, $result);
    }

    /**
     * Send the shared birthday greeting to the supplied address.
     */
    public function sendBirthdayEmail(string $email): array {
        $greeting = buildBirthdayGreeting(['full_name' => 'Sample Member']);

        $result = $this->mailService->sendGreeting(
            $greeting['subject'],
            $greeting['message'],
            $email,
            "God's Family United Methodist Church"
        );

        return $this->buildResult('Birthday greeting', $result);
    }

    /**
     * Send the shared wedding anniversary greeting to the supplied address.
     */
    public function sendAnniversaryEmail(string $email): array {
        $greeting = buildAnniversaryGreeting(['full_name' => 'Sample Member']);

        $result = $this->mailService->sendGreeting(
            $greeting['subject'],
            $greeting['message'],
            $email,
            "God's Family United Methodist Church (GFUMC)"
        );

        return $this->buildResult('Wedding anniversary greeting', $result);
    }

    /**
     * Send a test OTP email using the production template and format.
     * No password_resets row is written -- delivery check only.
     */
    public function sendOtpEmail(string $email): array {
        // Same 6-digit format as AuthController::requestOTP()
        $otp = sprintf('%06d', random_int(100000, 999999));

        $success = (bool)$this->mailService->sendOTPMail($email, $otp);
        $detail = $success
            ? 'Email accepted by the SMTP server. Preview code: ' . $otp
            : 'Email could not be sent.';

        return [
            'success' => $success,
            'label' => 'OTP email',
            'detail' => $detail,
        ];
    }

    /**
     * Return the newest announcement image as a path relative to public/.
     */
    public function latestAnnouncementImage(): ?string {
        $directory = BASE_PATH . '/public/uploads/announcements';
        if (!is_dir($directory)) {
            return null;
        }

        $files = glob($directory . '/*');
        if (!is_array($files)) {
            return null;
        }

        $newest = null;
        $newestTime = null;
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $mtime = @filemtime($file);
            if ($mtime === false) {
                continue;
            }
            if ($newest === null || $mtime > $newestTime) {
                $newest = $file;
                $newestTime = $mtime;
            }
        }

        if ($newest === null) {
            return null;
        }

        return 'uploads/announcements/' . basename($newest);
    }

    /**
     * Normalize a MailService result into the test-tool envelope.
     */
    private function buildResult(string $label, array $result): array {
        $success = !empty($result['success']);
        if ($success) {
            $detail = 'Email accepted by the SMTP server.';
        } else {
            $detail = (string)($result['error'] ?? 'Email could not be sent.');
            if (!empty($result['internal_error'])) {
                $detail .= ' ' . (string)$result['internal_error'];
            }
        }

        return [
            'success' => $success,
            'label' => $label,
            'detail' => $detail,
        ];
    }
}

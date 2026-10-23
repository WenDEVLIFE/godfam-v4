<?php
/**
 * Greetings - Shared plain-text builders for email greetings only.
 *
 * These builders return RAW text. MailService::sendGreeting() escapes the
 * message once before rendering the HTML email.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!function_exists('gfumc_first_name')) {
    /**
     * Return the first word of a full name in title-case.
     */
    function gfumc_first_name(string $fullName): string {
        $trimmed = trim($fullName);
        if ($trimmed === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $trimmed);
        $first = $parts[0] ?? '';
        if ($first === '') {
            return '';
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($first, 'UTF-8') : strtolower($first);
        return function_exists('mb_convert_case')
            ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8')
            : ucfirst($lower);
    }
}

if (!function_exists('buildBirthdayGreeting')) {
    function buildBirthdayGreeting(array $celebrant): array {
        $name = (string)($celebrant['full_name'] ?? '');
        $subject = "🎉 Happy Birthday from God's Family UMC, " . $name . "!";
        $message =
            "Dear " . $name . ",\n\n"
            . "Happy Birthday! 🎂🎉\n\n"
            . "God's Family United Methodist Church wishes you a wonderful and blessed birthday! May God continue to guide you, strengthen your faith, and fill your life with love, happiness, peace, and good health.\n\n"
            . "May this new year of your life bring you more blessings, meaningful opportunities, and beautiful moments with your family and loved ones.\n\n"
            . "Always remember that you are a valued member of our church family. May God's grace and love be with you today and always.\n\n"
            . "Once again, Happy Birthday! May the Lord bless you abundantly.\n\n"
            . "With prayers and blessings,\n"
            . "God's Family United Methodist Church\n"
            . "Peñaranda, Nueva Ecija\n"
            . "Camille Jane";

        return ['subject' => $subject, 'message' => $message];
    }
}

if (!function_exists('buildAnniversaryGreeting')) {
    function buildAnniversaryGreeting(array $celebrant): array {
        $name = (string)($celebrant['full_name'] ?? '');
        $subject = "💖 Happy Wedding Anniversary from God's Family UMC!";
        $message =
            "Happy Wedding Anniversary, " . $name . "! ❤️\n\n"
            . "May God continue to bless your marriage with lasting love, joy, understanding, and faithfulness. May He strengthen your bond and guide you both as you continue your journey together.\n\n"
            . "May your love grow deeper and your home remain filled with peace and God's blessings.\n\n"
            . "Happy Anniversary, and God bless your marriage always!\n\n"
            . "With prayers and love,\n"
            . "God's Family United Methodist Church (GFUMC)";

        return ['subject' => $subject, 'message' => $message];
    }
}


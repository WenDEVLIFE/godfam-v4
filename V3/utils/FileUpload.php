<?php
/**
 * FileUpload Utility
 * Strict server-side validation for image and file uploads.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

class FileUpload {
    private static $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/jpg'  => 'jpg',
        'image/webp' => 'webp'
    ];

    /**
     * Validate and process image upload securely.
     * Checks file existence, upload errors, max size, extension, and real MIME type.
     *
     * @param array $file $_FILES['input_name']
     * @param string $targetSubDir Subfolder inside assets/uploads/
     * @param int $maxSizeMB Max allowed file size in MB (default 5MB)
     * @return array ['success' => bool, 'path' => string, 'error' => string]
     */
    public static function processImage($file, $targetSubDir = 'members', $maxSizeMB = 5) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid upload parameters.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => "File size exceeds server limit (Maximum {$maxSizeMB}MB)."];
            default:
                return ['success' => false, 'error' => 'Unknown upload error occurred.'];
        }

        $maxBytes = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return ['success' => false, 'error' => "File is too large ({$file['size']} bytes). Maximum allowed size is {$maxSizeMB}MB."];
        }

        // Validate file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'error' => 'Invalid file format. Only JPEG, JPG, PNG, and WEBP images are allowed.'];
        }

        // Strict MIME type validation using finfo / mime_content_type
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } else if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        } else {
            $mime = $file['type'];
        }

        if (!array_key_exists($mime, self::$allowedMimeTypes)) {
            return ['success' => false, 'error' => 'Security Warning: Content type (' . htmlspecialchars($mime) . ') is not a valid image.'];
        }

        // Integrity check: verify actual image dimensions
        if (!@getimagesize($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Uploaded file is corrupted or not a valid image file.'];
        }

        $uploadDir = BASE_PATH . '/public/assets/uploads/' . trim($targetSubDir, '/') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFileName = $targetSubDir . '_' . uniqid() . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success'   => true,
                'path'      => 'assets/uploads/' . trim($targetSubDir, '/') . '/' . $newFileName,
                'file_name' => $newFileName
            ];
        }

        return ['success' => false, 'error' => 'Failed to move uploaded file to destination folder.'];
    }
}

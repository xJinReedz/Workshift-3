<?php
/**
 * File Storage and Upload Service
 */

namespace WorkShift\Services;

use WorkShift\Models\User;
use Exception;

class FileStorageService
{
    private string $uploadDir;
    private array $allowedMimes;
    private int $maxSize;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $storage = $config['storage'] ?? [];

        $this->uploadDir = $storage['upload_dir'] ?? (dirname(__DIR__, 2) . '/storage/uploads');
        $this->allowedMimes = $storage['allowed_mimes'] ?? [];
        $this->maxSize = ($storage['max_file_size_mb'] ?? 25) * 1024 * 1024;

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload(array $file, int $userId): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: {$file['error']}");
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception("File exceeds maximum allowed size of 25 MB.");
        }

        // Check user storage limit
        $userModel = new User();
        $user = $userModel->findById($userId);
        if ($user && !PlanLimitService::hasStorageAvailable($user, $file['size'])) {
            throw new Exception("Account storage quota exceeded. Upgrade to WorkShift Pro for 50 GB storage.");
        }

        // Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($detectedMime, $this->allowedMimes)) {
            throw new Exception("File type not permitted ({$detectedMime}). Allowed types: images, PDFs, docs, spreadsheets, zip archives.");
        }

        $extension = $this->allowedMimes[$detectedMime];
        $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->uploadDir . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Could not save uploaded file to storage.");
        }

        // Update user storage quota
        $userModel->adjustStorage($userId, (int)$file['size']);

        return [
            'original_name' => basename($file['name']),
            'stored_filename' => $storedFilename,
            'mime_type' => $detectedMime,
            'file_size' => (int)$file['size'],
            'file_path' => $destination,
        ];
    }

    public function serveDownload(array $fileRecord): void
    {
        $filePath = $this->uploadDir . '/' . $fileRecord['stored_filename'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        $origName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $fileRecord['original_name']);

        // Set safe headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileRecord['mime_type']);
        header('Content-Disposition: attachment; filename="' . $origName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }

    public function delete(string $storedFilename, int $userId, int $fileSize): void
    {
        $filePath = $this->uploadDir . '/' . $storedFilename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $userModel = new User();
        $userModel->adjustStorage($userId, -1 * $fileSize);
    }
}

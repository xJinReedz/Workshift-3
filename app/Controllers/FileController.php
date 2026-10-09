<?php
/**
 * File Controller (Secure File Downloads)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\TaskFile;
use WorkShift\Models\Client;
use WorkShift\Services\FileStorageService;

class FileController extends Controller
{
    private TaskFile $fileModel;
    private FileStorageService $storageService;

    public function __construct()
    {
        parent::__construct();
        $this->fileModel = new TaskFile();
        $this->storageService = new FileStorageService();
    }

    public function download(Request $request, string $id): void
    {
        $file = $this->fileModel->findById($id);
        if (!$file) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        $authorized = false;
        if (Auth::check()) {
            if ((string)$file['owner_id'] === (string)Auth::id() || Auth::isClient()) {
                $authorized = true;
            }
        } else {
            $portalToken = $request->input('portal_token');
            if ($portalToken) {
                $clientModel = new Client();
                $client = $clientModel->findByPortalToken($portalToken);
                if ($client && (string)$client['id'] === (string)$file['client_id']) {
                    $authorized = true;
                }
            }
        }

        if (!$authorized) {
            http_response_code(403);
            echo "Access denied to this file.";
            exit;
        }

        $this->storageService->serveDownload($file);
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $file = $this->fileModel->findById($id);
        if (!$file || (string)$file['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized file deletion', 'UNAUTHORIZED', 403);
            return;
        }

        $this->storageService->delete($file['filename'] ?? $file['storage_path'], $ownerId, (int)$file['file_size']);
        $this->fileModel->delete($id);

        json_success(['message' => 'File deleted successfully']);
    }
}

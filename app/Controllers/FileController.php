<?php
/**
 * File Controller (Secure File Downloads)
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
        $file = $this->fileModel->findById((int)$id);
        if (!$file) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        // Authorization check
        $authorized = false;
        if (Auth::check()) {
            if ((int)$file['user_id'] === Auth::id()) {
                $authorized = true;
            }
        } else {
            $portalToken = $request->input('portal_token');
            if ($portalToken) {
                $clientModel = new Client();
                $client = $clientModel->findByPortalToken($portalToken);
                if ($client && (int)$client['id'] === (int)$file['client_id']) {
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
        $userId = Auth::id();

        $file = $this->fileModel->findById((int)$id);
        if (!$file || (int)$file['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $this->storageService->delete($file['stored_filename'], $userId, (int)$file['file_size']);
        $this->fileModel->delete((int)$id);

        $this->json(['success' => true]);
    }
}

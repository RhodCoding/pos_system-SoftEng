<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Backup.php';

class BackupApi extends ApiHandler {
    private $backupModel;

    public function __construct() {
        parent::__construct();
        $this->backupModel = new Backup();
    }

    public function handleRequest() {
        $this->requireAuth();
        $this->requireAdmin();

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        if ($method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }

        switch ($action) {
            case 'create':
                $this->createBackup();
                break;
            case 'export':
                $this->exportData();
                break;
            default:
                $this->sendError('Invalid action');
        }
    }

    private function createBackup() {
        try {
            $filename = $this->backupModel->createBackup();
            
            // Set headers for file download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filename));
            
            // Output file
            readfile($filename);
            
            // Delete the temporary file
            unlink($filename);
            exit();
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function exportData() {
        $type = $_GET['type'] ?? null;
        if (!in_array($type, ['sales', 'inventory', 'customers', 'audit'])) {
            $this->sendError('Invalid export type');
        }

        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'user_id' => $_GET['user_id'] ?? null
        ];

        try {
            $filename = $this->backupModel->exportData($type, $filters);
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filename));
            
            // Output file
            readfile($filename);
            
            // Delete the temporary file
            unlink($filename);
            exit();
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new BackupApi();
$api->handleRequest();

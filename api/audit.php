<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/AuditTrail.php';

class AuditApi extends ApiHandler {
    private $auditModel;

    public function __construct() {
        parent::__construct();
        $this->auditModel = new AuditTrail();
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
            case 'history':
                $this->getAuditHistory();
                break;
            case 'entity':
                $this->getEntityHistory();
                break;
            default:
                $this->sendError('Invalid action');
        }
    }

    private function getAuditHistory() {
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action_type'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $limit = (int)($this->sanitizeInput($_GET['limit'] ?? '50', 'integer'));
        $offset = (int)($this->sanitizeInput($_GET['offset'] ?? '0', 'integer'));

        try {
            $history = $this->auditModel->getAuditHistory($filters, $limit, $offset);
            $this->sendResponse(['history' => $history]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getEntityHistory() {
        $entityType = $_GET['entity_type'] ?? null;
        $entityId = $_GET['entity_id'] ?? null;

        if (!$entityType || !$entityId) {
            $this->sendError('Entity type and ID are required');
        }

        try {
            $history = $this->auditModel->getEntityHistory($entityType, $entityId);
            $this->sendResponse(['history' => $history]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new AuditApi();
$api->handleRequest();

<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Settings.php';

class SettingsApi extends ApiHandler {
    private $settingsModel;

    public function __construct() {
        parent::__construct();
        $this->settingsModel = new Settings();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        $this->requireAuth();

        switch ($method) {
            case 'GET':
                if ($action === 'all') {
                    $this->requireAdmin();
                    $this->getAllSettings();
                } else {
                    $this->getSetting();
                }
                break;

            case 'POST':
                $this->requireAdmin();
                if ($action === 'initialize') {
                    $this->initializeSettings();
                } else {
                    $this->updateSettings();
                }
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllSettings() {
        try {
            $settings = $this->settingsModel->findAll();
            $this->sendResponse(['settings' => $settings]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getSetting() {
        $key = $_GET['key'] ?? null;
        if (!$key) {
            $this->sendError('Setting key is required');
        }

        try {
            $value = $this->settingsModel->getSetting($key);
            if ($value === null) {
                $this->sendError('Setting not found', 404);
            }
            $this->sendResponse(['setting' => [
                'key' => $key,
                'value' => $value
            ]]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function updateSettings() {
        $data = $this->getRequestData();
        if (empty($data['settings'])) {
            $this->sendError('No settings provided');
        }

        try {
            $success = $this->settingsModel->setMultiple($data['settings']);
            if (!$success) {
                $this->sendError('Failed to update settings');
            }
            $this->sendResponse(['message' => 'Settings updated successfully']);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function initializeSettings() {
        try {
            $success = $this->settingsModel->initializeDefaultSettings();
            if (!$success) {
                $this->sendError('Failed to initialize settings');
            }
            $this->sendResponse(['message' => 'Default settings initialized successfully']);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new SettingsApi();
$api->handleRequest();

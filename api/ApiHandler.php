<?php
require_once __DIR__ . '/../includes/Sanitizer.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/RateLimiter.php';

class ApiHandler {
    protected $logger;
    protected $rateLimiter;

    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->rateLimiter = RateLimiter::getInstance();
    }

    protected function checkRateLimit() {
        if (!$this->rateLimiter->checkLimit()) {
            $resetTime = $this->rateLimiter->getResetTime();
            $this->sendError('Rate limit exceeded. Please try again in ' . $resetTime . ' seconds.', 429);
        }
    }

    protected function sendResponse($data, $success = true) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            ...$data
        ]);
        exit;
    }

    protected function sendError($message, $code = 400) {
        http_response_code($code);
        $this->sendResponse(['message' => $message], false);
    }

    protected function requireAuth() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $this->sendError('Unauthorized', 401);
        }
        return $_SESSION['user_id'];
    }

    protected function requireAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->sendError('Unauthorized access', 403);
        }
    }

    protected function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            return $_GET;
        }
        
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        $data = [];
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
        } else if ($method === 'POST') {
            $data = $_POST;
        } else {
            parse_str(file_get_contents('php://input'), $data);
        }
        
        return $data;
    }

    protected function validateRequiredFields($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Missing required fields: ' . implode(', ', $missing));
        }
        
        return true;
    }

    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    protected function handleFileUpload($file, $allowedTypes, $maxSize = 5242880) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File size exceeds limit');
            default:
                throw new Exception('Unknown file upload error');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        return true;
    }
}

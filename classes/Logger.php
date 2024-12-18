<?php
class Logger {
    private static $instance = null;
    private $logFile;
    private $logLevel;

    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';

    private function __construct() {
        $this->logFile = __DIR__ . '/../logs/api.log';
        $this->logLevel = self::LEVEL_INFO; // Default level
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLogLevel($level) {
        $this->logLevel = $level;
    }

    private function shouldLog($level) {
        $levels = [
            self::LEVEL_ERROR => 4,
            self::LEVEL_WARNING => 3,
            self::LEVEL_INFO => 2,
            self::LEVEL_DEBUG => 1
        ];

        return $levels[$level] >= $levels[$this->logLevel];
    }

    private function formatMessage($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';

        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        
        return sprintf(
            "[%s] [%s] [IP: %s] [User: %s] [%s %s] %s%s\n",
            $timestamp,
            $level,
            $ip,
            $userId,
            $method,
            $endpoint,
            $message,
            $contextStr
        );
    }

    private function write($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return;
        }

        $formattedMessage = $this->formatMessage($level, $message, $context);
        
        // Rotate log file if it's too large (>10MB)
        if (file_exists($this->logFile) && filesize($this->logFile) > 10 * 1024 * 1024) {
            $this->rotateLogFile();
        }

        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }

    private function rotateLogFile() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = str_replace('.log', "_{$timestamp}.log", $this->logFile);
        rename($this->logFile, $backupFile);

        // Keep only last 5 log files
        $logFiles = glob(dirname($this->logFile) . '/*.log');
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach (array_slice($logFiles, 5) as $file) {
            unlink($file);
        }
    }

    public function error($message, $context = []) {
        $this->write(self::LEVEL_ERROR, $message, $context);
    }

    public function warning($message, $context = []) {
        $this->write(self::LEVEL_WARNING, $message, $context);
    }

    public function info($message, $context = []) {
        $this->write(self::LEVEL_INFO, $message, $context);
    }

    public function debug($message, $context = []) {
        $this->write(self::LEVEL_DEBUG, $message, $context);
    }

    public function logApiRequest($response = null, $error = null) {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
            'params' => $_GET ?? [],
            'body' => json_decode(file_get_contents('php://input'), true) ?? [],
            'response' => $response,
            'error' => $error
        ];

        if ($error) {
            $this->error('API Request Failed', $requestData);
        } else {
            $this->info('API Request Successful', $requestData);
        }
    }
}

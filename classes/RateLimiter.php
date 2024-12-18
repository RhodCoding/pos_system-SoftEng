<?php
class RateLimiter {
    private static $instance = null;
    private $db;
    private $logger;

    // Default limits
    const DEFAULT_WINDOW = 60; // 1 minute
    const DEFAULT_MAX_REQUESTS = 60; // 60 requests per minute
    
    // Different limits for different endpoints
    private $limits = [
        'default' => ['window' => 60, 'max_requests' => 60],
        'auth' => ['window' => 300, 'max_requests' => 5],      // 5 requests per 5 minutes for auth
        'orders' => ['window' => 60, 'max_requests' => 30],    // 30 requests per minute for orders
        'products' => ['window' => 60, 'max_requests' => 100], // 100 requests per minute for products
    ];

    private function __construct() {
        global $db;
        $this->db = $db;
        $this->logger = Logger::getInstance();
        
        // Create rate limit table if it doesn't exist
        $this->createTable();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            endpoint VARCHAR(100) NOT NULL,
            requests INT DEFAULT 1,
            window_start INT NOT NULL,
            INDEX idx_identifier_endpoint (identifier, endpoint),
            INDEX idx_window_start (window_start)
        )";
        
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            $this->logger->error('Failed to create rate_limits table', ['error' => $e->getMessage()]);
        }
    }

    private function cleanOldRecords() {
        $sql = "DELETE FROM rate_limits WHERE window_start < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', time() - 3600); // Clean records older than 1 hour
        $stmt->execute();
    }

    private function getIdentifier() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        return "{$ip}_{$userId}";
    }

    private function getEndpointType($endpoint) {
        if (strpos($endpoint, '/auth') !== false) return 'auth';
        if (strpos($endpoint, '/orders') !== false) return 'orders';
        if (strpos($endpoint, '/products') !== false) return 'products';
        return 'default';
    }

    public function checkLimit($endpoint = null) {
        $identifier = $this->getIdentifier();
        $endpointType = $this->getEndpointType($endpoint ?? $_SERVER['REQUEST_URI']);
        $limit = $this->limits[$endpointType] ?? $this->limits['default'];
        
        // Clean old records periodically
        if (rand(1, 100) <= 5) { // 5% chance to clean on each request
            $this->cleanOldRecords();
        }

        $currentWindow = time();
        $windowStart = $currentWindow - ($currentWindow % $limit['window']);

        // Get current request count
        $sql = "SELECT id, requests FROM rate_limits 
                WHERE identifier = ? AND endpoint = ? AND window_start = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $identifier, $endpointType, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $requests = $row['requests'] + 1;
            
            if ($requests > $limit['max_requests']) {
                $this->logger->warning('Rate limit exceeded', [
                    'identifier' => $identifier,
                    'endpoint' => $endpointType,
                    'requests' => $requests,
                    'limit' => $limit['max_requests']
                ]);
                return false;
            }

            // Update request count
            $sql = "UPDATE rate_limits SET requests = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $requests, $row['id']);
            $stmt->execute();
        } else {
            // Create new record
            $sql = "INSERT INTO rate_limits (identifier, endpoint, window_start) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $identifier, $endpointType, $windowStart);
            $stmt->execute();
        }

        return true;
    }

    public function getRemainingRequests($endpoint = null) {
        $identifier = $this->getIdentifier();
        $endpointType = $this->getEndpointType($endpoint ?? $_SERVER['REQUEST_URI']);
        $limit = $this->limits[$endpointType] ?? $this->limits['default'];
        
        $currentWindow = time();
        $windowStart = $currentWindow - ($currentWindow % $limit['window']);

        $sql = "SELECT requests FROM rate_limits 
                WHERE identifier = ? AND endpoint = ? AND window_start = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $identifier, $endpointType, $windowStart);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return max(0, $limit['max_requests'] - $row['requests']);
        }
        
        return $limit['max_requests'];
    }

    public function getResetTime($endpoint = null) {
        $endpointType = $this->getEndpointType($endpoint ?? $_SERVER['REQUEST_URI']);
        $limit = $this->limits[$endpointType] ?? $this->limits['default'];
        
        $currentWindow = time();
        return $limit['window'] - ($currentWindow % $limit['window']);
    }
}

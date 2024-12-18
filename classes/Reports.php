<?php
require_once __DIR__ . '/Database.php'; // Ensure you have the database connection
require_once __DIR__ . '/../config/database.php'; // Adjust the path as necessary

class Reports {
    protected $db;

    public function __construct() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function getTodaySales() {
        $query = "SELECT SUM(total_amount) AS total FROM orders WHERE DATE(created_at) = CURDATE()";
        $result = $this->db->query($query);
        
        if (!$result) {
            die("Query failed: " . $this->db->error); // Show error if query fails
        }
        
        $row = $result->fetch_assoc(); // Fetch the result
        var_dump($row); // Debugging line to check the value
        return $row['total'] ?? 0; // Should return 0 if no sales
    }

    public function getSalesByPeriod($startDate, $endDate) {
        $query = "SELECT SUM(total_amount) AS total FROM orders WHERE created_at BETWEEN '$startDate' AND '$endDate'";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['total'] ?? 0;
    }

    // Add more methods as needed...
}
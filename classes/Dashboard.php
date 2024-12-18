<?php
require_once __DIR__ . '/Database.php';

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getTodaySales() {
        $query = "SELECT 
                    COALESCE(SUM(o.total_amount), 0) as total,
                    COUNT(o.id) as order_count,
                    COALESCE(SUM(oi.quantity), 0) as items_sold
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE DATE(o.created_at) = CURDATE() 
                 AND o.status = 'completed'";
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }

    public function getTotalProducts() {
        $query = "SELECT COUNT(*) as total FROM products";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getTotalCategories() {
        $query = "SELECT COUNT(*) as total FROM categories";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getLowStockItems() {
        $query = "SELECT COUNT(*) as total 
                 FROM products 
                 WHERE stock <= alert_threshold";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getLastSevenDaysSales() {
        $query = "SELECT 
                    DATE(o.created_at) as date,
                    COALESCE(SUM(o.total_amount), 0) as total,
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(oi.quantity), 0) as items_sold
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 AND o.status = 'completed'
                 GROUP BY DATE(o.created_at)
                 ORDER BY date ASC";
        $result = $this->db->query($query);
        $sales = array();
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
        return $sales;
    }

    public function getEmployees() {
        $query = "SELECT 
                    u.id, u.name, u.username, u.role,
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as total_sales
                 FROM users u
                 LEFT JOIN orders o ON o.user_id = u.id AND o.status = 'completed'
                 WHERE u.role = 'employee'
                 GROUP BY u.id
                 ORDER BY total_sales DESC";
        $result = $this->db->query($query);
        $employees = array();
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        return $employees;
    }

    public function getTopSellingProducts() {
        $query = "SELECT 
                    p.id, p.name, p.price,
                    COALESCE(SUM(oi.quantity), 0) as total_quantity,
                    COALESCE(SUM(oi.quantity * oi.price), 0) as total_sales
                 FROM products p
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                 GROUP BY p.id
                 ORDER BY total_quantity DESC
                 LIMIT 5";
        $result = $this->db->query($query);
        $products = array();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    public function getSalesByHour() {
        $query = "SELECT 
                    HOUR(o.created_at) as hour,
                    COUNT(DISTINCT o.id) as order_count,
                    COALESCE(SUM(o.total_amount), 0) as total_sales,
                    COALESCE(SUM(oi.quantity), 0) as items_sold
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE DATE(o.created_at) = CURDATE()
                 AND o.status = 'completed'
                 GROUP BY HOUR(o.created_at)
                 ORDER BY hour ASC";
        $result = $this->db->query($query);
        $sales = array();
        while ($row = $result->fetch_assoc()) {
            $sales[] = $row;
        }
        return $sales;
    }

    public function getRecentOrders($limit = 5) {
        $query = "SELECT 
                    o.id, o.total_amount, o.created_at,
                    u.name as cashier_name,
                    COALESCE(SUM(oi.quantity), 0) as total_items
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.status = 'completed'
                 GROUP BY o.id
                 ORDER BY o.created_at DESC
                 LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = array();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        return $orders;
    }

    public function getCategoryDistribution() {
        $query = "SELECT 
                    c.name,
                    COUNT(DISTINCT p.id) as product_count,
                    COALESCE(SUM(p.stock), 0) as total_stock
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id
                 GROUP BY c.id, c.name
                 ORDER BY product_count DESC";
        $result = $this->db->query($query);
        $categories = array();
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        return $categories;
    }
}

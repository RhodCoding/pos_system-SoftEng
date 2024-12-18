<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/Payment.php';

class Order extends Model {
    protected $table = 'orders';
    protected $fillable = ['user_id', 'total_amount', 'payment_method', 'status'];

    public function createOrder($userId, $items, $paymentMethod) {
        $this->db->begin_transaction();
        
        try {
            // Calculate total amount
            $totalAmount = 0;
            $productModel = new Product();
            
            // Verify stock and calculate total
            foreach ($items as $item) {
                $product = $productModel->findById($item['product_id']);
                if (!$product || $product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
                }
                $totalAmount += $product['price'] * $item['quantity'];
            }

            // Create order
            $orderId = $this->create([
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'status' => 'pending'
            ]);

            if (!$orderId) {
                throw new Exception("Failed to create order");
            }

            // Create order items and update stock
            foreach ($items as $item) {
                $product = $productModel->findById($item['product_id']);
                $subtotal = $product['price'] * $item['quantity'];

                // Insert order item
                $query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
                         VALUES ({$orderId}, {$item['product_id']}, {$item['quantity']}, 
                         {$product['price']}, {$subtotal})";
                
                if (!$this->db->query($query)) {
                    throw new Exception("Failed to create order item");
                }

                // Update stock
                if (!$productModel->updateStock($item['product_id'], -$item['quantity'])) {
                    throw new Exception("Failed to update stock");
                }
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getOrderDetails($orderId) {
        $query = "SELECT o.*, oi.*, p.name as product_name, u.name as user_name 
                 FROM {$this->table} o 
                 JOIN order_items oi ON o.id = oi.order_id 
                 JOIN products p ON oi.product_id = p.id 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.id = " . $this->db->real_escape_string($orderId);
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getDailySales($date = null) {
        $date = $date ?? date('Y-m-d');
        $query = "SELECT DATE(created_at) as sale_date, 
                        COUNT(*) as total_orders, 
                        SUM(total_amount) as total_sales 
                 FROM {$this->table} 
                 WHERE DATE(created_at) = '{$date}' 
                 AND status = 'completed' 
                 GROUP BY DATE(created_at)";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }

    public function updateStatus($orderId, $status) {
        $validStatuses = ['pending', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status");
        }

        return $this->update($orderId, ['status' => $status]);
    }

    public function getSalesSummary($period = 'today') {
        $where = '';
        switch ($period) {
            case 'today':
                $where = "WHERE DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $where = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
        }

        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as average_order,
                    COUNT(DISTINCT user_id) as unique_customers
                 FROM {$this->table}
                 {$where} AND status = 'completed'";

        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }

    public function getTopSellingProducts($limit = 5, $period = 'week') {
        $where = '';
        switch ($period) {
            case 'week':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
        }

        $query = "SELECT 
                    p.id, p.name, p.price,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.subtotal) as total_sales
                 FROM order_items oi
                 JOIN products p ON p.id = oi.product_id
                 JOIN orders o ON o.id = oi.order_id
                 WHERE o.status = 'completed' {$where}
                 GROUP BY p.id
                 ORDER BY total_quantity DESC
                 LIMIT ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSalesByCategory($period = 'month') {
        $where = '';
        switch ($period) {
            case 'week':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $where = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
        }

        $query = "SELECT 
                    c.id, c.name,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.quantity) as total_items,
                    SUM(oi.subtotal) as total_sales
                 FROM categories c
                 JOIN products p ON p.category_id = c.id
                 JOIN order_items oi ON oi.product_id = p.id
                 JOIN orders o ON o.id = oi.order_id
                 WHERE o.status = 'completed' {$where}
                 GROUP BY c.id
                 ORDER BY total_sales DESC";

        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getHourlySales($date = null) {
        $date = $date ?? date('Y-m-d');
        
        $query = "SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales
                 FROM orders
                 WHERE DATE(created_at) = ? AND status = 'completed'
                 GROUP BY HOUR(created_at)
                 ORDER BY hour";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function processOrder($orderId, $amountPaid, $paymentMethod) {
        $this->db->begin_transaction();
        
        try {
            // Process payment
            $payment = new Payment();
            $paymentResult = $payment->processPayment($orderId, $amountPaid, $paymentMethod);
            
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }

            // Get order items
            $query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Update stock for each item
            $product = new Product();
            while ($item = $result->fetch_assoc()) {
                $stockResult = $product->updateStock($item['product_id'], $item['quantity']);
                if (!$stockResult['success']) {
                    throw new Exception($stockResult['error']);
                }
            }

            $this->db->commit();
            return $paymentResult;

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

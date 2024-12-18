<?php
require_once __DIR__ . '/Database.php';

class Payment {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function processPayment($orderId, $amountPaid, $paymentMethod) {
        try {
            // Get order details
            $query = "SELECT total_amount FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();

            if (!$order) {
                throw new Exception("Order not found");
            }

            // Validate payment amount
            if ($amountPaid < $order['total_amount']) {
                throw new Exception("Insufficient payment amount");
            }

            // Calculate change
            $change = $amountPaid - $order['total_amount'];

            // Update order status
            $query = "UPDATE orders SET status = 'completed' WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $orderId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update order status");
            }

            return [
                'success' => true,
                'change' => $change,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'total_amount' => $order['total_amount']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

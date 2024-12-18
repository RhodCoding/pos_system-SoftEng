<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Order.php';

class OrderApi extends ApiHandler {
    private $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        $this->requireAuth();

        switch ($method) {
            case 'GET':
                if ($action === 'daily_sales') {
                    $this->requireAdmin();
                    $this->getDailySales();
                } else {
                    $id = $_GET['id'] ?? null;
                    $id ? $this->getOrder($id) : $this->getAllOrders();
                }
                break;

            case 'POST':
                if ($action === 'update_status') {
                    $this->requireAdmin();
                    $this->updateOrderStatus();
                } else {
                    $this->createOrder();
                }
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllOrders() {
        if ($_SESSION['role'] === 'admin') {
            $orders = $this->orderModel->findAll([], 'created_at DESC');
        } else {
            $orders = $this->orderModel->findAll(
                ['user_id' => $_SESSION['user_id']], 
                'created_at DESC'
            );
        }
        $this->sendResponse(['orders' => $orders]);
    }

    private function getOrder($id) {
        $order = $this->orderModel->findById($id);
        if (!$order) {
            $this->sendError('Order not found', 404);
        }

        // Only admin or order owner can view order details
        if ($_SESSION['role'] !== 'admin' && $order['user_id'] !== $_SESSION['user_id']) {
            $this->sendError('Forbidden', 403);
        }

        $orderDetails = $this->orderModel->getOrderDetails($id);
        $this->sendResponse([
            'order' => $order,
            'items' => $orderDetails
        ]);
    }

    private function createOrder() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['items', 'payment_method']);

        if (empty($data['items'])) {
            $this->sendError('Order must contain at least one item');
        }

        try {
            $orderId = $this->orderModel->createOrder(
                $_SESSION['user_id'],
                $data['items'],
                $data['payment_method']
            );

            $order = $this->orderModel->getOrderDetails($orderId);
            $this->sendResponse(['order' => $order], 201);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function updateOrderStatus() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['order_id', 'status']);

        try {
            $this->orderModel->updateStatus($data['order_id'], $data['status']);
            $order = $this->orderModel->findById($data['order_id']);
            $this->sendResponse(['order' => $order]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getDailySales() {
        $date = $_GET['date'] ?? date('Y-m-d');
        $sales = $this->orderModel->getDailySales($date);
        $this->sendResponse(['sales' => $sales ?: [
            'sale_date' => $date,
            'total_orders' => 0,
            'total_sales' => 0
        ]]);
    }
}

// Handle the request
$api = new OrderApi();
$api->handleRequest();

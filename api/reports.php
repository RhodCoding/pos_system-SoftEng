<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Product.php';

class ReportsApi extends ApiHandler {
    private $orderModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->orderModel = new Order();
        $this->productModel = new Product();
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
            case 'sales_summary':
                $this->getSalesSummary();
                break;
            case 'top_products':
                $this->getTopProducts();
                break;
            case 'inventory_alerts':
                $this->getInventoryAlerts();
                break;
            case 'sales_by_category':
                $this->getSalesByCategory();
                break;
            case 'hourly_sales':
                $this->getHourlySales();
                break;
            default:
                $this->sendError('Invalid action');
        }
    }

    private function getSalesSummary() {
        $period = $this->sanitizeInput($_GET['period'] ?? 'today');
        
        try {
            $summary = $this->orderModel->getSalesSummary($period);
            $this->sendResponse(['summary' => $summary]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getTopProducts() {
        $limit = (int)$this->sanitizeInput($_GET['limit'] ?? '5', 'integer');
        $period = $this->sanitizeInput($_GET['period'] ?? 'week');
        
        try {
            $products = $this->orderModel->getTopSellingProducts($limit, $period);
            $this->sendResponse(['products' => $products]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getInventoryAlerts() {
        $threshold = (int)$this->sanitizeInput($_GET['threshold'] ?? '10', 'integer');
        
        try {
            $alerts = $this->productModel->getLowStockProducts($threshold);
            $this->sendResponse(['alerts' => $alerts]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getSalesByCategory() {
        $period = $this->sanitizeInput($_GET['period'] ?? 'month');
        
        try {
            $sales = $this->orderModel->getSalesByCategory($period);
            $this->sendResponse(['sales' => $sales]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getHourlySales() {
        $date = $this->sanitizeInput($_GET['date'] ?? date('Y-m-d'));
        
        try {
            $sales = $this->orderModel->getHourlySales($date);
            $this->sendResponse(['sales' => $sales]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new ReportsApi();
$api->handleRequest();

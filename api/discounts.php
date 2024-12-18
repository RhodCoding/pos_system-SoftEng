<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Discount.php';

class DiscountsApi extends ApiHandler {
    private $discountModel;

    public function __construct() {
        parent::__construct();
        $this->discountModel = new Discount();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        $this->requireAuth();

        switch ($method) {
            case 'GET':
                if ($action === 'validate') {
                    $this->validateDiscount();
                } else {
                    $this->requireAdmin();
                    $id = $_GET['id'] ?? null;
                    $id ? $this->getDiscount($id) : $this->getAllDiscounts();
                }
                break;

            case 'POST':
                $this->requireAdmin();
                $this->createDiscount();
                break;

            case 'PUT':
                $this->requireAdmin();
                $this->updateDiscount();
                break;

            case 'DELETE':
                $this->requireAdmin();
                $this->deleteDiscount();
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllDiscounts() {
        try {
            $discounts = $this->discountModel->findAll();
            $this->sendResponse(['discounts' => $discounts]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getDiscount($id) {
        try {
            $discount = $this->discountModel->findById($id);
            if (!$discount) {
                $this->sendError('Discount not found', 404);
            }
            $this->sendResponse(['discount' => $discount]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function validateDiscount() {
        $code = $_GET['code'] ?? null;
        $subtotal = floatval($_GET['subtotal'] ?? 0);
        $items = json_decode($_GET['items'] ?? '[]', true);

        if (!$code || !$subtotal || empty($items)) {
            $this->sendError('Code, subtotal and items are required');
        }

        try {
            $discount = $this->discountModel->validateDiscount($code, $subtotal, $items);
            $discountAmount = $this->discountModel->calculateDiscount($discount, $subtotal, $items);
            
            $this->sendResponse([
                'discount' => $discount,
                'amount' => $discountAmount
            ]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function createDiscount() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['code', 'type', 'value']);

        try {
            $discountId = $this->discountModel->createDiscount($data);
            if (!$discountId) {
                $this->sendError('Failed to create discount');
            }

            $discount = $this->discountModel->findById($discountId);
            $this->sendResponse(['discount' => $discount], 201);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function updateDiscount() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['id']);

        try {
            if (!$this->discountModel->update($data['id'], $data)) {
                $this->sendError('Failed to update discount');
            }

            $discount = $this->discountModel->findById($data['id']);
            $this->sendResponse(['discount' => $discount]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function deleteDiscount() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->sendError('Discount ID is required');
        }

        try {
            if (!$this->discountModel->delete($id)) {
                $this->sendError('Failed to delete discount');
            }
            $this->sendResponse(['message' => 'Discount deleted successfully']);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new DiscountsApi();
$api->handleRequest();

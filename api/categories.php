<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Category.php';

class CategoryApi extends ApiHandler {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new Category();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch ($method) {
            case 'GET':
                if ($action === 'with_products') {
                    $this->getCategoriesWithProductCount();
                } else {
                    $id = $_GET['id'] ?? null;
                    $id ? $this->getCategory($id) : $this->getAllCategories();
                }
                break;

            case 'POST':
                $this->requireAdmin();
                $this->createCategory();
                break;

            case 'PUT':
                $this->requireAdmin();
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    $this->sendError('Category ID is required');
                }
                $this->updateCategory($id);
                break;

            case 'DELETE':
                $this->requireAdmin();
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    $this->sendError('Category ID is required');
                }
                $this->deleteCategory($id);
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllCategories() {
        $categories = $this->categoryModel->findAll();
        $this->sendResponse(['categories' => $categories]);
    }

    private function getCategory($id) {
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            $this->sendError('Category not found', 404);
        }
        $this->sendResponse(['category' => $category]);
    }

    private function getCategoriesWithProductCount() {
        $categories = $this->categoryModel->getProductCount();
        $this->sendResponse(['categories' => $categories]);
    }

    private function createCategory() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['name']);

        $categoryId = $this->categoryModel->create($data);
        if (!$categoryId) {
            $this->sendError('Failed to create category');
        }

        $category = $this->categoryModel->findById($categoryId);
        $this->sendResponse(['category' => $category], 201);
    }

    private function updateCategory($id) {
        if (!$this->categoryModel->findById($id)) {
            $this->sendError('Category not found', 404);
        }

        $data = $this->getRequestData();
        if (!$this->categoryModel->update($id, $data)) {
            $this->sendError('Failed to update category');
        }

        $category = $this->categoryModel->findById($id);
        $this->sendResponse(['category' => $category]);
    }

    private function deleteCategory($id) {
        if (!$this->categoryModel->findById($id)) {
            $this->sendError('Category not found', 404);
        }

        try {
            if (!$this->categoryModel->deleteWithProducts($id)) {
                $this->sendError('Failed to delete category');
            }
            $this->sendResponse(['message' => 'Category and associated products deleted successfully']);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new CategoryApi();
$api->handleRequest();

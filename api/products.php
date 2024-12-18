<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductApi extends ApiHandler {
    private $productModel;
    private $categoryModel;
    private $uploadDir = __DIR__ . '/../uploads/products/';
    private $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch ($method) {
            case 'GET':
                if (isset($_GET['export'])) {
                    $this->requireAdmin();
                    $this->exportProducts();
                } elseif ($action === 'category') {
                    $categoryId = $_GET['id'] ?? null;
                    if (!$categoryId) {
                        $this->sendError('Category ID is required');
                    }
                    $this->getByCategory($categoryId);
                } elseif ($action === 'low_stock') {
                    $this->getLowStock();
                } else {
                    $id = $_GET['id'] ?? null;
                    $id ? $this->getProduct($id) : $this->getAllProducts();
                }
                break;

            case 'POST':
                $this->requireAdmin();
                $this->createProduct();
                break;

            case 'PUT':
                $this->requireAdmin();
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    $this->sendError('Product ID is required');
                }
                $this->updateProduct($id);
                break;

            case 'DELETE':
                $this->requireAdmin();
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    $this->sendError('Product ID is required');
                }
                $this->deleteProduct($id);
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllProducts() {
        try {
            $filters = [
                'category' => $_GET['category'] ?? null,
                'search' => $_GET['search'] ?? null,
                'stock_status' => $_GET['stock_status'] ?? null,
                'status' => $_GET['status'] ?? null,
                'sort' => $_GET['sort'] ?? 'name',
                'order' => $_GET['order'] ?? 'asc',
                'limit' => (int)($_GET['limit'] ?? 50),
                'offset' => (int)($_GET['offset'] ?? 0)
            ];

            $products = $this->productModel->findAll($filters);
            $total = $this->productModel->countAll($filters);

            $this->sendResponse([
                'success' => true,
                'products' => $products,
                'total' => $total,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch products: ' . $e->getMessage());
        }
    }

    private function getProduct($id) {
        $product = $this->productModel->findById($id);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        $this->sendResponse(['product' => $product]);
    }

    private function getByCategory($categoryId) {
        $products = $this->productModel->getByCategory($categoryId);
        $this->sendResponse(['products' => $products]);
    }

    private function getLowStock() {
        $threshold = $_GET['threshold'] ?? 10;
        $products = $this->productModel->getLowStock($threshold);
        $this->sendResponse(['products' => $products]);
    }

    private function handleImageUpload() {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedImageTypes)) {
            $this->sendError('Invalid image type. Allowed types: JPG, PNG, WebP');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_') . '.' . $extension;
        $filepath = $this->uploadDir . $filename;

        // Move and optimize image
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->sendError('Failed to upload image');
        }

        // Optimize image if needed
        $this->optimizeImage($filepath, $mimeType);

        return '/uploads/products/' . $filename;
    }

    private function optimizeImage($filepath, $mimeType) {
        // Load image based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($filepath);
                break;
            default:
                return;
        }

        // Resize if too large
        $maxWidth = 1200;
        $maxHeight = 1200;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Save optimized image
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $filepath, 85);
                break;
            case 'image/png':
                imagepng($image, $filepath, 8);
                break;
            case 'image/webp':
                imagewebp($image, $filepath, 85);
                break;
        }

        imagedestroy($image);
    }

    private function createProduct() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['name', 'price', 'category', 'stock', 'alert_threshold']);
        
        // Handle image upload
        $imagePath = $this->handleImageUpload();
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        // Set default status if not provided
        $data['status'] = $data['status'] ?? 'active';

        $productId = $this->productModel->create($data);
        if (!$productId) {
            $this->sendError('Failed to create product');
        }

        // Log the action
        $this->logAction('create', 'product', $productId, null, $data);

        $product = $this->productModel->findById($productId);
        $this->sendResponse(['product' => $product], 201);
    }

    private function updateProduct($id) {
        $product = $this->productModel->findById($id);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }

        $data = $this->getRequestData();
        $oldData = $product;

        // Handle image upload
        $imagePath = $this->handleImageUpload();
        if ($imagePath) {
            $data['image'] = $imagePath;
            // Delete old image if exists
            if ($product['image']) {
                $oldImagePath = __DIR__ . '/..' . $product['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        if (!$this->productModel->update($id, $data)) {
            $this->sendError('Failed to update product');
        }

        // Log the action
        $this->logAction('update', 'product', $id, $oldData, $data);

        $product = $this->productModel->findById($id);
        $this->sendResponse(['product' => $product]);
    }

    private function deleteProduct($id) {
        $product = $this->productModel->findById($id);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }

        // Delete product image if exists
        if ($product['image']) {
            $imagePath = __DIR__ . '/..' . $product['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        if (!$this->productModel->delete($id)) {
            $this->sendError('Failed to delete product');
        }

        // Log the action
        $this->logAction('delete', 'product', $id, $product, null);

        $this->sendResponse(['message' => 'Product deleted successfully']);
    }

    private function exportProducts() {
        $filters = [
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
            'stock_status' => $_GET['stock_status'] ?? null,
            'status' => $_GET['status'] ?? null,
            'sort' => 'name',
            'order' => 'asc'
        ];

        $products = $this->productModel->findAll($filters);

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['ID', 'Name', 'Category', 'Description', 'Price', 'Stock', 'Alert Threshold', 'Status', 'Created At', 'Updated At'];
        $sheet->fromArray([$headers], null, 'A1');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ]
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($products as $product) {
            $sheet->fromArray([[
                $product['id'],
                $product['name'],
                $product['category'],
                $product['description'],
                $product['price'],
                $product['stock'],
                $product['alert_threshold'],
                $product['status'],
                $product['created_at'],
                $product['updated_at']
            ]], null, "A{$row}");
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and output file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="products_export_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

// Handle the request
$api = new ProductApi();
$api->handleRequest();

<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Database.php';

class Product extends Model {
    protected $table = 'products';
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'stock',
        'alert_threshold',
        'image',
        'status'
    ];

    public function findAll($conditions = [], $orderBy = '') {
        try {
            $query = "SELECT p.*, c.name as category 
                     FROM {$this->table} p 
                     LEFT JOIN categories c ON p.category_id = c.id";
            
            if (!empty($conditions)) {
                $query .= " WHERE ";
                $conditions_arr = [];
                foreach ($conditions as $key => $value) {
                    $conditions_arr[] = "p.{$key} = '" . $this->db->real_escape_string($value) . "'";
                }
                $query .= implode(" AND ", $conditions_arr);
            }
            
            if ($orderBy) {
                $query .= " ORDER BY {$orderBy}";
            }
            
            $result = $this->db->query($query);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Error in Product findAll: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table}";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function countAll($filters = []) {
        $where = [];
        $params = [];
        $types = '';
        
        // Build where clause based on filters
        if (!empty($filters['category'])) {
            $where[] = 'category_id = ?';
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }
        
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'low':
                    $where[] = 'stock <= alert_threshold';
                    break;
                case 'medium':
                    $where[] = 'stock > alert_threshold AND stock <= (alert_threshold * 2)';
                    break;
                case 'high':
                    $where[] = 'stock > (alert_threshold * 2)';
                    break;
            }
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        // Build query
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Execute query
        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getByCategory($category) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE category_id = ? AND status = 'active'");
        $stmt->bind_param('s', $category);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStock($threshold = null) {
        $query = "SELECT * FROM {$this->table} WHERE stock <= COALESCE(?, alert_threshold) ORDER BY stock ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateStock($id, $quantity, $operation = 'add') {
        // Begin transaction
        $this->db->begin_transaction();
        
        try {
            // Get current stock
            $stmt = $this->db->prepare("SELECT stock FROM {$this->table} WHERE id = ? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                throw new Exception('Product not found');
            }
            
            $currentStock = $result['stock'];
            $newStock = $operation === 'add' ? 
                       $currentStock + $quantity : 
                       $currentStock - $quantity;
            
            // Check if operation would result in negative stock
            if ($newStock < 0) {
                throw new Exception('Insufficient stock');
            }
            
            // Update stock
            $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = ? WHERE id = ?");
            $stmt->bind_param('ii', $newStock, $id);
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateStockQuantity($productId, $quantity) {
        try {
            // Get current stock
            $query = "SELECT stock FROM products WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if (!$product) {
                throw new Exception("Product not found");
            }

            // Calculate new stock
            $newStock = $product['stock'] - $quantity;
            if ($newStock < 0) {
                throw new Exception("Insufficient stock");
            }

            // Update stock
            $query = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ii", $newStock, $productId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update stock");
            }

            // Check if stock is low (less than 10 items)
            if ($newStock < 10) {
                // You can implement notification logic here
                return [
                    'success' => true,
                    'low_stock_warning' => true,
                    'remaining_stock' => $newStock
                ];
            }

            return [
                'success' => true,
                'remaining_stock' => $newStock
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getSafeColumnName($column) {
        $allowedColumns = [
            'id',
            'name',
            'category_id',
            'price',
            'stock',
            'status',
            'created_at',
            'updated_at'
        ];
        
        return in_array($column, $allowedColumns) ? $column : 'name';
    }

    public function create($data) {
        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }

    public function update($id, $data) {
        // Always update the updated_at timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::update($id, $data);
    }
}

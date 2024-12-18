<?php
class Discount extends Model {
    protected $table = 'discounts';
    protected $fillable = [
        'code', 'type', 'value', 'min_purchase', 'max_discount',
        'start_date', 'end_date', 'usage_limit', 'used_count',
        'product_ids', 'category_ids', 'status'
    ];

    public function validateDiscount($code, $subtotal, $items) {
        $query = "SELECT * FROM {$this->table} 
                 WHERE code = ? 
                 AND status = 'active'
                 AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
                 AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
                 AND (usage_limit = 0 OR used_count < usage_limit)
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $discount = $stmt->get_result()->fetch_assoc();

        if (!$discount) {
            throw new Exception('Invalid or expired discount code');
        }

        // Check minimum purchase requirement
        if ($discount['min_purchase'] > 0 && $subtotal < $discount['min_purchase']) {
            throw new Exception("Minimum purchase amount of {$discount['min_purchase']} required");
        }

        // Check product/category restrictions
        if ($discount['product_ids'] || $discount['category_ids']) {
            $validItems = $this->getValidItems($discount);
            $hasValidItem = false;

            foreach ($items as $item) {
                if (in_array($item['product_id'], $validItems)) {
                    $hasValidItem = true;
                    break;
                }
            }

            if (!$hasValidItem) {
                throw new Exception('Discount not applicable to selected items');
            }
        }

        return $discount;
    }

    public function calculateDiscount($discount, $subtotal, $items) {
        $discountAmount = 0;

        switch ($discount['type']) {
            case 'percentage':
                $discountAmount = $subtotal * ($discount['value'] / 100);
                break;

            case 'fixed':
                $discountAmount = $discount['value'];
                break;

            case 'product':
                // Calculate discount only for valid products
                $validItems = $this->getValidItems($discount);
                foreach ($items as $item) {
                    if (in_array($item['product_id'], $validItems)) {
                        if ($discount['type'] === 'percentage') {
                            $discountAmount += $item['subtotal'] * ($discount['value'] / 100);
                        } else {
                            $discountAmount += $discount['value'] * $item['quantity'];
                        }
                    }
                }
                break;
        }

        // Apply maximum discount limit if set
        if ($discount['max_discount'] > 0) {
            $discountAmount = min($discountAmount, $discount['max_discount']);
        }

        return $discountAmount;
    }

    public function applyDiscount($code, $orderId) {
        $query = "UPDATE {$this->table} 
                 SET used_count = used_count + 1
                 WHERE code = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $code);
        return $stmt->execute();
    }

    private function getValidItems($discount) {
        $validItems = [];

        // Add products directly included
        if ($discount['product_ids']) {
            $productIds = explode(',', $discount['product_ids']);
            $validItems = array_merge($validItems, $productIds);
        }

        // Add products from included categories
        if ($discount['category_ids']) {
            $categoryIds = explode(',', $discount['category_ids']);
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            
            $query = "SELECT id FROM products WHERE category_id IN ($placeholders)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $validItems[] = $row['id'];
            }
        }

        return array_unique($validItems);
    }

    public function getActiveDiscounts() {
        $query = "SELECT * FROM {$this->table}
                 WHERE status = 'active'
                 AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
                 AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
                 AND (usage_limit = 0 OR used_count < usage_limit)
                 ORDER BY created_at DESC";

        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function createDiscount($data) {
        // Validate code uniqueness
        $query = "SELECT id FROM {$this->table} WHERE code = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $data['code']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Discount code already exists');
        }

        // Convert arrays to comma-separated strings
        if (isset($data['product_ids']) && is_array($data['product_ids'])) {
            $data['product_ids'] = implode(',', $data['product_ids']);
        }
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $data['category_ids'] = implode(',', $data['category_ids']);
        }

        return $this->create($data);
    }
}

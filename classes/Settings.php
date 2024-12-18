<?php
class Settings extends Model {
    protected $table = 'settings';
    protected $fillable = ['key', 'value', 'type', 'description'];
    private static $cache = [];

    public function getSetting($key, $default = null) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $query = "SELECT * FROM {$this->table} WHERE `key` = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            return $default;
        }

        $value = $this->castValue($result['value'], $result['type']);
        self::$cache[$key] = $value;
        return $value;
    }

    public function setSetting($key, $value, $type = 'string', $description = '') {
        $existingQuery = "SELECT id FROM {$this->table} WHERE `key` = ?";
        $stmt = $this->db->prepare($existingQuery);
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $query = "UPDATE {$this->table} SET `value` = ?, `type` = ?, `description` = ? WHERE `key` = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ssss', $value, $type, $description, $key);
        } else {
            $query = "INSERT INTO {$this->table} (`key`, `value`, `type`, `description`) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ssss', $key, $value, $type, $description);
        }

        $success = $stmt->execute();
        if ($success) {
            self::$cache[$key] = $this->castValue($value, $type);
        }
        return $success;
    }

    public function getMultiple($keys) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->getSetting($key);
        }
        return $result;
    }

    public function setMultiple($settings) {
        $success = true;
        foreach ($settings as $key => $data) {
            $value = $data['value'] ?? null;
            $type = $data['type'] ?? 'string';
            $description = $data['description'] ?? '';
            
            if (!$this->setSetting($key, $value, $type, $description)) {
                $success = false;
            }
        }
        return $success;
    }

    private function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }

    public function initializeDefaultSettings() {
        $defaults = [
            'store_name' => [
                'value' => 'POS-Namin',
                'type' => 'string',
                'description' => 'Store name displayed in receipts and reports'
            ],
            'store_address' => [
                'value' => '',
                'type' => 'string',
                'description' => 'Store address displayed in receipts'
            ],
            'store_phone' => [
                'value' => '',
                'type' => 'string',
                'description' => 'Store contact number'
            ],
            'currency' => [
                'value' => 'PHP',
                'type' => 'string',
                'description' => 'Default currency for prices'
            ],
            'tax_rate' => [
                'value' => '12',
                'type' => 'float',
                'description' => 'Default tax rate percentage'
            ],
            'low_stock_threshold' => [
                'value' => '10',
                'type' => 'integer',
                'description' => 'Product quantity threshold for low stock alerts'
            ],
            'enable_email_notifications' => [
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable email notifications for low stock and daily reports'
            ],
            'receipt_footer' => [
                'value' => 'Thank you for your purchase!',
                'type' => 'string',
                'description' => 'Custom message printed at the bottom of receipts'
            ],
            'business_hours' => [
                'value' => '{"mon":"9:00-18:00","tue":"9:00-18:00","wed":"9:00-18:00","thu":"9:00-18:00","fri":"9:00-18:00","sat":"9:00-17:00","sun":"closed"}',
                'type' => 'json',
                'description' => 'Store business hours'
            ],
            'allowed_payment_methods' => [
                'value' => 'cash,card',
                'type' => 'array',
                'description' => 'Enabled payment methods'
            ]
        ];

        return $this->setMultiple($defaults);
    }
}

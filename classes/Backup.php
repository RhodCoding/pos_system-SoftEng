<?php
class Backup {
    private $db;
    private $backupDir;
    private $tables = ['users', 'categories', 'products', 'orders', 'order_items', 'audit_trail'];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->backupDir = __DIR__ . '/../backups';
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }

    public function createBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $this->backupDir . "/backup_{$timestamp}.sql";
        $handle = fopen($filename, 'w');

        // Add database recreation
        fwrite($handle, "-- POS System Database Backup\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`;\n");
        fwrite($handle, "USE `" . DB_NAME . "`;\n\n");

        foreach ($this->tables as $table) {
            // Get table structure
            $result = $this->db->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_row();
            fwrite($handle, "\n\n" . $row[1] . ";\n\n");

            // Get table data
            $result = $this->db->query("SELECT * FROM `$table`");
            while ($row = $result->fetch_assoc()) {
                $values = array_map(function($value) {
                    if ($value === null) return 'NULL';
                    return "'" . $this->db->real_escape_string($value) . "'";
                }, $row);
                
                fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
            }
        }

        fclose($handle);
        return $filename;
    }

    public function exportData($type, $filters = []) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $this->backupDir . "/{$type}_export_{$timestamp}.csv";
        $handle = fopen($filename, 'w');

        switch ($type) {
            case 'sales':
                $this->exportSales($handle, $filters);
                break;
            case 'inventory':
                $this->exportInventory($handle, $filters);
                break;
            case 'customers':
                $this->exportCustomers($handle, $filters);
                break;
            case 'audit':
                $this->exportAuditTrail($handle, $filters);
                break;
        }

        fclose($handle);
        return $filename;
    }

    private function exportSales($handle, $filters) {
        // Write CSV header
        fputcsv($handle, ['Order ID', 'Date', 'Customer', 'Products', 'Total Amount', 'Payment Method', 'Status']);

        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT o.*, u.name as customer_name,
                 GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
                 FROM orders o
                 LEFT JOIN users u ON u.id = o.user_id
                 LEFT JOIN order_items oi ON oi.order_id = o.id
                 LEFT JOIN products p ON p.id = oi.product_id
                 {$whereClause}
                 GROUP BY o.id
                 ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            fputcsv($handle, [
                $row['id'],
                $row['created_at'],
                $row['customer_name'],
                $row['products'],
                $row['total_amount'],
                $row['payment_method'],
                $row['status']
            ]);
        }
    }

    private function exportInventory($handle, $filters) {
        fputcsv($handle, ['Product ID', 'Name', 'Category', 'Price', 'Stock Quantity', 'Last Updated']);

        $query = "SELECT p.*, c.name as category_name
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 ORDER BY p.name";

        $result = $this->db->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($handle, [
                $row['id'],
                $row['name'],
                $row['category_name'],
                $row['price'],
                $row['stock_quantity'],
                $row['updated_at']
            ]);
        }
    }

    private function exportAuditTrail($handle, $filters) {
        fputcsv($handle, ['Date', 'User', 'Action', 'Entity Type', 'Entity ID', 'Changes']);

        $auditTrail = new AuditTrail();
        $records = $auditTrail->getAuditHistory($filters);

        foreach ($records as $record) {
            $changes = '';
            if ($record['old_values'] && $record['new_values']) {
                $old = json_decode($record['old_values'], true);
                $new = json_decode($record['new_values'], true);
                $changes = array_reduce(array_keys($new), function($carry, $key) use ($old, $new) {
                    return $carry . "$key: " . ($old[$key] ?? 'null') . " → " . $new[$key] . ", ";
                }, '');
                $changes = rtrim($changes, ', ');
            }

            fputcsv($handle, [
                $record['created_at'],
                $record['user_name'],
                $record['action'],
                $record['entity_type'],
                $record['entity_id'],
                $changes
            ]);
        }
    }
}

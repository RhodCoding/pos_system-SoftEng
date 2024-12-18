<?php
class AuditTrail extends Model {
    protected $table = 'audit_trail';
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'ip_address'];

    public function logAction($userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        return $this->create($data);
    }

    public function getAuditHistory($filters = [], $limit = 50, $offset = 0) {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
            $types .= 'i';
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $params[] = $filters['action'];
            $types .= 's';
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity_type'];
            $types .= 's';
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = ?';
            $params[] = $filters['entity_id'];
            $types .= 'i';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT a.*, u.username, u.name as user_name 
                 FROM {$this->table} a
                 LEFT JOIN users u ON u.id = a.user_id
                 {$whereClause}
                 ORDER BY a.created_at DESC
                 LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getEntityHistory($entityType, $entityId) {
        $query = "SELECT a.*, u.username, u.name as user_name
                 FROM {$this->table} a
                 LEFT JOIN users u ON u.id = a.user_id
                 WHERE a.entity_type = ? AND a.entity_id = ?
                 ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param('si', $entityType, $entityId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

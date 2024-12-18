<?php
abstract class Model {
    protected $db;
    protected $table;
    protected $fillable = [];

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("Model initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("Error in findById: " . $e->getMessage());
            throw $e;
        }
    }

    public function findAll($conditions = [], $orderBy = '') {
        try {
            $query = "SELECT * FROM {$this->table}";
            $params = [];
            $types = '';
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    $whereClause[] = "{$key} = ?";
                    $params[] = $value;
                    $types .= is_int($value) ? 'i' : 's';
                }
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }

            if (!empty($orderBy)) {
                $query .= " ORDER BY " . $this->db->real_escape_string($orderBy);
            }

            $stmt = $this->db->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Error in findAll: " . $e->getMessage());
            throw $e;
        }
    }

    public function create($data) {
        try {
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            if (empty($filteredData)) {
                throw new Exception("No valid fields to insert");
            }
            
            $columns = implode(', ', array_keys($filteredData));
            $values = implode(', ', array_fill(0, count($filteredData), '?'));
            $types = str_repeat('s', count($filteredData));
            
            $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
            $stmt = $this->db->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param($types, ...array_values($filteredData));
            $result = $stmt->execute();
            
            if ($result) {
                return $this->db->insert_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error in create: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $fields = array_intersect_key($data, array_flip($this->fillable));
            if (empty($fields)) {
                throw new Exception("No valid fields to update");
            }

            $setClause = implode(' = ?, ', array_keys($fields)) . ' = ?';
            $query = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $types = str_repeat('s', count($fields)) . 'i';
            $values = array_values($fields);
            $values[] = $id;
            $stmt->bind_param($types, ...$values);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update record: " . $stmt->error);
            }
            
            return $this->findById($id);
        } catch (Exception $e) {
            error_log("Error in update: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete record: " . $stmt->error);
            }
            
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            error_log("Error in delete: " . $e->getMessage());
            throw $e;
        }
    }
}

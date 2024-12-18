<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Employee extends Model {
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'name', 'role', 'status'];

    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        try {
            // Check if username exists
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE username = ?");
            $stmt->bind_param('s', $data['username']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return [
                    'success' => false,
                    'errors' => ['username' => 'Username already exists'],
                    'message' => 'Username already exists'
                ];
            }

            // Hash the password
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Set defaults
            $data['role'] = 'employee';
            $data['status'] = 'active';
            
            // Create employee
            $result = parent::create($data);
            if ($result) {
                return ['success' => true, 'message' => 'Employee created successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to create employee'];
            }
        } catch (Exception $e) {
            error_log("Error creating employee: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAllEmployees() {
        $query = "SELECT id, username, name, status FROM {$this->table} WHERE role = 'employee' ORDER BY id DESC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function update($id, $data) {
        try {
            // Handle password separately
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }

            $id = $this->db->real_escape_string($id);
            $updates = [];

            foreach ($this->fillable as $field) {
                if (isset($data[$field])) {
                    $value = $this->db->real_escape_string($data[$field]);
                    $updates[] = "{$field} = '{$value}'";
                }
            }

            if (!empty($updates)) {
                $query = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = {$id} AND role = 'employee'";
                return $this->db->query($query);
            }
            return false;
        } catch (Exception $e) {
            error_log("Error updating employee: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        $id = $this->db->real_escape_string($id);
        $query = "DELETE FROM {$this->table} WHERE id = {$id} AND role = 'employee'";
        return $this->db->query($query);
    }

    public function getById($id) {
        $id = $this->db->real_escape_string($id);
        $query = "SELECT id, username, name, status FROM {$this->table} WHERE id = {$id} AND role = 'employee'";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
}

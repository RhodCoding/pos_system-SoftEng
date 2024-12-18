<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Database.php';

class User extends Model {
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'name', 'role'];

    public function authenticate($username, $password) {
        $username = $this->db->real_escape_string($username);
        $query = "SELECT * FROM {$this->table} WHERE username = '{$username}' LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return parent::create($data);
    }

    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return parent::update($id, $data);
    }
}

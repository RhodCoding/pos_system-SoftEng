<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Database.php';

class Category extends Model {
    protected $table = 'categories';
    protected $fillable = ['name', 'description'];

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getProductCount() {
        $query = "SELECT c.id, c.name, COUNT(p.id) as product_count 
                 FROM {$this->table} c 
                 LEFT JOIN products p ON c.id = p.category_id 
                 GROUP BY c.id, c.name";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function deleteWithProducts($id) {
        $this->db->begin_transaction();
        try {
            // Delete products in this category
            $query = "DELETE FROM products WHERE category_id = " . $this->db->real_escape_string($id);
            $this->db->query($query);

            // Delete the category
            $this->delete($id);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}

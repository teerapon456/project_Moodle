<?php
class CategoryType {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all category types
    public function getAll() {
        $query = "SELECT * FROM iga_category_types ORDER BY type_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get category type by ID
    public function getById($id) {
        $query = "SELECT * FROM iga_category_types WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add new category type
    public function create($name, $description = null) {
        $query = "INSERT INTO iga_category_types (type_name, type_description) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $name, $description);
        return $stmt->execute();
    }

    // Update category type
    public function update($id, $name, $description = null) {
        $query = "UPDATE iga_category_types SET type_name = ?, type_description = ? WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $name, $description, $id);
        return $stmt->execute();
    }

    // Delete category type
    public function delete($id) {
        // First, set category_type_id to NULL for all tests using this type
        $updateQuery = "UPDATE iga_tests SET category_type_id = NULL WHERE category_type_id = ?";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();
        
        // Then delete the type
        $deleteQuery = "DELETE FROM iga_category_types WHERE type_id = ?";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $id);
        return $deleteStmt->execute();
    }
}
?>

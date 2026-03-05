<?php

class CategoryModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllCategories($searchQuery = '', $filterType = '', $offset = 0, $limit = 10)
    {
        $sql = "
            SELECT qc.category_id, qc.category_name, qc.category_description, qc.category_type_id, ct.type_name AS category_type 
            FROM iga_question_categories qc
            JOIN iga_category_types ct ON qc.category_type_id = ct.type_id
        ";

        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "qc.category_name LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }

        if (!empty($filterType)) {
            $whereClauses[] = "qc.category_type_id = :filter";
            $params[':filter'] = $filterType;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY qc.category_id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryCount($searchQuery = '', $filterType = '')
    {
        $sql = "
            SELECT COUNT(*) AS count 
            FROM iga_question_categories qc 
            JOIN iga_category_types ct ON qc.category_type_id = ct.type_id
        ";

        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "qc.category_name LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }

        if (!empty($filterType)) {
            $whereClauses[] = "qc.category_type_id = :filter";
            $params[':filter'] = $filterType;
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function isCategoryInUse($categoryId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM iga_questions WHERE category_id = :id");
        $stmt->execute([':id' => $categoryId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function addCategory($name, $description, $typeId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO iga_question_categories (category_name, category_description, category_type_id) VALUES (:name, :desc, :typeId)");
        return $stmt->execute([
            ':name' => $name,
            ':desc' => $description,
            ':typeId' => $typeId
        ]);
    }

    public function updateCategory($id, $name, $description, $typeId)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_question_categories SET category_name = :name, category_description = :desc, category_type_id = :typeId WHERE category_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':desc' => $description,
            ':typeId' => $typeId
        ]);
    }

    public function deleteCategory($id)
    {
        if ($this->isCategoryInUse($id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM iga_question_categories WHERE category_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getAllCategoryTypes($searchQuery = '', $offset = 0, $limit = 10)
    {
        $sql = "SELECT type_id, type_name, type_description, created_at FROM iga_category_types";

        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "type_name LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategoryTypesList()
    {
        $sql = "SELECT type_id, type_name, type_description, created_at FROM iga_category_types ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryTypeCount($searchQuery = '')
    {
        $sql = "SELECT COUNT(*) FROM iga_category_types";

        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "type_name LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function isCategoryTypeInUse($typeId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM iga_question_categories WHERE category_type_id = :id");
        $stmt->execute([':id' => $typeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function addCategoryType($name, $description)
    {
        $stmt = $this->pdo->prepare("INSERT INTO iga_category_types (type_name, type_description) VALUES (:name, :desc)");
        return $stmt->execute([
            ':name' => $name,
            ':desc' => $description
        ]);
    }

    public function updateCategoryType($id, $name, $description)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_category_types SET type_name = :name, type_description = :desc WHERE type_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':desc' => $description
        ]);
    }

    public function deleteCategoryType($id)
    {
        if ($this->isCategoryTypeInUse($id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM iga_category_types WHERE type_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

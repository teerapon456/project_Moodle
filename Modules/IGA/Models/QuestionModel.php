<?php

class QuestionModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get questions with section info, category, search/filter, pagination
     */
    public function getAllQuestions($searchQuery = '', $categoryId = null, $type = '', $sectionId = null, $offset = 0, $limit = 20)
    {
        $sql = "
            SELECT q.question_id, q.question_text, q.question_type, q.category_id,
                   q.score, q.question_order, q.is_critical, q.section_id,
                   c.category_name,
                   s.section_name, s.test_id,
                   t.test_name,
                   (SELECT COUNT(*) FROM iga_question_options o WHERE o.question_id = q.question_id) AS option_count
            FROM iga_questions q
            LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
            LEFT JOIN iga_sections s ON q.section_id = s.section_id
            LEFT JOIN iga_tests t ON s.test_id = t.test_id
        ";

        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "q.question_text LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }
        if (!empty($categoryId)) {
            $whereClauses[] = "q.category_id = :catId";
            $params[':catId'] = $categoryId;
        }
        if (!empty($type)) {
            $whereClauses[] = "q.question_type = :type";
            $params[':type'] = $type;
        }
        if (!empty($sectionId)) {
            $whereClauses[] = "q.section_id = :secId";
            $params[':secId'] = $sectionId;
        }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY t.test_name ASC, s.section_order ASC, q.question_order ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => &$v) $stmt->bindParam($k, $v);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count questions with filters
     */
    public function getQuestionCount($searchQuery = '', $categoryId = null, $type = '', $sectionId = null)
    {
        $sql = "SELECT COUNT(*) FROM iga_questions q";
        $whereClauses = [];
        $params = [];

        if (!empty($searchQuery)) {
            $whereClauses[] = "q.question_text LIKE :search";
            $params[':search'] = '%' . $searchQuery . '%';
        }
        if (!empty($categoryId)) {
            $whereClauses[] = "q.category_id = :catId";
            $params[':catId'] = $categoryId;
        }
        if (!empty($type)) {
            $whereClauses[] = "q.question_type = :type";
            $params[':type'] = $type;
        }
        if (!empty($sectionId)) {
            $whereClauses[] = "q.section_id = :secId";
            $params[':secId'] = $sectionId;
        }
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get single question with options
     */
    public function getQuestionById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT q.*, c.category_name, s.section_name, s.test_id, t.test_name
            FROM iga_questions q
            LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
            LEFT JOIN iga_sections s ON q.section_id = s.section_id
            LEFT JOIN iga_tests t ON s.test_id = t.test_id
            WHERE q.question_id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get options for a question
     */
    public function getOptionsByQuestionId($questionId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iga_question_options WHERE question_id = :id ORDER BY option_id ASC");
        $stmt->execute([':id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all questions for a section (with options)
     */
    public function getQuestionsBySection($sectionId)
    {
        $stmt = $this->pdo->prepare("
            SELECT q.*, c.category_name
            FROM iga_questions q
            LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
            WHERE q.section_id = :sid
            ORDER BY q.question_order ASC
        ");
        $stmt->execute([':sid' => $sectionId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach options
        foreach ($questions as &$q) {
            $q['options'] = $this->getOptionsByQuestionId($q['question_id']);
        }
        return $questions;
    }

    /**
     * Get all sections for a test (with question + option hierarchy)
     */
    public function getSectionsWithQuestions($testId)
    {
        // Get sections
        $stmt = $this->pdo->prepare("
            SELECT s.*, t.test_name,
                   (SELECT COUNT(*) FROM iga_questions q2 WHERE q2.section_id = s.section_id) AS question_count,
                   (SELECT SUM(q3.score) FROM iga_questions q3 WHERE q3.section_id = s.section_id) AS max_score
            FROM iga_sections s
            JOIN iga_tests t ON s.test_id = t.test_id
            WHERE s.test_id = :tid
            ORDER BY s.section_order ASC
        ");
        $stmt->execute([':tid' => $testId]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach questions with options to each section
        foreach ($sections as &$sec) {
            $sec['questions'] = $this->getQuestionsBySection($sec['section_id']);
        }
        return $sections;
    }

    /**
     * Create a new section
     */
    public function createSection($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO iga_sections (test_id, section_name, description, duration_minutes, section_order)
            VALUES (:tid, :name, :desc, :dur, :order)
        ");
        $stmt->execute([
            ':tid' => $data['test_id'],
            ':name' => $data['section_name'],
            ':desc' => $data['description'] ?? null,
            ':dur' => $data['duration_minutes'] ?? 0,
            ':order' => $data['section_order'] ?? 0
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update an existing section
     */
    public function updateSection($id, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_sections SET
            section_name = :name, description = :desc, duration_minutes = :dur, section_order = :order
            WHERE section_id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['section_name'],
            ':desc' => $data['description'] ?? null,
            ':dur' => $data['duration_minutes'] ?? 0,
            ':order' => $data['section_order'] ?? 0
        ]);
    }

    /**
     * Delete a section (and its questions)
     */
    public function deleteSection($id)
    {
        $this->pdo->beginTransaction();
        try {
            // Delete options first, then questions, then section
            $this->pdo->prepare("DELETE FROM iga_question_options WHERE question_id IN (SELECT question_id FROM iga_questions WHERE section_id = :id)")->execute([':id' => $id]);
            $this->pdo->prepare("DELETE FROM iga_questions WHERE section_id = :id")->execute([':id' => $id]);
            $this->pdo->prepare("DELETE FROM iga_sections WHERE section_id = :id")->execute([':id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Create a new question
     */
    public function createQuestion($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO iga_questions (section_id, category_id, question_text, question_type, score, question_order, is_critical)
            VALUES (:sid, :cid, :text, :type, :score, :order, :crit)
        ");
        $stmt->execute([
            ':sid' => $data['section_id'],
            ':cid' => $data['category_id'] ?? null,
            ':text' => $data['question_text'],
            ':type' => $data['question_type'],
            ':score' => $data['score'] ?? 0,
            ':order' => $data['question_order'] ?? 0,
            ':crit' => $data['is_critical'] ?? 0
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update an existing question
     */
    public function updateQuestion($id, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_questions SET
            category_id = :cid, question_text = :text, question_type = :type, 
            score = :score, question_order = :order, is_critical = :crit
            WHERE question_id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':cid' => $data['category_id'] ?? null,
            ':text' => $data['question_text'],
            ':type' => $data['question_type'],
            ':score' => $data['score'] ?? 0,
            ':order' => $data['question_order'] ?? 0,
            ':crit' => $data['is_critical'] ?? 0
        ]);
    }

    /**
     * Delete a question (and its options)
     */
    public function deleteQuestion($id)
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM iga_question_options WHERE question_id = :id")->execute([':id' => $id]);
            $this->pdo->prepare("DELETE FROM iga_questions WHERE question_id = :id")->execute([':id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Set options for a question (Delete existing and insert new)
     */
    public function setOptions($questionId, $options)
    {
        $this->pdo->prepare("DELETE FROM iga_question_options WHERE question_id = :qid")->execute([':qid' => $questionId]);

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_question_options (question_id, option_text, is_correct)
            VALUES (:qid, :text, :corr)
        ");

        foreach ($options as $opt) {
            $stmt->execute([
                ':qid' => $questionId,
                ':text' => $opt['option_text'],
                ':corr' => $opt['is_correct'] ?? 0
            ]);
        }
        return true;
    }
}

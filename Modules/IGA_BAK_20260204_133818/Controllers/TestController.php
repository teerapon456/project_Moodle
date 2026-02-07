<?php

require_once __DIR__ . '/IGABaseController.php';

class TestController extends IGABaseController
{
    /**
     * List all tests
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view');

        $search = $_GET['search'] ?? '';
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $conditions = ["1=1"];
        $params = [];

        if ($search) {
            $conditions[] = "test_name LIKE ?";
            $params[] = "%$search%";
        }

        $whereClause = implode(' AND ', $conditions);

        // Count total
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM iga_tests WHERE $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get Data
        $stmt = $this->pdo->prepare("
            SELECT t.*, u.fullname as created_by_name 
            FROM iga_tests t
            LEFT JOIN users u ON t.created_by_user_id = u.id
            WHERE $whereClause
            ORDER BY t.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/test_list', [
            'title' => 'จัดการแบบทดสอบ',
            'tests' => $tests,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'search' => $search
        ]);
    }

    /**
     * Show Create Form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $this->render('admin/test_form', [
            'title' => 'สร้างแบบทดสอบใหม่',
            'test' => null
        ]);
    }

    /**
     * Store New Test
     */
    public function store($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $testName = $data['test_name'] ?? '';
        if (empty($testName)) {
            return $this->error('กรุณาระบุชื่อแบบทดสอบ');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_tests (test_name, description, duration_minutes, is_published, min_passing_score, created_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $testName,
            $data['description'] ?? '',
            $data['duration_minutes'] ?? 0,
            isset($data['is_published']) ? 1 : 0,
            $data['min_passing_score'] ?? 0,
            $this->user['id'] ?? null
        ]);

        header('Location: index.php?controller=test&action=index');
    }

    /**
     * Show Edit Form
     */
    public function edit()
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $id = $_GET['id'] ?? null;
        if (!$id) die("Invalid ID");

        $stmt = $this->pdo->prepare("SELECT * FROM iga_tests WHERE test_id = ?");
        $stmt->execute([$id]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$test) die("Test not found");

        $this->render('admin/test_form', [
            'title' => 'แก้ไขแบบทดสอบ',
            'test' => $test
        ]);
    }

    /**
     * Update Test
     */
    public function update($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $id = $data['test_id'] ?? null;
        if (!$id) return $this->error("Invalid ID");

        $stmt = $this->pdo->prepare("
            UPDATE iga_tests 
            SET test_name = ?, description = ?, duration_minutes = ?, is_published = ?, min_passing_score = ?, updated_at = NOW()
            WHERE test_id = ?
        ");
        $stmt->execute([
            $data['test_name'],
            $data['description'] ?? '',
            $data['duration_minutes'] ?? 0,
            isset($data['is_published']) ? 1 : 0,
            $data['min_passing_score'] ?? 0,
            $id
        ]);

        header('Location: index.php?controller=test&action=index');
    }

    /**
     * Delete Test
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requirePermission('delete');

        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $this->pdo->prepare("DELETE FROM iga_tests WHERE test_id = ?");
            $stmt->execute([$id]);
        }

        header('Location: index.php?controller=test&action=index');
    }

    /**
     * Manage Test Structure (Sections & Questions)
     */
    public function structure()
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $id = $_GET['id'] ?? null;
        if (!$id) die("Invalid ID");

        // Get Test
        $stmt = $this->pdo->prepare("SELECT * FROM iga_tests WHERE test_id = ?");
        $stmt->execute([$id]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$test) die("Test not found");

        // Get Sections
        $stmt = $this->pdo->prepare("SELECT * FROM iga_sections WHERE test_id = ? ORDER BY section_order ASC");
        $stmt->execute([$id]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Questions for each section
        foreach ($sections as &$section) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM iga_questions 
                WHERE section_id = ? 
                ORDER BY question_order ASC
            ");
            $stmt->execute([$section['section_id']]);
            $section['questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('admin/test_structure', [
            'title' => 'จัดการโครงสร้างแบบทดสอบ: ' . $test['test_name'],
            'test' => $test,
            'sections' => $sections
        ]);
    }
}

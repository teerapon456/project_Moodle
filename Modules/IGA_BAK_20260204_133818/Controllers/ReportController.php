<?php

require_once __DIR__ . '/IGABaseController.php';

class ReportController extends IGABaseController
{
    /**
     * Admin: List All Test Results
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view'); // Admin permission

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $test_id = $_GET['test_id'] ?? '';

        $conditions = ["1=1"];
        $params = [];

        if ($search) {
            $conditions[] = "(COALESCE(u.fullname, app.full_name) LIKE ? OR t.test_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($test_id) {
            $conditions[] = "a.test_id = ?";
            $params[] = $test_id;
        }

        $whereClause = implode(' AND ', $conditions);

        // Count
        $sqlCount = "
            SELECT COUNT(*) 
            FROM iga_user_test_attempts a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN iga_applicants app ON a.user_id = app.applicant_id
            JOIN iga_tests t ON a.test_id = t.test_id
            WHERE $whereClause
        ";
        $stmt = $this->pdo->prepare($sqlCount);
        $stmt->execute($params);
        $total_items = $stmt->fetchColumn();

        // Get Data
        $sql = "
            SELECT a.*, 
                   COALESCE(u.fullname, app.full_name) as fullname,
                   COALESCE(u.email, app.email) as email,
                   t.test_name, 
                   t.min_passing_score as max_score,
                   (CASE WHEN a.total_score >= COALESCE(t.min_passing_score, 0) THEN 1 ELSE 0 END) as is_passed
            FROM iga_user_test_attempts a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN iga_applicants app ON a.user_id = app.applicant_id
            JOIN iga_tests t ON a.test_id = t.test_id
            WHERE $whereClause
            ORDER BY a.start_time DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Tests for Dropdown
        $stmt = $this->pdo->query("SELECT test_id, test_name FROM iga_tests ORDER BY test_name ASC");
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/report_list', [
            'title' => 'รายงานผลการสอบ',
            'attempts' => $attempts,
            'total_items' => $total_items,
            'page' => $page,
            'total_pages' => ceil($total_items / $limit),
            'search' => $search,
            'test_id' => $test_id,
            'tests' => $tests
        ]);
    }

    /**
     * User: View Own Result (Or Admin View User Result)
     */
    public function result()
    {
        $this->requireAuth();
        $attemptId = $_GET['id'] ?? null;
        if (!$attemptId) die("Invalid ID");

        $stmt = $this->pdo->prepare("
            SELECT a.*, t.test_name, t.min_passing_score as pass_score, 
                   COALESCE(u.fullname, app.full_name) as fullname
            FROM iga_user_test_attempts a
            JOIN iga_tests t ON a.test_id = t.test_id
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN iga_applicants app ON a.user_id = app.applicant_id
            WHERE a.attempt_id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) die("Result not found");

        // Permission Check: Owner or Admin
        // Identify current user ID (supports both 'id' and 'applicant_id')
        $currentUserId = $this->user['id'] ?? ($this->user['applicant_id'] ?? 0);

        // If not owner AND not admin (admin has 'view' perm), deny
        // Note: Applicants only have 'view' perm to SEE EXAMS, but here we perform extra check for ownership.
        // Admin has global 'view' permission.
        // So strict check: "Is Owner OR Has Admin View Perm".
        // Issue: Applicant has 'view' perm via IGABaseController override.
        // We need to distinguish "General View" vs "Admin View".
        // Admin permissions come from `hasPermission`.
        // Applicants return true for 'view'.
        // So `hasPermission('view')` is true for applicants too!
        // We must check if they are the owner FIRST.

        $isOwner = ($attempt['user_id'] == $currentUserId);

        // Check if Admin (Employee with specific permissions)
        // Access permissions directly or rely on `hasPermission` but handle Applicant specific logic.
        // Hack: Check if `role_id` exists. Applicants don't have role_id.
        $isAdmin = isset($this->user['role_id']);

        if (!$isOwner && !$isAdmin) {
            die("Permission Denied");
        }

        // Calculate Section-wise Performance
        $stmt = $this->pdo->prepare("
            SELECT s.section_id, s.section_title, 
                   SUM(q.points) as section_max_score,
                   COALESCE(SUM(ur.score_earned), 0) as section_score
            FROM iga_sections s
            JOIN iga_questions q ON s.section_id = q.section_id
            LEFT JOIN iga_user_answers ur ON q.question_id = ur.question_id AND ur.attempt_id = ?
            WHERE s.test_id = ?
            GROUP BY s.section_id
            ORDER BY s.section_order ASC
        ");
        $stmt->execute([$attemptId, $attempt['test_id']]);
        $sectionResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('user/result', [
            'title' => 'ผลการสอบ: ' . $attempt['test_name'],
            'attempt' => $attempt,
            'sectionResults' => $sectionResults
        ]);
    }
}

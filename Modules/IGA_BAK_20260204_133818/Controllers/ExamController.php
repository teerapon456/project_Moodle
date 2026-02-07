<?php

require_once __DIR__ . '/IGABaseController.php';

class ExamController extends IGABaseController
{
    /**
     * User Dashboard - List Available Tests
     */
    public function index()
    {
        $this->requireAuth();

        // Get Published Tests
        $stmt = $this->pdo->prepare("
            SELECT * FROM iga_tests 
            WHERE is_published = 1 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if user has taken any tests (to show status)
        foreach ($tests as &$test) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM iga_user_test_attempts 
                WHERE user_id = ? AND test_id = ? 
                ORDER BY start_time DESC LIMIT 1
            ");
            $stmt->execute([$this->user['id'], $test['test_id']]);
            $test['last_attempt'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $this->render('user/dashboard', [
            'title' => 'การสอบของฉัน',
            'tests' => $tests
        ]);
    }

    /**
     * Test Introduction / Instructions
     */
    public function intro()
    {
        $this->requireAuth();
        $id = $_GET['id'] ?? null;
        if (!$id) die("Invalid ID");

        $stmt = $this->pdo->prepare("SELECT * FROM iga_tests WHERE test_id = ? AND is_published = 1");
        $stmt->execute([$id]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$test) die("Test not available");

        $this->render('user/exam_intro', [
            'title' => $test['test_name'],
            'test' => $test
        ]);
    }

    /**
     * Start Exam (Create Attempt)
     */
    public function start()
    {
        $this->requireAuth();
        $testId = $_POST['test_id'] ?? null;
        if (!$testId) die("Invalid Test ID");

        // Check recent ongoing attempt
        $stmt = $this->pdo->prepare("
            SELECT * FROM iga_user_test_attempts 
            WHERE user_id = ? AND test_id = ? AND is_completed = 0
            ORDER BY start_time DESC LIMIT 1
        ");
        $stmt->execute([$this->user['id'], $testId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            // Create new attempt
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_user_test_attempts (test_id, user_id, start_time, ip_address, user_agent) 
                VALUES (?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $testId,
                $this->user['id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            $attemptId = $this->pdo->lastInsertId();
        } else {
            $attemptId = $attempt['attempt_id'];
        }

        header("Location: index.php?controller=exam&action=paper&attempt_id=$attemptId");
    }

    /**
     * The Exam Paper (Questions)
     */
    public function paper()
    {
        $this->requireAuth();
        $attemptId = $_GET['attempt_id'] ?? null;

        $stmt = $this->pdo->prepare("
            SELECT a.*, t.* 
            FROM iga_user_test_attempts a
            JOIN iga_tests t ON a.test_id = t.test_id
            WHERE a.attempt_id = ? AND a.user_id = ?
        ");
        $stmt->execute([$attemptId, $this->user['id']]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt || $attempt['is_completed']) {
            die("Attempt not valid or already completed.");
        }

        // Fetch Sections and Questions
        // Optimized: Fetch all inclusive
        $stmt = $this->pdo->prepare("SELECT * FROM iga_sections WHERE test_id = ? ORDER BY section_order ASC");
        $stmt->execute([$attempt['test_id']]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sections as &$section) {
            $stmt = $this->pdo->prepare("
                SELECT q.*, qo.option_id, qo.option_text, qo.option_order 
                FROM iga_questions q
                LEFT JOIN iga_question_options qo ON q.question_id = qo.question_id
                WHERE q.section_id = ? 
                ORDER BY q.question_order ASC, qo.option_order ASC
            ");
            $stmt->execute([$section['section_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group options under questions
            $questions = [];
            foreach ($rows as $row) {
                $qid = $row['question_id'];
                if (!isset($questions[$qid])) {
                    $questions[$qid] = [
                        'question_id' => $qid,
                        'question_text' => $row['question_text'],
                        'question_type' => $row['question_type'],
                        'points' => $row['points'],
                        'options' => []
                    ];
                }
                if ($row['option_id']) {
                    $questions[$qid]['options'][] = [
                        'option_id' => $row['option_id'],
                        'option_text' => $row['option_text']
                    ];
                }
            }
            $section['questions'] = array_values($questions);
        }

        $this->render('user/exam_paper', [
            'title' => $attempt['test_name'],
            'attempt' => $attempt,
            'sections' => $sections
        ]);
    }

    /**
     * AJAX Save Answer
     */
    public function saveAnswer()
    {
        $this->requireAuth();
        // Get JSON Input directly since BaseController usually merges, but let's be explicit for AJAX
        $data = json_decode(file_get_contents('php://input'), true);

        $attemptId = $data['attempt_id'] ?? null;
        $questionId = $data['question_id'] ?? null;
        $answer = $data['answer'] ?? null;
        $type = $data['type'] ?? 'option'; // option or text

        if (!$attemptId || !$questionId) return $this->error("Invalid Data");

        // Verify Attempt Ownership
        $stmt = $this->pdo->prepare("SELECT user_id, is_completed FROM iga_user_test_attempts WHERE attempt_id = ?");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt || $attempt['user_id'] != $this->user['id'] || $attempt['is_completed']) {
            return $this->error("Permission Denied or Exam Completed");
        }

        // Check if answer exists
        $stmt = $this->pdo->prepare("SELECT user_answer_id FROM iga_user_answers WHERE attempt_id = ? AND question_id = ?");
        $stmt->execute([$attemptId, $questionId]);
        $existing = $stmt->fetchColumn();

        if ($type == 'option') {
            $column = 'selected_option_id';
            $ansValue = $answer;
        } else {
            $column = 'user_answer_text';
            $ansValue = $answer;
        }

        if ($existing) {
            $sql = "UPDATE iga_user_answers SET $column = ?, answered_at = NOW() WHERE user_answer_id = ?";
            $this->pdo->prepare($sql)->execute([$ansValue, $existing]);
        } else {
            $sql = "INSERT INTO iga_user_answers (attempt_id, question_id, $column, answered_at) VALUES (?, ?, ?, NOW())";
            $this->pdo->prepare($sql)->execute([$attemptId, $questionId, $ansValue]);
        }

        echo json_encode(['success' => true]);
        exit; // JSON only
    }

    /**
     * Submit Exam
     */
    public function submit()
    {
        $this->requireAuth();
        $attemptId = $_POST['attempt_id'] ?? null;

        if (!$attemptId) die("Invalid Attempt ID");

        // Mark as completed
        $stmt = $this->pdo->prepare("
            UPDATE iga_user_test_attempts 
            SET is_completed = 1, end_time = NOW() 
            WHERE attempt_id = ? AND user_id = ?
        ");
        $stmt->execute([$attemptId, $this->user['id']]);

        // Trigger Grading (Simple auto-grade for now)
        $this->gradeAttempt($attemptId);

        // Redirect to Dashboard or Result (TODO: Result Page)
        echo "<script>alert('ส่งข้อสอบเรียบร้อยแล้ว'); window.location='index.php?controller=exam&action=index';</script>";
    }

    /**
     * Internal Grading Helper
     */
    private function gradeAttempt($attemptId)
    {
        // Fetch all answers and question correct options
        $stmt = $this->pdo->prepare("
            SELECT a.user_answer_id, a.question_id, a.selected_option_id, q.points, q.question_type,
                   (SELECT is_correct FROM iga_question_options WHERE option_id = a.selected_option_id) as is_correct_option
            FROM iga_user_answers a
            JOIN iga_questions q ON a.question_id = q.question_id
            WHERE a.attempt_id = ?
        ");
        $stmt->execute([$attemptId]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalScore = 0;

        foreach ($answers as $ans) {
            $score = 0;
            if ($ans['question_type'] == 'single_choice' && $ans['is_correct_option'] == 1) {
                $score = $ans['points'];
            }
            // Add other logic for multi-choice/text later

            if ($score > 0) {
                $totalScore += $score;
                $this->pdo->prepare("UPDATE iga_user_answers SET score_earned = ?, is_reviewed = 1 WHERE user_answer_id = ?")
                    ->execute([$score, $ans['user_answer_id']]);
            }
        }

        // Update Total Score
        $this->pdo->prepare("UPDATE iga_user_test_attempts SET total_score = ? WHERE attempt_id = ?")->execute([$totalScore, $attemptId]);
    }
}

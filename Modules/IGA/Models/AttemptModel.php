<?php

class AttemptModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Start or resume a test attempt
     */
    public function getOrCreateAttempt($userId, $testId, $attemptId = null)
    {
        if ($attemptId) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM iga_user_test_attempts 
                WHERE attempt_id = :aid AND user_id = :uid AND test_id = :tid AND is_completed = 0
            ");
            $stmt->execute([':aid' => $attemptId, ':uid' => $userId, ':tid' => $testId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM iga_user_test_attempts 
                WHERE user_id = :uid AND test_id = :tid AND is_completed = 0 
                ORDER BY start_time DESC LIMIT 1
            ");
            $stmt->execute([':uid' => $userId, ':tid' => $testId]);
        }

        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            // Create new attempt
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_user_test_attempts (user_id, test_id, start_time, is_completed, current_question_index, time_spent_seconds) 
                VALUES (:uid, :tid, NOW(), 0, 0, 0)
            ");
            $stmt->execute([':uid' => $userId, ':tid' => $testId]);
            $attemptId = $this->pdo->lastInsertId();

            return $this->getOrCreateAttempt($userId, $testId, $attemptId);
        }

        return $attempt;
    }

    /**
     * Get questions for an attempt, matching legacy randomization logic
     */
    public function getAttemptQuestions($attemptId, $testId)
    {
        // 1. Check if questions are already locked for this attempt
        $stmt = $this->pdo->prepare("
            SELECT q.*, aq.shown_order, s.section_name, s.section_order, s.duration_minutes as section_duration,
                   c.category_name, c.category_id
            FROM iga_user_attempt_questions aq
            JOIN iga_questions q ON aq.question_id = q.question_id
            JOIN iga_sections s ON q.section_id = s.section_id
            LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
            WHERE aq.attempt_id = :aid
            ORDER BY aq.shown_order ASC
        ");
        $stmt->execute([':aid' => $attemptId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($questions)) {
            // Attach options to each question
            foreach ($questions as &$q) {
                $q['question_options'] = $this->getOptionsByQuestionId($q['question_id']);
            }
            return $questions;
        }

        // 2. Questions not locked yet -> Perform randomization and lock
        return $this->initializeAttemptQuestions($attemptId, $testId);
    }

    /**
     * Helper to get options for a question
     */
    private function getOptionsByQuestionId($questionId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iga_question_options WHERE question_id = :qid ORDER BY option_id ASC");
        $stmt->execute([':qid' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function initializeAttemptQuestions($attemptId, $testId)
    {
        static $recursionGuard = 0;
        if ($recursionGuard++ > 1) return [];

        // Get random settings
        $stmt = $this->pdo->prepare("SELECT * FROM iga_test_random_question_settings WHERE test_id = :tid");
        $stmt->execute([':tid' => $testId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        $isRandom = $settings && $settings['is_random_mode'];

        // Get all sections and questions - FIRST column must be section_id for FETCH_GROUP
        $stmt = $this->pdo->prepare("
            SELECT s.section_id, q.*, s.section_name, s.section_order, s.duration_minutes as section_duration
            FROM iga_sections s
            JOIN iga_questions q ON s.section_id = q.section_id
            WHERE s.test_id = :tid
            ORDER BY s.section_order ASC, q.question_order ASC
        ");
        $stmt->execute([':tid' => $testId]);
        $allQuestions = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        if (empty($allQuestions)) {
            return [];
        }

        $selectedQuestions = [];

        if (!$isRandom) {
            foreach ($allQuestions as $sid => $qs) {
                foreach ($qs as $q) $selectedQuestions[] = $q;
            }
        } else {
            $alwaysInclude = json_decode($settings['always_include_questions'] ?? '[]', true);
            $sectionCounts = json_decode($settings['section_random_counts'] ?? '[]', true);

            // Reformat sectionCounts if needed
            $secQuota = [];
            if (is_array($sectionCounts)) {
                $isAssoc = array_keys($sectionCounts) !== range(0, count($sectionCounts) - 1);
                if ($isAssoc) {
                    foreach ($sectionCounts as $sid => $val) $secQuota[(int)$sid] = (int)$val;
                } else {
                    foreach ($sectionCounts as $item) {
                        if (isset($item['section_id'], $item['count'])) $secQuota[(int)$item['section_id']] = (int)$item['count'];
                    }
                }
            }

            foreach ($allQuestions as $sid => $qs) {
                $quota = $secQuota[(int)$sid] ?? 0;
                $secPicks = $this->pickQuestionsForSection($qs, $quota, $alwaysInclude);
                foreach ($secPicks as $pq) $selectedQuestions[] = $pq;
            }
        }

        // Lock questions in DB
        if (!empty($selectedQuestions)) {
            $insertStmt = $this->pdo->prepare("INSERT INTO iga_user_attempt_questions (attempt_id, question_id, shown_order) VALUES (:aid, :qid, :order)");
            foreach ($selectedQuestions as $idx => $sq) {
                $insertStmt->execute([
                    ':aid' => $attemptId,
                    ':qid' => $sq['question_id'],
                    ':order' => $idx + 1
                ]);
            }
        }

        return $this->getAttemptQuestions($attemptId, $testId);
    }

    private function pickQuestionsForSection(array $qList, int $quota, array $alwaysIncludeIds)
    {
        $alwaysSet = array_flip($alwaysIncludeIds);
        $always = [];
        $others = [];
        foreach ($qList as $q) {
            if (isset($alwaysSet[$q['question_id']])) $always[] = $q;
            else $others[] = $q;
        }

        if ($quota <= 0) return $always;

        // Group by category to ensure coverage
        $byCat = [];
        foreach ($others as $q) {
            $cid = $q['category_id'] ?? 0;
            $byCat[$cid][] = $q;
        }
        foreach ($byCat as &$arr) shuffle($arr);

        $remaining = max(0, $quota - count($always));
        $picks = [];

        if ($remaining > 0) {
            // 1st pass: 1 from each cat not in always
            $coveredCats = [];
            foreach ($always as $aq) $coveredCats[$aq['category_id'] ?? 0] = true;

            foreach ($byCat as $cid => &$arr) {
                if ($remaining <= 0) break;
                if (!isset($coveredCats[$cid]) && !empty($arr)) {
                    $picks[] = array_shift($arr);
                    $remaining--;
                }
            }
        }

        if ($remaining > 0) {
            // 2nd pass: fill from remaining pool
            $pool = [];
            foreach ($byCat as $arr) foreach ($arr as $q) $pool[] = $q;
            shuffle($pool);
            $fill = array_slice($pool, 0, $remaining);
            $picks = array_merge($picks, $fill);
        }

        return array_merge($always, $picks);
    }

    /**
     * Save/Update question answer
     */
    public function saveAnswer($attemptId, $questionId, $answerText, $timeSpent = 0)
    {
        // Get question info to calculate score
        $stmt = $this->pdo->prepare("SELECT question_type, score FROM iga_questions WHERE question_id = :qid");
        $stmt->execute([':qid' => $questionId]);
        $qInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $isCorrect = 0;
        $scoreEarned = 0;

        if ($qInfo['question_type'] == 'multiple_choice' || $qInfo['question_type'] == 'true_false') {
            $stmt = $this->pdo->prepare("SELECT is_correct FROM iga_question_options WHERE question_id = :qid AND option_id = :oid");
            $stmt->execute([':qid' => $questionId, ':oid' => $answerText]);
            $opt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($opt && $opt['is_correct']) {
                $isCorrect = 1;
                $scoreEarned = $qInfo['score'];
            }
        } elseif ($qInfo['question_type'] == 'accept') {
            $isCorrect = 1;
            $scoreEarned = $qInfo['score'];
        }

        // Check if answer exists
        $stmt = $this->pdo->prepare("SELECT user_answer_id FROM iga_user_answers WHERE attempt_id = :aid AND question_id = :qid");
        $stmt->execute([':aid' => $attemptId, ':qid' => $questionId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE iga_user_answers SET 
                user_answer_text = :text, is_correct = :corr, score_earned = :score, 
                answer_time_seconds = :time, answered_at = NOW() 
                WHERE user_answer_id = :id
            ");
            return $stmt->execute([
                ':text' => $answerText,
                ':corr' => $isCorrect,
                ':score' => $scoreEarned,
                ':time' => $timeSpent,
                ':id' => $existing['user_answer_id']
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_user_answers (attempt_id, question_id, user_answer_text, is_correct, score_earned, answer_time_seconds, answered_at) 
                VALUES (:aid, :qid, :text, :corr, :score, :time, NOW())
            ");
            return $stmt->execute([
                ':aid' => $attemptId,
                ':qid' => $questionId,
                ':text' => $answerText,
                ':corr' => $isCorrect,
                ':score' => $scoreEarned,
                ':time' => $timeSpent
            ]);
        }
    }

    /**
     * Get user answers for an attempt
     */
    public function getAttemptAnswers($attemptId)
    {
        $stmt = $this->pdo->prepare("SELECT question_id, user_answer_text FROM iga_user_answers WHERE attempt_id = :aid");
        $stmt->execute([':aid' => $attemptId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Update attempt state (timer and current index)
     */
    public function updateAttemptState($attemptId, $index, $timeSpentOverall)
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_user_test_attempts SET 
            current_question_index = :idx, time_spent_seconds = :time, updated_at = NOW() 
            WHERE attempt_id = :aid
        ");
        return $stmt->execute([':aid' => $attemptId, ':idx' => $index, ':time' => $timeSpentOverall]);
    }

    /**
     * Update AFK (Tab-Switch) count
     */
    public function updateAfkCount($attemptId, $afkCount)
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_user_test_attempts SET 
            afk_count = :afk, updated_at = NOW() 
            WHERE attempt_id = :aid
        ");
        return $stmt->execute([':aid' => $attemptId, ':afk' => $afkCount]);
    }

    /**
     * Update section time
     */
    public function updateSectionTime($attemptId, $sectionId, $timeSpent, $startTime = null)
    {
        $stmt = $this->pdo->prepare("SELECT section_time_id FROM iga_user_section_times WHERE attempt_id = :aid AND section_id = :sid");
        $stmt->execute([':aid' => $attemptId, ':sid' => $sectionId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $sql = "UPDATE iga_user_section_times SET time_spent_seconds = :time";
            $params = [':time' => $timeSpent, ':id' => $existing['section_time_id']];
            if ($startTime) {
                $sql .= ", start_timestamp = :start";
                $params[':start'] = date('Y-m-d H:i:s', $startTime);
            }
            $sql .= " WHERE section_time_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_user_section_times (attempt_id, section_id, time_spent_seconds, start_timestamp) 
                VALUES (:aid, :sid, :time, :start)
            ");
            return $stmt->execute([
                ':aid' => $attemptId,
                ':sid' => $sectionId,
                ':time' => $timeSpent,
                ':start' => $startTime ? date('Y-m-d H:i:s', $startTime) : null
            ]);
        }
    }

    /**
     * Get section times
     */
    public function getSectionTimes($attemptId)
    {
        $stmt = $this->pdo->prepare("SELECT section_id, time_spent_seconds, start_timestamp FROM iga_user_section_times WHERE attempt_id = :aid");
        $stmt->execute([':aid' => $attemptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finish attempt and calculate total score
     */
    public function finishAttempt($attemptId, $afkCount = 0, $submissionStatus = 'normal')
    {
        // 1. Calculate total score
        $stmt = $this->pdo->prepare("SELECT SUM(score_earned) FROM iga_user_answers WHERE attempt_id = :aid");
        $stmt->execute([':aid' => $attemptId]);
        $totalScore = (float)$stmt->fetchColumn();

        // 2. Mark as completed
        $stmt = $this->pdo->prepare("
            UPDATE iga_user_test_attempts SET 
            is_completed = 1, 
            total_score = :score, 
            afk_count = :afk,
            submission_status = :status,
            end_time = NOW(), 
            updated_at = NOW() 
            WHERE attempt_id = :aid
        ");
        return $stmt->execute([
            ':aid' => $attemptId,
            ':score' => $totalScore,
            ':afk' => $afkCount,
            ':status' => $submissionStatus
        ]);
    }
}

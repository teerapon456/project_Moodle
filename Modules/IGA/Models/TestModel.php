<?php

class TestModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Auto-unpublish tests that have passed their unpublished_at date.
     */
    public function autoUnpublishExpired()
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_tests 
            SET is_published = 0 
            WHERE is_published = 1 
              AND unpublished_at IS NOT NULL 
              AND unpublished_at <= NOW()
        ");
        return $stmt->execute();
    }

    // ==========================================
    //  Dashboard / User-facing queries
    // ==========================================

    /**
     * Get published tests visible to a specific user.
     * Replicates legacy logic: emplevel, orgunit, emptype, individual targeting.
     *
     * @param int|null    $emplevelId   User's emplevel_id
     * @param string|null $orgUnitName  User's OrgUnitName
     * @param string|null $emptype      User's emptype
     * @param string|null $userId       User's id (for individual targeting)
     * @return array
     */
    public function getAvailableTestsForUser($emplevelId, $orgUnitName, $emptype, $userId)
    {
        // Auto-unpublish expired tests first
        $this->autoUnpublishExpired();

        $sql = "
            SELECT DISTINCT
                t.test_id, t.test_name, t.description, t.test_no, t.language,
                t.duration_minutes, t.is_published, t.emptype,
                t.min_passing_score, t.show_result_immediately,
                IFNULL(trc.is_random_mode, 0) AS is_random_mode,
                trc.always_include_questions,
                trc.section_random_counts
            FROM iga_tests t
            LEFT JOIN iga_test_random_question_settings trc ON t.test_id = trc.test_id
            WHERE t.is_published = 1
              AND (t.published_at IS NULL OR t.published_at <= NOW())
              AND (t.unpublished_at IS NULL OR t.unpublished_at > NOW())
              AND (
                    /* Normal user/employee: check emplevel + orgunit + emptype */
                    (
                      (t.emptype IS NULL OR t.emptype = 'all' OR t.emptype = :emptype1)
                      AND (
                        NOT EXISTS (SELECT 1 FROM iga_test_emplevels te0 WHERE te0.test_id = t.test_id)
                        OR EXISTS (
                            SELECT 1 FROM iga_test_emplevels te
                            WHERE te.test_id = t.test_id AND te.level_id = :emp_level1
                        )
                      )
                      AND (
                        NOT EXISTS (SELECT 1 FROM iga_test_orgunits to0 WHERE to0.test_id = t.test_id)
                        OR EXISTS (
                            SELECT 1 FROM iga_test_orgunits torg
                            WHERE torg.test_id = t.test_id AND torg.orgunitname = :orgunit1
                        )
                      )
                    )
                    OR EXISTS (
                        SELECT 1 FROM iga_test_users tu
                        WHERE tu.test_id = t.test_id AND tu.user_id = :user_id1
                    )
                  )
            ORDER BY t.test_name ASC
        ";

        $params = [
            ':emptype1'   => $emptype ?? 'all',
            ':emp_level1' => $emplevelId ?? 0,
            ':orgunit1'  => $orgUnitName ?? '',
            ':user_id1'  => $userId ?? 0,
        ];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute random total count for each test
        foreach ($tests as &$test) {
            $test['random_total_count'] = 0;
            if (!empty($test['is_random_mode'])) {
                $test['random_total_count'] = $this->computeRandomTotal(
                    $test['always_include_questions'] ?? null,
                    $test['section_random_counts'] ?? null
                );
            }
        }

        return $tests;
    }

    /**
     * Get user's test attempts (for dashboard status tracking)
     */
    public function getUserAttempts($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT uta.attempt_id, uta.test_id, uta.is_completed,
                   uta.total_score, uta.start_time, uta.end_time,
                   uta.time_spent_seconds, uta.current_question_index,
                   t.test_no, t.test_name
            FROM iga_user_test_attempts uta
            JOIN iga_tests t ON uta.test_id = t.test_id
            WHERE uta.user_id = :uid
            ORDER BY uta.start_time DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Build dashboard test list with status (not_started / in_progress / completed).
     * Groups by test_no for multi-language tests.
     */
    public function buildDashboardTests($emplevelId, $orgUnitName, $emptype, $userId)
    {
        $publishedTests = $this->getAvailableTestsForUser($emplevelId, $orgUnitName, $emptype, $userId);
        $userAttempts = $this->getUserAttempts($userId);

        // Classify attempts by lookup_key (test_no or test_id)
        $inProgressData = [];
        $completedData = [];

        foreach ($userAttempts as $attempt) {
            $lookupKey = ($attempt['test_no'] !== null) ? $attempt['test_no'] : $attempt['test_id'];
            if (!empty($attempt['is_completed'])) {
                $completedData[$lookupKey] = true;
            } else {
                if (!isset($inProgressData[$lookupKey])) {
                    $inProgressData[$lookupKey] = $attempt;
                }
            }
        }

        // Group by test_no for multi-language popup
        $groupedByTestNo = [];
        foreach ($publishedTests as $test) {
            if ($test['test_no'] !== null) {
                $groupedByTestNo[$test['test_no']][] = $test;
            }
        }

        // Build final list
        $result = [];
        $processedKeys = [];

        foreach ($publishedTests as $test) {
            $testNo = $test['test_no'];
            $lookupKey = ($testNo !== null) ? $testNo : $test['test_id'];

            // Skip if already processed or completed
            if (isset($processedKeys[$lookupKey]) || isset($completedData[$lookupKey])) {
                continue;
            }

            $status = 'not_started';
            $attemptId = null;
            if (isset($inProgressData[$lookupKey])) {
                $status = 'in_progress';
                $attemptId = $inProgressData[$lookupKey]['attempt_id'];
            }

            // Build multi-language options
            $languageOptions = [];
            if ($testNo !== null && isset($groupedByTestNo[$testNo])) {
                foreach ($groupedByTestNo[$testNo] as $langOption) {
                    $olKey = ($langOption['test_no'] !== null) ? $langOption['test_no'] : $langOption['test_id'];
                    if (!isset($completedData[$olKey]) && !isset($inProgressData[$olKey])) {
                        $languageOptions[] = $langOption;
                    }
                }
            } else {
                $languageOptions[] = $test;
            }

            $test['status'] = $status;
            $test['attempt_id'] = $attemptId;
            $test['language_options'] = $languageOptions;

            $result[] = $test;
            $processedKeys[$lookupKey] = true;
        }

        // Sort: in_progress first, then by name
        usort($result, function ($a, $b) {
            if ($a['status'] === 'in_progress' && $b['status'] !== 'in_progress') return -1;
            if ($a['status'] !== 'in_progress' && $b['status'] === 'in_progress') return 1;
            return strcmp($a['test_name'], $b['test_name']);
        });

        return $result;
    }

    /**
     * Compute random total from JSON settings (matching legacy logic)
     */
    private function computeRandomTotal(?string $alwaysJson, ?string $sectionCountsJson): int
    {
        $total = 0;
        if (!empty($sectionCountsJson)) {
            $sc = json_decode($sectionCountsJson, true);
            if (is_array($sc)) {
                $isAssoc = array_keys($sc) !== range(0, count($sc) - 1);
                if ($isAssoc) {
                    foreach ($sc as $cnt) $total += max(0, (int)$cnt);
                } else {
                    foreach ($sc as $row) {
                        if (is_array($row) && isset($row['count'])) {
                            $total += max(0, (int)$row['count']);
                        }
                    }
                }
            }
        }
        return $total;
    }

    // ==========================================
    //  Admin CRUD
    // ==========================================

    /**
     * Get all tests (admin view) with search/pagination
     */
    public function getAllTests($searchQuery = '', $offset = 0, $limit = 10)
    {
        $sql = "
            SELECT t.test_id, t.test_name, t.description, t.is_published,
                   t.duration_minutes, t.created_at, t.published_at, t.unpublished_at,
                   t.test_no, t.language, t.emptype, t.min_passing_score,
                   t.show_result_immediately, t.category_type_id,
                   u.fullname AS creator_name,
                   ct.type_name AS category_name,
                   (SELECT COUNT(*) FROM iga_sections s WHERE s.test_id = t.test_id) AS section_count,
                   (SELECT COUNT(*) FROM iga_questions q
                    JOIN iga_sections s2 ON q.section_id = s2.section_id
                    WHERE s2.test_id = t.test_id) AS question_count,
                   IFNULL(trc.is_random_mode, 0) AS is_random_mode
            FROM iga_tests t
            LEFT JOIN users u ON t.created_by_user_id = u.id
            LEFT JOIN iga_category_types ct ON t.category_type_id = ct.type_id
            LEFT JOIN iga_test_random_question_settings trc ON t.test_id = trc.test_id
        ";

        $params = [];
        if (!empty($searchQuery)) {
            $sql .= " WHERE (t.test_name LIKE :search OR t.description LIKE :search2)";
            $params[':search'] = '%' . $searchQuery . '%';
            $params[':search2'] = '%' . $searchQuery . '%';
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => &$v) $stmt->bindParam($k, $v);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count tests
     */
    public function getTestCount($searchQuery = '')
    {
        $sql = "SELECT COUNT(*) FROM iga_tests t";
        $params = [];
        if (!empty($searchQuery)) {
            $sql .= " WHERE (t.test_name LIKE :search OR t.description LIKE :search2)";
            $params[':search'] = '%' . $searchQuery . '%';
            $params[':search2'] = '%' . $searchQuery . '%';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get single test by ID
     */
    public function getTestById($testId)
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, u.fullname AS creator_name, ct.type_name AS category_name,
                   IFNULL(trc.is_random_mode, 0) AS is_random_mode,
                   trc.always_include_questions, trc.section_random_counts
            FROM iga_tests t
            LEFT JOIN users u ON t.created_by_user_id = u.id
            LEFT JOIN iga_category_types ct ON t.category_type_id = ct.type_id
            LEFT JOIN iga_test_random_question_settings trc ON t.test_id = trc.test_id
            WHERE t.test_id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $testId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create test
     */
    public function createTest($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO iga_tests (test_name, description, duration_minutes,
                show_result_immediately, min_passing_score, category_type_id,
                emptype, test_no, language, is_published, published_at, unpublished_at,
                created_by_user_id, created_at)
            VALUES (:name, :desc, :dur, :show, :min, :cat, :etype, :tno, :lang,
                :pub, :pub_at, :unpub_at, :by, NOW())
        ");
        $stmt->execute([
            ':name'     => $data['test_name'],
            ':desc'     => $data['description'] ?? '',
            ':dur'      => $data['duration_minutes'] ?? 0,
            ':show'     => $data['show_result_immediately'] ?? 1,
            ':min'      => $data['min_passing_score'] ?? null,
            ':cat'      => $data['category_type_id'] ?? null,
            ':etype'    => $data['emptype'] ?? 'all',
            ':tno'      => $data['test_no'] ?? null,
            ':lang'     => $data['language'] ?? 'TH',
            ':pub'      => $data['is_published'] ?? 0,
            ':pub_at'   => $data['published_at'] ?? null,
            ':unpub_at' => $data['unpublished_at'] ?? null,
            ':by'       => $data['created_by_user_id'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update test
     */
    public function updateTest($testId, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE iga_tests SET
                test_name = :name, description = :desc,
                duration_minutes = :dur, show_result_immediately = :show,
                min_passing_score = :min, category_type_id = :cat,
                emptype = :etype, test_no = :tno, language = :lang,
                is_published = :pub, published_at = :pub_at, unpublished_at = :unpub_at,
                updated_at = NOW()
            WHERE test_id = :id
        ");
        return $stmt->execute([
            ':id'       => $testId,
            ':name'     => $data['test_name'],
            ':desc'     => $data['description'] ?? '',
            ':dur'      => $data['duration_minutes'] ?? 0,
            ':show'     => $data['show_result_immediately'] ?? 1,
            ':min'      => $data['min_passing_score'] ?? null,
            ':cat'      => $data['category_type_id'] ?? null,
            ':etype'    => $data['emptype'] ?? 'all',
            ':tno'      => $data['test_no'] ?? null,
            ':lang'     => $data['language'] ?? 'TH',
            ':pub'      => $data['is_published'] ?? 0,
            ':pub_at'   => $data['published_at'] ?? null,
            ':unpub_at' => $data['unpublished_at'] ?? null,
        ]);
    }

    public function publishTest($testId, $publishedAt = null, $unpublishedAt = null)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_tests SET is_published = 1, published_at = :pub, unpublished_at = :unpub, updated_at = NOW() WHERE test_id = :id");
        return $stmt->execute([':id' => $testId, ':pub' => $publishedAt ?? date('Y-m-d H:i:s'), ':unpub' => $unpublishedAt]);
    }

    public function unpublishTest($testId)
    {
        $stmt = $this->pdo->prepare("UPDATE iga_tests SET is_published = 0, updated_at = NOW() WHERE test_id = :id");
        return $stmt->execute([':id' => $testId]);
    }

    public function deleteTest($testId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM iga_tests WHERE test_id = :id");
        return $stmt->execute([':id' => $testId]);
    }

    /**
     * Clone a test and all its related data (sections, questions, options, targeting)
     */
    public function cloneTest($testId, $createdByUserId)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Get original test to verify existence
            $orig = $this->getTestById($testId);
            if (!$orig) throw new Exception("Original test not found");

            // 2. Clone test row
            // We use a direct INSERT SELECT for efficiency where possible
            // Note: we don't use 'test_id' as it's auto-increment
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_tests (
                    test_name, description, duration_minutes, show_result_immediately, 
                    min_passing_score, category_type_id, emptype, test_no, language,
                    created_by_user_id, is_published, created_at
                )
                SELECT 
                    CONCAT('[Copy] ', test_name), description, duration_minutes, show_result_immediately, 
                    min_passing_score, category_type_id, emptype, test_no, language,
                    :by, 0, NOW()
                FROM iga_tests WHERE test_id = :orig_id
            ");
            $stmt->execute([':orig_id' => $testId, ':by' => $createdByUserId]);
            $newTestId = $this->pdo->lastInsertId();

            // 3. Clone random settings
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_test_random_question_settings (
                    test_id, is_random_mode, always_include_questions, section_random_counts, created_at
                )
                SELECT :new_id, is_random_mode, always_include_questions, section_random_counts, NOW()
                FROM iga_test_random_question_settings WHERE test_id = :orig_id
            ");
            $stmt->execute([':new_id' => $newTestId, ':orig_id' => $testId]);

            // 4. Clone targeting (emplevels)
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_test_emplevels (test_id, level_id)
                SELECT :new_id, level_id FROM iga_test_emplevels WHERE test_id = :orig_id
            ");
            $stmt->execute([':new_id' => $newTestId, ':orig_id' => $testId]);

            // 5. Clone targeting (orgunits)
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_test_orgunits (test_id, orgunitname)
                SELECT :new_id, orgunitname FROM iga_test_orgunits WHERE test_id = :orig_id
            ");
            $stmt->execute([':new_id' => $newTestId, ':orig_id' => $testId]);

            // 6. Clone targeting (users)
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_test_users (test_id, user_id)
                SELECT :new_id, user_id FROM iga_test_users WHERE test_id = :orig_id
            ");
            $stmt->execute([':new_id' => $newTestId, ':orig_id' => $testId]);

            // 7. Clone sections and their sub-data
            // We need to iterate through sections to get their new IDs for questions
            $sections = $this->pdo->prepare("SELECT * FROM iga_sections WHERE test_id = :id");
            $sections->execute([':id' => $testId]);
            $sectionsList = $sections->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sectionsList as $sec) {
                // Insert section
                $stmt = $this->pdo->prepare("
                    INSERT INTO iga_sections (test_id, section_name, description, duration_minutes, section_order, created_at)
                    VALUES (:tid, :name, :desc, :dur, :ord, NOW())
                ");
                $stmt->execute([
                    ':tid'  => $newTestId,
                    ':name' => $sec['section_name'],
                    ':desc' => $sec['description'],
                    ':dur'  => $sec['duration_minutes'],
                    ':ord'  => $sec['section_order']
                ]);
                $newSecId = $this->pdo->lastInsertId();

                // Clone questions for this section
                $questions = $this->pdo->prepare("SELECT * FROM iga_questions WHERE section_id = :sid");
                $questions->execute([':sid' => $sec['section_id']]);
                $questionsList = $questions->fetchAll(PDO::FETCH_ASSOC);

                foreach ($questionsList as $q) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO iga_questions (section_id, category_id, question_text, question_type, score, question_order, is_critical, created_at)
                        VALUES (:sid, :cat, :txt, :type, :score, :ord, :crit, NOW())
                    ");
                    $stmt->execute([
                        ':sid'   => $newSecId,
                        ':cat'   => $q['category_id'],
                        ':txt'   => $q['question_text'],
                        ':type'  => $q['question_type'],
                        ':score' => $q['score'],
                        ':ord'   => $q['question_order'],
                        ':crit'  => $q['is_critical']
                    ]);
                    $newQid = $this->pdo->lastInsertId();

                    // Clone options for this question
                    $stmt = $this->pdo->prepare("
                        INSERT INTO iga_question_options (question_id, option_text, is_correct, created_at)
                        SELECT :new_qid, option_text, is_correct, NOW()
                        FROM iga_question_options WHERE question_id = :orig_qid
                    ");
                    $stmt->execute([':new_qid' => $newQid, ':orig_qid' => $q['question_id']]);
                }
            }

            $this->pdo->commit();
            return $newTestId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get sections for a test
     */
    public function getSections($testId)
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*,
                   (SELECT COUNT(*) FROM iga_questions q WHERE q.section_id = s.section_id) AS question_count
            FROM iga_sections s WHERE s.test_id = :id ORDER BY s.section_order ASC
        ");
        $stmt->execute([':id' => $testId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get emplevel targeting for a test
     */
    public function getTestEmpLevels($testId)
    {
        $stmt = $this->pdo->prepare("
            SELECT te.level_id, el.level_code AS emplevel_name
            FROM iga_test_emplevels te
            LEFT JOIN emplevelcode el ON te.level_id = el.level_id
            WHERE te.test_id = :id
        ");
        $stmt->execute([':id' => $testId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get orgunit targeting for a test
     */
    public function getTestOrgUnits($testId)
    {
        $stmt = $this->pdo->prepare("SELECT orgunitname FROM iga_test_orgunits WHERE test_id = :id");
        $stmt->execute([':id' => $testId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get random question settings for a test
     */
    public function getRandomSettings($testId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM iga_test_random_question_settings WHERE test_id = :id LIMIT 1");
        $stmt->execute([':id' => $testId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Set employee level targeting (Delete existing and insert new)
     */
    public function setTestEmpLevels($testId, array $levelIds)
    {
        $this->pdo->prepare("DELETE FROM iga_test_emplevels WHERE test_id = :tid")->execute([':tid' => $testId]);
        if (empty($levelIds)) return true;

        $stmt = $this->pdo->prepare("INSERT INTO iga_test_emplevels (test_id, level_id) VALUES (:tid, :lid)");
        foreach ($levelIds as $lid) {
            $stmt->execute([':tid' => $testId, ':lid' => $lid]);
        }
        return true;
    }

    /**
     * Set orgunit targeting (Delete existing and insert new)
     */
    public function setTestOrgUnits($testId, array $orgUnitNames)
    {
        $this->pdo->prepare("DELETE FROM iga_test_orgunits WHERE test_id = :tid")->execute([':tid' => $testId]);
        if (empty($orgUnitNames)) return true;

        $stmt = $this->pdo->prepare("INSERT INTO iga_test_orgunits (test_id, orgunitname) VALUES (:tid, :name)");
        foreach ($orgUnitNames as $name) {
            $stmt->execute([':tid' => $testId, ':name' => $name]);
        }
        return true;
    }

    /**
     * Set/Update random question settings
     */
    public function setRandomSettings($testId, array $data)
    {
        $stmt = $this->pdo->prepare("SELECT test_id FROM iga_test_random_question_settings WHERE test_id = :tid");
        $stmt->execute([':tid' => $testId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $this->pdo->prepare("
                UPDATE iga_test_random_question_settings SET
                    is_random_mode = :rand,
                    always_include_questions = :always,
                    section_random_counts = :counts,
                    updated_at = NOW()
                WHERE test_id = :tid
            ");
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO iga_test_random_question_settings (test_id, is_random_mode, always_include_questions, section_random_counts, created_at)
                VALUES (:tid, :rand, :always, :counts, NOW())
            ");
        }

        return $stmt->execute([
            ':tid' => $testId,
            ':rand' => $data['is_random_mode'] ?? 0,
            ':always' => $data['always_include_questions'] ?? '[]',
            ':counts' => $data['section_random_counts'] ?? '[]'
        ]);
    }

    /**
     * Get all unique employee levels from the system
     */
    public function getAllEmpLevels()
    {
        return $this->pdo->query("SELECT level_id, level_code AS level_name FROM emplevelcode ORDER BY level_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all unique org units from the system
     */
    public function getAllOrgUnits()
    {
        return $this->pdo->query("SELECT DISTINCT OrgUnitName FROM users WHERE OrgUnitName IS NOT NULL AND OrgUnitName != '' ORDER BY OrgUnitName ASC")->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get targeted users for a test
     */
    public function getTestUsers($testId)
    {
        $stmt = $this->pdo->prepare("
            SELECT tu.user_id, u.fullname, u.username, u.Level3Name as department
            FROM iga_test_users tu
            JOIN users u ON tu.user_id = u.id
            WHERE tu.test_id = :tid
        ");
        $stmt->execute([':tid' => $testId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Set targeted users for a test
     */
    public function setTestUsers($testId, array $userIds)
    {
        $this->pdo->prepare("DELETE FROM iga_test_users WHERE test_id = :tid")->execute([':tid' => $testId]);
        if (empty($userIds)) return true;

        $stmt = $this->pdo->prepare("INSERT INTO iga_test_users (test_id, user_id) VALUES (:tid, :uid)");
        foreach ($userIds as $uid) {
            $stmt->execute([':tid' => $testId, ':uid' => $uid]);
        }
        return true;
    }

    /**
     * Search users by name or username
     */
    public function searchUsers($query)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, fullname, username, Level3Name as department
            FROM users
            WHERE (fullname LIKE :q OR username LIKE :q2)
              AND is_active = 1
            LIMIT 20
        ");
        $q = "%" . $query . "%";
        $stmt->execute([':q' => $q, ':q2' => $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all questions for a test (organized by section)
     */
    public function getAllQuestions($testId)
    {
        $stmt = $this->pdo->prepare("
            SELECT q.question_id, q.question_text, s.section_name, s.section_id
            FROM iga_questions q
            JOIN iga_sections s ON q.section_id = s.section_id
            WHERE s.test_id = :tid
            ORDER BY s.section_order ASC, q.question_order ASC
        ");
        $stmt->execute([':tid' => $testId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

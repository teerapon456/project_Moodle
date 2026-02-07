<?php

require_once __DIR__ . '/IGABaseController.php';

class QuestionController extends IGABaseController
{
    /**
     * Store Question
     */
    public function store($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $testId = $data['test_id'] ?? null;
        $sectionId = $data['section_id'] ?? null;

        if (!$testId || !$sectionId) return $this->error('Missing ID');

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_questions (section_id, question_text, question_type, points, question_order)
            VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(question_order), 0) + 1 FROM iga_questions q2 WHERE q2.section_id = ?))
        ");
        $stmt->execute([
            $sectionId,
            $data['question_text'],
            $data['question_type'],
            $data['points'] ?? 1,
            $sectionId
        ]);

        header("Location: index.php?controller=test&action=structure&id=$testId");
    }

    /**
     * Delete Question
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requirePermission('delete');

        $id = $_GET['id'] ?? null;
        $testId = $_GET['test_id'] ?? null;

        if ($id) {
            $stmt = $this->pdo->prepare("DELETE FROM iga_questions WHERE question_id = ?");
            $stmt->execute([$id]);
        }

        header("Location: index.php?controller=test&action=structure&id=$testId");
    }

    /**
     * Add Option to Question
     */
    public function storeOption($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $testId = $data['test_id'] ?? null;
        $questionId = $data['question_id'] ?? null;

        if (!$questionId) return $this->error("Missing Question ID");

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_question_options (question_id, option_text, is_correct, option_order)
            VALUES (?, ?, ?, (SELECT COALESCE(MAX(option_order), 0) + 1 FROM iga_question_options o2 WHERE o2.question_id = ?))
        ");

        $isCorrect = isset($data['is_correct']) ? 1 : 0;

        $stmt->execute([
            $questionId,
            $data['option_text'],
            $isCorrect,
            $questionId
        ]);

        header("Location: index.php?controller=test&action=structure&id=$testId");
    }

    /**
     * Delete Option
     */
    public function deleteOption()
    {
        $this->requireAuth();
        $this->requirePermission('delete');

        $id = $_GET['id'] ?? null;
        $testId = $_GET['test_id'] ?? null;

        if ($id) {
            $stmt = $this->pdo->prepare("DELETE FROM iga_question_options WHERE option_id = ?");
            $stmt->execute([$id]);
        }

        header("Location: index.php?controller=test&action=structure&id=$testId");
    }
}

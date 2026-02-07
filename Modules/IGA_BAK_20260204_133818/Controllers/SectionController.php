<?php

require_once __DIR__ . '/IGABaseController.php';

class SectionController extends IGABaseController
{
    /**
     * Store New Section
     */
    public function store($data)
    {
        $this->requireAuth();
        $this->requirePermission('edit');

        $testId = $data['test_id'] ?? null;
        $name = $data['section_title'] ?? 'Untitled Section';

        if (!$testId) return $this->error('Test ID Required');

        $stmt = $this->pdo->prepare("
            INSERT INTO iga_sections (test_id, section_title, section_order, instructions)
            VALUES (?, ?, (SELECT COALESCE(MAX(section_order), 0) + 1 FROM iga_sections s2 WHERE s2.test_id = ?), ?)
        ");
        $stmt->execute([$testId, $name, $testId, $data['instructions'] ?? '']);

        // Return to manage page
        header("Location: index.php?controller=test&action=structure&id=$testId");
    }

    /**
     * Delete Section
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requirePermission('delete');

        $id = $_GET['id'] ?? null;
        $testId = $_GET['test_id'] ?? null;

        if ($id) {
            $stmt = $this->pdo->prepare("DELETE FROM iga_sections WHERE section_id = ?");
            $stmt->execute([$id]);
        }

        if ($testId) {
            header("Location: index.php?controller=test&action=structure&id=$testId");
        } else {
            header("Location: index.php?controller=test&action=index");
        }
    }
}

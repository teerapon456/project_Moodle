<?php
// Modules/HRPolicies/Controllers/HRPoliciesController.php

require_once __DIR__ . '/../Models/HRPoliciesModel.php';

class HRPoliciesController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new HRPoliciesModel($conn);
    }

    public function handleRequest()
    {
        $action = $_REQUEST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'list') {
                $this->listPolicies();
            } elseif ($action === 'get_history') {
                $this->getPolicyHistory();
            } else {
                $this->sendError('Invalid GET action', 400);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'save') {
                $this->savePolicy();
            } elseif ($action === 'delete') {
                $this->deletePolicy();
            } else {
                $this->sendError('Invalid POST action', 400);
            }
        } else {
            $this->sendError('Invalid request method', 405);
        }
    }

    private function listPolicies()
    {
        try {
            $policies = $this->model->getAllPolicies();
            $this->sendResponse(['success' => true, 'data' => $policies]);
        } catch (Exception $e) {
            $this->sendError('Failed to load policies: ' . $e->getMessage(), 500);
        }
    }

    private function savePolicy()
    {
        $id = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $userId = $_SESSION['user']['id'] ?? null;

        if (empty($title) || empty($content)) {
            $this->sendError('Title and Content are required', 400);
            return;
        }

        try {
            if ($id) {
                // Update existing
                $success = $this->model->updatePolicy($id, $title, $category, $content, $isActive, $userId);
            } else {
                // Create new
                $success = $this->model->createPolicy($title, $category, $content, $isActive, $userId);
            }

            if ($success) {
                $this->sendResponse(['success' => true, 'message' => 'Policy saved successfully.']);
            } else {
                $this->sendError('Failed to save policy to database', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Failed to save policy: ' . $e->getMessage(), 500);
        }
    }

    private function deletePolicy()
    {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            $this->sendError('Policy ID is required', 400);
            return;
        }

        try {
            $success = $this->model->deletePolicy($id);
            if ($success) {
                $this->sendResponse(['success' => true, 'message' => 'Policy deleted successfully.']);
            } else {
                $this->sendError('Failed to delete policy from database', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Failed to delete policy: ' . $e->getMessage(), 500);
        }
    }

    private function getPolicyHistory()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->sendError('Policy ID is required', 400);
            return;
        }

        try {
            $history = $this->model->getPolicyHistory($id);
            $this->sendResponse(['success' => true, 'data' => $history]);
        } catch (Exception $e) {
            $this->sendError('Failed to load history: ' . $e->getMessage(), 500);
        }
    }

    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function sendError($message, $statusCode = 500)
    {
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

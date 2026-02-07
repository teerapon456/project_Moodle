<?php

/**
 * NotificationController - API for notifications
 */

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../core/Services/NotificationService.php';

class NotificationController extends BaseController
{
    protected $user;

    public function __construct()
    {
        require_once __DIR__ . '/../../core/Config/SessionConfig.php';
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }
        $this->user = $_SESSION['user'] ?? null;
    }

    protected function requireAuth()
    {
        if (!$this->user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
            exit;
        }
    }

    public function processRequest()
    {
        $this->requireAuth();

        $action = $_GET['action'] ?? 'list';
        $userId = $this->user['id'] ?? 0;

        switch ($action) {
            case 'list':
                $this->list($userId);
                break;
            case 'markAsRead':
                $this->markAsRead($userId);
                break;
            case 'markAllAsRead':
                $this->markAllAsRead($userId);
                break;
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
        }
    }

    private function list($userId)
    {
        $page = (int)($_GET['page'] ?? 1);
        $notifications = NotificationService::getAll($userId, $page, 20);
        $unreadCount = NotificationService::getUnreadCount($userId);

        $this->jsonResponse([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    private function markAsRead($userId)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing ID'], 400);
            return;
        }

        $result = NotificationService::markAsRead($id, $userId);
        $this->jsonResponse(['success' => $result]);
    }

    private function markAllAsRead($userId)
    {
        $count = NotificationService::markAllAsRead($userId);
        $this->jsonResponse(['success' => true, 'marked' => $count]);
    }

    private function jsonResponse($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

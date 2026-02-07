<?php
// Modules/YearlyActivity/Controllers/NotificationController.php

require_once __DIR__ . '/../../../core/Services/NotificationService.php';
require_once __DIR__ . '/../Helpers/PermissionHelper.php';

class NotificationController
{
    private $perm;

    public function __construct()
    {
        $this->perm = YAPermissionHelper::getInstance();
    }

    // Get notifications for current user
    public function getMyNotifications($limit = 20, $unreadOnly = false)
    {
        $userId = $this->perm->getUserId();
        if (!$userId) return [];

        if ($unreadOnly) {
            return NotificationService::getUnread($userId, $limit);
        } else {
            return NotificationService::getAll($userId, 1, $limit);
        }
    }

    // Get unread count for current user
    public function getUnreadCount()
    {
        $userId = $this->perm->getUserId();
        if (!$userId) return 0;

        return NotificationService::getUnreadCount($userId);
    }

    // Create a notification
    public function create($userId, $title, $message, $type = 'info', $link = null)
    {
        // Wrapper for core service
        return NotificationService::create($userId, $type, $title, $message, [], $link);
    }

    // Create notification for multiple users
    public function createForMultiple($userIds, $title, $message, $type = 'info', $link = null)
    {
        if (empty($userIds)) return false;
        NotificationService::createBulk($userIds, $type, $title, $message, [], $link);
        return true;
    }

    // Notify users assigned to an activity
    public function notifyActivityAssignees($activityId, $title, $message, $type = 'info')
    {
        // Need to query specific module table for this logic, so we still need DB connection here
        // or helper.
        // Let's instantiate DB just for this method if getting assignees logic is local.
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) return false;

        // Get all users assigned to this activity
        $stmt = $conn->prepare("SELECT DISTINCT user_id FROM ya_rasci WHERE activity_id = :activity_id");
        $stmt->execute([':activity_id' => $activityId]);
        $userIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');

        // Filter out 0 or null
        $userIds = array_filter($userIds, function ($uid) {
            return $uid > 0;
        });

        $link = "?page=activities&highlight=$activityId";
        return $this->createForMultiple($userIds, $title, $message, $type, $link);
    }

    // Mark notification as read
    public function markAsRead($notificationId)
    {
        $userId = $this->perm->getUserId();
        // Core service requires user ID for security
        return NotificationService::markAsRead($notificationId, $userId);
    }

    // Mark all as read for current user
    public function markAllAsRead()
    {
        $userId = $this->perm->getUserId();
        return NotificationService::markAllAsRead($userId);
    }

    // Delete old notifications (cleanup)
    public function cleanup($daysOld = 30)
    {
        // Delegate to core service (which cleans globally)
        // Or should we only clean *this module's* notifications?
        // Core service cleanup() cleans ALL notifications. 
        // Maybe we shouldn't run this from a module unless it's a cron job.
        // But for compatibility with existing code calling it:
        return NotificationService::cleanup($daysOld);
    }
}

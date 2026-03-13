<?php

/**
 * NotificationService - Real-time notification system
 * ระบบแจ้งเตือนแบบ real-time
 */

require_once __DIR__ . '/../Database/Database.php';

class NotificationService
{
    private static $pdo = null;

    private static function getPdo()
    {
        if (self::$pdo === null) {
            $db = new Database();
            self::$pdo = $db->getConnection();
        }
        return self::$pdo;
    }

    /**
     * Create a notification
     * @param int $userId Target user ID
     * @param string $type Notification type (info, success, warning, error)
     * @param string $title Title
     * @param string $message Message content
     * @param array $data Additional data (optional)
     * @param string $link Link to action (optional)
     */
    public static function create($userId, $type, $title, $message, $data = [], $link = null)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, link, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        $stmt->execute([
            $userId,
            $type,
            $title,
            $message,
            json_encode($data),
            $link
        ]);

        $notifId = $pdo->lastInsertId();
        
        return $notifId;
    }

    /**
     * Create notification for multiple users
     */
    public static function createBulk(array $userIds, $type, $title, $message, $data = [], $link = null)
    {
        foreach ($userIds as $userId) {
            self::create($userId, $type, $title, $message, $data, $link);
        }
    }

    /**
     * Get unread notifications for user
     */
    public static function getUnread($userId, $limit = 20)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all notifications for user (with pagination)
     */
    public static function getAll($userId, $page = 1, $perPage = 20)
    {
        $pdo = self::getPdo();
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread count
     */
    public static function getUnreadCount($userId)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get latest notification ID for user
     */
    public static function getLastId($userId)
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT MAX(id) FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $userId)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all as read for user
     */
    public static function markAllAsRead($userId)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);

        return $stmt->rowCount();
    }

    /**
     * Delete old notifications (cleanup)
     */
    public static function cleanup($daysOld = 30)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysOld]);

        return $stmt->rowCount();
    }

    /**
     * Check for new notifications since last check
     */
    public static function getNewSince($userId, $lastId)
    {
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND id > ? AND is_read = 0
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $lastId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Get User ID by Email
     * 
     * @param string $email User email
     * @return int|null User ID or null if not found/inactive
     */
    public static function getUserIdByEmail($email)
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([trim($email)]);
        return $stmt->fetchColumn() ?: null;
    }

    /**
     * Send notification to user by email
     * Resolves user ID from email automatically
     * 
     * @param string $email Target email
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Message content
     * @param array $data Additional data
     * @param string|null $link Link URL
     * @return int|bool Notification ID or false if user not found
     */
    public static function sendToEmail($email, $type, $title, $message, $data = [], $link = null)
    {
        $userId = self::getUserIdByEmail($email);
        if ($userId) {
            return self::create($userId, $type, $title, $message, $data, $link);
        }
        return false;
    }

    /**
     * Send notification to all admins of a module
     * Fetches admin emails from system_settings
     * 
     * @param int $moduleId Module ID (e.g., 2 for Car Booking)
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Message content
     * @param string|null $link Link URL
     * @return void
     */
    public static function sendToModuleAdmins($moduleId, $type, $title, $message, $link = null)
    {
        require_once __DIR__ . '/EmailService.php';
        $settings = EmailService::getModuleSettings($moduleId);
        $adminEmails = $settings['admin_emails'] ?? '';

        if ($adminEmails) {
            // Handle JSON if present (legacy)
            $jsonDecoded = json_decode($adminEmails, true);
            if (is_array($jsonDecoded)) {
                $emails = $jsonDecoded;
            } else {
                $emails = explode(',', $adminEmails);
            }

            foreach ($emails as $email) {
                if (trim($email)) {
                    self::sendToEmail($email, $type, $title, $message, [], $link);
                }
            }
        }
    }
}

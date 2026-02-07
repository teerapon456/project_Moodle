<?php

/**
 * Car Booking Module - Dashboard Controller
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/BookingController.php';

class CBDashboardController extends CBBaseController
{
    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        $this->requireAuth();

        $roleId = $this->user['role_id'] ?? 0;
        $userId = $this->user['id'] ?? 0;
        $isAdmin = (int)$roleId === 1;

        // Get permission to check manager status
        $canManage = false;
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(p.can_manage, 0) as can_manage
                FROM core_modules cm
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = ?
                WHERE cm.code = 'CAR_BOOKING'
            ");
            $stmt->execute([$roleId]);
            $perm = $stmt->fetch(PDO::FETCH_ASSOC);
            $canManage = $isAdmin || !empty($perm['can_manage']);
        } catch (Exception $e) {
            // ignore
        }

        // Query bookings based on role
        if ($canManage) {
            $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM cb_bookings GROUP BY status");
        } else {
            $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM cb_bookings WHERE user_id = ? GROUP BY status");
            $stmt->execute([$userId]);
        }
        $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stats = [
            'total' => array_sum($statusCounts),
            'pending_supervisor' => $statusCounts['pending_supervisor'] ?? 0,
            'pending_manager' => $statusCounts['pending_manager'] ?? 0,
            'approved' => $statusCounts['approved'] ?? 0,
            'rejected' => $statusCounts['rejected'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
        ];

        // Calculate pending total
        $stats['pending'] = $stats['pending_supervisor'] + $stats['pending_manager'];

        // Get cars count
        $carsTotal = $this->pdo->query("SELECT COUNT(*) FROM cb_cars")->fetchColumn();
        $carsActive = $this->pdo->query("SELECT COUNT(*) FROM cb_cars WHERE status = 'active'")->fetchColumn();

        $stats['cars_total'] = (int)$carsTotal;
        $stats['cars_active'] = (int)$carsActive;

        return $this->success(['stats' => $stats]);
    }

    /**
     * Get recent bookings for dashboard
     */
    public function recent()
    {
        $this->requireAuth();

        $roleId = $this->user['role_id'] ?? 0;
        $userId = $this->user['id'] ?? 0;
        $isAdmin = (int)$roleId === 1;

        $limit = 5;

        if ($isAdmin) {
            $stmt = $this->pdo->prepare("
                SELECT b.*, u.username, u.fullname
                FROM cb_bookings b
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT b.*, u.username, u.fullname
                FROM cb_bookings b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
        }

        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['bookings' => $bookings]);
    }
}

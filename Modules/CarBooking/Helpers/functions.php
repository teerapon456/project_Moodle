<?php

/**
 * Car Booking Module - Helpers
 * ฟังก์ชันช่วยเหลือสำหรับโมดูลจองรถ
 */

/**
 * Get status badge info
 */
function getStatusBadge($status)
{
    $map = [
        'pending_supervisor' => ['text' => 'รอหัวหน้าอนุมัติ', 'class' => 'badge-warning'],
        'pending_manager' => ['text' => 'รอผู้จัดการอนุมัติ', 'class' => 'badge-info'],
        'approved' => ['text' => 'อนุมัติแล้ว', 'class' => 'badge-success'],
        'rejected' => ['text' => 'ปฏิเสธ', 'class' => 'badge-danger'],
        'cancelled' => ['text' => 'ยกเลิก', 'class' => 'badge-default'],
        'completed' => ['text' => 'เสร็จสิ้น', 'class' => 'badge-info']
    ];
    return $map[$status] ?? ['text' => $status, 'class' => 'badge-default'];
}

/**
 * Format date to Thai
 */
function formatThaiDate($dateStr)
{
    if (!$dateStr) return '-';
    return date('d/m/Y', strtotime($dateStr));
}

/**
 * Format datetime to Thai
 */
function formatThaiDateTime($dateStr)
{
    if (!$dateStr) return '-';
    return date('d/m/Y H:i', strtotime($dateStr));
}

/**
 * Get car label from booking data
 */
function getCarLabel($booking)
{
    if (!empty($booking['assigned_car_id'])) {
        $label = trim(($booking['assigned_car_brand'] ?? '') . ' ' . ($booking['assigned_car_model'] ?? ''));
        if (!empty($booking['assigned_car_plate'])) {
            $label .= ' (' . $booking['assigned_car_plate'] . ')';
        }
        return $label ?: '-';
    } elseif (!empty($booking['fleet_card_number'])) {
        return 'Fleet Card: ' . $booking['fleet_card_number'];
    }
    return '-';
}

/**
 * ตรวจสอบสิทธิ์ module
 */
function checkModulePermission($moduleCode, $roleId)
{
    if ((int)$roleId === 1) {
        return ['can_view' => 1, 'can_edit' => 1, 'can_delete' => 1, 'can_manage' => 1];
    }
    try {
        require_once __DIR__ . '/../../core/Database/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        if (!$conn) return ['can_view' => true, 'can_edit' => false, 'can_delete' => false, 'can_manage' => false];

        $sql = "SELECT cm.id,
                       COALESCE(p.can_view, 0) as can_view,
                       COALESCE(p.can_edit, 0) as can_edit,
                       COALESCE(p.can_delete, 0) as can_delete,
                       COALESCE(p.can_manage, 0) as can_manage
                FROM core_modules cm
                LEFT JOIN core_module_permissions p
                  ON p.module_id = cm.id AND p.role_id = :role_id
                WHERE cm.code = :code
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':code', $moduleCode);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: ['can_view' => true, 'can_edit' => false, 'can_delete' => false, 'can_manage' => false];
    } catch (Exception $e) {
        return ['can_view' => true, 'can_edit' => false, 'can_delete' => false, 'can_manage' => false];
    }
}

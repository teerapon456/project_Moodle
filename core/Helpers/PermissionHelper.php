<?php

/**
 * Permission Helper - ตรวจสอบสิทธิ์และแสดงข้อความ error
 */

require_once __DIR__ . '/../Database/Database.php';

/**
 * แสดงข้อความ Access Denied สำหรับ View
 * 
 * @param string $module ชื่อ module (เช่น 'ระบบหอพัก', 'ระบบจองรถ')
 * @param string $reason เหตุผลที่ถูกปฏิเสธ (default: 'คุณไม่มีสิทธิ์ในการเข้าถึง')
 * @return void
 */
function renderAccessDenied($module = 'โมดูลนี้', $reason = null)
{
    $message = $reason ?: "คุณไม่มีสิทธิ์ในการเข้าถึง{$module}";
    echo '<div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
        <i class="ri-lock-line text-4xl text-red-400 mb-4 block"></i>
        <h3 class="text-lg font-semibold text-red-700 mb-2">ไม่มีสิทธิ์เข้าถึง</h3>
        <p class="text-red-600">' . htmlspecialchars($message) . '</p>
    </div>';
}

/**
 * ตรวจสอบสิทธิ์ View และแสดง error ถ้าไม่มีสิทธิ์
 * 
 * @param bool $canView สิทธิ์ view
 * @param string $module ชื่อ module
 * @return bool true = มีสิทธิ์, false = ไม่มีสิทธิ์ (แสดง error แล้ว)
 */
function checkViewPermission($canView, $module = 'โมดูลนี้')
{
    if (!$canView) {
        renderAccessDenied($module);
        return false;
    }
    return true;
}

/**
 * ตรวจสอบสิทธิ์ Admin และแสดง error ถ้าไม่มีสิทธิ์
 * 
 * @param bool $canView สิทธิ์ view
 * @param bool $isAdmin สิทธิ์ admin
 * @param string $module ชื่อ module
 * @return bool true = มีสิทธิ์, false = ไม่มีสิทธิ์ (แสดง error แล้ว)
 */
function checkAdminPermission($canView, $isAdmin, $module = 'โมดูลนี้')
{
    if (!$canView) {
        renderAccessDenied($module);
        return false;
    }
    if (!$isAdmin) {
        renderAccessDenied($module, 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น');
        return false;
    }
    return true;
}

/**
 * ตรวจสอบสิทธิ์ Edit และแสดง error ถ้าไม่มีสิทธิ์
 * 
 * @param bool $canView สิทธิ์ view
 * @param bool $canEdit สิทธิ์ edit
 * @param string $module ชื่อ module
 * @return bool true = มีสิทธิ์, false = ไม่มีสิทธิ์ (แสดง error แล้ว)
 */
function checkEditPermission($canView, $canEdit, $module = 'โมดูลนี้')
{
    if (!$canView) {
        renderAccessDenied($module);
        return false;
    }
    if (!$canEdit) {
        renderAccessDenied($module, 'คุณไม่มีสิทธิ์ในการแก้ไขข้อมูล');
        return false;
    }
    return true;
}

/**
 * ตรวจสอบสิทธิ์ Manager และแสดง error ถ้าไม่มีสิทธิ์ (สำหรับ CarBooking)
 * 
 * @param bool $canView สิทธิ์ view
 * @param bool $canManage สิทธิ์ manage
 * @param string $module ชื่อ module
 * @return bool true = มีสิทธิ์, false = ไม่มีสิทธิ์ (แสดง error แล้ว)
 */
function checkManagerPermission($canView, $canManage, $module = 'โมดูลนี้')
{
    if (!$canView) {
        renderAccessDenied($module);
        return false;
    }
    if (!$canManage) {
        renderAccessDenied($module, 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น');
        return false;
    }
    return true;
}

/**
 * ตรวจสอบสิทธิ์ Module Access จาก Database
 * 
 * @param string $moduleCode รหัส module (เช่น 'HR_SERVICES', 'EMAIL_LOGS')
 * @param int $roleId Role ID ของผู้ใช้
 * @param PDO|null $conn Optional database connection
 * @return array ['can_view' => bool, 'can_edit' => bool, 'can_delete' => bool, 'can_manage' => bool]
 */
function userHasModuleAccess($moduleCode, $roleId, $conn = null)
{
    $defaultPerms = [
        'can_view' => false,
        'can_edit' => false,
        'can_delete' => false,
        'can_manage' => false,
        'data_scope' => 'all',
        'allowed_departments' => null
    ];

    try {
        if (!$conn) {
            $db = new Database();
            $conn = $db->getConnection();
        }
        if (!$conn) return $defaultPerms;

        $sql = "SELECT COALESCE(p.can_view, 0) as can_view, 
                       COALESCE(p.can_edit, 0) as can_edit, 
                       COALESCE(p.can_delete, 0) as can_delete, 
                       COALESCE(p.can_manage, 0) as can_manage,
                       COALESCE(p.data_scope, 'all') as data_scope,
                       p.allowed_departments 
                FROM core_modules cm 
                LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id 
                WHERE cm.code = :code LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':code', $moduleCode);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: $defaultPerms;
    } catch (Exception $e) {
        if (ini_get('display_errors')) {
            echo "<!-- Module Access Error: " . htmlspecialchars($e->getMessage()) . " -->";
            // Also try to output to screen if not in a buffer
            if (ob_get_level() == 0) {
                echo "<div style='color:red; border:1px solid red; padding:10px;'>Permission Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        return $defaultPerms;
    }
}

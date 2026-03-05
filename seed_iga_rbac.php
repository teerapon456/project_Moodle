<?php
require_once __DIR__ . '/core/Config/Env.php';
require_once __DIR__ . '/core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $igaModuleId = 5; // IGA already registered

    // Check existing role IDs
    $stmt = $conn->query("SELECT id, name FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "--- Existing Roles ---\n";
    foreach ($roles as $r) {
        echo "  ID: {$r['id']} | Name: {$r['name']}\n";
    }

    // For each admin-ish role, grant full permissions on IGA module
    // We'll find the admin/Super_user_Recruitment roles and grant them manage access
    $adminRoleNames = ['admin', 'Super_user_Recruitment', 'editor'];

    foreach ($roles as $role) {
        $roleName = $role['name'];
        $roleId = $role['id'];

        // Check if permission already exists
        $stmtCheck = $conn->prepare("SELECT id FROM core_module_permissions WHERE module_id = :mod AND role_id = :role");
        $stmtCheck->execute([':mod' => $igaModuleId, ':role' => $roleId]);

        if ($stmtCheck->fetchColumn()) {
            echo "SKIP: Permission for role '{$roleName}' (ID:{$roleId}) on IGA already exists.\n";
            continue;
        }

        if (in_array($roleName, $adminRoleNames)) {
            // Full access for admin roles
            $stmt = $conn->prepare("INSERT INTO core_module_permissions (module_id, role_id, can_view, can_edit, can_delete, can_manage, data_scope) VALUES (:mod, :role, 1, 1, 1, 1, 'all')");
            $stmt->execute([':mod' => $igaModuleId, ':role' => $roleId]);
            echo "INSERTED: Full permissions for role '{$roleName}' (ID:{$roleId}).\n";
        } else {
            // View-only for other roles (employees)
            $stmt = $conn->prepare("INSERT INTO core_module_permissions (module_id, role_id, can_view, can_edit, can_delete, can_manage, data_scope) VALUES (:mod, :role, 1, 0, 0, 0, 'employee_all')");
            $stmt->execute([':mod' => $igaModuleId, ':role' => $roleId]);
            echo "INSERTED: View-only permissions for role '{$roleName}' (ID:{$roleId}).\n";
        }
    }

    echo "\nRBAC seeding complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

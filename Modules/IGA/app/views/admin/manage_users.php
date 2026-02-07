<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อจัดการผู้ใช้งาน

// กำหนด ID ของ Super Admin (สมมติว่าเป็น user_id ที่ 1)
// **สำคัญ: คุณต้องเปลี่ยนเลข 1 ให้ตรงกับ user_id ของผู้ดูแลระบบสูงสุดของคุณในฐานข้อมูล**
define('SUPER_ADMIN_ID', 1);

// *** CORRECTED PATH: functions.php must be included BEFORE header.php ***
// This ensures that the Gettext()() function and other utilities are available
// for setting $page_title and other early operations.
// The path now reflects manage_users.php being inside 'views/admin/'
require_once __DIR__ . '/../../includes/header.php'; // header.php จะรวม db_connect.php

$page_title = get_text('page_title_manage_users'); // เปลี่ยนเป็นชื่อหน้าที่ถูกต้อง
// ในไฟล์ PHP ของคุณ เช่น header.php หรือก่อนทำการ query ใดๆ ที่แก้ไขข้อมูล
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} else {
    // หากไม่มี user_id ใน session (เช่น Guest) หรือไม่มีการเชื่อมต่อ db
    $conn->query("SET @user_id = NULL");
}

require_login();
if (!has_role('admin')) {
    set_alert(get_text("no_permission"), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login"); // Adjust redirect path if necessary
    exit();
}

// --- ส่วนของการจัดการ Request (เพิ่ม/แก้ไข/ลบ ผู้ใช้) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token เพื่อความปลอดภัย (เฉพาะการดำเนินการที่แก้ไขข้อมูล)
    $requires_csrf = isset($_POST['action']) && in_array($_POST['action'], ['add_user', 'edit_user', 'delete_user', 'toggle_user_status']);
    
    if ($requires_csrf && !verify_csrf_token()) {
        set_alert(get_text("security_error_csrf"), "danger");
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // This redirect is relative to the current file
        exit();
    }

    // เพิ่มผู้ใช้ใหม่
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0; // Capture is_active

        // ตรวจสอบความถูกต้องของข้อมูล
        if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($role_id)) {
            set_alert(get_text("fill_all_fields"), "danger");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert(get_text("invalid_email_format"), "danger");
        } else {
            // Hash รหัสผ่าน
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                // ตรวจสอบ username หรือ email ซ้ำ
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();

                if ($count > 0) {
                    set_alert(get_text("username_email_exists"), "danger");
                } else {
                    // MODIFIED: Added is_active to INSERT statement
                    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssii", $username, $hashed_password, $full_name, $email, $role_id, $is_active); 
                    if ($stmt->execute()) {
                        set_alert(get_text("add_user_success", $username), "success");
                    } else {
                        set_alert(get_text("add_user_error", $stmt->error), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_add_user", $e->getMessage()), "danger");
            }
        }
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // Redirect เพื่อป้องกันการส่งฟอร์มซ้ำ (relative to current file)
        exit();
    }

    // แก้ไขผู้ใช้
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $user_id = (int)$_POST['user_id'];

        // --- เพิ่มเงื่อนไขสำหรับ Super Admin ตรงนี้ ---
        // ไม่สามารถแก้ไขข้อมูลของผู้ดูแลระบบสูงสุดได้
        if ($user_id === SUPER_ADMIN_ID) {
            set_alert(get_text("super_admin_cannot_be_modified"), "danger");
            header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // Relative redirect
            exit();
        }
        // --- สิ้นสุดเงื่อนไข Super Admin ---

        $username = trim($_POST['username']);
        $password = $_POST['password']; // อาจจะว่างถ้าไม่ต้องการเปลี่ยน
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0; // Capture is_active

        if (empty($username) || empty($full_name) || empty($email) || empty($role_id)) {
            set_alert(get_text("fill_all_fields"), "danger");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert(get_text("invalid_email_format"), "danger");
        } else {
            try {
                // MODIFIED: Added is_active to UPDATE statement
                $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role_id = ?, is_active = ? ";
                if (!empty($password)) {
                    $sql .= ", password_hash = ? ";
                }
                $sql .= "WHERE user_id = ?";

                $stmt = $conn->prepare($sql);

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // MODIFIED: Adjusted bind_param for is_active and password
                    $stmt->bind_param("ssssssi", $username, $full_name, $email, $role_id, $is_active, $hashed_password, $user_id);
                } else {
                    // MODIFIED: Adjusted bind_param for is_active
                    $stmt->bind_param("ssssii", $username, $full_name, $email, $role_id, $is_active, $user_id);
                }

                if ($stmt->execute()) {
                    set_alert(get_text("edit_user_success", $username), "success");
                } else {
                    set_alert(get_text("edit_user_error", $stmt->error), "danger");
                }
                $stmt->close();
            } catch (Exception $e) {
                set_alert(get_text("tech_error_edit_user", $e->getMessage()), "danger");
            }
        }
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // Relative redirect
        exit();
    }

    // ลบผู้ใช้
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = (int)$_POST['user_id'];

        // --- เพิ่มเงื่อนไขสำหรับ Super Admin ตรงนี้ ---
        // ไม่สามารถลบผู้ดูแลระบบสูงสุดได้
        if ($user_id === SUPER_ADMIN_ID) {
            set_alert(get_text("super_admin_cannot_be_deleted"), "danger");
            header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // Relative redirect
            exit();
        }
        // --- สิ้นสุดเงื่อนไข Super Admin ---

        try {
            // ควรมีการตรวจสอบความสัมพันธ์กับตารางอื่น ๆ ก่อนลบจริง
            // เช่น ตรวจสอบว่าผู้ใช้นี้มีการทำแบบทดสอบหรือไม่
            // สำหรับตอนนี้ เราจะลบโดยตรง หากมี foreign key constraint อาจจะล้มเหลว
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                set_alert(get_text("delete_user_success"), "success");
            } else {
                set_alert(get_text("delete_user_error", $stmt->error), "danger");
            }
            $stmt->close();
        } catch (Exception $e) {
                set_alert(get_text("tech_error_delete_user", $e->getMessage()), "danger");
        }
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users"); // Relative redirect
        exit();
    }

    // --- ส่วนของการจัดการ Request (เพิ่ม/แก้ไข/ลบ บทบาท) ---
    // เพิ่มบทบาทใหม่
    if (isset($_POST['action']) && $_POST['action'] === 'add_role') {
        $role_name = trim($_POST['role_name']);
        // NEW: Capture is_active for roles
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $created_by = $_SESSION['user_id'] ?? null; // Get user ID from session, null if not logged in

        if (empty($role_name)) {
            set_alert(get_text("role_name_empty"), "danger");
        } else {
            try {
                // ตรวจสอบชื่อบทบาทซ้ำ
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ?");
                $check_stmt->bind_param("s", $role_name);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();

                if ($count > 0) {
                    set_alert(get_text("role_name_exists"), "danger");
                } else {
                    // MODIFIED: Added is_active, created_by, and created_at to INSERT statement for roles
                    // Assumes created_at column exists in 'roles' table and defaults to CURRENT_TIMESTAMP
                    $stmt = $conn->prepare("INSERT INTO roles (role_name, is_active, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    // Note: 'sii' for role_name, is_active, created_by. created_at is handled by NOW()
                    $stmt->bind_param("sii", $role_name, $is_active, $created_by);
                    if ($stmt->execute()) {
                        set_alert(get_text("add_role_success", $role_name), "success"); // Corrected: removed array around $role_name
                    } else {
                        set_alert(get_text("add_role_error", [$stmt->error]), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_add_role", [$e->getMessage()]), "danger");
            }
        }
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users");
        exit();
    }

    // แก้ไขบทบาท
    if (isset($_POST['action']) && $_POST['action'] === 'edit_role') {
        $role_id = (int)$_POST['role_id'];
        $role_name = trim($_POST['role_name']); // This is the new role name from the form
        $new_is_active = isset($_POST['is_active']) ? 1 : 0;

        // --- NEW: Check if the role being edited is the 'admin' role by its current name in DB ---
        // First, get the current role_name for the given role_id from the database
        $stmt_current_role_name = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $stmt_current_role_name->bind_param("i", $role_id);
        $stmt_current_role_name->execute();
        $current_role_name_result = $stmt_current_role_name->get_result()->fetch_assoc();
        $current_role_name_db = $current_role_name_result ? strtolower($current_role_name_result['role_name']) : null;
        $stmt_current_role_name->close();

        if ($current_role_name_db === 'admin') {
            set_alert(get_text("admin_role_cannot_be_modified"), "danger");
            header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users");
            exit();
        }
        // --- End of new check ---

        if (empty($role_name)) {
            set_alert(get_text("role_name_empty"), "danger");
        } else {
            try {
                // Step 1: Get the current 'is_active' status of the role from the database
                $stmt_current_status = $conn->prepare("SELECT is_active FROM roles WHERE role_id = ?");
                $stmt_current_status->bind_param("i", $role_id);
                $stmt_current_status->execute();
                $current_is_active_result = $stmt_current_status->get_result()->fetch_assoc();
                $current_is_active = $current_is_active_result ? (int)$current_is_active_result['is_active'] : null;
                $stmt_current_status->close();

                // Step 2: Check if the user is trying to deactivate an active role
                // (i.e., current status is active (1) and new status is inactive (0))
                if ($current_is_active === 1 && $new_is_active === 0) {
                    // Step 3: Count active users associated with this role
                    // We specifically check for 'active' users (is_active = 1)
                    $check_users_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND is_active = 1");
                    $check_users_stmt->bind_param("i", $role_id);
                    $check_users_stmt->execute();
                    $user_count = $check_users_stmt->get_result()->fetch_row()[0];
                    $check_users_stmt->close();

                    // Step 4: If there are active users, prevent deactivation
                    if ($user_count > 0) {
                        set_alert(get_text("cannot_deactivate_role_has_active_users", [$role_name, $user_count]), "danger");
                        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users");
                        exit(); // Stop execution
                    }
                }

                // Proceed with name duplication check if no deactivation conflict
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ? AND role_id != ?");
                $check_stmt->bind_param("si", $role_name, $role_id);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();

                if ($count > 0) {
                    set_alert(get_text("role_name_exists"), "danger");
                } else {
                    // If no conflict, proceed with the update
                    $stmt = $conn->prepare("UPDATE roles SET role_name = ?, is_active = ? WHERE role_id = ?");
                    $stmt->bind_param("sii", $role_name, $new_is_active, $role_id); // Use $new_is_active here
                    if ($stmt->execute()) {
                        set_alert(get_text("edit_role_success", $role_name), "success");
                    } else {
                        set_alert(get_text("edit_role_error", [$stmt->error]), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_edit_role", [$e->getMessage()]), "danger");
            }
        }
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/users");
        exit();
    }
}

$users = [];
$roles = []; // เก็บ roles ทั้งหมด รวมถึง role_id ด้วย เพื่อใช้ใน JS

try {
    // ดึงข้อมูล Roles (สำหรับ dropdown ในฟอร์มและตารางบทบาท)
    // MODIFIED: Joined with users table to get created_by username and added created_at
    $stmt_roles = $conn->prepare("
        SELECT
            r.role_id,
            r.role_name,
            r.is_active,
            r.created_at,
            u.username AS created_by_username
        FROM
            roles r
        LEFT JOIN
            users u ON r.created_by = u.user_id
        ORDER BY
            r.role_id ASC
    ");
    $stmt_roles->execute();
    $result_roles = $stmt_roles->get_result();
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
    $stmt_roles->close();

    // --- NEW/MODIFIED: Handle all filter parameters for users ---
    $filter_role_id = isset($_POST['filter_role_id']) ? (int)$_POST['filter_role_id'] : 0; // 0 means "All Roles" or no filter
    $search_query = isset($_POST['search_query']) ? trim($_POST['search_query']) : '';
    $filter_is_active = isset($_POST['filter_is_active']) ? (int)$_POST['filter_is_active'] : -1; // -1 for all, 0 for inactive, 1 for active

    // ดึงข้อมูลผู้ใช้งานทั้งหมด
    // MODIFIED: Added u.is_active to the SELECT query and WHERE clause for role, search and active status filtering
    $sql_users = "
        SELECT
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.created_at,
            u.is_active,
            r.role_name
        FROM
            users u
        JOIN
            roles r ON u.role_id = r.role_id
    ";

    $where_clauses = [];
    $params = [];
    $param_types = "";

    if ($filter_role_id > 0) {
        $where_clauses[] = "u.role_id = ?";
        $params[] = $filter_role_id;
        $param_types .= "i";
    }

    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR r.role_name LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= "ssss";
    }

    if ($filter_is_active !== -1) {
        $where_clauses[] = "u.is_active = ?";
        $params[] = $filter_is_active;
        $param_types .= "i";
    }

    if (!empty($where_clauses)) {
        $sql_users .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql_users .= " ORDER BY u.user_id ASC";

    $stmt_users = $conn->prepare($sql_users);

    if (!empty($params)) {
        $stmt_users->bind_param($param_types, ...$params);
    }

    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt_users->close();
} catch (Exception $e) {
    set_alert(get_text("error_loading_users", [$e->getMessage()]), "danger");
}

?>

<div class="container-fluid w-80-custom py-4">
    <?php echo get_alert(); // แสดงข้อความแจ้งเตือน
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0 text-primary-custom"><?php echo get_text("manage_users_page_heading"); ?></h1>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-2"></i> <?php echo get_text("add_new_user"); ?>
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><?php echo get_text("list_of_users"); ?></h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <form action="/INTEQC_GLOBAL_ASSESMENT/admin/users" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search_query" class="form-label"><?php echo get_text("search_label"); ?></label>
                        <input type="text" class="form-control" id="search_query" name="search_query" placeholder="<?php echo get_text("search_placeholder"); ?>" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_role_id" class="form-label"><?php echo get_text("filter_by_role"); ?></label>
                        <select class="form-select" id="filter_role_id" name="filter_role_id">
                            <option value="0"><?php echo get_text("all_roles"); ?></option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>"
                                    <?php echo ($filter_role_id == $role['role_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_is_active" class="form-label"><?php echo get_text("status_label"); ?></label>
                        <select class="form-select" id="filter_is_active" name="filter_is_active">
                            <option value="-1" <?php echo ($filter_is_active == -1) ? 'selected' : ''; ?>><?php echo get_text("all_status"); ?></option>
                            <option value="1" <?php echo ($filter_is_active == 1) ? 'selected' : ''; ?>><?php echo get_text("active"); ?></option>
                            <option value="0" <?php echo ($filter_is_active == 0) ? 'selected' : ''; ?>><?php echo get_text("inactive"); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-sm"><?php echo get_text("apply_filter"); ?></button>
                        <button type="button" class="btn btn-primary-custom btn-sm" id="resetFilterBtn">
                            <?php echo get_text("reset_filter"); ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php if (empty($users)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?php echo get_text("no_users_in_system"); ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th><?php echo get_text("table_header_id"); ?></th>
                                <th><?php echo get_text("table_header_username"); ?></th>
                                <th><?php echo get_text("table_header_full_name"); ?></th>
                                <th><?php echo get_text("table_header_email"); ?></th>
                                <th><?php echo get_text("table_header_role"); ?></th>
                                <th><?php echo get_text("table_header_is_active"); ?></th>
                                <th><?php echo get_text("table_header_created_at"); ?></th>
                                <th><?php echo get_text("table_header_actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success"><?php echo get_text("active"); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo get_text("inactive"); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(thai_datetime_format($user['created_at'], false)); ?></td>
                                    <td>
                                        <?php if ($user['user_id'] == SUPER_ADMIN_ID): ?>
                                            <span class="badge bg-secondary text-white"><i class="fas fa-lock me-1"></i> <?php echo get_text("super_admin_badge"); ?></span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning me-1 edit-user-btn"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-user-id="<?php echo htmlspecialchars($user['user_id']); ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role-id="<?php echo htmlspecialchars(get_role_id_by_name($user['role_name'], $roles)); ?>"
                                                data-is-active="<?php echo htmlspecialchars($user['is_active']); ?>">
                                                <i class="fas fa-edit"></i> <?php echo get_text("edit_button"); ?>
                                            </button>
                                            <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 text-primary-custom"><?php echo get_text("manage_roles_heading"); ?></h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="fas fa-plus-circle me-2"></i> <?php echo get_text("add_new_role"); ?>
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><?php echo get_text("list_of_roles"); ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($roles)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?php echo get_text("no_roles_in_system"); ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th><?php echo get_text("table_header_id"); ?></th>
                                <th><?php echo get_text("table_header_role_name"); ?></th>
                                <th><?php echo get_text('table_header_is_active'); ?></th>
                                <th><?php echo get_text('table_header_created_by'); ?></th>
                                <th><?php echo get_text('table_header_created_at'); ?></th>
                                <th><?php echo get_text("table_header_actions"); ?></th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($role['role_id']); ?></td>
                                    <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                    <td>
                                        <?php if ($role['is_active']): ?>
                                            <span class="badge bg-success"><?php echo get_text("active"); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo get_text("inactive"); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($role['created_by_username'] ?? get_text('unknown_user')); ?></td>
                                    <td><?php echo htmlspecialchars(isset($role['created_at']) ? thai_datetime_format($role['created_at'], false) : get_text('not_available')); ?></td>
                                    <td> <?php if (strtolower($role['role_name']) === 'admin'): ?>
                                            <span class="badge bg-secondary text-white"><i class="fas fa-lock me-1"></i> <?php echo get_text("super_admin_badge"); ?></span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning me-1 edit-role-btn"
                                                data-bs-toggle="modal" data-bs-target="#editRoleModal"
                                                data-role-id="<?php echo htmlspecialchars($role['role_id']); ?>"
                                                data-role-name="<?php echo htmlspecialchars($role['role_name']); ?>"
                                                data-is-active="<?php echo htmlspecialchars($role['is_active']); ?>">
                                                <i class="fas fa-edit"></i> <?php echo get_text("edit_button"); ?>
                                            </button>
                                            <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addUserModalLabel"><?php echo get_text("add_user_modal_title"); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/users" method="POST">
                <?php echo generate_csrf_token(); // ตรวจสอบว่าฟังก์ชันนี้มีอยู่ใน functions.php
                ?>
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_username" class="form-label"><?php echo get_text("username_label"); ?></label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label"><?php echo get_text("password_label"); ?></label>
                        <input type="password" class="form-control" id="add_password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_full_name" class="form-label"><?php echo get_text("full_name_label"); ?></label>
                        <input type="text" class="form-control" id="add_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label"><?php echo get_text("email_label"); ?></label>
                        <input type="email" class="form-control" id="add_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_role_id" class="form-label"><?php echo get_text("role_label"); ?></label>
                        <select class="form-select" id="add_role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="add_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="add_is_active"><?php echo get_text("is_active_label"); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text("cancel_button"); ?></button>
                    <button type="submit" class="btn btn-primary-custom"><?php echo get_text("save_button"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editUserModalLabel"><?php echo get_text("edit_user_modal_title_prefix"); ?> <span id="edit_username_title_display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label"><?php echo get_text("username_label"); ?></label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label"><?php echo get_text("password_optional_label"); ?></label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label"><?php echo get_text("full_name_label"); ?></label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label"><?php echo get_text("email_label"); ?></label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label"><?php echo get_text("role_label"); ?></label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active"><?php echo get_text("is_active_label"); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text("cancel_button"); ?></button>
                    <button type="submit" class="btn btn-warning"><?php echo get_text("save_changes_button"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addRoleModalLabel"><?php echo get_text("add_role_modal_title"); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="add_role">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_role_name" class="form-label"><?php echo get_text("role_name_label"); ?></label>
                        <input type="text" class="form-control" id="add_role_name" name="role_name" required>
                    </div>
                    <div class="form-group form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="add_role_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="add_role_is_active"><?php echo get_text('is_active_label'); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text("cancel_button"); ?></button>
                    <button type="submit" class="btn btn-primary-custom"><?php echo get_text("save_button"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editRoleModalLabel"><?php echo get_text("edit_role_modal_title_prefix"); ?> <span id="edit_role_name_title_display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="role_id" id="edit_role_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label"><?php echo get_text("role_name_label"); ?></label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_role_is_active" name="is_active" value="1"> <label class="form-check-label" for="edit_role_is_active"><?php echo get_text("is_active_label"); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text("cancel_button"); ?></button>
                    <button type="submit" class="btn btn-warning"><?php echo get_text("save_changes_button"); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // โค้ดสำหรับ Modal แก้ไขผู้ใช้งาน (Edit User Modal)
        var editUserModal = document.getElementById('editUserModal');
        if (editUserModal) { // ตรวจสอบว่า Modal มีอยู่จริง
            editUserModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var userId = button.getAttribute('data-user-id');
                var username = button.getAttribute('data-username');
                var fullName = button.getAttribute('data-full-name');
                var email = button.getAttribute('data-email');
                var roleId = button.getAttribute('data-role-id');
                var isActive = button.getAttribute('data-is-active'); // Get is_active value

                var modalTitle = editUserModal.querySelector('.modal-title');
                var modalUserIdInput = editUserModal.querySelector('#edit_user_id');
                var modalUsernameInput = editUserModal.querySelector('#edit_username');
                var modalFullNameInput = editUserModal.querySelector('#edit_full_name');
                var modalEmailInput = editUserModal.querySelector('#edit_email');
                var modalRoleIdSelect = editUserModal.querySelector('#edit_role_id');
                var modalIsActiveCheckbox = editUserModal.querySelector('#edit_is_active'); // Get checkbox element
                var modalUsernameTitleDisplay = editUserModal.querySelector('#edit_username_title_display'); // New element for username in title

                // Update title with translated prefix and username
                if (modalUsernameTitleDisplay) {
                    modalUsernameTitleDisplay.textContent = username;
                }
                // The modal title itself is already set by PHP's Gettext()("edit_user_modal_title_prefix")

                modalUserIdInput.value = userId;
                modalUsernameInput.value = username;
                modalFullNameInput.value = fullName;
                modalEmailInput.value = email;
                modalRoleIdSelect.value = roleId; // กำหนดค่า role_id ที่ถูกเลือก
                // Set checkbox state based on isActive value
                modalIsActiveCheckbox.checked = (isActive === '1'); // Check if '1' (true)

            });
        }


        // โค้ดสำหรับ Modal แก้ไขบทบาท (Edit Role Modal - NEW)
        var editRoleModal = document.getElementById('editRoleModal');
        if (editRoleModal) {
            editRoleModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var roleId = button.getAttribute('data-role-id');
                var roleName = button.getAttribute('data-role-name');
                var roleIsActive = button.getAttribute('data-is-active'); // NEW: Get is_active value from button

                var modalRoleIdInput = editRoleModal.querySelector('#edit_role_id');
                var modalRoleNameInput = editRoleModal.querySelector('#edit_role_name');
                var modalRoleIsActiveCheckbox = editRoleModal.querySelector('#edit_role_is_active'); // NEW: Get checkbox element
                var modalRoleNameTitleDisplay = editRoleModal.querySelector('#edit_role_name_title_display');

                if (modalRoleNameTitleDisplay) {
                    modalRoleNameTitleDisplay.textContent = roleName;
                }

                modalRoleIdInput.value = roleId;
                modalRoleNameInput.value = roleName;
                // NEW: Set checkbox state based on roleIsActive value
                if (modalRoleIsActiveCheckbox) {
                    modalRoleIsActiveCheckbox.checked = (roleIsActive === '1');
                }
            });
        }

    });

    // Helper function to get role ID by name (used for setting dropdown in edit user modal)
    // This function needs to be available globally if not already in functions.php
    // (Currently defined in PHP, so this JS needs to re-implement if roles are only fetched in JS)
    // For now, assume it's correctly used from PHP.
    function get_role_id_by_name(roleName, rolesArray) {
        for (var i = 0; i < rolesArray.length; i++) {
            if (rolesArray[i].role_name === roleName) {
                return rolesArray[i].role_id;
            }
        }
        return null; // Should not happen if roles data is consistent
    }

    // Pass roles data from PHP to JavaScript
    var allRoles = <?php echo json_encode($roles); ?>;

    // Override the get_role_id_by_name function for client-side use
    // This ensures the JS can find the correct role_id for pre-filling the dropdown
    window.get_role_id_by_name = function(roleName) {
        for (var i = 0; i < allRoles.length; i++) {
            if (allRoles[i].role_name === roleName) {
                return allRoles[i].role_id;
            }
        }
        return null;
    };

    document.addEventListener('DOMContentLoaded', function() {
        // The filter logic is now handled by the form submission with GET requests.
        // The previous JavaScript for testFilter change event is no longer needed
        // as the form submission will handle the redirect.

        // NEW: Add event listener for the Reset Filter button
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                window.location.href = '/INTEQC_GLOBAL_ASSESMENT/admin/users'; // Redirect to the page without any GET parameters
            });
        }
    });
</script>
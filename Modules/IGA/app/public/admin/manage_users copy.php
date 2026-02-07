<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อจัดการผู้ใช้งาน

// กำหนด ID ของ Super Admin (สมมติว่าเป็น user_id ที่ 1)
// **สำคัญ: คุณต้องเปลี่ยนเลข 1 ให้ตรงกับ user_id ของผู้ดูแลระบบสูงสุดของคุณในฐานข้อมูล**
define('SUPER_ADMIN_ID','0ADMIN');

// ต้องเรียกใช้ functions.php ก่อน header.php
// เพื่อให้ฟังก์ชัน Gettext() และอื่นๆ พร้อมใช้งาน
require_once __DIR__ . '/../../includes/header.php'; // header.php จะรวม db_connect.php

$page_title = get_text('page_title_manage_users');

// ตั้งค่า user_id ให้กับ Session สำหรับการใช้งานใน SQL Trigger หรือการตรวจสอบ
if (isset($_SESSION['user_id']) && isset($conn) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} elseif (isset($conn) && $conn) {
    $conn->query("SET @user_id = NULL");
}

require_login();
if (!has_role('admin') && !has_role('new_role')) {
    set_alert(get_text("no_permission"), "danger");
    header("Location: login");
    exit();
}

// ---------------- Helper (PHP) ----------------
function php_get_role_id_by_name($roleName, $rolesArray) {
    foreach ($rolesArray as $r) {
        if (isset($r['role_name']) && $r['role_name'] === $roleName) {
            return $r['role_id'];
        }
    }
    return null;
}

// --- ส่วนของการจัดการ Request (เพิ่ม/แก้ไข/ลบ ผู้ใช้/บทบาท) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token เพื่อความปลอดภัย
    $requires_csrf = isset($_POST['action']) && in_array($_POST['action'], ['add_user', 'edit_user', 'delete_user', 'toggle_user_status', 'add_role', 'edit_role']);
    if ($requires_csrf && !verify_csrf_token()) {
        set_alert(get_text("security_error_csrf"), "danger");
        header("Location: /admin/users");
        exit();
    }

    // เพิ่มผู้ใช้ใหม่
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username     = trim($_POST['username']);
        $password     = $_POST['password'];
        $full_name    = trim($_POST['full_name']);
        $email        = trim($_POST['email']);
        $emplevel_id = (isset($_POST['emplevel_id']) && $_POST['emplevel_id'] !== '')? (int)$_POST['emplevel_id']: null;
        $role_id      = (int) $_POST['role_id'];
        $is_active    = isset($_POST['is_active']) ? 1 : 0;

        if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($role_id)) {
            set_alert(get_text("fill_all_fields"), "danger");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert(get_text("invalid_email_format"), "danger");
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();
                if ($count > 0) {
                    set_alert(get_text("username_email_exists"), "danger");
                } else {
                    $user_id   = md5(uniqid(rand(), true));
                    $person_id = md5(uniqid(rand(), true));
                    $stmt = $conn->prepare("INSERT INTO users 
                        (user_id, person_id, username, password_hash, full_name, email, emplevel_id, role_id, is_active, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->bind_param(
                        "ssssssiii",
                        $user_id,
                        $person_id,
                        $username,
                        $hashed_password,
                        $full_name,
                        $email,
                        $emplevel_id,
                        $role_id,
                        $is_active
                    );
                    if ($stmt->execute()) {
                        set_alert(get_text("add_user_success", $username), "success");
                    } else {
                        set_alert(get_text("add_user_error", [$stmt->error]), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_add_user", [$e->getMessage()]), "danger");
            }
        }
        header("Location: /admin/users");
        exit();
    }

    // แก้ไขผู้ใช้ (รวม emplevel_id)
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $person_id   = trim($_POST['person_id']);
        if ($person_id === SUPER_ADMIN_ID) {
            set_alert(get_text("super_admin_cannot_be_modified"), "danger");
            header("Location: /admin/users");
            exit();
        }
        $username    = trim($_POST['username']);
        $password    = $_POST['password'];
        $full_name   = trim($_POST['full_name']);
        $email       = trim($_POST['email']);
        $role_id     = (int) $_POST['role_id'];
        $emplevel_id = (isset($_POST['emplevel_id']) && $_POST['emplevel_id'] !== '')? (int)$_POST['emplevel_id']: null;
        $is_active   = isset($_POST['is_active']) ? 1 : 0;

        if (empty($username) || empty($full_name) || empty($email) || empty($role_id) || empty($person_id)) {
            set_alert(get_text("fill_all_fields"), "danger");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert(get_text("invalid_email_format"), "danger");
        } else {
            try {
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND person_id != ?");
                $check_stmt->bind_param("sss", $username, $email, $person_id);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();

                if ($count > 0) {
                    set_alert(get_text("username_email_exists"), "danger");
                } else {
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role_id = ?, emplevel_id = ?, is_active = ?, updated_at = NOW() ";
                    if (!empty($password)) {
                        $sql .= ", password_hash = ? ";
                    }
                    $sql .= "WHERE person_id = ?";
                    $stmt = $conn->prepare($sql);
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt->bind_param("sssiisss", $username, $full_name, $email, $role_id, $emplevel_id, $is_active, $hashed_password, $person_id);
                    } else {
                        $stmt->bind_param("sssiiss", $username, $full_name, $email, $role_id, $emplevel_id, $is_active, $person_id);
                    }

                    if ($stmt->execute()) {
                        set_alert(get_text("edit_user_success", $username), "success");
                    } else {
                        set_alert(get_text("edit_user_error", [$stmt->error]), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_edit_user", [$e->getMessage()]), "danger");
            }
        }
        header("Location: /admin/users");
        exit();
    }

    // ลบผู้ใช้
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $person_id = trim($_POST['person_id']);
        if ($person_id === SUPER_ADMIN_ID) {
            set_alert(get_text("super_admin_cannot_be_deleted"), "danger");
            header("Location: /admin/users");
            exit();
        }
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE person_id = ?");
            $stmt->bind_param("s", $person_id);
            if ($stmt->execute()) {
                set_alert(get_text("delete_user_success"), "success");
            } else {
                set_alert(get_text("delete_user_error", [$stmt->error]), "danger");
            }
            $stmt->close();
        } catch (Exception $e) {
            set_alert(get_text("tech_error_delete_user", [$e->getMessage()]), "danger");
        }
        header("Location: /admin/users");
        exit();
    }

    // เพิ่มบทบาทใหม่
    if (isset($_POST['action']) && $_POST['action'] === 'add_role') {
        $role_name = trim($_POST['role_name']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $created_by = $_SESSION['user_id'] ?? null;
        if (empty($role_name)) {
            set_alert(get_text("role_name_empty"), "danger");
        } else {
            try {
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ?");
                $check_stmt->bind_param("s", $role_name);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();
                if ($count > 0) {
                    set_alert(get_text("role_name_exists"), "danger");
                } else {
                    $stmt = $conn->prepare("INSERT INTO roles (role_name, is_active, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("sii", $role_name, $is_active, $created_by);
                    if ($stmt->execute()) {
                        set_alert(get_text("add_role_success", $role_name), "success");
                    } else {
                        set_alert(get_text("add_role_error", [$stmt->error]), "danger");
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                set_alert(get_text("tech_error_add_role", [$e->getMessage()]), "danger");
            }
        }
        header("Location: /admin/users");
        exit();
    }

    // แก้ไขบทบาท
    if (isset($_POST['action']) && $_POST['action'] === 'edit_role') {
        $role_id = (int) $_POST['role_id'];
        $role_name = trim($_POST['role_name']);
        $new_is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt_current_role_name = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $stmt_current_role_name->bind_param("i", $role_id);
        $stmt_current_role_name->execute();
        $current_role_name_result = $stmt_current_role_name->get_result()->fetch_assoc();
        $current_role_name_db = $current_role_name_result ? strtolower($current_role_name_result['role_name']) : null;
        $stmt_current_role_name->close();

        if ($current_role_name_db === 'admin') {
            set_alert(get_text("admin_role_cannot_be_modified"), "danger");
            header("Location: /admin/users");
            exit();
        }

        if (empty($role_name)) {
            set_alert(get_text("role_name_empty"), "danger");
        } else {
            try {
                $stmt_current_status = $conn->prepare("SELECT is_active FROM roles WHERE role_id = ?");
                $stmt_current_status->bind_param("i", $role_id);
                $stmt_current_status->execute();
                $current_is_active_result = $stmt_current_status->get_result()->fetch_assoc();
                $current_is_active = $current_is_active_result ? (int) $current_is_active_result['is_active'] : null;
                $stmt_current_status->close();

                if ($current_is_active === 1 && $new_is_active === 0) {
                    $check_users_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND is_active = 1");
                    $check_users_stmt->bind_param("i", $role_id);
                    $check_users_stmt->execute();
                    $user_count = $check_users_stmt->get_result()->fetch_row()[0];
                    $check_users_stmt->close();
                    if ($user_count > 0) {
                        set_alert(get_text("cannot_deactivate_role_has_active_users", [$role_name, $user_count]), "danger");
                        header("Location: /admin/users");
                        exit();
                    }
                }

                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ? AND role_id != ?");
                $check_stmt->bind_param("si", $role_name, $role_id);
                $check_stmt->execute();
                $count = $check_stmt->get_result()->fetch_row()[0];
                $check_stmt->close();

                if ($count > 0) {
                    set_alert(get_text("role_name_exists"), "danger");
                } else {
                    $stmt = $conn->prepare("UPDATE roles SET role_name = ?, is_active = ? WHERE role_id = ?");
                    $stmt->bind_param("sii", $role_name, $new_is_active, $role_id);
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
        header("Location: /admin/users");
        exit();
    }
}

// ---------------- Load data ----------------
$users = [];
$roles = [];
$empLevels = []; // emplevelcode(level_id, level_code)
$items_per_page = 7;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $items_per_page;

try {
    // ดึงข้อมูลบทบาททั้งหมด
    $stmt_roles = $conn->prepare("
        SELECT
            r.role_id,
            r.role_name,
            r.is_active,
            r.created_at,
            u.username AS created_by_username
        FROM roles r
        LEFT JOIN users u ON r.created_by = u.user_id
        ORDER BY r.role_id ASC
    ");
    $stmt_roles->execute();
    $result_roles = $stmt_roles->get_result();
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
    $stmt_roles->close();

    // ดึงข้อมูลระดับพนักงานจาก emplevelcode (level_id, level_code)
    $stmt_emplevel = $conn->prepare("SELECT level_id, level_code FROM emplevelcode ORDER BY level_id ASC");
    $stmt_emplevel->execute();
    $result_emplevel = $stmt_emplevel->get_result();
    while ($row = $result_emplevel->fetch_assoc()) {
        $empLevels[] = $row; // ใช้ key: level_id, level_code
    }
    $stmt_emplevel->close();

    // ฟิลเตอร์
    $filter_role_id = isset($_GET['role_id']) ? (int) $_GET['role_id'] : 0;
    if ($filter_role_id === 0 && isset($_REQUEST['filter_role_id'])) {
        $filter_role_id = (int) $_REQUEST['filter_role_id'];
    }
    $search_query = isset($_REQUEST['search_query']) ? trim($_REQUEST['search_query']) : '';
    $filter_is_active = isset($_REQUEST['filter_is_active']) ? (int) $_REQUEST['filter_is_active'] : -1;

    $sql_users = "
        SELECT
            u.user_id,
            u.person_id,
            u.username,
            u.full_name,
            u.email,
            u.created_at,
            u.is_active,
            u.emplevel_id,          -- FK ไป emplevelcode.level_id
            r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
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

    $count_sql = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.role_id";
    if (!empty($where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    $count_stmt->close();

    $sql_users .= " ORDER BY u.username ASC LIMIT ? OFFSET ?";
    $param_types .= 'ii';
    $params[] = $items_per_page;
    $params[] = $offset;

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
    <?php echo get_alert(); ?>

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
                <form action="/admin/users" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search_query" class="form-label"><?php echo get_text("search_label"); ?></label>
                        <input type="text" class="form-control" id="search_query" name="search_query" placeholder="<?php echo get_text("search_placeholder"); ?>" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_role_id" class="form-label"><?php echo get_text("filter_by_role"); ?></label>
                        <select class="form-select" id="filter_role_id" name="filter_role_id">
                            <option value="0"><?php echo get_text("all_roles"); ?></option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role_id']); ?>"
                                    <?php echo ($filter_role_id == $r['role_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['role_name']); ?>
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
                            <?php $i = ($page - 1) * $items_per_page + 1; ?>
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
                                        <?php if ($user['person_id'] === SUPER_ADMIN_ID): ?>
                                            <span class="badge bg-secondary text-white"><i class="fas fa-lock me-1"></i> <?php echo get_text("super_admin_badge"); ?></span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning me-1 edit-user-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal"
                                                data-person-id="<?php echo htmlspecialchars($user['person_id']); ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role-id="<?php echo htmlspecialchars(php_get_role_id_by_name($user['role_name'], $roles)); ?>"
                                                data-emplevel-id="<?php echo htmlspecialchars($user['emplevel_id']); ?>"
                                                data-is-active="<?php echo htmlspecialchars($user['is_active']); ?>">
                                                <i class="fas fa-edit"></i> <?php echo get_text("edit_button"); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <?php
                    $filter_params = "";
                    if ($filter_role_id > 0) $filter_params .= "&role_id=" . urlencode($filter_role_id);
                    if (!empty($search_query)) $filter_params .= "&search_query=" . urlencode($search_query);
                    if ($filter_is_active !== -1) $filter_params .= "&filter_is_active=" . urlencode($filter_is_active);
                    ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $filter_params ?>" <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $visible_pages = 5;
                            $start_page = max(1, $page - floor($visible_pages / 2));
                            $end_page = min($total_pages, $start_page + $visible_pages - 1);
                            if ($end_page - $start_page + 1 < $visible_pages) {
                                $start_page = max(1, $end_page - $visible_pages + 1);
                            }
                            if ($start_page > 1) {
                                ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= $filter_params ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif;
                            }
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $filter_params ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $total_pages ?><?= $filter_params ?>"><?= $total_pages ?></a>
                                </li>
                            <?php } ?>

                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $filter_params ?>" <?= $page >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
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
                                <th><?php echo get_text("table_header_actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['role_id']); ?></td>
                                    <td><?php echo htmlspecialchars($r['role_name']); ?></td>
                                    <td>
                                        <?php if ($r['is_active']): ?>
                                            <span class="badge bg-success"><?php echo get_text("active"); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo get_text("inactive"); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($r['created_by_username'])) echo htmlspecialchars($r['created_by_username']);
                                        else echo get_text('not_available');
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(isset($r['created_at']) ? thai_datetime_format($r['created_at'], false) : get_text('not_available')); ?></td>
                                    <td>
                                        <?php if (strtolower($r['role_name']) === 'admin'): ?>
                                            <span class="badge bg-secondary text-white"><i class="fas fa-lock me-1"></i> <?php echo get_text("super_admin_badge"); ?></span>
                                        <?php else: ?>
                                            
                                            <button class="btn btn-sm btn-warning me-1 edit-role-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editRoleModal"
                                                data-role-id="<?php echo htmlspecialchars($r['role_id']); ?>"
                                                data-role-name="<?php echo htmlspecialchars($r['role_name']); ?>"
                                                data-is-active="<?php echo htmlspecialchars($r['is_active']); ?>">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addUserModalLabel"><?php echo get_text("add_user_modal_title"); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
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

                    <!-- Emplevel จาก emplevelcode(level_id, level_code) -->
                    <div class="mb-3">
                        <label for="add_emplevel_id" class="form-label"><?php echo get_text("label_emplevel"); ?></label>
                        <select class="form-select" id="add_emplevel_id" name="emplevel_id">
                            <option value=""><? echo get_text('Not specified')?></option>
                            <?php foreach ($empLevels as $lv): ?>
                                <option value="<?php echo htmlspecialchars($lv['level_id']); ?>">
                                    <?php echo htmlspecialchars($lv['level_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="add_role_id" class="form-label"><?php echo get_text("role_label"); ?></label>
                        <select class="form-select" id="add_role_id" name="role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role_id']); ?>">
                                    <?php echo htmlspecialchars($r['role_name']); ?>
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

<!-- Edit User Modal (เพิ่ม emplevel) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editUserModalLabel"><?php echo get_text("edit_user_modal_title_prefix"); ?> <span id="edit_username_title_display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="person_id" id="edit_person_id">
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

                    <!-- Emplevel (ใช้ level_id/level_code) -->
                    <div class="mb-3">
                        <label for="edit_emplevel_id" class="form-label"><?php echo get_text("label_emplevel"); ?></label>
                        <select class="form-select" id="edit_emplevel_id" name="emplevel_id" required>
                            <option value=""><? echo get_text('Not specified')?></option>
                            <?php foreach ($empLevels as $lv): ?>
                                <option value="<?php echo htmlspecialchars($lv['level_id']); ?>">
                                    <?php echo htmlspecialchars($lv['level_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label"><?php echo get_text("role_label"); ?></label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role_id']); ?>">
                                    <?php echo htmlspecialchars($r['role_name']); ?>
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

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addRoleModalLabel"><?php echo get_text("add_role_modal_title"); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users" method="POST">
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

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editRoleModalLabel"><?php echo get_text("edit_role_modal_title_prefix"); ?> <span id="edit_role_name_title_display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/users" method="POST">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="role_id" id="edit_role_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label"><?php echo get_text("role_name_label"); ?></label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_role_is_active" name="is_active" value="1"> 
                        <label class="form-check-label" for="edit_role_is_active"><?php echo get_text("is_active_label"); ?></label>
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
        var editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function(event) {
                var button   = event.relatedTarget;
                var personId = button.getAttribute('data-person-id');
                var username = button.getAttribute('data-username');
                var fullName = button.getAttribute('data-full-name');
                var email    = button.getAttribute('data-email');
                var roleId   = button.getAttribute('data-role-id');
                var empLvId  = button.getAttribute('data-emplevel-id'); // users.emplevel_id (FK → emplevelcode.level_id)
                var isActive = button.getAttribute('data-is-active');

                var modalPersonIdInput    = editUserModal.querySelector('#edit_person_id');
                var modalUsernameInput    = editUserModal.querySelector('#edit_username');
                var modalFullNameInput    = editUserModal.querySelector('#edit_full_name');
                var modalEmailInput       = editUserModal.querySelector('#edit_email');
                var modalRoleIdSelect     = editUserModal.querySelector('#edit_role_id');
                var modalEmpLevelSelect   = editUserModal.querySelector('#edit_emplevel_id');
                var modalIsActiveCheckbox = editUserModal.querySelector('#edit_is_active');
                var modalUsernameTitle    = editUserModal.querySelector('#edit_username_title_display');

                if (modalUsernameTitle) modalUsernameTitle.textContent = username;

                modalPersonIdInput.value        = personId;
                modalUsernameInput.value        = username;
                modalFullNameInput.value        = fullName;
                modalEmailInput.value           = email;
                modalRoleIdSelect.value         = roleId;
                if (modalEmpLevelSelect) modalEmpLevelSelect.value = empLvId; // ตรงกับ level_id
                modalIsActiveCheckbox.checked   = (isActive === '1');
            });
        }

        var editRoleModal = document.getElementById('editRoleModal');
        if (editRoleModal) {
            editRoleModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var roleId = button.getAttribute('data-role-id');
                var roleName = button.getAttribute('data-role-name');
                var roleIsActive = button.getAttribute('data-is-active');

                var modalRoleIdInput = editRoleModal.querySelector('#edit_role_id');
                var modalRoleNameInput = editRoleModal.querySelector('#edit_role_name');
                var modalRoleIsActiveCheckbox = editRoleModal.querySelector('#edit_role_is_active');
                var modalRoleNameTitleDisplay = editRoleModal.querySelector('#edit_role_name_title_display');

                if (modalRoleNameTitleDisplay) modalRoleNameTitleDisplay.textContent = roleName;

                modalRoleIdInput.value = roleId;
                modalRoleNameInput.value = roleName;
                if (modalRoleIsActiveCheckbox) {
                    modalRoleIsActiveCheckbox.checked = (roleIsActive === '1');
                }
            });
        }

        const resetFilterBtn = document.getElementById('resetFilterBtn');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                window.location.href = '/admin/users';
            });
        }
    });

    // เก็บ roles ฝั่ง JS เผื่อใช้ภายหลัง
    var allRoles = <?php echo json_encode($roles); ?>;
    window.get_role_id_by_name = function(roleName) {
        for (var i = 0; i < allRoles.length; i++) {
            if (allRoles[i].role_name === roleName) {
                return allRoles[i].role_id;
            }
        }
        return null;
    };
</script>

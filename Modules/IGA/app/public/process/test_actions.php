<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json'); // กำหนดให้ response เป็น JSON

$conn->query("SET time_zone = '+07:00'");

$response = ['success' => false, 'message' => ''];

// ตรวจสอบว่าล็อกอินและเป็น Admin
require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
  set_alert(get_text('alert_no_admin_permission'), "danger");
  header("Location: /login");
  exit();
}
// ในไฟล์ PHP ของคุณ เช่น header.php หรือก่อนทำการ query ใดๆ ที่แก้ไขข้อมูล
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (string)($_SESSION['user_id'] ?? '');
    $uid = $conn->real_escape_string($current_user_id);
    $conn->query("SET @user_id = '{$uid}'"); // ครอบ quote เสมอ
    // หรือถ้าไม่ได้ใช้ @user_id ที่อื่นจริงๆ ให้ลบทั้งบรรทัดนี้ทิ้งได้เลย

} else {
    // หากไม่มี user_id ใน session (เช่น Guest) หรือไม่มีการเชื่อมต่อ db
    $conn->query("SET @user_id = NULL");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $test_id = $_POST['test_id'] ?? null;
    $test_name = trim($_POST['test_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    // เพิ่มบรรทัดนี้เพื่อรับค่า duration_minutes
    $duration_minutes = filter_var($_POST['duration_minutes'] ?? 0, FILTER_VALIDATE_INT, array("options" => array("min_range" => 0)));

    if (empty($test_id) || !is_numeric($test_id)) {
        $response['message'] = get_text('invalid_test_id');
        echo json_encode($response);
        exit();
    }

    try {
        if ($action === 'delete') {
            // ลบแบบทดสอบ
            $stmt = $conn->prepare("DELETE FROM iga_tests WHERE test_id = ?");
            $stmt->bind_param("i", $test_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = get_text('delete_test_success');
                } else {
                    $response['message'] = get_text('test_not_found_for_deletion');
                }
            } else {
                $response['message'] = get_text('delete_test_error') . ": " . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'publish' || $action === 'unpublish') {
            // เผยแพร่หรือยกเลิกการเผยแพร่
            $is_published = ($action === 'publish') ? 1 : 0;
            $stmt = $conn->prepare("UPDATE iga_tests SET is_published = ? WHERE test_id = ?");
            $stmt->bind_param("ii", $is_published, $test_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $message_key = ($is_published ? 'publish_test_success' : 'unpublish_test_success');
                    $response['message'] = get_text($message_key);
                } else {
                    $response['message'] = get_text('test_not_found_or_status_unchanged');
                }
            } else {
                $response['message'] = get_text('update_test_status_error') . ": " . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = get_text('invalid_action');
        }
    } catch (Exception $e) {
        $response['message'] = get_text('generic_error') . ": " . $e->getMessage();
    }
} else {
    $response['message'] = get_text('invalid_request_method');
}

echo json_encode($response);

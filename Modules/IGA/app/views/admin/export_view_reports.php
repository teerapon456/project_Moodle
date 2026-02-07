<?php
// ไฟล์นี้สำหรับส่งออกข้อมูลรายงานการทำแบบทดสอบเป็นไฟล์ CSV/Excel
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบให้แน่ใจว่าได้เรียก language_helper.php ก่อน functions.php
// หรือให้ functions.php เรียก language_helper.php
require_once __DIR__ . '/../../includes/functions.php'; // functions.php ควรมี require_once db_connect.php

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission'), "danger"); // get_text ไม่รับ array เปล่า
    header("Location: ../../public/login.php");
    exit();
}

// รับค่าตัวกรองจาก URL เหมือนกับ view_reports.php
$test_id_filter = $_GET['test_id'] ?? null;
$search_query = $_GET['search_query'] ?? '';
$filter_status = $_GET['filter_status'] ?? '-1'; // สถานะ completed/in progress
$filter_roles = $_GET['filter_roles'] ?? '-1';
$pass_fail_filter = $_GET['pass_fail_filter'] ?? '-1'; // NEW: สถานะผ่าน/ไม่ผ่าน

try {
    // เตรียม SQL query สำหรับดึงข้อมูลการทำแบบทดสอบ
    $sql = "
        SELECT
            uta.attempt_id,
            u.full_name AS user_name,
            r.role_name AS user_role,
            t.test_name,
            uta.start_time,
            uta.end_time,
            uta.total_score,
            uta.is_completed,
            uta.time_spent_seconds,
            t.min_passing_score, -- ดึงคะแนนผ่านขั้นต่ำของแบบทดสอบ
            COALESCE(test_max_scores.max_test_score, 0) AS max_test_score -- รวมคะแนนสูงสุดที่เป็นไปได้ของแบบทดสอบ
        FROM user_test_attempts uta
        JOIN users u ON uta.user_id = u.user_id
        JOIN tests t ON uta.test_id = t.test_id
        JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN ( -- Subquery เพื่อรวมคะแนนสูงสุดที่เป็นไปได้ของแต่ละแบบทดสอบ
            SELECT
                s.test_id,
                SUM(q.score) AS max_test_score
            FROM sections s
            JOIN questions q ON s.section_id = q.section_id
            GROUP BY s.test_id
        ) AS test_max_scores ON t.test_id = test_max_scores.test_id
    ";

    $where_clauses = [];
    $params = [];
    $types = "";

    // เพิ่มเงื่อนไขการกรองบทบาทเริ่มต้น (Associate หรือ Applicant)
    $where_clauses[] = "r.role_name IN ('associate', 'applicant')";

    // เพิ่มเงื่อนไขการกรองตาม test_id หากมี
    if ($test_id_filter && is_numeric($test_id_filter) && $test_id_filter > 0) {
        $where_clauses[] = "uta.test_id = ?";
        $params[] = (int)$test_id_filter;
        $types .= "i";
    }

    // เพิ่มเงื่อนไขการค้นหาทั่วไป
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(u.full_name LIKE ? OR r.role_name LIKE ? OR t.test_name LIKE ? OR uta.attempt_id LIKE ? OR uta.total_score LIKE ? OR uta.start_time LIKE ? OR end_time LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sssssss";
    }

    // เพิ่มเงื่อนไขการกรองตามสถานะ (Completed/In Progress)
    // **สำคัญ:** หากมีการเลือก filter_status นี้ จะทำงานแยกกับ pass_fail_filter
    if ($filter_status !== '-1') {
        $where_clauses[] = "uta.is_completed = ?";
        $params[] = (int)$filter_status;
        $types .= "i";
    }

    // เพิ่มเงื่อนไขการกรองตาม Role ID (Associate/Applicant)
    if ($filter_roles == '1') { // Associate
        $key = array_search("r.role_name IN ('associate', 'applicant')", $where_clauses);
        if ($key !== false) {
            unset($where_clauses[$key]);
        }
        $where_clauses[] = "r.role_name = 'associate'";
    } elseif ($filter_roles == '2') { // Applicant
        $key = array_search("r.role_name IN ('associate', 'applicant')", $where_clauses);
        if ($key !== false) {
            unset($where_clauses[$key]);
        }
        $where_clauses[] = "r.role_name = 'applicant'";
    }

    // NEW & FIXED: เพิ่มเงื่อนไขการกรองตามสถานะผ่าน/ไม่ผ่าน
    if ($pass_fail_filter !== '-1') {
        // เมื่อมีการกรอง Pass/Fail ให้บังคับกรองเฉพาะที่ 'Completed' เท่านั้น
        // ตรวจสอบว่ามีการเพิ่มเงื่อนไข uta.is_completed ก่อนหน้านี้หรือไม่
        $is_completed_already_filtered = false;
        $is_completed_param_index = -1;
        foreach ($where_clauses as $index => $clause) {
            if (strpos($clause, 'uta.is_completed = ?') !== false) {
                $is_completed_already_filtered = true;
                // หา index ของ parameter ที่สอดคล้องกับ uta.is_completed = ?
                // นี่เป็นวิธีที่ง่ายกว่าการหาตำแหน่งใน $types string ตรงๆ
                // สมมติว่า params ถูกเพิ่มตามลำดับของ clauses
                // (ซึ่งในโค้ดปัจจุบันเป็นเช่นนั้น)
                $temp_sql = "SELECT 1 WHERE " . implode(" AND ", array_slice($where_clauses, 0, $index + 1));
                preg_match_all('/\?/', $temp_sql, $matches, PREG_OFFSET_CAPTURE);
                $is_completed_param_index = count($matches[0]) - 1; // ตำแหน่งของ ? ตัวสุดท้ายที่เกี่ยวข้องกับ is_completed
                break;
            }
        }

        // หากยังไม่ได้กรองสถานะ Completed ให้เพิ่มเงื่อนไข
        // หรือถ้า filter_status เดิมเป็น 0 (In Progress) แต่เลือก pass/fail ให้เปลี่ยนเป็น 1
        if (!$is_completed_already_filtered || (int)$filter_status === 0) {
            if ($is_completed_already_filtered && (int)$filter_status === 0) {
                // ถ้าเดิมมี filter_status = 0 อยู่แล้ว ให้ลบออก
                unset($where_clauses[$index]);
                if ($is_completed_param_index !== -1) {
                    array_splice($params, $is_completed_param_index, 1);
                    $types = substr_replace($types, '', $is_completed_param_index, 1);
                }
            }
            $where_clauses[] = "uta.is_completed = 1"; // บังคับให้เป็น completed
            // ไม่ต้องเพิ่ม param และ type เพราะเป็นค่าคงที่
        }


        // เงื่อนไขในการกรอง: (user_percentage_score >= min_passing_score)
        // ถ้า $pass_fail_filter เป็น '1' (Passed) แสดงว่า (user_percentage_score >= min_passing_score) ต้องเป็น TRUE (1)
        // ถ้า $pass_fail_filter เป็น '0' (Failed) แสดงว่า (user_percentage_score >= min_passing_score) ต้องเป็น FALSE (0)
        $where_clauses[] = "(CASE WHEN COALESCE(test_max_scores.max_test_score, 0) > 0 AND uta.is_completed = 1 THEN (uta.total_score / test_max_scores.max_test_score * 100) >= t.min_passing_score ELSE 0 END) = ?";
        $params[] = (int)$pass_fail_filter;
        $types .= "i";
    }


    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY uta.start_time DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Export Prepare failed: " . $conn->error);
        throw new Exception("Database prepare error. Please check server logs.");
    }

    // ผูกพารามิเตอร์ถ้ามี
    if ($types) {
        // ใช้ call_user_func_array สำหรับ bind_param เพื่อรองรับพารามิเตอร์จำนวนมาก
        $bind_params = [];
        $bind_params[] = &$types; // ใส่ type string เป็นพารามิเตอร์แรก
        for ($i = 0; $i < count($params); $i++) {
            $bind_params[] = &$params[$i]; // เพิ่ม reference ของแต่ละพารามิเตอร์
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // ตั้งค่า HTTP Headers สำหรับการดาวน์โหลดไฟล์ CSV
    $filename = "test_reports_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // เปิด output stream
    $output = fopen('php://output', 'w');

    // เพิ่ม BOM (Byte Order Mark) สำหรับ UTF-8 เพื่อให้ Excel เปิดภาษาไทยได้ถูกต้อง
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // กำหนด Headers ของตาราง
    fputcsv($output, [
        get_text("table_header_number"),
        get_text("table_header_examinee_name"),
        get_text("table_header_user_role"),
        get_text("table_header_test_name"),
        get_text("table_header_start_date"),
        get_text("table_header_end_date"),
        get_text("table_header_total_score"),
        get_text("table_header_max_score"), // NEW
        get_text("table_header_passing_score"), // NEW
        get_text("table_header_pass_fail_status"), // NEW
        get_text("table_header_status"),
        get_text("table_header_time_spent_seconds")
    ]);

    $i = 1;
    while ($row = $result->fetch_assoc()) {
        $status_completed_or_in_progress = $row['is_completed'] ? get_text("status_completed") : get_text("status_in_progress");
        $end_time = $row['end_time'] ? thai_datetime_format($row['end_time']) : get_text("status_not_completed");

        $pass_fail_status = get_text("status_not_available"); // Default
        if ($row['is_completed'] && $row['max_test_score'] > 0 && $row['total_score'] !== null) {
            $percentage_score = ($row['total_score'] / $row['max_test_score']) * 100;
            if ($percentage_score >= $row['min_passing_score']) {
                $pass_fail_status = get_text("status_passed");
            } else {
                $pass_fail_status = get_text("status_failed");
            }
        } elseif (!$row['is_completed']) {
            $pass_fail_status = get_text("status_in_progress"); // ถ้ายังไม่เสร็จ ก็ถือว่ายังไม่มีสถานะผ่าน/ไม่ผ่าน
        }


        fputcsv($output, [
            $i++,
            $row['user_name'],
            $row['user_role'],
            $row['test_name'],
            thai_datetime_format($row['start_time']),
            $end_time,
            number_format($row['total_score'], 2),
            number_format($row['max_test_score'], 2), // NEW
            number_format($row['min_passing_score'], 2), // NEW
            $pass_fail_status, // NEW
            $status_completed_or_in_progress,
            $row['time_spent_seconds']
        ]);
    }

    fclose($output);
    $stmt->close();
    // $conn->close(); // ไม่ควรปิด connection ที่นี่ หาก functions.php หรือไฟล์อื่นยังคงใช้งาน $conn
    exit();

} catch (Exception $e) {
    error_log("Error in export_view_reports.php: " . $e->getMessage());
    // ตั้งค่า Header ให้เป็นข้อความธรรมดาหากเกิดข้อผิดพลาด เพื่อให้เบราว์เซอร์แสดงข้อความได้
    header('Content-Type: text/plain; charset=UTF-8');
    echo get_text("export_error_message") . ": " . $e->getMessage();
    exit();
}
?>
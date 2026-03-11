<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

/**
 * เพิ่มลิงก์ "ประวัติหลักสูตร" ใน user profile navigation
 */
function local_coursehistory_extend_navigation_user_settings(\navigation_node $parentnode,
                                                              \stdClass $user,
                                                              \context_user $context,
                                                              \stdClass $course,
                                                              \context_course $coursecontext) {
    global $USER;

    // แสดงเฉพาะเจ้าของ profile หรือผู้ที่มีสิทธิ์ viewall
    if ($USER->id == $user->id || has_capability('local/coursehistory:viewall', \context_system::instance())) {
        $url = new \moodle_url('/local/coursehistory/profile.php', ['userid' => $user->id]);
        $parentnode->add(
            get_string('coursehistory', 'local_coursehistory'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'coursehistory',
            new \pix_icon('i/course', '')
        );
    }
}

/**
 * เพิ่มลิงก์ใน user profile page
 */
function local_coursehistory_myprofile_navigation(\core_user\output\myprofile\tree $tree,
                                                   $user, $iscurrentuser, $course) {
    if ($iscurrentuser || has_capability('local/coursehistory:viewall', \context_system::instance())) {
        $url = new \moodle_url('/local/coursehistory/profile.php', ['userid' => $user->id]);
        $node = new \core_user\output\myprofile\node(
            'miscellaneous',
            'coursehistory',
            get_string('coursehistory', 'local_coursehistory'),
            null,
            $url
        );
        $tree->add_node($node);
    }
}

/**
 * ค้นหาหลักสูตรที่ตรงกับชื่อหรือ IDNumber
 *
 * @param string $coursename ชื่อหลักสูตรที่ต้องการ match
 * @param string $idnumber รหัสวิชา (ถ้ามี)
 * @return object|false course record ถ้าเจอ, false ถ้าไม่เจอ
 */
function local_coursehistory_match_course($coursename, $idnumber = '') {
    global $DB;

    $coursename = trim($coursename);
    $idnumber = trim($idnumber);

    // 1. ลอง Match ด้วย IDNumber ก่อน (แม่นยำที่สุด)
    if (!empty($idnumber)) {
        $course = $DB->get_record_select('course',
            "idnumber = :idnumber AND idnumber != ''",
            ['idnumber' => $idnumber],
            '*', IGNORE_MULTIPLE);
        if ($course) {
            return $course;
        }
    }

    if (empty($coursename)) {
        return false;
    }

    // 2. ลอง Exact Name Match
    $course = $DB->get_record_select('course',
        $DB->sql_like('fullname', ':name', false),
        ['name' => $coursename],
        '*', IGNORE_MULTIPLE);

    if ($course) {
        return $course;
    }

    // 3. ลอง LIKE match (Fuzzy)
    $likename = '%' . $DB->sql_like_escape($coursename) . '%';
    $course = $DB->get_record_select('course',
        $DB->sql_like('fullname', ':name', false),
        ['name' => $likename],
        '*', IGNORE_MULTIPLE);

    return $course ?: false;
}

/**
 * ตรวจสอบว่าไฟล์ที่อัปโหลดเป็น certificate ที่ถูกต้อง
 * (ตรวจ MIME type: PDF หรือ image)
 *
 * @param stored_file $file
 * @return bool
 */
function local_coursehistory_validate_certificate_file($file) {
    if (!$file) {
        return false;
    }

    $mimetype = $file->get_mimetype();
    $allowed = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    if (!in_array($mimetype, $allowed)) {
        return false;
    }

    // ตรวจขนาดไฟล์ (ไม่เกิน 5MB)
    $maxsize = 5 * 1024 * 1024;
    if ($file->get_filesize() > $maxsize) {
        return false;
    }

    // ตรวจว่าไฟล์ไม่ว่าง
    if ($file->get_filesize() == 0) {
        return false;
    }

    return true;
}

/**
 * ดึงรายการ submissions ของผู้เรียน
 *
 * @param int $userid
 * @param string $status 'all', 'pending', 'approved', 'rejected'
 * @return array
 */
function local_coursehistory_get_user_submissions($userid, $status = 'all') {
    global $DB;

    $params = ['userid' => $userid];
    $where = 'userid = :userid';

    if ($status !== 'all') {
        $statusmap = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
        if (isset($statusmap[$status])) {
            $where .= ' AND status = :status';
            $params['status'] = $statusmap[$status];
        }
    }

    return $DB->get_records_select('local_coursehistory', $where, $params, 'timecreated DESC');
}

/**
 * ดึงข้อมูลสรุปจำนวน submissions ของผู้เรียน
 *
 * @param int $userid
 * @return object {total, pending, approved, rejected}
 */
function local_coursehistory_get_user_stats($userid) {
    global $DB;

    $stats = new \stdClass();
    $stats->total    = $DB->count_records('local_coursehistory', ['userid' => $userid]);
    $stats->pending  = $DB->count_records('local_coursehistory', ['userid' => $userid, 'status' => 0]);
    $stats->approved = $DB->count_records('local_coursehistory', ['userid' => $userid, 'status' => 1]);
    $stats->rejected = $DB->count_records('local_coursehistory', ['userid' => $userid, 'status' => 2]);

    return $stats;
}

/**
 * สร้าง status badge HTML
 *
 * @param int $status 0=pending, 1=approved, 2=rejected
 * @return string HTML
 */
function local_coursehistory_status_badge($status) {
    switch ($status) {
        case 0:
            return \html_writer::tag('span',
                get_string('status_pending', 'local_coursehistory'),
                ['class' => 'badge badge-warning']);
        case 1:
            return \html_writer::tag('span',
                get_string('status_approved', 'local_coursehistory'),
                ['class' => 'badge badge-success']);
        case 2:
            return \html_writer::tag('span',
                get_string('status_rejected', 'local_coursehistory'),
                ['class' => 'badge badge-danger']);
        default:
            return '';
    }
}

/**
 * File serving callback for local_coursehistory
 */
function local_coursehistory_pluginfile($course, $cm, $context, $filearea, $args,
                                        $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'certificate') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    // ตรวจสิทธิ์: เจ้าของไฟล์ หรือ ผู้มีสิทธิ์ viewall
    $record = $DB->get_record('local_coursehistory', ['id' => $itemid]);
    if (!$record) {
        return false;
    }

    if ($USER->id != $record->userid &&
        !has_capability('local/coursehistory:viewall', \context_system::instance())) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_coursehistory', 'certificate',
                           $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * ส่ง notification ไปยังผู้ส่ง submission เมื่อสถานะเปลี่ยน
 *
 * @param object $record record จาก local_coursehistory
 * @param int $newstatus 1=approved, 2=rejected
 * @param object $reviewer user object ของผู้ตรวจ
 * @param string $comment ความคิดเห็นของผู้ตรวจ
 */
function local_coursehistory_notify_user($record, $newstatus, $reviewer, $comment = '') {
    global $DB;

    $submitter = $DB->get_record('user', ['id' => $record->userid]);
    if (!$submitter) {
        return;
    }

    // กำหนดข้อความตามสถานะ
    if ($newstatus == 1) {
        $subject = get_string('notify_approved_subject', 'local_coursehistory');
        $statustext = get_string('status_approved', 'local_coursehistory');
    } else {
        $subject = get_string('notify_rejected_subject', 'local_coursehistory');
        $statustext = get_string('status_rejected', 'local_coursehistory');
    }

    // สร้างข้อความ
    $messagedata = new \stdClass();
    $messagedata->coursename  = format_string($record->coursename);
    $messagedata->status      = $statustext;
    $messagedata->reviewer    = fullname($reviewer);
    $messagedata->comment     = !empty($comment) ? $comment : '-';
    $messagedata->date        = userdate(time(), '%d/%m/%Y %H:%M');

    $fullmessage = get_string('notify_status_body', 'local_coursehistory', $messagedata);

    // ส่ง notification ผ่าน Moodle Message API
    $message = new \core\message\message();
    $message->component         = 'local_coursehistory';
    $message->name              = 'statuschanged';
    $message->userfrom          = $reviewer;
    $message->userto            = $submitter;
    $message->subject           = $subject;
    $message->fullmessage       = $fullmessage;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml   = nl2br(s($fullmessage));
    $message->smallmessage      = $subject . ': ' . format_string($record->coursename);
    $message->notification      = 1;
    $message->contexturl        = (new \moodle_url('/local/coursehistory/view.php',
                                    ['id' => $record->id]))->out(false);
    $message->contexturlname    = get_string('viewsubmission', 'local_coursehistory');

    message_send($message);
}

/**
 * ดึงข้อมูลหลักสูตรที่ผู้ใช้จบแล้วจากระบบ Moodle
 *
 * @param int $userid รหัสผู้ใช้
 * @return array รายการหลักสูตรที่จบแล้ว
 */
function local_coursehistory_get_completed_courses($userid) {
    global $DB;
    
    $sql = "SELECT cc.id, cc.course, cc.timecompleted, cc.reaggregate,
                   c.fullname, c.shortname, c.category, c.startdate, c.enddate,
                   cat.name as categoryname
            FROM {course_completions} cc
            JOIN {course} c ON cc.course = c.id
            LEFT JOIN {course_categories} cat ON c.category = cat.id
            WHERE cc.userid = :userid
            AND cc.timecompleted IS NOT NULL
            AND cc.timecompleted > 0
            AND c.visible = 1
            ORDER BY cc.timecompleted DESC";
    
    return $DB->get_records_sql($sql, ['userid' => $userid]);
}

/**
 * แปลงข้อมูลหลักสูตรที่จบแล้วเป็นรูปแบบเดียวกับ coursehistory
 *
 * @param int $userid รหัสผู้ใช้
 * @return array รายการหลักสูตรที่จบแล้วในรูปแบบ coursehistory
 */
function local_coursehistory_get_completed_courses_as_history($userid) {
    $completed = local_coursehistory_get_completed_courses($userid);
    $history_items = [];
    
    foreach ($completed as $course) {
        $item = new stdClass();
        $item->id = 'internal_' . $course->id;
        $item->userid = $userid;
        $item->coursename = $course->fullname;
        $item->instructorname = get_string('system_instructor', 'local_coursehistory');
        $item->organization = $course->categoryname ?: get_string('unknown_organization', 'local_coursehistory');
        $item->matchedcourseid = $course->course;
        $item->status = 1; // อนุมัติโดยอัตโนมัติ
        $item->reviewedby = 0; // ระบบ
        $item->reviewcomment = get_string('auto_approved', 'local_coursehistory');
        $item->timecreated = $course->timecompleted;
        $item->timemodified = $course->timecompleted;
        $item->source_type = 'internal'; // บ่งชี้ว่ามาจากระบบภายใน
        
        $history_items[] = $item;
    }
    
    return $history_items;
}

/**
 * ดึงข้อมูลประวัติหลักสูตรทั้งหมด (ภายใน + ภายนอก)
 *
 * @param int $userid รหัสผู้ใช้
 * @param string $status 'all', 'pending', 'approved', 'rejected'
 * @return array รายการหลักสูตรทั้งหมด
 */
function local_coursehistory_get_all_course_history($userid, $status = 'all') {
    global $DB;
    
    $all_history = [];
    $seen_internal_courseids = [];
    
    // 1. ดึงข้อมูลจากตารางประวัติของเราก่อน (รวมถึงที่ Snapshot มาแล้ว)
    $external_courses = local_coursehistory_get_user_submissions($userid, $status);
    foreach ($external_courses as $course) {
        $course->source_type = 'history'; 
        if ($course->matchedcourseid) {
            $seen_internal_courseids[] = $course->matchedcourseid;
        }
        $all_history[] = $course;
    }
    
    // 2. ดึงข้อมูลหลักสูตรภายใน (จบแล้วจาก Moodle) ที่ยังไม่มีการ Snapshot
    if ($status === 'all' || $status === 'approved') {
        $internal_courses = local_coursehistory_get_completed_courses_as_history($userid);
        foreach ($internal_courses as $course) {
            // ถ้ายังไม่มีในตาราง history ของเรา ค่อยเพิ่มเข้าไป (เป็น Live View)
            if (!in_array($course->matchedcourseid, $seen_internal_courseids)) {
                $course->source_type = 'internal_live';
                $all_history[] = $course;
            }
        }
    }
    
    // เรียงตามวันที่ (ยึดตามวันที่จบ/ส่ง ล่าสุดขึ้นก่อน)
    usort($all_history, function($a, $b) {
        $timea = !empty($a->enddate) ? $a->enddate : $a->timecreated;
        $timeb = !empty($b->enddate) ? $b->enddate : $b->timecreated;
        return $timeb - $timea;
    });
    
    return $all_history;
}

/**
 * ดึงสถิติรวมทั้งหลักสูตรภายในและภายนอก
 *
 * @param int $userid รหัสผู้ใช้
 * @return object สถิติรวม
 */
function local_coursehistory_get_combined_stats($userid) {
    global $DB;
    
    // 1. ดึงสถิติจจากตาราง History ของเรา (รวม Snapshots)
    $stats = local_coursehistory_get_user_stats($userid);
    
    // 2. ดึงรายการหลักสูตรใน Moodle Core จริงๆ
    $internal_courses = local_coursehistory_get_completed_courses($userid);
    
    // 3. ตรวจสอบว่ามีกี่วิชาที่ "ยังไม่ได้ Snapshot" มาลงตารางเรา
    $not_snapshotted_count = 0;
    $seen_internal_courseids = $DB->get_fieldset_select('local_coursehistory', 'matchedcourseid', 
        'userid = :userid AND matchedcourseid > 0', ['userid' => $userid]);
    
    foreach ($internal_courses as $c) {
        if (!in_array($c->course, $seen_internal_courseids)) {
            $not_snapshotted_count++;
        }
    }

    $stats->internal = count($internal_courses);
    $stats->external = $stats->total - count($seen_internal_courseids); // ประมาณการส่วนที่มาจากข้างนอกจริงๆ
    $stats->total += $not_snapshotted_count;
    $stats->approved += $not_snapshotted_count;
    
    return $stats;
}

/**
 * นับจำนวน submissions ที่รอการอนุมัติ (สำหรับ reviewer badge)
 *
 * @return int จำนวน pending submissions
 */
function local_coursehistory_get_pending_count() {
    global $DB, $USER;

    $systemcontext = \context_system::instance();

    // ถ้าเป็น admin หรือผู้มีสิทธิ์ review ทั้งหมด -> นับทั้งหมด
    if (has_capability('local/coursehistory:review', $systemcontext)) {
        return $DB->count_records('local_coursehistory', ['status' => 0]);
    }

    // ถ้าเป็นหัวหน้าทีม (reviewteam) -> นับเฉพาะลูกทีม
    if (has_capability('local/coursehistory:reviewteam', $systemcontext)) {
        if (file_exists(__DIR__ . '/../local_orgchart/lib.php')) {
            require_once(__DIR__ . '/../local_orgchart/lib.php');
            $subordinates = local_orgchart_get_subordinates($USER->id);
            if (!empty($subordinates)) {
                list($insql, $params) = $DB->get_in_or_equal($subordinates, SQL_PARAMS_NAMED);
                $params['status'] = 0;
                return $DB->count_records_select('local_coursehistory', "status = :status AND userid $insql", $params);
            }
        }
    }

    return 0;
}

/**
 * ดึงรายการ user IDs ที่ผู้ตรวจคนนี้มีสิทธิ์ตรวจสอบ
 *
 * @param int $reviewerid
 * @return array|null รายชื่อ IDs หรือ null ถ้าเห็นทั้งหมด
 */
function local_coursehistory_get_reviewable_userids($reviewerid) {
    global $USER;
    $systemcontext = \context_system::instance();

    if (has_capability('local/coursehistory:review', $systemcontext)) {
        return null; // เห็นทั้งหมด
    }

    if (has_capability('local/coursehistory:reviewteam', $systemcontext)) {
        if (file_exists(__DIR__ . '/../local_orgchart/lib.php')) {
            require_once(__DIR__ . '/../local_orgchart/lib.php');
            return local_orgchart_get_subordinates($reviewerid);
        }
    }

    return []; // ไม่เห็นใครเลย
}


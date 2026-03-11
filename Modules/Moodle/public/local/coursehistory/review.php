<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026 Inteqc Company
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * หน้าตรวจสอบ/อนุมัติ submissions สำหรับ admin/teacher/head_team/HRD
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/coursehistory/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/coursehistory:review', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursehistory/review.php'));
$PAGE->set_title(get_string('reviewsubmissions', 'local_coursehistory'));
$PAGE->set_heading(get_string('reviewsubmissions', 'local_coursehistory'));
$PAGE->set_pagelayout('admin');

// --- จัดการ action: approve / reject (POST จาก view.php) ---
$action   = optional_param('action', '', PARAM_ALPHA);
$recordid = optional_param('id', 0, PARAM_INT);

if ($action && $recordid && confirm_sesskey()) {
    global $DB;

    $record = $DB->get_record('local_coursehistory', ['id' => $recordid], '*', MUST_EXIST);
    $comment = optional_param('reviewcomment', '', PARAM_TEXT);

    if ($action === 'approve') {
        $record->status        = 1;
        $record->reviewedby    = $USER->id;
        $record->reviewcomment = $comment;
        $record->timemodified  = time();
        $DB->update_record('local_coursehistory', $record);

        // ★ ส่ง notification ไปยังผู้ส่ง
        local_coursehistory_notify_user($record, 1, $USER, $comment);

        redirect(
            new moodle_url('/local/coursehistory/review.php'),
            get_string('approved_success', 'local_coursehistory'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else if ($action === 'reject') {
        $record->status        = 2;
        $record->reviewedby    = $USER->id;
        $record->reviewcomment = $comment;
        $record->timemodified  = time();
        $DB->update_record('local_coursehistory', $record);

        // ★ ส่ง notification ไปยังผู้ส่ง
        local_coursehistory_notify_user($record, 2, $USER, $comment);

        redirect(
            new moodle_url('/local/coursehistory/review.php'),
            get_string('rejected_success', 'local_coursehistory'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
}

// --- ตรวจสอบสิทธิ์การดู (เห็นเฉพาะลูกทีมถ้าเป็น head_team) ---
$reviewableids = local_coursehistory_get_reviewable_userids($USER->id);
$where_auth = '1=1';
$params_auth = [];

if ($reviewableids !== null) {
    if (empty($reviewableids)) {
        // ไม่มีสิทธิ์ดูใครเลย
        $where_auth = '1=0';
    } else {
        list($insql, $params_auth) = $DB->get_in_or_equal($reviewableids, SQL_PARAMS_NAMED, 'auth');
        $where_auth = "h.userid $insql";
    }
}

// --- ดึงข้อมูล ---
$filter = optional_param('filter', 'pending', PARAM_ALPHA);

$params = array_merge($params_auth);
$where  = "($where_auth)";

if ($filter === 'pending') {
    $where .= ' AND h.status = :status';
    $params['status'] = 0;
} else if ($filter === 'approved') {
    $where .= ' AND h.status = :status';
    $params['status'] = 1;
} else if ($filter === 'rejected') {
    $where .= ' AND h.status = :status';
    $params['status'] = 2;
}

$sql = "SELECT h.*, u.firstname, u.lastname, u.email,
               r.firstname AS reviewer_firstname, r.lastname AS reviewer_lastname
          FROM {local_coursehistory} h
          JOIN {user} u ON u.id = h.userid
     LEFT JOIN {user} r ON r.id = h.reviewedby
         WHERE $where
         ORDER BY h.timecreated DESC";

$records = $DB->get_records_sql($sql, $params);

// --- นับจำนวน pending ---
$pendingcount = local_coursehistory_get_pending_count();

// --- แสดงผล ---
echo $OUTPUT->header();

// Pending count badge
if ($pendingcount > 0) {
    echo html_writer::div(
        '🔔 ' . get_string('pending_review_count', 'local_coursehistory', $pendingcount),
        'alert alert-warning mb-3'
    );
}

// Filter buttons
$filterurl = new moodle_url('/local/coursehistory/review.php');
echo html_writer::start_div('mb-3');
$filters = ['all', 'pending', 'approved', 'rejected'];
foreach ($filters as $f) {
    $btnclass = ($filter === $f) ? 'btn-primary' : 'btn-outline-secondary';
    $label = get_string('filter_' . $f, 'local_coursehistory');

    // เพิ่ม badge count สำหรับ pending
    if ($f === 'pending' && $pendingcount > 0) {
        $label .= ' ' . html_writer::tag('span', $pendingcount, ['class' => 'badge badge-light']);
    }

    echo html_writer::link(
        new moodle_url($filterurl, ['filter' => $f]),
        $label,
        ['class' => "btn btn-sm $btnclass mr-1 mb-1"]
    );
}
echo html_writer::end_div();

// ตารางข้อมูล
if (empty($records)) {
    echo $OUTPUT->notification(
        get_string('nosubmissions', 'local_coursehistory'),
        'info'
    );
} else {
    $table = new html_table();

    // กำหนด header ตามสถานะที่ดู
    $headers = [
        get_string('learner', 'local_coursehistory'),
        get_string('idnumber', 'local_coursehistory'),
        get_string('coursename', 'local_coursehistory'),
        get_string('coursetype', 'local_coursehistory'),
        get_string('occurrence', 'local_coursehistory'),
        get_string('organization', 'local_coursehistory'),
        get_string('startdate', 'local_coursehistory'),
        get_string('enddate', 'local_coursehistory'),
        get_string('coursematch', 'local_coursehistory'),
        get_string('status', 'local_coursehistory'),
        get_string('datesubmitted', 'local_coursehistory'),
    ];

    // ★ แสดงคอลัมน์ผู้ตรวจสอบ ถ้าดู approved/rejected/all
    if ($filter !== 'pending') {
        $headers[] = get_string('reviewedby_label', 'local_coursehistory');
        $headers[] = get_string('review_date', 'local_coursehistory');
    }

    $headers[] = get_string('actions', 'local_coursehistory');
    $table->head = $headers;
    $table->attributes['class'] = 'table table-striped table-hover';

    $num = 1;
    foreach ($records as $r) {
        $viewurl = new moodle_url('/local/coursehistory/view.php', ['id' => $r->id]);
        $fullname = fullname($r);

        // Course match badge
        $matchbadge = $r->matchedcourseid
            ? html_writer::tag('span', '✅ ' . get_string('matched', 'local_coursehistory'),
                               ['class' => 'badge badge-success'])
            : html_writer::tag('span', '—', ['class' => 'text-muted']);

        // Status badge
        $statusbadge = local_coursehistory_status_badge($r->status);

        // ★ Action: ปุ่ม "ดูรายละเอียด" (ต้องเข้าไปอนุมัติที่หน้า view)
        $actions = html_writer::link($viewurl,
            get_string('viewsubmission', 'local_coursehistory'),
            ['class' => 'btn btn-sm btn-outline-info']);

        // เพิ่มปุ่ม "ตรวจสอบ" สำหรับ pending
        if ($r->status == 0) {
            $actions = html_writer::link($viewurl,
                '📋 ' . get_string('review_action', 'local_coursehistory'),
                ['class' => 'btn btn-sm btn-warning']);
        }

        $row = [
            $fullname,
            (!empty($r->idnumber) ? format_string($r->idnumber) : '—'),
            html_writer::link($viewurl, format_string($r->coursename)),
            (!empty($r->coursetype) ? get_string('coursetype_'.$r->coursetype, 'local_coursehistory') : '—'),
            (!empty($r->occurrence) ? format_string($r->occurrence) : '—'),
            format_string($r->organization),
            (!empty($r->startdate) ? userdate($r->startdate, '%d/%m/%Y %H:%M') : '—'),
            (!empty($r->enddate) ? userdate($r->enddate, '%d/%m/%Y %H:%M') : '—'),
            $matchbadge,
            $statusbadge,
            userdate($r->timecreated, '%d/%m/%Y %H:%M'),
        ];

        // ★ คอลัมน์ผู้ตรวจสอบ
        if ($filter !== 'pending') {
            if ($r->reviewedby && $r->reviewer_firstname) {
                $reviewername = $r->reviewer_firstname . ' ' . $r->reviewer_lastname;
                // reviewedby = 0 means auto-approved by system
                if ($r->reviewedby == 0) {
                    $reviewername = get_string('auto_approved', 'local_coursehistory');
                }
                $row[] = $reviewername;
                $row[] = userdate($r->timemodified, '%d/%m/%Y %H:%M');
            } else if ($r->reviewedby === '0' || $r->reviewedby == 0) {
                $row[] = ($r->status == 1)
                    ? get_string('auto_approved', 'local_coursehistory')
                    : '—';
                $row[] = ($r->status != 0) ? userdate($r->timemodified, '%d/%m/%Y %H:%M') : '—';
            } else {
                $row[] = '—';
                $row[] = '—';
            }
        }

        $row[] = $actions;
        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();

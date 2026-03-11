<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * หน้าดูรายละเอียดของ submission แต่ละรายการ
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/coursehistory/lib.php');

require_login();
$context = context_system::instance();

$id = required_param('id', PARAM_INT);

global $DB;
$record = $DB->get_record('local_coursehistory', ['id' => $id], '*', MUST_EXIST);

// ตรวจสิทธิ์: เจ้าของ หรือ ผู้มีสิทธิ์ viewall
if ($USER->id != $record->userid) {
    require_capability('local/coursehistory:viewall', $context);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursehistory/view.php', ['id' => $id]));
$PAGE->set_title(get_string('viewsubmission', 'local_coursehistory'));
$PAGE->set_heading(get_string('viewsubmission', 'local_coursehistory'));
$PAGE->set_pagelayout('standard');

// Breadcrumb
$PAGE->navbar->add(
    get_string('coursehistory', 'local_coursehistory'),
    new moodle_url('/local/coursehistory/profile.php', ['userid' => $record->userid])
);
$PAGE->navbar->add(format_string($record->coursename));

// ดึงข้อมูลผู้ส่ง
$submitter = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);

// ดึงข้อมูลผู้ตรวจ
$reviewer = null;
if ($record->reviewedby) {
    $reviewer = $DB->get_record('user', ['id' => $record->reviewedby]);
}

// ดึงไฟล์ใบรับรอง
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_coursehistory', 'certificate',
                              $record->id, 'id', false);

echo $OUTPUT->header();

// --- ข้อมูลหลักสูตร ---
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');

echo html_writer::tag('h5', format_string($record->coursename), ['class' => 'card-title']);
echo local_coursehistory_status_badge($record->status);

echo html_writer::start_tag('dl', ['class' => 'row mt-3']);

// ผู้ส่ง
echo html_writer::tag('dt', get_string('learner', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', fullname($submitter) . ' (' . $submitter->email . ')', ['class' => 'col-sm-9']);

// ชื่อหลักสูตร
echo html_writer::tag('dt', get_string('coursename', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', format_string($record->coursename), ['class' => 'col-sm-9']);

// รหัสหลักสูตร
if (!empty($record->idnumber)) {
    echo html_writer::tag('dt', get_string('idnumber', 'local_coursehistory'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', format_string($record->idnumber), ['class' => 'col-sm-9']);
}

// ประเภทหลักสูตร
if (!empty($record->coursetype)) {
    echo html_writer::tag('dt', get_string('coursetype', 'local_coursehistory'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', get_string('coursetype_' . $record->coursetype, 'local_coursehistory'), ['class' => 'col-sm-9']);
}

// รุ่นที่ / ครั้งที่
if (!empty($record->occurrence)) {
    echo html_writer::tag('dt', get_string('occurrence', 'local_coursehistory'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', format_string($record->occurrence), ['class' => 'col-sm-9']);
}

// ชื่อวิทยากร
echo html_writer::tag('dt', get_string('instructorname', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', format_string($record->instructorname), ['class' => 'col-sm-9']);

// สถาบัน/องค์กร
echo html_writer::tag('dt', get_string('organization', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', format_string($record->organization), ['class' => 'col-sm-9']);

// วันที่เริ่มต้น
echo html_writer::tag('dt', get_string('startdate', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', userdate($record->startdate, '%d/%m/%Y %H:%M'), ['class' => 'col-sm-9']);

// วันที่สิ้นสุด
echo html_writer::tag('dt', get_string('enddate', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', userdate($record->enddate, '%d/%m/%Y %H:%M'), ['class' => 'col-sm-9']);

// Course Match
echo html_writer::tag('dt', get_string('coursematch', 'local_coursehistory'), ['class' => 'col-sm-3']);
if ($record->matchedcourseid) {
    $matchedcourse = $DB->get_record('course', ['id' => $record->matchedcourseid]);
    $matchtext = $matchedcourse
        ? '✅ ' . format_string($matchedcourse->fullname)
        : '⚠️ ' . get_string('coursenotfound', 'local_coursehistory');
    echo html_writer::tag('dd', $matchtext, ['class' => 'col-sm-9']);
} else {
    echo html_writer::tag('dd', '—' . get_string('nomatch', 'local_coursehistory'), ['class' => 'col-sm-9 text-muted']);
}

// วันที่ส่ง
echo html_writer::tag('dt', get_string('datesubmitted', 'local_coursehistory'), ['class' => 'col-sm-3']);
echo html_writer::tag('dd', userdate($record->timecreated, '%d/%m/%Y %H:%M'), ['class' => 'col-sm-9']);

// ผู้ตรวจสอบ
if ($reviewer) {
    echo html_writer::tag('dt', get_string('reviewedby_label', 'local_coursehistory'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', fullname($reviewer), ['class' => 'col-sm-9']);
}

// ความคิดเห็นผู้ตรวจ
if (!empty($record->reviewcomment)) {
    echo html_writer::tag('dt', get_string('reviewcomment', 'local_coursehistory'), ['class' => 'col-sm-3']);
    echo html_writer::tag('dd', format_text($record->reviewcomment), ['class' => 'col-sm-9']);
}

echo html_writer::end_tag('dl');
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// --- ไฟล์ใบรับรอง ---
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h6', get_string('certificatefile', 'local_coursehistory'),
    ['class' => 'card-subtitle mb-2']);

if (!empty($files)) {
    foreach ($files as $file) {
        $fileurl = moodle_url::make_pluginfile_url(
            $context->id,
            'local_coursehistory',
            'certificate',
            $record->id,
            $file->get_filepath(),
            $file->get_filename()
        );

        $mimetype = $file->get_mimetype();
        $filesize = display_size($file->get_filesize());

        echo html_writer::start_div('d-flex align-items-center mb-2');

        // ไอคอนไฟล์
        $icon = ($mimetype === 'application/pdf') ? '📄' : '🖼️';
        echo html_writer::tag('span', $icon, ['class' => 'mr-2', 'style' => 'font-size:1.5em;']);

        echo html_writer::start_div('');
        echo html_writer::link($fileurl, $file->get_filename(), [
            'class' => 'font-weight-bold',
            'target' => '_blank',
        ]);
        echo html_writer::tag('small', " ($filesize)", ['class' => 'text-muted ml-1']);
        echo html_writer::end_div();

        echo html_writer::end_div();

        // ถ้าเป็นรูปภาพ แสดง preview
        if (strpos($mimetype, 'image/') === 0) {
            echo html_writer::img($fileurl, get_string('certificate_preview', 'local_coursehistory'), [
                'class' => 'img-fluid border rounded mt-2',
                'style' => 'max-width:500px;',
            ]);
        }
    }
} else {
    echo html_writer::tag('p', get_string('nofile', 'local_coursehistory'), ['class' => 'text-muted']);
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// --- ปุ่ม action สำหรับ admin ---
if (has_capability('local/coursehistory:review', $context) && $record->status == 0) {
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h6', get_string('reviewactions', 'local_coursehistory'), ['class' => 'card-subtitle mb-3']);

    // Form สำหรับ approve/reject พร้อม comment
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/coursehistory/review.php'),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $record->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('reviewcomment', 'local_coursehistory'),
        ['for' => 'reviewcomment']);
    echo html_writer::tag('textarea', '', [
        'name' => 'reviewcomment',
        'id' => 'reviewcomment',
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => get_string('reviewcomment_placeholder', 'local_coursehistory'),
    ]);
    echo html_writer::end_div();

    echo html_writer::start_div('mt-2');
    echo html_writer::tag('button', get_string('approve', 'local_coursehistory'), [
        'type' => 'submit',
        'name' => 'action',
        'value' => 'approve',
        'class' => 'btn btn-success mr-2',
    ]);
    echo html_writer::tag('button', get_string('reject', 'local_coursehistory'), [
        'type' => 'submit',
        'name' => 'action',
        'value' => 'reject',
        'class' => 'btn btn-danger',
    ]);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
}

// --- ปุ่มกลับ ---
echo html_writer::start_div('mt-3');
if (has_capability('local/coursehistory:review', $context)) {
    echo html_writer::link(
        new moodle_url('/local/coursehistory/review.php'),
        get_string('backtoreview', 'local_coursehistory'),
        ['class' => 'btn btn-outline-primary mr-2']
    );
}
echo html_writer::link(
    new moodle_url('/local/coursehistory/profile.php', ['userid' => $record->userid]),
    get_string('backtohistory', 'local_coursehistory'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();

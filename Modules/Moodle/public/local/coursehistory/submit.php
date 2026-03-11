<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * หน้าส่งข้อมูลหลักสูตรที่เคยเรียนมาแล้ว
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/coursehistory/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/coursehistory:submit', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursehistory/submit.php'));
$PAGE->set_title(get_string('submitcourse', 'local_coursehistory'));
$PAGE->set_heading(get_string('submitcourse', 'local_coursehistory'));
$PAGE->set_pagelayout('standard');

// Add CSS and JS for autocomplete
$PAGE->requires->css('/local/coursehistory/assets/css/autocomplete.css');
$PAGE->requires->js('/local/coursehistory/assets/js/autocomplete.js');

// Breadcrumb
$PAGE->navbar->add(
    get_string('coursehistory', 'local_coursehistory'),
    new moodle_url('/local/coursehistory/profile.php', ['userid' => $USER->id])
);
$PAGE->navbar->add(get_string('submitcourse', 'local_coursehistory'));

$form = new \local_coursehistory\form\submit_form();

// กดยกเลิก → กลับ dashboard
if ($form->is_cancelled()) {
    redirect(new moodle_url('/my/'));
}

// Submit form
if ($data = $form->get_data()) {
    global $DB;

    // --- ตรวจสอบไฟล์ certificate ---
    $draftitemid = file_get_submitted_draft_itemid('certificatefile');

    // --- ค้นหาหลักสูตรที่ match ---
    $matchedcourse = local_coursehistory_match_course($data->coursename);

    // --- บันทึกข้อมูล ---
    $record = new stdClass();
    $record->userid          = $USER->id;
    $record->idnumber        = trim($data->idnumber);
    $record->coursetype      = $data->coursetype;
    $record->occurrence      = trim($data->occurrence);
    $record->coursename      = trim($data->coursename);
    $record->startdate       = $data->startdate;
    $record->enddate         = $data->enddate;
    $record->instructorname  = trim($data->instructorname);
    $record->organization    = trim($data->organization);
    $record->certificatefile = 0; // จะอัปเดตหลังบันทึกไฟล์
    $record->matchedcourseid = $matchedcourse ? $matchedcourse->id : null;
    $record->status          = 0; // 0=pending (Always require review)
    $record->reviewedby      = null;
    $record->reviewcomment   = null;
    $record->timecreated     = time();
    $record->timemodified    = time();

    $recordid = $DB->insert_record('local_coursehistory', $record);

    // --- บันทึกไฟล์จาก draft area ไป permanent storage ---
    if ($draftitemid) {
        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'local_coursehistory',
            'certificate',
            $recordid,
            ['maxbytes' => 5 * 1024 * 1024, 'accepted_types' => ['.pdf', '.jpg', '.jpeg', '.png']]
        );

        // ตรวจสอบไฟล์ที่บันทึกแล้ว
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_coursehistory', 'certificate',
                                      $recordid, 'id', false);

        if (!empty($files)) {
            $file = reset($files);
            $isvalid = local_coursehistory_validate_certificate_file($file);

            if (!$isvalid) {
                // ลบ record และไฟล์ที่ไม่ถูกต้อง
                $DB->delete_records('local_coursehistory', ['id' => $recordid]);
                $fs->delete_area_files($context->id, 'local_coursehistory', 'certificate', $recordid);

                redirect(
                    new moodle_url('/local_coursehistory/submit.php'),
                    get_string('err_invalidfile', 'local_coursehistory'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            // อัปเดต certificatefile field
            $DB->set_field('local_coursehistory', 'certificatefile', $recordid, ['id' => $recordid]);
        }
    }

    // --- สำเร็จ: redirect พร้อมข้อความ ---
    $message = get_string('submitsuccess', 'local_coursehistory');
    $notifytype = \core\output\notification::NOTIFY_INFO;

    redirect(
        new moodle_url('/local/coursehistory/profile.php', ['userid' => $USER->id]),
        $message,
        null,
        $notifytype
    );
}

// --- แสดงหน้า form ---
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('submitcourse_desc', 'local_coursehistory'));
$form->display();
echo $OUTPUT->footer();

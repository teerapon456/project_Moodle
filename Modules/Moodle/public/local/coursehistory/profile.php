<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * หน้า profile แสดงประวัติหลักสูตรของผู้เรียน
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/coursehistory/lib.php');

require_login();
$context = context_system::instance();

$userid = optional_param('userid', $USER->id, PARAM_INT);

// ตรวจสิทธิ์
if ($USER->id != $userid) {
    require_capability('local/coursehistory:viewall', $context);
}

$targetuser = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursehistory/profile.php', ['userid' => $userid]));
$PAGE->set_title(get_string('coursehistory', 'local_coursehistory') . ': ' . fullname($targetuser));
$PAGE->set_heading(get_string('coursehistory', 'local_coursehistory') . ': ' . fullname($targetuser));
$PAGE->set_pagelayout('standard');

// Add CSS and JS for search/filter
$PAGE->requires->css('/local/coursehistory/assets/css/autocomplete.css');
$PAGE->requires->js('/local/coursehistory/assets/js/autocomplete.js');
$PAGE->requires->js('/local/coursehistory/assets/js/search-filter.js');
$PAGE->requires->css('/local/coursehistory/assets/css/calendar.css');
$PAGE->requires->js('/local/coursehistory/assets/js/calendar.js');

// Breadcrumb
$PAGE->navbar->add(get_string('coursehistory', 'local_coursehistory'));

echo $OUTPUT->header();

// --- สรุปสถิติ ---
$stats = local_coursehistory_get_combined_stats($userid);

echo html_writer::start_div('row mb-4');

$statcards = [
    ['total',    $stats->total,    'badge-primary',  '📚'],
    ['approved', $stats->approved, 'badge-success',  '✅'],
    ['pending',  $stats->pending,  'badge-warning',  '⏳'],
    ['rejected', $stats->rejected, 'badge-danger',   '❌'],
];

foreach ($statcards as $card) {
    echo html_writer::start_div('col-sm-3 col-6 mb-2');
    echo html_writer::start_div('card text-center');
    echo html_writer::start_div('card-body py-3');
    echo html_writer::tag('div', $card[3], ['style' => 'font-size:1.5em;']);
    echo html_writer::tag('div', $card[1], ['class' => 'h4 mb-0 font-weight-bold']);
    echo html_writer::tag('small',
        get_string('stat_' . $card[0], 'local_coursehistory'),
        ['class' => "badge $card[2]"]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // row

// --- ปุ่มเพิ่มหลักสูตร (สำหรับเจ้าของเท่านั้น) ---
if ($USER->id == $userid && has_capability('local/coursehistory:submit', $context)) {
    echo html_writer::start_div('mb-3');
    echo html_writer::link(
        new moodle_url('/local/coursehistory/submit.php'),
        '➕ ' . get_string('submitcourse', 'local_coursehistory'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();
}

// --- Search and Filter Section ---
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('searchfilter', 'local_coursehistory'), ['class' => 'card-title mb-3']);

// Search box
echo html_writer::start_div('row mb-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('searchcourses', 'local_coursehistory'), ['for' => 'search-input', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'search-input',
    'class' => 'form-control',
    'placeholder' => get_string('searchplaceholder', 'local_coursehistory'),
    'data-target' => 'course-table'
]);
echo html_writer::end_div();

// Status filter
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('statusfilter', 'local_coursehistory'), ['for' => 'status-filter', 'class' => 'form-label']);
echo html_writer::start_tag('select', [
    'id' => 'status-filter',
    'class' => 'form-select',
    'data-target' => 'course-table'
]);
echo html_writer::tag('option', get_string('allstatus', 'local_coursehistory'), ['value' => 'all']);
echo html_writer::tag('option', get_string('status_pending', 'local_coursehistory'), ['value' => '0']);
echo html_writer::tag('option', get_string('status_approved', 'local_coursehistory'), ['value' => '1']);
echo html_writer::tag('option', get_string('status_rejected', 'local_coursehistory'), ['value' => '2']);
echo html_writer::end_tag('select');
echo html_writer::end_div();

// Year filter
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('yearfilter', 'local_coursehistory'), ['for' => 'year-filter', 'class' => 'form-label']);
echo html_writer::start_tag('select', [
    'id' => 'year-filter',
    'class' => 'form-select',
    'data-target' => 'course-table'
]);

// Generate year options
$current_year = date('Y');
echo html_writer::tag('option', get_string('allyears', 'local_coursehistory'), ['value' => 'all']);
for ($year = $current_year; $year >= $current_year - 5; $year--) {
    echo html_writer::tag('option', $year, ['value' => $year]);
}

echo html_writer::end_tag('select');
echo html_writer::end_div();

// Source type filter
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('sourcetypefilter', 'local_coursehistory'), ['for' => 'source-filter', 'class' => 'form-label']);
echo html_writer::start_tag('select', [
    'id' => 'source-filter',
    'class' => 'form-select',
    'data-target' => 'course-table'
]);
echo html_writer::tag('option', get_string('allsources', 'local_coursehistory'), ['value' => 'all']);
echo html_writer::tag('option', get_string('internal_course', 'local_coursehistory'), ['value' => 'internal']);
echo html_writer::tag('option', get_string('external_course', 'local_coursehistory'), ['value' => 'external']);
echo html_writer::end_tag('select');
echo html_writer::end_div();
echo html_writer::end_div(); // row

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// --- Calendar Section ---
echo html_writer::start_div('row mb-4');
echo html_writer::start_div('col-12');
echo html_writer::tag('div', '', ['id' => 'course-calendar']);
echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

// --- ตารางรายการหลักสูตร ---
$submissions = local_coursehistory_get_all_course_history($userid);

if (empty($submissions)) {
    echo $OUTPUT->notification(
        get_string('nosubmissions', 'local_coursehistory'),
        'info'
    );
} else {
    $table = new html_table();
    $table->head = [
        get_string('idnumber', 'local_coursehistory'),
        get_string('coursename', 'local_coursehistory'),
        get_string('coursetype', 'local_coursehistory'),
        get_string('occurrence', 'local_coursehistory'),
        get_string('instructorname', 'local_coursehistory'),
        get_string('organization', 'local_coursehistory'),
        get_string('sourcetype', 'local_coursehistory'),
        get_string('startdate', 'local_coursehistory'),
        get_string('enddate', 'local_coursehistory'),
        get_string('coursematch', 'local_coursehistory'),
        get_string('status', 'local_coursehistory'),
        get_string('datesubmitted', 'local_coursehistory'),
        get_string('actions', 'local_coursehistory'),
    ];
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->attributes['id'] = 'course-table';

    $num = 1;
    foreach ($submissions as $s) {
        // Determine view URL based on source type
        if (isset($s->source_type) && $s->source_type === 'internal') {
            $viewurl = new moodle_url('/course/view.php', ['id' => $s->matchedcourseid]);
        } else {
            $viewurl = new moodle_url('/local/coursehistory/view.php', ['id' => $s->id]);
        }

        // Source type badge
        $sourceBadge = '';
        if (isset($s->source_type)) {
            if ($s->source_type === 'internal') {
                $sourceBadge = html_writer::tag('span', '🏠 ' . get_string('internal_course', 'local_coursehistory'), 
                    ['class' => 'badge badge-info']);
            } else {
                $sourceBadge = html_writer::tag('span', '🌍 ' . get_string('external_course', 'local_coursehistory'), 
                    ['class' => 'badge badge-secondary']);
            }
        }

        // Course match
        $matchbadge = $s->matchedcourseid
            ? html_writer::tag('span', '✅', ['class' => 'badge badge-success', 'title' => get_string('matched', 'local_coursehistory')])
            : html_writer::tag('span', '—', ['class' => 'text-muted']);

        // Status badge
        $statusbadge = local_coursehistory_status_badge($s->status);

        // View button
        $actions = html_writer::link($viewurl,
            get_string('view', 'local_coursehistory'),
            ['class' => 'btn btn-sm btn-outline-info']);

        $table->data[] = [
            (!empty($s->idnumber) ? format_string($s->idnumber) : '—'),
            html_writer::link($viewurl, format_string($s->coursename)),
            (!empty($s->coursetype) ? get_string('coursetype_'.$s->coursetype, 'local_coursehistory') : '—'),
            (!empty($s->occurrence) ? format_string($s->occurrence) : '—'),
            format_string($s->instructorname),
            format_string($s->organization),
            $sourceBadge,
            (!empty($s->startdate) ? userdate($s->startdate, '%d/%m/%Y %H:%M') : '—'),
            (!empty($s->enddate) ? userdate($s->enddate, '%d/%m/%Y %H:%M') : '—'),
            $matchbadge,
            $statusbadge,
            userdate($s->timecreated, '%d/%m/%Y %H:%M'),
            $actions,
        ];
    }

    echo html_writer::table($table);
}

// --- Admin link ---
if (has_capability('local/coursehistory:review', $context)) {
    echo html_writer::start_div('mt-3');
    echo html_writer::link(
        new moodle_url('/local/coursehistory/review.php'),
        get_string('gotoreview', 'local_coursehistory'),
        ['class' => 'btn btn-outline-warning']
    );
    echo html_writer::end_div();
}

echo $OUTPUT->footer();

// --- JavaScript for calendar data ---
echo html_writer::start_tag('script', ['type' => 'text/javascript']);
echo 'window.courseHistoryData = ' . json_encode(array_values($submissions)) . ';';
echo html_writer::end_tag('script');

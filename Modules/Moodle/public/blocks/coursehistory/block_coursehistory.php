<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    block_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once($CFG->dirroot . '/local/coursehistory/lib.php');

/**
 * Block: เพิ่มหลักสูตรที่เคยเรียนมาแล้ว
 *
 * แสดงบน Dashboard ของผู้เรียน:
 * - ปุ่ม "เพิ่มหลักสูตร"
 * - สรุปจำนวน submissions
 * - รายการล่าสุด 5 รายการ
 */
class block_coursehistory extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_coursehistory');
    }

    public function applicable_formats() {
        return [
            'admin'       => false,
            'site-index'  => true,
            'course-view' => false,
            'mod'         => false,
            'my'          => true,   // ★ แสดงได้บน Dashboard
        ];
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }

    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // --- ปุ่มเพิ่มหลักสูตร ---
        $submiturl = new moodle_url('/local/coursehistory/submit.php');
        $html = html_writer::link($submiturl,
            '➕ ' . get_string('addcourse', 'block_coursehistory'),
            ['class' => 'btn btn-primary btn-sm btn-block mb-3']
        );

        // --- ★ แสดง pending badge สำหรับผู้มีสิทธิ์ review (ทั้งหมด หรือ เฉพาะทีม) ---
        $systemcontext = context_system::instance();
        if (has_capability('local/coursehistory:review', $systemcontext) || 
            has_capability('local/coursehistory:reviewteam', $systemcontext)) {
            
            $pendingcount = local_coursehistory_get_pending_count();
            if ($pendingcount > 0) {
                $reviewurl = new moodle_url('/local/coursehistory/review.php', ['filter' => 'pending']);
                $html .= html_writer::link($reviewurl,
                    '🔔 ' . get_string('pending_review', 'block_coursehistory', $pendingcount),
                    ['class' => 'btn btn-warning btn-sm btn-block mb-3']
                );
            }
        }

        // --- สรุปสถิติ ---
        $stats = local_coursehistory_get_user_stats($USER->id);

        $html .= html_writer::start_div('d-flex justify-content-around text-center mb-3');

        // Total
        $html .= html_writer::start_div('');
        $html .= html_writer::tag('div', $stats->total,
            ['class' => 'h4 mb-0 text-primary font-weight-bold', 'style' => 'font-weight:700;']);
        $html .= html_writer::tag('small',
            get_string('stat_total', 'block_coursehistory'),
            ['class' => 'text-muted']);
        $html .= html_writer::end_div();

        // Approved
        $html .= html_writer::start_div('');
        $html .= html_writer::tag('div', $stats->approved,
            ['class' => 'h4 mb-0 text-success font-weight-bold', 'style' => 'font-weight:700;']);
        $html .= html_writer::tag('small',
            get_string('stat_approved', 'block_coursehistory'),
            ['class' => 'text-muted']);
        $html .= html_writer::end_div();

        // Pending
        $html .= html_writer::start_div('');
        $html .= html_writer::tag('div', $stats->pending,
            ['class' => 'h4 mb-0 text-warning font-weight-bold', 'style' => 'font-weight:700;']);
        $html .= html_writer::tag('small',
            get_string('stat_pending', 'block_coursehistory'),
            ['class' => 'text-muted']);
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();

        // --- รายการล่าสุด ---
        $submissions = local_coursehistory_get_user_submissions($USER->id);

        if (!empty($submissions)) {
            $html .= html_writer::tag('h6',
                get_string('recentsubmissions', 'block_coursehistory'),
                ['class' => 'mb-2', 'style' => 'font-weight:600;']);

            $shown = 0;
            foreach ($submissions as $sub) {
                if ($shown >= 5) break;

                $viewurl = new moodle_url('/local/coursehistory/view.php', ['id' => $sub->id]);
                $statusbadge = local_coursehistory_status_badge($sub->status);

                $html .= html_writer::start_div('d-flex justify-content-between align-items-center py-1 border-bottom');

                $html .= html_writer::start_div('', ['style' => 'min-width:0;flex:1;']);
                $html .= html_writer::link($viewurl, format_string($sub->coursename), [
                    'class' => 'text-truncate d-block small',
                    'title' => format_string($sub->coursename),
                ]);
                $html .= html_writer::end_div();

                $html .= html_writer::start_div('ml-2 text-nowrap');
                $html .= $statusbadge;
                $html .= html_writer::end_div();

                $html .= html_writer::end_div();

                $shown++;
            }
        } else {
            $html .= html_writer::tag('p',
                get_string('nosubmissions', 'block_coursehistory'),
                ['class' => 'text-muted small']
            );
        }

        $this->content->text = $html;

        // --- Footer link ---
        $profileurl = new moodle_url('/local/coursehistory/profile.php', ['userid' => $USER->id]);
        $this->content->footer = html_writer::link($profileurl,
            get_string('viewall', 'block_coursehistory'),
            ['class' => 'btn btn-xs btn-outline-primary btn-block mt-2']
        );

        return $this->content;
    }
}

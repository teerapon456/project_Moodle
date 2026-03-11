<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_coursehistory;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Event observer for local_coursehistory
 */
class observer {

    /**
     * Observer for course completion.
     * When a course is completed, we snapshot it into our local_coursehistory table.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;

        $userid   = $event->relateduserid;
        $courseid = $event->courseid;

        // Get course details.
        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, idnumber', MUST_EXIST);

        // Prepare record for snapshot.
        $record = new stdClass();
        $record->userid          = $userid;
        $record->idnumber        = $course->idnumber;
        $record->coursetype      = 'mandatory'; // Default for internal completions.
        $record->occurrence      = date('Y'); // Default to current year or session.
        $record->coursename      = $course->fullname;
        $record->instructorname  = 'System';
        $record->organization    = get_config('core', 'sitename');
        $record->certificatefile = 0;
        $record->matchedcourseid = $courseid;
        $record->status          = 1; // Auto-approved because it's internal.
        $record->timecreated     = time();
        $record->timemodified    = time();
        
        // Use completion time if available.
        $completion = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $courseid]);
        if ($completion && $completion->timecompleted) {
            $record->startdate = $completion->timestarted ? $completion->timestarted : $completion->timecompleted;
            $record->enddate   = $completion->timecompleted;
        } else {
            $record->startdate = time();
            $record->enddate   = time();
        }

        // Insert snapshot record.
        $DB->insert_record('local_coursehistory', $record);
    }
}

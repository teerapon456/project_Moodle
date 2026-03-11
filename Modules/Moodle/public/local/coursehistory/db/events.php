<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_completed',
        'callback'    => 'local_coursehistory\observer::course_completed',
    ],
];

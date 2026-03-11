<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026 Inteqc Company
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // แจ้งเตือนเมื่อ coursehistory ถูกอนุมัติหรือปฏิเสธ
    'statuschanged' => [
        'capability' => 'local/coursehistory:submit',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDOFF,
        ],
    ],

    // แจ้งผู้มีสิทธิ์ review เมื่อมี submission ใหม่
    'newsubmission' => [
        'capability' => 'local/coursehistory:review',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
];

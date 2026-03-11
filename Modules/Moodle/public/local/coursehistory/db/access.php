<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // ผู้เรียนสามารถส่งข้อมูลหลักสูตรภายนอกได้
    'local/coursehistory:submit' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'user' => CAP_ALLOW,
        ],
    ],

    // ผู้จัดการ/admin สามารถตรวจสอบและอนุมัติ/ปฏิเสธได้ทั่งหมด
    'local/coursehistory:review' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
        ],
    ],

    // หัวหน้าทีมสามารถตรวจสอบและอนุมัติหลักสูตรเฉพาะลูกทีมของตนเอง
    'local/coursehistory:reviewteam' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [], // กำหนดให้หัวหน้าที่ได้รับ role เท่านั้น
    ],

    // ผู้จัดการ/admin สามารถดูข้อมูลหลักสูตรของผู้เรียนทุกคน
    'local/coursehistory:viewall' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
        ],
    ],
];

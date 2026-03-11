<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    block_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/coursehistory:myaddinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'user' => CAP_ALLOW,
        ],
    ],
    'block/coursehistory:addinstance' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
        ],
    ],
];

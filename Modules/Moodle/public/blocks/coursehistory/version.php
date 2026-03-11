<?php
// This file is part of Inteqc Company e-Learning System
//
// @package    block_coursehistory
// @copyright  2026 Inteqc Company
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026030501;        // YYYYMMDDXX — bumped for permission changes
$plugin->requires  = 2024042200;
$plugin->component = 'block_coursehistory';
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.0';
$plugin->dependencies = [
    'local_coursehistory' => 2026030500,
];

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Define install function.
 *
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author     LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Theme_academi install function.
 * สร้างไฟล์ default เฉพาะเมื่อไฟล์ต้นทางมีอยู่และอ่านได้ (ป้องกัน file_exception ตอนติดตั้ง)
 *
 * @return void
 */
function xmldb_theme_academi_install() {
    global $CFG;

    if (method_exists('core_plugin_manager', 'reset_caches')) {
        core_plugin_manager::reset_caches();
    }

    $fs = get_file_storage();
    $contextid = context_system::instance()->id;
    $userid = get_admin()->id;
    $basedir = $CFG->dirroot . '/theme/academi/pix/home/';

    $defaults = [
        [
            'filearea' => 'slide1',
            'filename' => 'slide1.jpg',
            'path' => 'slide1.jpg',
        ],
        [
            'filearea' => 'logo',
            'filename' => 'white-logo.png',
            'path' => 'logo.png',
        ],
        [
            'filearea' => 'footerlogo',
            'filename' => 'footerlogo.png',
            'path' => 'footerlogo.png',
        ],
        [
            'filearea' => 'safe_7',
            'filename' => 'safe_7.png',
            'path' => 'safe_7.png',
        ],
    ];

    foreach ($defaults as $def) {
        $pathname = $basedir . $def['path'];
        if (!is_file($pathname) || !is_readable($pathname)) {
            continue;
        }
        $filerecord = new stdClass();
        $filerecord->component = 'theme_academi';
        $filerecord->contextid = $contextid;
        $filerecord->userid = $userid;
        $filerecord->filearea = $def['filearea'];
        $filerecord->filepath = '/';
        $filerecord->itemid = 0;
        $filerecord->filename = $def['filename'];
        try {
            $fs->create_file_from_pathname($filerecord, $pathname);
        } catch (Exception $e) {
            // ข้ามไฟล์ที่อัปโหลดไม่สำเร็จ ไม่ให้ติดตั้งธีมล้ม
        }
    }
}

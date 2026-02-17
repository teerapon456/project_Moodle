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
 * Admin settings configuration for promoted course section.
 *
 * @package    theme_academi
 * @copyright  2023 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// Promoted Courses.
$temp = new admin_settingpage('theme_academi_promotedcourse', get_string('promotedcoursesheading', 'theme_academi'));

// Promoted Courses Heading.
$name = 'theme_academi_promotedcoursesheading';
$heading = get_string('promotedcoursesheading', 'theme_academi');
$information = '';
$setting = new admin_setting_heading($name, $heading, $information);
$temp->add($setting);

// Enable / Disable option for Promoted Courses.
$name = 'theme_academi/pcoursestatus';
$title = get_string('status', 'theme_academi');
$description = get_string('statusdesc', 'theme_academi');
$default = YES;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$temp->add($setting);

// Promoted courses Block title.
$name = 'theme_academi/promotedtitle';
$title = get_string('title', 'theme_academi');
$description = get_string('promotedtitledesc', 'theme_academi');
$default = 'lang:promotedtitledefault';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$temp->add($setting);

// Promoted courses block description.
$name = 'theme_academi/promotedcoursedesc';
$title = get_string('description', 'theme_academi');
$description = get_string('description_desc', 'theme_academi');
$default = 'lang:description_default';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$temp->add($setting);

// แหล่งหลักสูตรแนะนำ: รหัสหลักสูตร / จากหมวดหมู่ / ใหม่ล่าสุด
$name = 'theme_academi/promotedcoursesource';
$title = get_string('promotedcoursesource', 'theme_academi');
$description = get_string('promotedcoursesourcedesc', 'theme_academi');
$default = 'ids';
$choices = [
    'ids' => get_string('promotedcoursesource_ids', 'theme_academi'),
    'category' => get_string('promotedcoursesource_category', 'theme_academi'),
    'latest' => get_string('promotedcoursesource_latest', 'theme_academi'),
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$temp->add($setting);

// หมวดหมู่สำหรับหลักสูตรแนะนำ (เมื่อเลือก "จากหมวดหมู่")
$name = 'theme_academi/promotedcoursecategory';
$title = get_string('promotedcoursecategory', 'theme_academi');
$description = get_string('promotedcoursecategorydesc', 'theme_academi');
$default = 0;
$choices = [0 => get_string('none', 'core')];
try {
    $root = \core_course_category::get(0);
    $cats = $root->get_children();
    foreach ($cats as $cat) {
        if ($cat->is_uservisible()) {
            $choices[$cat->id] = $cat->get_formatted_name();
        }
    }
} catch (Exception $e) {
    // Ignore.
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$temp->add($setting);

// Promoted courses (ใช้เมื่อเลือก "รหัสหลักสูตร").
$name = 'theme_academi/promotedcourses';
$title = get_string('pcourses', 'theme_academi');
$description = get_string('pcoursesdesc', 'theme_academi');
$default = [];
$courses[0] = '';
$cnt = 0;
if ($ccc = get_courses('all', 'c.sortorder ASC', 'c.id,c.shortname,c.visible,c.category')) {
    foreach ($ccc as $cc) {
        if ($cc->visible == "0" || $cc->id == "1") {
            continue;
        }
        $cnt++;
        $courses[$cc->id] = $cc->shortname;
        // Set some courses for default option.
        if ($cnt < 8) {
            $default[] = $cc->id;
        }
    }
}
$coursedefault = implode(",", $default);
$setting = new admin_setting_configtext($name, $title, $description, $coursedefault);
$temp->add($setting);
$settings->add($temp);

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

defined('MOODLE_INTERNAL') || die();

$temp = new admin_settingpage('theme_academi_categorystrip', get_string('categorystripheading', 'theme_academi'));

$name = 'theme_academi/categorystripstatus';
$title = get_string('categorystripstatus', 'theme_academi');
$description = get_string('categorystripstatusdesc', 'theme_academi');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$temp->add($setting);

$name = 'theme_academi/categorystripcount';
$title = get_string('categorystripcount', 'theme_academi');
$description = get_string('categorystripcountdesc', 'theme_academi');
$setting = new admin_setting_configselect(
    $name,
    $title,
    $description,
    4,
    [
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
    ]
);
$temp->add($setting);

// เรียงลำดับหมวดหมู่: ตามชื่อ / ตามจำนวนหลักสูตร
$name = 'theme_academi/categorystripsort';
$title = get_string('categorystripsort', 'theme_academi');
$description = get_string('categorystripsortdesc', 'theme_academi');
$setting = new admin_setting_configselect(
    $name,
    $title,
    $description,
    'name',
    [
        'name' => get_string('categorystripsort_name', 'theme_academi'),
        'coursecount' => get_string('categorystripsort_coursecount', 'theme_academi'),
    ]
);
$temp->add($setting);

// แสดงเฉพาะหมวดหมู่ที่เลือก (ว่าง = ทั้งหมดระดับบน). รหัสหมวดคั่นด้วยจุลภาค
$name = 'theme_academi/categorystripids';
$title = get_string('categorystripids', 'theme_academi');
$description = get_string('categorystripidsdesc', 'theme_academi');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
$temp->add($setting);

$settings->add($temp);

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

$temp = new admin_settingpage('theme_academi_coursecatalog', get_string('coursecatalogheading', 'theme_academi'));

// Phase 2: หลายหมวดบนหน้าเดียว (แบบ CMU MOOC)
$name = 'theme_academi/frontpage_layout';
$title = get_string('frontpage_layout', 'theme_academi');
$description = get_string('frontpage_layoutdesc', 'theme_academi');
$setting = new admin_setting_configselect(
    $name,
    $title,
    $description,
    'default',
    [
        'default' => get_string('frontpage_layout_default', 'theme_academi'),
        'multicategory' => get_string('frontpage_layout_multicategory', 'theme_academi'),
    ]
);
$temp->add($setting);

$name = 'theme_academi/multicategory_categoryids';
$title = get_string('multicategory_categoryids', 'theme_academi');
$description = get_string('multicategory_categoryidsdesc', 'theme_academi');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
$temp->add($setting);

$name = 'theme_academi/multicategory_per_section';
$title = get_string('multicategory_per_section', 'theme_academi');
$description = get_string('multicategory_per_sectiondesc', 'theme_academi');
$setting = new admin_setting_configselect(
    $name,
    $title,
    $description,
    8,
    [4 => '4', 6 => '6', 8 => '8', 12 => '12', 16 => '16', 20 => '20']
);
$temp->add($setting);

$name = 'theme_academi/coursecatalogperpage';
$title = get_string('coursecatalogperpage', 'theme_academi');
$description = get_string('coursecatalogperpagedesc', 'theme_academi');
$setting = new admin_setting_configselect(
    $name,
    $title,
    $description,
    6,
    [
        6 => '6',
        9 => '9',
        12 => '12',
        18 => '18',
        24 => '24',
    ]
);
$temp->add($setting);

// Responsive: จำนวนคอลัมน์การ์ดหลักสูตรต่อแถว
$name = 'theme_academi/coursecardcolumns_desktop';
$title = get_string('coursecardcolumns_desktop', 'theme_academi');
$description = get_string('coursecardcolumns_desktopdesc', 'theme_academi');
$setting = new admin_setting_configselect($name, $title, $description, 3, [
    2 => '2',
    3 => '3',
    4 => '4',
]);
$temp->add($setting);

$name = 'theme_academi/coursecardcolumns_tablet';
$title = get_string('coursecardcolumns_tablet', 'theme_academi');
$description = get_string('coursecardcolumns_tabletdesc', 'theme_academi');
$setting = new admin_setting_configselect($name, $title, $description, 2, [
    2 => '2',
    3 => '3',
]);
$temp->add($setting);

$name = 'theme_academi/coursecardcolumns_mobile';
$title = get_string('coursecardcolumns_mobile', 'theme_academi');
$description = get_string('coursecardcolumns_mobiledesc', 'theme_academi');
$setting = new admin_setting_configselect($name, $title, $description, 1, [
    1 => '1',
    2 => '2',
]);
$temp->add($setting);
$settings->add($temp);

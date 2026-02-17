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
 * Login Layout
 *
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author     LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$loginlayout = theme_academi_get_setting('loginlayout', false) ?: 'centered';
$darkmode = theme_academi_get_setting('darkmode', false) ?: 'off';
$logourl = theme_academi_get_logo_url('header');
$microsofturl = trim(theme_academi_get_setting('login_microsoft_url', false) ?: '');
$showmicrosoft = !empty($microsofturl);

$extraclasses = ['login-layout-' . $loginlayout];
if ($darkmode === 'on') {
    $extraclasses[] = 'theme-academi-dark';
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
require_once(dirname(__FILE__) .'/includes/layoutdata.php');

global $CFG;
$templatecontext += [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'loginlayout' => $loginlayout,
    'darkmode' => $darkmode,
    'logourl' => $logourl,
    'showmicrosoft' => $showmicrosoft,
    'microsofturl' => $microsofturl,
    'login_homeurl' => $CFG->wwwroot,
];
echo $OUTPUT->render_from_template('theme_academi/login', $templatecontext);

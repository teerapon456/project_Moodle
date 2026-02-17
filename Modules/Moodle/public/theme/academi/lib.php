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
 * lib.php
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('FRONTPAGEPROMOTEDCOURSE', 10);
define('FRONTPAGESITEFEATURES', 11);
define('FRONTPAGEMARKETINGSPOT', 12);
define('FRONTPAGEJUMBOTRON', 13);

define('THEMEDEFAULT', 16);
define('SMALL', 15);
define('MEDIUM', 17);
define('LARGE', 18);

define('MOODLEBASED', 0);
define('THEMEBASED', 1);

define('CAROUSEL', 1);

define('EXPAND', 0);
define('COLLAPSE', 1);

define('NO', 0);
define('YES', 1);

define('SAMEWINDOW', 0);
define('NEWWINDOW', 1);

define('LOGO', 0);
define('SITENAME', 1);
define('LOGOANDSITENAME', 2);

/**
 * Load the Jquery and migration files.
 * โหลดฟอนต์ Noto Sans Myanmar เมื่อเลือกภาษาพม่า
 *
 * @param moodle_page $page
 * @return void
 */
function theme_academi_page_init(moodle_page $page) {
    global $CFG;
    $page->requires->js_call_amd('theme_academi/theme', 'init');
    // โหลดฟอนต์รองรับอักษรพม่าเมื่อใช้ภาษาพม่า
    if (current_language() === 'my') {
        $page->requires->css(
            new moodle_url('https://fonts.googleapis.com/css2?family=Noto+Sans+Myanmar:wght@400;600;700&display=swap'),
            true
        );
    }
    // โหลด Noto Sans Thai เมื่อเลือกฟอนต์ไทย
    $hf = theme_academi_get_setting('headingfont', false);
    $bf = theme_academi_get_setting('bodyfont', false);
    if ($hf === 'noto_thai' || $bf === 'noto_thai') {
        $page->requires->css(
            new moodle_url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap'),
            true
        );
    }
}

/**
 * Loads the CSS Styles and replace the background images.
 * If background image not available in the settings take the default images.
 *
 * @param string $css
 * @param object $theme
 * @return string
 */
function theme_academi_process_css($css, $theme) {
    global $OUTPUT, $CFG;
    $css = theme_academi_pre_css_set_fontwww($css);
    // Set custom CSS.
    $customcss = $theme->settings->customcss;
    $css = theme_academi_set_customcss($css , $customcss);
    return $css;
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 * @return string The CSS which now contains our custom CSS.
 * @return string $css
 */
function theme_academi_set_customcss($css, $customcss) {
    $tag = '[[setting:customcss]]';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_academi_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    static $theme;
    $bgimgs = ['footerbgimg', 'loginbg', 'mspotmedia'];

    if (empty($theme)) {
        $theme = theme_config::load('academi');
    }
    if ($context->contextlevel == CONTEXT_SYSTEM) {

        if ($filearea === 'logo') {
            return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
        } else if ($filearea === 'footerlogo') {
            return $theme->setting_file_serve('footerlogo', $args, $forcedownload, $options);
        } else if ($filearea === 'pagebackground') {
            return $theme->setting_file_serve('pagebackground', $args, $forcedownload, $options);
        } else if (preg_match("/slide[1-9][0-9]*image/", $filearea) !== false) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else if (in_array($filearea, $bgimgs)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        } else {
            send_file_not_found();
        }
    } else {
        send_file_not_found();
    }
}

/**
 * Loads the CSS Styles and put the font path
 *
 * @param string $css
 * @return string
 */
function theme_academi_pre_css_set_fontwww($css) {
    global $CFG;
    if (empty($CFG->themewww)) {
        $themewww = $CFG->wwwroot."/theme";
    } else {
        $themewww = $CFG->themewww;
    }
    $tag = '[[setting:fontwww]]';
    $css = str_replace($tag, $themewww.'/academi/fonts/', $css);
    return $css;
}

/**
 * Load the font folder path into the scss.
 * @return string
 */
function theme_academi_set_fontwww() {
    global $CFG;
    if (empty($CFG->themewww)) {
        $themewww = $CFG->wwwroot."/theme";
    } else {
        $themewww = $CFG->themewww;
    }
    $fontwww = '$fontwww: "'.$themewww.'/academi/fonts/"'.";\n";
    return $fontwww;
}


/**
 * Description
 *
 * @param string $type logo position type.
 * @return type|string
 */
function theme_academi_get_logo_url($type = 'header') {
    global $OUTPUT;
    static $theme;
    if (empty($theme)) {
        $theme = theme_config::load('academi');
    }
    if ($type == 'header') {
        $logo = $theme->setting_file_url('logo', 'logo');
        $logo = empty($logo) ? $OUTPUT->get_compact_logo_url() : $logo;
    } else if ($type == 'footer') {
        $logo = $theme->setting_file_url('footerlogo', 'footerlogo');
        $logo = empty($logo) ? '' : $logo;
    }
    return $logo;
}

/**
 *
 * Description
 * @param string $setting
 * @param bool $format
 * @return string
 */
function theme_academi_get_setting($setting, $format = true) {
    global $CFG, $PAGE;
    require_once($CFG->dirroot . '/lib/weblib.php');
    static $theme;
    if (empty($theme)) {
        $theme = theme_config::load('academi');
    }
    if (empty($theme->settings->$setting)) {
        return false;
    } else if (!$format) {
        $return = $theme->settings->$setting;
    } else if ($format === 'format_text') {
        $return = format_text($theme->settings->$setting, FORMAT_PLAIN);
    } else if ($format === 'format_html') {
        $return = format_text($theme->settings->$setting, FORMAT_HTML, ['trusted' => true, 'noclean' => true]);
    } else if ($format === 'file') {
        $return = $PAGE->theme->setting_file_url($setting, $setting);
    } else {
        $return = format_string($theme->settings->$setting);
    }
    return (isset($return)) ? theme_academi_lang($return) : '';
}

/**
 * Returns the language values from the given lang string or key.
 * @param string $key
 * @return string
 */
function theme_academi_lang($key='') {
    $pos = strpos($key, 'lang:');
    if ($pos !== false) {
        list($l, $k) = explode(":", $key);
        if (get_string_manager()->string_exists($k, 'theme_academi')) {
            $v = get_string($k, 'theme_academi');
            return $v;
        } else {
            return $key;
        }
    } else {
        return $key;
    }
}

/**
 * Get category strip data for front page (แถบปุ่มจากชื่อ category ของหลักสูตรจริงใน Moodle).
 * ดึงหมวดหมู่หลักสูตรระดับบน (top-level) มาแสดง ไม่เกิน 4 ปุ่ม (หรือตามที่ตั้งค่า).
 *
 * @return array ['show' => bool, 'items' => [['title' => ..., 'icon' => ..., 'url' => ...], ...]]
 */
function theme_academi_get_category_strip() {
    $status = theme_academi_get_setting('categorystripstatus', false);
    if (empty($status)) {
        return ['show' => false, 'items' => []];
    }

    $max = (int) theme_academi_get_setting('categorystripcount', false);
    if ($max <= 0 || $max > 8) {
        $max = 4;
    }

    $sort = theme_academi_get_setting('categorystripsort', false) ?: 'name';
    $filterids = trim(theme_academi_get_setting('categorystripids', false) ?: '');
    $items = [];
    $defaulticons = ['laptop', 'book-open', 'gears', 'shield-halved'];

    try {
        $root = \core_course_category::get(0);
        $children = $root->get_children();

        if ($filterids !== '') {
            $ids = array_map('intval', array_filter(explode(',', $filterids)));
            $allowed = array_flip($ids);
            $children = array_filter($children, function($cat) use ($allowed) {
                return isset($allowed[(int) $cat->id]);
            });
        }

        $list = [];
        foreach ($children as $cat) {
            if (!$cat->is_uservisible()) {
                continue;
            }
            // สร้าง URL ดูหลักสูตรในหมวด (รองรับทุกเวอร์ชัน Moodle ที่ไม่มี get_url()).
            $categoryurl = new moodle_url('/course/index.php', ['categoryid' => $cat->id]);
            $list[] = [
                'id' => $cat->id,
                'title' => $cat->get_formatted_name(),
                'url' => $categoryurl->out(false),
                'coursecount' => $cat->get_courses_count(),
            ];
        }

        if ($sort === 'coursecount') {
            usort($list, function($a, $b) {
                return $b['coursecount'] - $a['coursecount'];
            });
        } else {
            usort($list, function($a, $b) {
                return strcmp($a['title'], $b['title']);
            });
        }

        $list = array_slice($list, 0, $max);
        foreach ($list as $i => $row) {
            $items[] = [
                'title' => $row['title'],
                'url' => $row['url'],
                'icon' => $defaulticons[$i % 4],
            ];
        }
    } catch (Exception $e) {
        $items = [];
    }

    return [
        'show' => !empty($items),
        'items' => $items,
    ];
}

/**
 * Get sections for front page multi-category layout (Phase 2 - CMU style).
 * Each section = one category with name, url, and list of courses (id, name, url, imgurl, summary).
 *
 * @return array [ ['id' => int, 'name' => string, 'url' => string, 'coursecount' => int, 'courses' => [...] ], ... ]
 */
function theme_academi_get_multicategory_sections() {
    global $CFG, $OUTPUT;

    $layout = theme_academi_get_setting('frontpage_layout', false);
    if ($layout !== 'multicategory') {
        return [];
    }

    $ids = trim(theme_academi_get_setting('multicategory_categoryids', false) ?: '');
    if ($ids === '') {
        return [];
    }

    $persection = (int) (theme_academi_get_setting('multicategory_per_section', false) ?: 8);
    $persection = max(4, min(20, $persection));

    $catids = array_map('intval', array_filter(explode(',', $ids)));
    $sections = [];
    $noimgurl = $OUTPUT->image_url('no-image', 'theme')->out(false);

    foreach ($catids as $catid) {
        try {
            $cat = \core_course_category::get($catid);
        } catch (Exception $e) {
            continue;
        }
        if (!$cat->is_uservisible()) {
            continue;
        }

        $categoryurl = new \moodle_url('/course/index.php', ['categoryid' => $cat->id]);
        $courses = $cat->get_courses(['recursive' => false, 'sort' => ['sortorder' => 1]]);
        $list = [];
        $count = 0;
        foreach ($courses as $course) {
            if ($course->id == SITEID || !$course->visible) {
                continue;
            }
            if ($count >= $persection) {
                break;
            }
            $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $imgurl = $noimgurl;
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $imgurl = \file_encode_url(
                    $CFG->wwwroot . '/pluginfile.php',
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                    !$isimage
                );
                if (!$isimage) {
                    $imgurl = $noimgurl;
                }
                break;
            }
            $summary = '';
            if (!empty($course->summary)) {
                $raw = strip_tags($course->summary);
                $summary = \core_text::substr($raw, 0, 100);
                if (\core_text::strlen($raw) > 100) {
                    $summary .= '…';
                }
            }
            $list[] = [
                'id' => $course->id,
                'name' => $course->get_formatted_name(),
                'url' => $courseurl->out(false),
                'imgurl' => $imgurl,
                'summary' => $summary,
            ];
            $count++;
        }

        $sections[] = [
            'id' => $cat->id,
            'name' => $cat->get_formatted_name(),
            'url' => $categoryurl->out(false),
            'coursecount' => $cat->get_courses_count(),
            'courses' => $list,
        ];
    }

    return $sections;
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_academi_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = (isset($theme->settings->preset) && !empty($theme->settings->preset)) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = \context_system::instance();
    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/academi/scss/preset/default.scss');
    } else if ($filename == 'eguru') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/academi/scss/preset/eguru.scss');
    } else if ($filename == 'klass') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/academi/scss/preset/klass.scss');
    } else if ($filename == 'enlightlite') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/academi/scss/preset/enlightlite.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_academi', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Fallback to default.
        $scss .= file_get_contents($CFG->dirroot . '/theme/academi/scss/preset/default.scss');
    }
    return $scss;
}

/**
 * Get the configuration values into main scss variables.
 *
 * @param string $theme theme data.
 * @return string $scss return the scss values.
 */
function theme_academi_get_pre_scss($theme) {
    $scss = '';
    $helperobj = new theme_academi\helper();
    $scss .= $helperobj->load_bgimages($theme, $scss);
    $scss .= $helperobj->load_additional_scss_settings();
    return $scss;
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_academi_get_extra_scss($theme) {
    // Load the settings from the parent.
    $theme = theme_config::load('boost');
    // Call the parent themes get_extra_scss function.
    $scss = theme_boost_get_extra_scss($theme);
    // ซ่อนข้อความ "Deprecated style in use (.ml)" เพื่อไม่ให้ผู้ใช้เห็น (มาจาก Boost/core ยังใช้ class .ml)
    $scss .= '
[class*="ml-auto"],
[class*="ml-"],
.ml-auto,
.ml-1, .ml-2, .ml-3, .ml-4, .ml-5 {
    background: transparent !important;
}
[class*="ml-auto"]::before,
[class*="ml-"]::before,
.ml-auto::before,
.ml-1::before, .ml-2::before, .ml-3::before, .ml-4::before, .ml-5::before {
    display: none !important;
}
';
    // Dark mode overrides.
    $scss .= '
body.theme-academi-dark {
    background-color: #1a1d21;
    color: #e4e6eb;
}
body.theme-academi-dark #page,
body.theme-academi-dark .drawers,
body.theme-academi-dark .main-inner {
    background-color: #1a1d21;
}
body.theme-academi-dark .card,
body.theme-academi-dark .coursebox,
body.theme-academi-dark .bg-white {
    background-color: #25282c;
    border-color: #3a3f45;
    color: #e4e6eb;
}
body.theme-academi-dark .card a,
body.theme-academi-dark .coursebox a,
body.theme-academi-dark h1, body.theme-academi-dark h2,
body.theme-academi-dark h3, body.theme-academi-dark h4,
body.theme-academi-dark h5, body.theme-academi-dark h6 {
    color: #e4e6eb;
}
body.theme-academi-dark .text-muted,
body.theme-academi-dark .text-body {
    color: #b0b3b8 !important;
}
body.theme-academi-dark #page-footer,
body.theme-academi-dark .footer-dark {
    background-color: #111314 !important;
    color: #b0b3b8;
}
';
    return $scss;
}

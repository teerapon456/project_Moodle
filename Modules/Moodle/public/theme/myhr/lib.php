<?php
defined('MOODLE_INTERNAL') || die();

function theme_myhr_get_main_scss_content($theme)
{
    global $CFG;

    // 1. Prepend custom variables (so they override defaults)
    $scss = file_get_contents($CFG->dirroot . '/theme/myhr/scss/pre.scss');

    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_boost', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - maybe new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    // 2. Append custom styles (CSS rules)
    $scss .= file_get_contents($CFG->dirroot . '/theme/myhr/scss/post.scss');

    return $scss;
}

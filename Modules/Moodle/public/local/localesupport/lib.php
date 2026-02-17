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
 * Locale support plugin - set PHP locale early so Moodle can format dates/numbers for en/th.
 *
 * @package    local_localesupport
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Called after config is loaded. Tries to set PHP locale so Moodle stops showing
 * "server does not fully support English/Thai" and uses the chosen locale for formatting.
 *
 * Only runs in web context; skips during install/upgrade/CLI to avoid breaking the site.
 */
function local_localesupport_after_config() {
    global $CFG;

    // Do not run during install, upgrade, or CLI (e.g. cron) to avoid breaking the site.
    if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
        return;
    }
    if (!empty($CFG->upgraderunning)) {
        return;
    }
    if (defined('ABORT_AFTER_CONFIG')) {
        return;
    }

    $locale = get_config('local_localesupport', 'locale');
    if (empty($locale) || $locale === 'default') {
        return;
    }

    $categories = [LC_TIME, LC_NUMERIC, LC_MONETARY];
    foreach ($categories as $cat) {
        @setlocale($cat, $locale);
    }
    @setlocale(LC_ALL, $locale);
}

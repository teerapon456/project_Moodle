<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

/**
 * Handle database upgrades for local_coursehistory
 */
function xmldb_local_coursehistory_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026031001) {

        // Define field startdate to be added to local_coursehistory.
        $table = new xmldb_table('local_coursehistory');
        
        $field_start = new xmldb_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'organization');
        if (!$dbman->field_exists($table, $field_start)) {
            $dbman->add_field($table, $field_start);
        }

        // Define field enddate to be added to local_coursehistory.
        $field_end = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'startdate');
        if (!$dbman->field_exists($table, $field_end)) {
            $dbman->add_field($table, $field_end);
        }

        // Local_coursehistory savepoint reached.
        upgrade_plugin_savepoint(true, 2026031001, 'local', 'coursehistory');
    }

    if ($oldversion < 2026031002) {

        // Define field idnumber to be added to local_coursehistory.
        $table = new xmldb_table('local_coursehistory');
        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'userid');

        // Launch add field idnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Local_coursehistory savepoint reached.
        upgrade_plugin_savepoint(true, 2026031002, 'local', 'coursehistory');
    }

    if ($oldversion < 2026031003) {

        // Define field coursetype to be added to local_coursehistory.
        $table = new xmldb_table('local_coursehistory');
        $field_type = new xmldb_field('coursetype', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'idnumber');
        if (!$dbman->field_exists($table, $field_type)) {
            $dbman->add_field($table, $field_type);
        }

        // Define field occurrence to be added to local_coursehistory.
        $field_occ = new xmldb_field('occurrence', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'coursetype');
        if (!$dbman->field_exists($table, $field_occ)) {
            $dbman->add_field($table, $field_occ);
        }

        // Local_coursehistory savepoint reached.
        upgrade_plugin_savepoint(true, 2026031003, 'local', 'coursehistory');
    }

    return true;
}

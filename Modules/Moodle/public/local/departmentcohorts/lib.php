<?php

/**
 * Core logic for Department/Institution Cohort Management
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Enqueue custom CSS for the plugin
 */
function local_departmentcohorts_enqueue_assets()
{
    global $PAGE, $OUTPUT;
    $PAGE->requires->css(new moodle_url('/local/departmentcohorts/styles.css'));
}

/**
 * Get unique groups from mdl_user based on type (department or institution)
 */
function local_departmentcohorts_get_groups($type = 'department', $search = '')
{
    global $DB;
    $field = ($type === 'institution') ? 'institution' : 'department';

    $sql = "SELECT DISTINCT {$field} as groupname FROM {user} WHERE {$field} IS NOT NULL AND {$field} != '' AND deleted = 0";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND " . $DB->sql_like($field, '?', false);
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY {$field} ASC";
    $records = $DB->get_records_sql($sql, $params);

    $results = [];
    foreach ($records as $record) {
        $results[] = $record->groupname;
    }
    return $results;
}

/**
 * Get users in a specific group
 */
function local_departmentcohorts_get_users_by_group($type, $value)
{
    global $DB;
    $field = ($type === 'institution') ? 'institution' : 'department';
    return $DB->get_records('user', [$field => $value, 'deleted' => 0], 'lastname, firstname', 'id, firstname, lastname, email, department, institution');
}

/**
 * Get cohort for a group if it exists
 */
function local_departmentcohorts_get_cohort($type, $value)
{
    global $DB;
    $prefix = ($type === 'institution') ? 'inst_' : 'dept_';
    $idnumber = $prefix . md5($value);
    return $DB->get_record('cohort', ['idnumber' => $idnumber]);
}

/**
 * Create cohort for a group
 */
function local_departmentcohorts_create_cohort($type, $value)
{
    global $DB;
    $prefix = ($type === 'institution') ? 'inst_' : 'dept_';
    $idnumber = $prefix . md5($value);
    $syscontext = context_system::instance();

    if ($cohort = $DB->get_record('cohort', ['idnumber' => $idnumber])) {
        return $cohort->id;
    }

    $cohortdata = new stdClass();
    $cohortdata->contextid = $syscontext->id;
    $cohortdata->name = $value;
    $cohortdata->idnumber = $idnumber;
    $cohortdata->description = "Auto-created from " . ucfirst($type) . ": " . $value;
    $cohortdata->visible = 1;
    return cohort_add_cohort($cohortdata);
}

/**
 * Sync members to cohort (Add new, Remove old)
 */
function local_departmentcohorts_sync_members($type, $value)
{
    global $DB;
    $cohort = local_departmentcohorts_get_cohort($type, $value);
    if (!$cohort) return ['added' => 0, 'removed' => 0];

    $group_users = local_departmentcohorts_get_users_by_group($type, $value);
    $group_user_ids = array_keys($group_users);

    // Get current cohort members
    $current_members = $DB->get_records('cohort_members', ['cohortid' => $cohort->id], '', 'userid');
    $current_member_ids = array_keys($current_members);

    $added = 0;
    $removed = 0;

    // Add new members
    foreach ($group_user_ids as $userid) {
        if (!in_array($userid, $current_member_ids)) {
            cohort_add_member($cohort->id, $userid);
            $added++;
        }
    }

    // Remove old members
    foreach ($current_member_ids as $userid) {
        if (!in_array($userid, $group_user_ids)) {
            cohort_remove_member($cohort->id, $userid);
            $removed++;
        }
    }

    // Update cohort name if it changed (optional, but good for sync)
    // if ($cohort->name !== $value) {
    //     $cohort->name = $value;
    //     cohort_update_cohort($cohort);
    // }

    return ['added' => $added, 'removed' => $removed];
}

/**
 * Delete cohort for a group
 */
function local_departmentcohorts_delete_cohort($type, $value)
{
    $cohort = local_departmentcohorts_get_cohort($type, $value);
    if ($cohort) {
        cohort_delete_cohort($cohort);
        return true;
    }
    return false;
}

/**
 * Get map of all plugin-managed cohorts for efficient lookups
 */
function local_departmentcohorts_get_all_cohorts_map($type)
{
    global $DB;
    $prefix = ($type === 'institution') ? 'inst_%' : (($type === 'custom') ? 'custom_%' : 'dept_%');

    $sql = "SELECT idnumber, id, name FROM {cohort} WHERE idnumber LIKE ?";
    $records = $DB->get_records_sql($sql, [$prefix]);

    $map = [];
    foreach ($records as $record) {
        $map[$record->idnumber] = $record;
    }
    return $map;
}

/**
 * Get all users for custom group creation
 */
function local_departmentcohorts_get_all_users($search = '')
{
    global $DB;
    $sql = "SELECT id, firstname, lastname, email, department, institution 
            FROM {user} 
            WHERE deleted = 0 AND id > 1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (" . $DB->sql_like('firstname', '?', false) . " 
                    OR " . $DB->sql_like('lastname', '?', false) . " 
                    OR " . $DB->sql_like('email', '?', false) . "
                    OR " . $DB->sql_like('department', '?', false) . "
                    OR " . $DB->sql_like('institution', '?', false) . ")";
        $searchterm = "%$search%";
        $params = [$searchterm, $searchterm, $searchterm, $searchterm, $searchterm];
    }

    $sql .= " ORDER BY firstname, lastname";
    return $DB->get_records_sql($sql, $params);
}

/**
 * Create and sync a custom cohort based on selected user IDs
 */
function local_departmentcohorts_create_sync_custom_cohort($cohortname, $userids)
{
    global $DB;
    $syscontext = context_system::instance();
    $idnumber = 'custom_' . md5($cohortname);

    // Check if cohort exists or create
    $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
    if (!$cohort) {
        $cohortdata = new stdClass();
        $cohortdata->contextid = $syscontext->id;
        $cohortdata->name = $cohortname;
        $cohortdata->idnumber = $idnumber;
        $cohortdata->description = "Auto-created Custom Group: " . $cohortname;
        $cohortdata->visible = 1;
        $cohortid = cohort_add_cohort($cohortdata);
        $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
    }

    if (!$cohort) {
        return ['added' => 0, 'removed' => 0, 'error' => true];
    }

    // Get current members
    $current_members = $DB->get_records('cohort_members', ['cohortid' => $cohort->id], '', 'userid');
    $current_member_ids = array_keys($current_members);

    $added = 0;

    // Add new members from selection
    foreach ($userids as $userid) {
        $userid = (int)$userid;
        if (!in_array($userid, $current_member_ids)) {
            cohort_add_member($cohort->id, $userid);
            $added++;
        }
    }

    return ['added' => $added, 'removed' => 0, 'cohortname' => $cohort->name];
}

/**
 * Get all plugin-managed cohorts (dept_, inst_, custom_) with member count
 */
function local_departmentcohorts_get_managed_cohorts($search = '')
{
    global $DB;
    $sql = "SELECT c.id, c.name, c.idnumber, c.description, 
                   (SELECT COUNT(*) FROM {cohort_members} cm WHERE cm.cohortid = c.id) as membercount
            FROM {cohort} c
            WHERE (c.idnumber LIKE 'dept_%' OR c.idnumber LIKE 'inst_%' OR c.idnumber LIKE 'custom_%')";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND " . $DB->sql_like('c.name', '?', false);
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY c.name ASC";
    return $DB->get_records_sql($sql, $params);
}

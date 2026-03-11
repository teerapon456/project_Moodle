<?php
/**
 * Core logic for Department Cohort Management
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Get unique departments from mdl_user
 */
function local_departmentcohorts_get_departments($search = '') {
    global $DB;
    $sql = "SELECT DISTINCT department FROM {user} WHERE department IS NOT NULL AND deleted = 0";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND " . $DB->sql_like('department', '?', false);
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY department ASC";
    $records = $DB->get_records_sql($sql, $params);
    return array_keys($records);
}

/**
 * Get users in a specific department
 */
function local_departmentcohorts_get_users_by_dept($dept) {
    global $DB;
    return $DB->get_records('user', ['department' => $dept, 'deleted' => 0], 'lastname, firstname', 'id, firstname, lastname, email, department');
}

/**
 * Create cohort for a department
 */
function local_departmentcohorts_create_cohort($dept) {
    global $DB;
    $idnumber = 'dept_' . clean_param($dept, PARAM_ALPHANUMEXT);
    $syscontext = context_system::instance();

    if ($cohort = $DB->get_record('cohort', ['idnumber' => $idnumber])) {
        return $cohort->id;
    }

    $cohortdata = new stdClass();
    $cohortdata->contextid = $syscontext->id;
    $cohortdata->name = $dept;
    $cohortdata->idnumber = $idnumber;
    $cohortdata->description = 'Auto-created from Department: ' . $dept;
    $cohortdata->visible = 1;
    return cohort_add_cohort($cohortdata);
}

/**
 * Sync members to cohort
 */
function local_departmentcohorts_sync_members($dept) {
    global $DB;
    $idnumber = 'dept_' . clean_param($dept, PARAM_ALPHANUMEXT);
    $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
    if (!$cohort) return 0;

    $users = local_departmentcohorts_get_users_by_dept($dept);
    $added = 0;
    foreach ($users as $u) {
        if (!cohort_is_member($cohort->id, $u->id)) {
            cohort_add_member($cohort->id, $u->id);
            $added++;
        }
    }
    return $added;
}

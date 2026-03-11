<?php
/**
 * Main Management UI for Department Cohorts
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

$syscontext = context_system::instance();
require_login();
require_capability('local/departmentcohorts:manage', $syscontext);

admin_externalpage_setup('local_departmentcohorts_manage');

$search = optional_param('search', '', PARAM_TEXT);
$selected_dept = optional_param('dept', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHA);

// Handle Actions
if ($action && confirm_sesskey()) {
    if ($action == 'create') {
        local_departmentcohorts_create_cohort($selected_dept);
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['dept' => $selected_dept, 'search' => $search]), get_string('cohortcreated', 'local_departmentcohorts'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action == 'sync') {
        $added = local_departmentcohorts_sync_members($selected_dept);
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['dept' => $selected_dept, 'search' => $search]), get_string('memberssynced', 'local_departmentcohorts') . " (Added: $added)", null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();

$depts = local_departmentcohorts_get_departments($search);
$dept_objects = [];
foreach ($depts as $d) {
    $dept_objects[] = [
        'name' => $d,
        'active' => ($d === $selected_dept)
    ];
}

$users = [];
$user_count = 0;
if ($selected_dept) {
    $users = local_departmentcohorts_get_users_by_dept($selected_dept);
    $user_count = count($users);
    $users = array_values($users);
}

$template_data = [
    'baseurl' => new moodle_url('/local/departmentcohorts/index.php', ['search' => $search]),
    'search' => $search,
    'departments' => $dept_objects,
    'selected_dept' => $selected_dept,
    'users' => $users,
    'usercount' => $user_count,
    'sesskey' => sesskey()
];

echo $OUTPUT->render_from_template('local_departmentcohorts/index', $template_data);

echo $OUTPUT->footer();

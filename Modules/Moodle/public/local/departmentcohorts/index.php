<?php

/**
 * Main Management UI for Department/Institution Cohorts
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

require_login();

$syscontext = context_system::instance();
require_capability('local/departmentcohorts:manage', $syscontext);

admin_externalpage_setup('local_departmentcohorts_manage');

$type = optional_param('type', 'department', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHA);
$cohortid = optional_param('cohortid', '', PARAM_TEXT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);

$cohortname = optional_param('cohortname', '', PARAM_TEXT);
$userids = optional_param_array('userids', [], PARAM_INT);

// Handle Actions (Sync Members)
if ($action === 'sync' && confirm_sesskey() && !empty($cohortid)) {
    $parts = explode('|', $cohortid, 2);
    if (count($parts) === 2) {
        $ctype = $parts[0];
        $cgroup = $parts[1];

        // Ensure cohort exists first
        $cohort = local_departmentcohorts_get_cohort($ctype, $cgroup);
        if (!$cohort) {
            local_departmentcohorts_create_cohort($ctype, $cgroup);
        }

        // Sync members
        $stats = local_departmentcohorts_sync_members($ctype, $cgroup);
        $msg = get_string('memberssynced', 'local_departmentcohorts') . " (Added: {$stats['added']}, Removed: {$stats['removed']})";

        $redirecturl = new moodle_url('/local/departmentcohorts/index.php', [
            'type' => $type,
            'search' => $search
        ]);
        redirect($redirecturl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Handle Sync All
if ($action === 'syncall' && confirm_sesskey()) {
    $groups = local_departmentcohorts_get_groups($type);
    $total_added = 0;
    $total_removed = 0;
    $total_groups = 0;

    foreach ($groups as $group) {
        if (empty($group)) continue;

        // Ensure cohort exists
        $cohort = local_departmentcohorts_get_cohort($type, $group);
        if (!$cohort) {
            local_departmentcohorts_create_cohort($type, $group);
        }

        $stats = local_departmentcohorts_sync_members($type, $group);
        $total_added += $stats['added'];
        $total_removed += $stats['removed'];
        $total_groups++;
    }

    $msg = get_string('syncallcomplete', 'local_departmentcohorts')
        . " — Groups: {$total_groups}, Added: {$total_added}, Removed: {$total_removed}";
    redirect(new moodle_url('/local/departmentcohorts/index.php', [
        'type' => $type,
        'search' => $search
    ]), $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Handle Custom Cohort Creation
if ($action === 'create_custom' && confirm_sesskey()) {
    if (empty($cohortname)) {
        $msg = "Cohort name is required.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'custom', 'search' => $search]), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    if (empty($userids)) {
        $msg = "Please select at least one user.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'custom', 'search' => $search]), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    $stats = local_departmentcohorts_create_sync_custom_cohort($cohortname, $userids);
    if (isset($stats['error'])) {
        $msg = "Error creating custom cohort.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'custom', 'search' => $search]), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    $msg = "Custom cohort '{$stats['cohortname']}' created/updated successfully! (Added: {$stats['added']} users)";
    redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'custom', 'search' => $search]), $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Handle Download Template CSV
if ($action === 'download_template') {
    $filename = 'cohort_upload_template.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    // BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";
    echo "email,cohortname\n";
    echo "user1@example.com,Training Group A\n";
    echo "user2@example.com,Training Group A\n";
    echo "user3@example.com,Training Group B\n";
    die();
}

// Handle CSV Upload
if ($action === 'upload_csv' && confirm_sesskey() && isset($_FILES['csvfile'])) {
    $file = $_FILES['csvfile'];

    if ($file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        $msg = "Error uploading file.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'upload']), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        $msg = "Cannot read uploaded file.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'upload']), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    // Read header row
    $header = fgetcsv($handle);
    // Strip BOM if present
    if ($header && isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }
    $header = array_map('strtolower', array_map('trim', $header));

    $email_col = array_search('email', $header);
    $cohort_col = array_search('cohortname', $header);

    if ($email_col === false || $cohort_col === false) {
        fclose($handle);
        $msg = "CSV must have 'email' and 'cohortname' columns.";
        redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'upload']), $msg, null, \core\output\notification::NOTIFY_ERROR);
    }

    // Group users by cohort name
    $cohort_users = [];
    $not_found = 0;
    $total_rows = 0;

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row) || !isset($row[$email_col]) || !isset($row[$cohort_col])) continue;
        $email = trim($row[$email_col]);
        $cname = trim($row[$cohort_col]);
        if (empty($email) || empty($cname)) continue;

        $total_rows++;

        // Find user by email
        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0], 'id');
        if (!$user) {
            $not_found++;
            continue;
        }

        if (!isset($cohort_users[$cname])) {
            $cohort_users[$cname] = [];
        }
        $cohort_users[$cname][] = $user->id;
    }
    fclose($handle);

    // Create cohorts and sync
    $total_added = 0;
    $cohorts_created = 0;
    foreach ($cohort_users as $cname => $uids) {
        $stats = local_departmentcohorts_create_sync_custom_cohort($cname, $uids);
        $total_added += $stats['added'];
        $cohorts_created++;
    }

    $msg = get_string('uploadsuccess', 'local_departmentcohorts')
        . " — Cohorts: {$cohorts_created}, Users added: {$total_added}, Not found: {$not_found} (Total rows: {$total_rows})";
    redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'upload']), $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Handle Delete Cohort
if ($action === 'delete' && confirm_sesskey() && $deleteid > 0) {
    global $DB;
    $cohort = $DB->get_record('cohort', ['id' => $deleteid]);
    if ($cohort) {
        cohort_delete_cohort($cohort);
        $msg = get_string('cohortdeleted', 'local_departmentcohorts') . ": {$cohort->name}";
    } else {
        $msg = "Cohort not found.";
    }
    redirect(new moodle_url('/local/departmentcohorts/index.php', ['type' => 'managed']), $msg, null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_title(get_string('managecohorts', 'local_departmentcohorts'));
$PAGE->set_heading(get_string('managecohorts', 'local_departmentcohorts'));

echo $OUTPUT->header();

$template_data = [
    'baseurl' => (new moodle_url('/local/departmentcohorts/index.php'))->out(false),
    'type' => $type,
    'is_dept' => ($type === 'department'),
    'is_inst' => ($type === 'institution'),
    'is_custom' => ($type === 'custom'),
    'is_upload' => ($type === 'upload'),
    'is_managed' => ($type === 'managed'),
    'search' => $search,
    'cohorts' => [],
    'allusers' => [],
    'managed_cohorts' => [],
    'sesskey' => sesskey(),
    'upload_url' => (new moodle_url('/local/departmentcohorts/index.php'))->out(false) . '?action=upload_csv',
    'download_template_url' => (new moodle_url('/local/departmentcohorts/index.php', ['action' => 'download_template']))->out(false)
];

$baseurl = new moodle_url('/local/departmentcohorts/index.php');

if ($type === 'custom') {
    // Fetch all users for the custom table
    $users = local_departmentcohorts_get_all_users($search);
    foreach ($users as $user) {
        $template_data['allusers'][] = [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'department' => $user->department,
            'institution' => $user->institution
        ];
    }

    $template_data['has_users'] = !empty($template_data['allusers']);
    $template_data['create_custom_url'] = $baseurl->out(false) . '?action=create_custom';
} else if ($type === 'managed') {
    // Fetch all plugin-managed cohorts
    $managed = local_departmentcohorts_get_managed_cohorts($search);
    foreach ($managed as $c) {
        $template_data['managed_cohorts'][] = [
            'id' => $c->id,
            'name' => $c->name,
            'idnumber' => $c->idnumber,
            'membercount' => $c->membercount,
            'description' => $c->description,
            'deleteurl' => (new moodle_url('/local/departmentcohorts/index.php', [
                'action' => 'delete',
                'deleteid' => $c->id,
                'sesskey' => sesskey(),
                'type' => 'managed'
            ]))->out(false)
        ];
    }
    $template_data['has_managed'] = !empty($template_data['managed_cohorts']);
} else if ($type === 'department' || $type === 'institution') {
    // Fetch aggregated groups for department/institution with cohort status
    $groups = local_departmentcohorts_get_groups($type, $search);
    $cohort_map = local_departmentcohorts_get_all_cohorts_map($type);
    $prefix = ($type === 'institution') ? 'inst_' : 'dept_';

    foreach ($groups as $group) {
        if (empty($group)) continue;

        $users = local_departmentcohorts_get_users_by_group($type, $group);
        $idnumber = $prefix . md5($group);
        $has_cohort = isset($cohort_map[$idnumber]);

        $template_data['cohorts'][] = [
            'name' => $group,
            'group_type' => $type,
            'usercount' => count($users),
            'id' => $type . '|' . $group,
            'syncurl' => $baseurl->out(false) . '?action=sync',
            'sesskey' => sesskey(),
            'has_cohort' => $has_cohort
        ];
    }
}
// type === 'upload' shows only the upload form, no data fetching needed.

echo $OUTPUT->render_from_template(
    'local_departmentcohorts/index',
    $template_data
);

echo $OUTPUT->footer();

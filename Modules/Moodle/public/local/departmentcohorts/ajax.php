<?php
/**
 * AJAX Service for Department Cohort Preview (Optional extension)
 */
define('AJAX_SCRIPT', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$dept = optional_param('dept', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHA);

try {
    require_login();
    require_sesskey();
    if (!has_capability('local/departmentcohorts:manage', context_system::instance())) {
        throw new Exception("Access denied");
    }

    $response = ['status' => 'success', 'data' => []];

    if ($action == 'preview' && $dept) {
        $users = local_departmentcohorts_get_users_by_dept($dept);
        $response['data'] = array_values($users);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

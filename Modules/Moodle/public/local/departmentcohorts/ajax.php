<?php

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$PAGE->set_url('/local/departmentcohorts/ajax.php');

$type = optional_param('type', 'department', PARAM_ALPHA);
$group = optional_param('group', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHA);

$allowedtypes = ['department', 'institution'];

try {

    if (!in_array($type, $allowedtypes)) {
        throw new Exception('Invalid type');
    }

    if (!has_capability('local/departmentcohorts:manage', context_system::instance())) {
        throw new Exception('Access denied');
    }

    if ($action !== 'preview') {
        require_sesskey();
    }

    $response = [
        'status' => 'success',
        'data' => []
    ];

    if ($action === 'preview' && $group) {

        $users = local_departmentcohorts_get_users_by_group($type, $group);

        $cleanusers = [];

        foreach ($users as $u) {
            $cleanusers[] = [
                'id' => $u->id,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'email' => $u->email
            ];
        }

        $response['data'] = $cleanusers;
    }

    \core\session\manager::write_close();

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {

    \core\session\manager::write_close();

    header('Content-Type: application/json', true, 403);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

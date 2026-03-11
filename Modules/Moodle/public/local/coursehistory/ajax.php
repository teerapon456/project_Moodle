<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * AJAX endpoints for autocomplete functionality
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/datalib.php');

$action = required_param('action', PARAM_ALPHA);
$query = optional_param('q', '', PARAM_RAW);
$query = trim($query);

$context = context_system::instance();
require_capability('local/coursehistory:submit', $context);

header('Content-Type: application/json');

switch ($action) {
    case 'courses':
        echo json_encode(local_coursehistory_autocomplete_courses($query));
        break;
        
    case 'instructors':
        echo json_encode(local_coursehistory_autocomplete_instructors($query));
        break;
        
    case 'organizations':
        echo json_encode(local_coursehistory_autocomplete_organizations($query));
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * Autocomplete for course names from mdl_course
 */
function local_coursehistory_autocomplete_courses($query) {
    global $DB;
    
    $results = [];
    
    if (strlen($query) < 2) {
        return $results;
    }
    
    // Search courses by fullname or shortname
    $sql = "SELECT id, fullname, shortname 
            FROM {course} 
            WHERE " . $DB->sql_like('fullname', ':query', false) . "
               OR " . $DB->sql_like('shortname', ':query2', false) . "
            AND visible = 1
            ORDER BY fullname
            LIMIT 20";
    
    $courses = $DB->get_records_sql($sql, [
        'query' => '%' . $DB->sql_like_escape($query) . '%',
        'query2' => '%' . $DB->sql_like_escape($query) . '%'
    ]);
    
    foreach ($courses as $course) {
        $results[] = [
            'id' => $course->id,
            'value' => format_string($course->fullname),
            'label' => format_string($course->fullname) . ' (' . format_string($course->shortname) . ')'
        ];
    }
    
    return $results;
}

/**
 * Autocomplete for instructor names from mdl_user with teacher roles
 */
function local_coursehistory_autocomplete_instructors($query) {
    global $DB;
    
    $results = [];
    
    if (strlen($query) < 2) {
        return $results;
    }
    
    // Get users with teacher/editingteacher/manager roles
    $teacherroles = $DB->get_records('role', ['archetype' => 'teacher']);
    $teacherroles = array_merge($teacherroles, $DB->get_records('role', ['archetype' => 'editingteacher']));
    $teacherroles = array_merge($teacherroles, $DB->get_records('role', ['archetype' => 'manager']));
    
    if (empty($teacherroles)) {
        return $results;
    }
    
    $roleids = array_keys($teacherroles);
    list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
    
    // Search users with teacher roles
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            WHERE ra.roleid $insql
            AND u.deleted = 0
            AND u.suspended = 0
            AND (" . $DB->sql_like('u.firstname', ':query', false) . "
                 OR " . $DB->sql_like('u.lastname', ':query2', false) . "
                 OR " . $DB->sql_like("CONCAT(u.firstname, ' ', u.lastname)", ':query3', false) . ")
            ORDER BY u.lastname, u.firstname
            LIMIT 20";
    
    $params['query'] = '%' . $DB->sql_like_escape($query) . '%';
    $params['query2'] = '%' . $DB->sql_like_escape($query) . '%';
    $params['query3'] = '%' . $DB->sql_like_escape($query) . '%';
    
    $users = $DB->get_records_sql($sql, $params);
    
    foreach ($users as $user) {
        $fullname = fullname($user);
        $results[] = [
            'id' => $user->id,
            'value' => $fullname,
            'label' => $fullname . ' (' . $user->email . ')'
        ];
    }
    
    return $results;
}

/**
 * Autocomplete for organizations from course categories and existing data
 */
function local_coursehistory_autocomplete_organizations($query) {
    global $DB;
    
    $results = [];
    
    if (strlen($query) < 2) {
        return $results;
    }
    
    // Get organizations from course categories
    $sql = "SELECT id, name 
            FROM {course_categories}
            WHERE " . $DB->sql_like('name', ':query', false) . "
            AND parent != 0
            ORDER BY name
            LIMIT 15";
    
    $categories = $DB->get_records_sql($sql, [
        'query' => '%' . $DB->sql_like_escape($query) . '%'
    ]);
    
    foreach ($categories as $category) {
        $results[] = [
            'id' => 'cat_' . $category->id,
            'value' => format_string($category->name),
            'label' => format_string($category->name)
        ];
    }
    
    // Get organizations from existing coursehistory submissions
    $sql = "SELECT DISTINCT organization
            FROM {local_coursehistory}
            WHERE " . $DB->sql_like('organization', ':query', false) . "
            ORDER BY organization
            LIMIT 10";
    
    $organizations = $DB->get_fieldset_sql($sql, [
        'query' => '%' . $DB->sql_like_escape($query) . '%'
    ]);
    
    foreach ($organizations as $org) {
        $results[] = [
            'id' => 'hist_' . md5($org),
            'value' => format_string($org),
            'label' => format_string($org)
        ];
    }
    
    // Remove duplicates and limit results
    $unique_results = [];
    $seen = [];
    
    foreach ($results as $result) {
        $key = strtolower($result['value']);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique_results[] = $result;
        }
    }
    
    return array_slice($unique_results, 0, 20);
}

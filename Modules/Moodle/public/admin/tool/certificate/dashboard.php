<?php
// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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
 * Dashboard for managing issued certificates.
 *
 * @package    tool_certificate
 * @copyright  2024 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_certificate/dashboard');

// Check capabilities - ensure only people who can view the admin tree can access this.
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/admin/tool/certificate/dashboard.php');
$PAGE->set_title(get_string('certificatemanagement', 'tool_certificate'));
$PAGE->set_heading(get_string('certificatemanagement', 'tool_certificate'));
$PAGE->navbar->add(get_string('certificatemanagement', 'tool_certificate'));

// Handle revoke action if passed.
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$issueid = optional_param('issueid', 0, PARAM_INT);

if ($action === 'delete' && $issueid) {
    require_sesskey();
    
    global $DB;
    if ($DB->record_exists('tool_certificate_issues', ['id' => $issueid])) {
        // Delete the issue record.
        $DB->delete_records('tool_certificate_issues', ['id' => $issueid]);
        
        // Optionally, log this action or trigger an event if needed in the future.
        \core\notification::success(get_string('certificatedashboard_deletesuccess', 'tool_certificate'));
    } else {
        \core\notification::error(get_string('certificatedashboard_deleteerror', 'tool_certificate'));
    }
    
    redirect(new moodle_url('/admin/tool/certificate/dashboard.php'));
}

// Get statistics.
global $DB;
$totalissued = $DB->count_records('tool_certificate_issues');
$totaltemplates = $DB->count_records('tool_certificate_templates');

// Get issues for the table.
$sql = "SELECT i.id, i.code, i.timecreated, u.firstname, u.lastname, u.email, t.name AS templatename, c.fullname AS coursename 
        FROM {tool_certificate_issues} i
        JOIN {user} u ON i.userid = u.id
        LEFT JOIN {tool_certificate_templates} t ON i.templateid = t.id
        LEFT JOIN {course} c ON i.courseid = c.id
        ORDER BY i.timecreated DESC";
$issues = $DB->get_records_sql($sql);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificatemanagement', 'tool_certificate'));

// Output Stats Cards
?>
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title"><?php echo get_string('certificatedashboard_totalissued', 'tool_certificate'); ?></h5>
                <h2 class="card-text"><?php echo $totalissued; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><?php echo get_string('certificatedashboard_totaltemplates', 'tool_certificate'); ?></h5>
                <h2 class="card-text"><?php echo $totaltemplates; ?></h2>
            </div>
        </div>
    </div>
</div>

<a href="<?php echo new moodle_url('/admin/tool/certificate/manage_templates.php'); ?>" class="btn btn-primary mb-3">
    <?php echo get_string('issuecertificates', 'tool_certificate'); ?>
</a>

<h3><?php echo get_string('certificatesissues', 'tool_certificate'); ?></h3>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Course</th>
                <th>Template</th>
                <th>Issued Date</th>
                <th>Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><?php echo s($issue->firstname . ' ' . $issue->lastname); ?></td>
                    <td><?php echo s($issue->email); ?></td>
                    <td><?php echo s($issue->coursename ? $issue->coursename : '-'); ?></td>
                    <td><?php echo s($issue->templatename); ?></td>
                    <td><?php echo userdate($issue->timecreated); ?></td>
                    <td><small><?php echo s($issue->code); ?></small></td>
                    <td>
                        <?php 
                        $editurl = new moodle_url('/admin/tool/certificate/edit_issue.php', [
                            'issueid' => $issue->id
                        ]);
                        $deleteurl = new moodle_url('/admin/tool/certificate/dashboard.php', [
                            'action' => 'delete',
                            'issueid' => $issue->id,
                            'sesskey' => sesskey()
                        ]);
                        $confirmmsg = get_string('certificatedashboard_deleteconfirm', 'tool_certificate');
                        ?>
                        <a href="<?php echo $editurl; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="<?php echo $deleteurl; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo s($confirmmsg); ?>');">
                            <?php echo get_string('certificatedashboard_delete', 'tool_certificate'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($issues)): ?>
                <tr>
                    <td colspan="7" class="text-center">No certificates issued yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
echo $OUTPUT->footer();

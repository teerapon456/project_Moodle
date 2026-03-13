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
 * Edit issued certificate.
 *
 * @package    tool_certificate
 * @copyright  2024 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

class edit_issue_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $issueid = $this->_customdata['issueid'];
        $issue = $this->_customdata['issue'];

        $mform->addElement('hidden', 'issueid', $issueid);
        $mform->setType('issueid', PARAM_INT);

        $mform->addElement('text', 'code', get_string('code', 'tool_certificate'), ['maxlength' => 40]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->setDefault('code', $issue->code);

        $mform->addElement('date_time_selector', 'timecreated', get_string('issueddate', 'tool_certificate'));
        $mform->setDefault('timecreated', $issue->timecreated);

        $mform->addElement('date_time_selector', 'expires', get_string('expirydate', 'tool_certificate'), ['optional' => true]);
        if ($issue->expires) {
            $mform->setDefault('expires', $issue->expires);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}

$issueid = required_param('issueid', PARAM_INT);

admin_externalpage_setup('tool_certificate/dashboard');
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/admin/tool/certificate/edit_issue.php', ['issueid' => $issueid]);
$PAGE->set_title(get_string('editcertificate', 'tool_certificate'));
$PAGE->set_heading(get_string('editcertificate', 'tool_certificate'));

global $DB;
$issue = $DB->get_record('tool_certificate_issues', ['id' => $issueid], '*', MUST_EXIST);

$form = new edit_issue_form(null, ['issueid' => $issueid, 'issue' => $issue]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/certificate/dashboard.php'));
} else if ($data = $form->get_data()) {
    $updatedata = [
        'id' => $data->issueid,
        'code' => $data->code,
        'timecreated' => $data->timecreated,
        'expires' => $data->expires ?? 0,
        'timemodified' => time()
    ];

    $DB->update_record('tool_certificate_issues', $updatedata);

    \core\notification::success(get_string('certificatedashboard_updatesuccess', 'tool_certificate'));
    redirect(new moodle_url('/admin/tool/certificate/dashboard.php'));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

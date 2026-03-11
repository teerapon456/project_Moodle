<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_coursehistory\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form สำหรับผู้เรียนส่งข้อมูลหลักสูตรที่เคยเรียนมาแล้ว
 */
class submit_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        // --- หัวข้อ: ข้อมูลหลักสูตร ---
        $mform->addElement('header', 'courseinfo',
            get_string('courseinfo', 'local_coursehistory'));

        // รหัสหลักสูตร
        $mform->addElement('text', 'idnumber', get_string('idnumber', 'local_coursehistory'));
        $mform->setType('idnumber', PARAM_RAW);
        $mform->addHelpButton('idnumber', 'idnumber', 'local_coursehistory');

        // ประเภทหลักสูตร
        $options = [
            'mandatory' => get_string('coursetype_mandatory', 'local_coursehistory'),
            'plan'      => get_string('coursetype_plan', 'local_coursehistory'),
            'elective'  => get_string('coursetype_elective', 'local_coursehistory'),
        ];
        $mform->addElement('select', 'coursetype', get_string('coursetype', 'local_coursehistory'), $options);
        $mform->setDefault('coursetype', 'elective');

        // รุ่นที่ / ครั้งที่
        $mform->addElement('text', 'occurrence', get_string('occurrence', 'local_coursehistory'));
        $mform->setType('occurrence', PARAM_TEXT);
        $mform->addHelpButton('occurrence', 'occurrence', 'local_coursehistory');

        // ชื่อหลักสูตร
        $coursename_group = [];
        $coursename_group[] = $mform->createElement('text', 'coursename',
            get_string('coursename', 'local_coursehistory'),
            ['size' => 60, 'maxlength' => 255, 'autocomplete' => 'off', 'class' => 'form-autocomplete']);
        $coursename_group[] = $mform->createElement('static', 'coursename_help', '', 
            '<span class="autocomplete-indicator">🔍</span>');
        $mform->addGroup($coursename_group, 'coursename_group', get_string('coursename', 'local_coursehistory'), '', false);
        $mform->setType('coursename', PARAM_TEXT);
        $mform->addRule('coursename', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('coursename', 'coursename', 'local_coursehistory');
        
        // วันที่เริ่มต้นและสิ้นสุด
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', 'local_coursehistory'));
        $mform->addElement('date_time_selector', 'enddate', get_string('enddate', 'local_coursehistory'));
        $mform->addRule('startdate', get_string('required'), 'required', null, 'client');
        $mform->addRule('enddate', get_string('required'), 'required', null, 'client');

        // ชื่อวิทยากร
        $instructor_group = [];
        $instructor_group[] = $mform->createElement('text', 'instructorname',
            get_string('instructorname', 'local_coursehistory'),
            ['size' => 60, 'maxlength' => 255, 'autocomplete' => 'off', 'class' => 'form-autocomplete']);
        $instructor_group[] = $mform->createElement('static', 'instructor_help', '', 
            '<span class="autocomplete-indicator">👤</span>');
        $mform->addGroup($instructor_group, 'instructor_group', get_string('instructorname', 'local_coursehistory'), '', false);
        $mform->setType('instructorname', PARAM_TEXT);
        $mform->addRule('instructorname', get_string('required'), 'required', null, 'client');

        // สถาบัน/หน่วยงาน/องค์กร
        $organization_group = [];
        $organization_group[] = $mform->createElement('text', 'organization',
            get_string('organization', 'local_coursehistory'),
            ['size' => 60, 'maxlength' => 255, 'autocomplete' => 'off', 'class' => 'form-autocomplete']);
        $organization_group[] = $mform->createElement('static', 'organization_help', '', 
            '<span class="autocomplete-indicator">🏢</span>');
        $mform->addGroup($organization_group, 'organization_group', get_string('organization', 'local_coursehistory'), '', false);
        $mform->setType('organization', PARAM_TEXT);
        $mform->addRule('organization', get_string('required'), 'required', null, 'client');

        // --- หัวข้อ: ใบรับรอง ---
        $mform->addElement('header', 'certfileheader',
            get_string('certificatefile', 'local_coursehistory'));

        // อัปโหลดไฟล์ใบรับรอง
        $mform->addElement('filepicker', 'certificatefile',
            get_string('uploadcertificate', 'local_coursehistory'),
            null,
            [
                'maxbytes'       => 5 * 1024 * 1024, // 5 MB
                'accepted_types' => ['.pdf', '.jpg', '.jpeg', '.png'],
            ]);
        $mform->addRule('certificatefile', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('certificatefile', 'uploadcertificate', 'local_coursehistory');

        // --- ข้อมูลที่ซ่อน ---
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // --- ปุ่ม Submit ---
        $this->add_action_buttons(true, get_string('submitcourse', 'local_coursehistory'));
    }

    /**
     * Server-side validation
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // ตรวจสอบว่ากรอกข้อมูลครบ
        if (empty(trim($data['coursename'] ?? ''))) {
            $errors['coursename_group'] = get_string('err_required', 'local_coursehistory');
        }
        if (empty(trim($data['instructorname'] ?? ''))) {
            $errors['instructor_group'] = get_string('err_required', 'local_coursehistory');
        }
        if (empty(trim($data['organization'] ?? ''))) {
            $errors['organization_group'] = get_string('err_required', 'local_coursehistory');
        }

        // ตรวจสอบการส่งซ้ำ (Overlap check)
        if (!isset($errors['coursename_group'])) {
            global $DB, $USER;
            $where = "userid = :userid AND coursename = :name AND status != 2";
            $params = ['userid' => $USER->id, 'name' => trim($data['coursename'])];
            
            if (!empty($data['idnumber'])) {
                $where = "userid = :userid AND (idnumber = :idnumber OR coursename = :name) AND status != 2";
                $params['idnumber'] = trim($data['idnumber']);
            }

            $existings = $DB->get_records_select('local_coursehistory', $where, $params);
            foreach ($existings as $ex) {
                if ($data['startdate'] < $ex->enddate && $data['enddate'] > $ex->startdate) {
                    $errors['coursename'] = get_string('error_duplicate_record', 'local_coursehistory');
                    break;
                }
            }
        }

        return $errors;
    }
}

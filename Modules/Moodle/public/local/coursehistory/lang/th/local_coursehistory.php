<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_coursehistory
// @copyright  2026
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$string['pluginname']       = 'ประวัติหลักสูตร';
$string['coursehistory']     = 'ประวัติหลักสูตร';

// Form fields
$string['courseinfo']        = 'ข้อมูลหลักสูตร';
$string['idnumber']          = 'รหัสหลักสูตร';
$string['idnumber_help']     = 'ป้อนรหัสมาตรฐานของหลักสูตร (ถ้ามี)';
$string['coursename']       = 'ชื่อหลักสูตร';
$string['coursename_help']  = 'ป้อนชื่อเต็มของหลักสูตรที่คุณได้เรียนจบมาจากภายนอก.';
$string['startdate']       = 'วันที่เริ่มต้น';
$string['enddate']         = 'วันที่สิ้นสุด';
$string['occurrence']      = 'รุ่นที่ / ครั้งที่';
$string['occurrence_help'] = 'ระบุรุ่นที่เรียนหรือครั้งที่จัดการอบรม (เช่น รุ่นที่ 1/2567)';
$string['coursetype']      = 'ประเภทหลักสูตร';
$string['coursetype_mandatory'] = 'หลักสูตรบังคับ (Mandatory)';
$string['coursetype_plan']      = 'หลักสูตรตามแผน (Yearly Plan)';
$string['coursetype_elective']  = 'หลักสูตรเลือกเสรี (Elective)';
$string['instructorname']   = 'ชื่อวิทยากร';
$string['organization']     = 'สถาบัน / หน่วยงาน / องค์กร';
$string['certificatefile']  = 'ไฟล์ใบรับรอง';
$string['uploadcertificate']     = 'อัปโหลดใบรับรอง';
$string['uploadcertificate_help'] = 'อัปโหลดใบรับรองหรือหลักฐานการผ่านหลักสูตร รูปแบบที่รับ: PDF, JPG, PNG (ไม่เกิน 5MB)';

// Actions
$string['submitcourse']     = 'เพิ่มหลักสูตรที่เคยเรียน';
$string['submitcourse_desc'] = 'กรอกข้อมูลหลักสูตรที่คุณเคยเรียนจบมาแล้ว กรุณากรอกข้อมูลให้ครบถ้วนและอัปโหลดใบรับรอง';
$string['approve']          = 'อนุมัติ';
$string['reject']           = 'ปฏิเสธ';
$string['view']             = 'ดูรายละเอียด';

// Status
$string['status']           = 'สถานะ';
$string['status_pending']   = 'รอตรวจสอบ';
$string['status_approved']  = 'อนุมัติแล้ว';
$string['status_rejected']  = 'ไม่อนุมัติ';

// Messages
$string['submitsuccess']    = 'ส่งข้อมูลหลักสูตรสำเร็จ! กรุณารอผู้ดูแลระบบตรวจสอบ';
$string['submitsuccess_autoapproved'] = 'ส่งข้อมูลสำเร็จ! กรุณารอผู้ดูแลระบบตรวจสอบ (มีหลักสูตรที่ตรงกันในระบบ "{$a}")';
$string['submitsuccess_pending']      = 'ส่งข้อมูลสำเร็จ! กรุณารอผู้ดูแลระบบตรวจสอบ';
$string['coursematched']    = 'หลักสูตรนี้ตรงกับหลักสูตรในระบบ: "{$a}" (ต้องรอการตรวจสอบ)';
$string['approved_success'] = 'อนุมัติเรียบร้อยแล้ว';
$string['rejected_success'] = 'ปฏิเสธเรียบร้อยแล้ว';
$string['err_required']     = 'กรุณากรอกข้อมูลในช่องนี้';
$string['err_invalidfile']  = 'ไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์ใบรับรองเท่านั้น (PDF, JPG หรือ PNG)';

// Review
$string['reviewsubmissions']       = 'ตรวจสอบรายการที่ส่งเข้ามา';
$string['reviewedby_label']        = 'ตรวจสอบโดย';
$string['reviewcomment']           = 'ความเห็นผู้ตรวจ';
$string['reviewcomment_placeholder'] = 'เพิ่มความเห็น (ไม่บังคับ)...';
$string['reviewactions']           = 'การดำเนินการ';
$string['backtoreview']            = 'กลับไปหน้าตรวจสอบ';
$string['backtohistory']           = 'กลับไปประวัติหลักสูตร';
$string['gotoreview']              = 'ไปหน้าตรวจสอบ';

// Table & Profile
$string['learner']          = 'ผู้เรียน';
$string['coursematch']      = 'ตรงกับหลักสูตรในระบบ';
$string['matched']          = 'ตรงกัน';
$string['nomatch']          = ' ไม่พบหลักสูตรที่ตรงกันในระบบ';
$string['coursenotfound']   = 'หลักสูตรที่เคยตรงกันถูกลบไปแล้ว';
$string['datesubmitted']    = 'วันที่ส่ง';
$string['actions']          = 'การดำเนินการ';
$string['viewsubmission']   = 'ดูรายละเอียดรายการ';
$string['nosubmissions']    = 'ยังไม่มีรายการหลักสูตรที่ส่งเข้ามา';
$string['nofile']           = 'ไม่มีไฟล์ใบรับรอง';
$string['certificate_preview'] = 'ตัวอย่างใบรับรอง';

// Stats
$string['stat_total']       = 'ทั้งหมด';
$string['stat_approved']    = 'อนุมัติ';
$string['stat_pending']     = 'รอตรวจสอบ';
$string['stat_rejected']    = 'ไม่อนุมัติ';

// Filters
$string['filter_all']       = 'ทั้งหมด';
$string['filter_pending']   = 'รอตรวจสอบ';
$string['filter_approved']  = 'อนุมัติแล้ว';
$string['filter_rejected']  = 'ไม่อนุมัติ';

// Search and Filter
$string['searchfilter']     = 'ค้นหาและกรอง';
$string['searchcourses']    = 'ค้นหาหลักสูตร';
$string['searchplaceholder'] = 'ค้นหาตามชื่อหลักสูตร, วิทยากร หรือองค์กร...';
$string['statusfilter']     = 'กรองตามสถานะ';
$string['allstatus']        = 'ทุกสถานะ';
$string['yearfilter']       = 'กรองตามปี';
$string['allyears']         = 'ทุกปี';
$string['sourcetypefilter'] = 'กรองตามประเภท';
$string['allsources']       = 'ทุกประเภท';
$string['sourcetype']       = 'ประเภทหลักสูตร';
$string['internal_course']  = 'หลักสูตรภายใน';
$string['external_course']  = 'หลักสูตรภายนอก';
$string['system_instructor'] = 'ระบบ';
$string['unknown_organization'] = 'ไม่ทราบ';
$string['auto_approved']      = 'อนุมัติโดยอัตโนมัติ (จากระบบภายใน)';
$string['error_duplicate_record'] = 'คุณเคยบันทึกหลักสูตรนี้ในช่วงเวลาดังกล่าวไปแล้ว โปรดตรวจสอบประวัติของคุณ';
$string['notify_approved_subject'] = 'ประวัติหลักสูตรของคุณได้รับการอนุมัติ';

// Calendar
$string['calendar']         = 'ปฏิทินหลักสูตร';
$string['today']            = 'วันนี้';
$string['thisweek']         = 'สัปดาห์นี้';
$string['thismonth']        = 'เดือนนี้';
$string['thisyear']         = 'ปีนี้';
$string['daterange']        = 'ช่วงวันที่';
$string['fromdate']         = 'จากวันที่';
$string['todate']           = 'ถึงวันที่';
$string['applyfilter']      = 'ใช้ตัวกรอง';
$string['scheduleview']    = 'มุมมองตาราง';

// Capabilities
$string['coursehistory:submit']  = 'ส่งข้อมูลหลักสูตรภายนอก';
$string['coursehistory:review']  = 'ตรวจสอบรายการหลักสูตรที่ส่งเข้ามาทั้งหมด';
$string['coursehistory:reviewteam'] = 'ตรวจสอบรายการหลักสูตรเฉพาะลูกทีมของตนเอง';
$string['coursehistory:viewall'] = 'ดูประวัติหลักสูตรของผู้เรียนทุกคน';

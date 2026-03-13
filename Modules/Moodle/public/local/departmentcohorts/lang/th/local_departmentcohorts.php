<?php

/**
 * Language strings for local_departmentcohorts (Thai)
 */
defined('MOODLE_INTERNAL') || die();

// Plugin info
$string['pluginname'] = 'การจัดการกลุ่มผู้เรียนภายในระบบ';
$string['managecohorts'] = 'จัดการกลุ่มผู้เรียน';
$string['managecohortsdesc'] = 'สร้างและซิงค์ข้อมูลกลุ่มผู้เรียนโดยอ้างอิงจากสายงานหรือบริษัทของผู้ใช้ในระบบ';

// Filter & Search
$string['searchcohort'] = 'ค้นหากลุ่ม';
$string['filter'] = 'กรอง';
$string['bydepartment'] = 'ตามสายงาน (Department)';
$string['byinstitution'] = 'ตามบริษัท (Company)';
$string['bycustom'] = 'กำหนดเอง (รายชื่อทั้งหมด)';
$string['byupload'] = 'อัปโหลดไฟล์ (CSV)';
$string['bymanaged'] = 'กลุ่มที่สร้างแล้ว';

// Table headers
$string['department'] = 'สายงาน';
$string['institution'] = 'บริษัท';
$string['cohortname'] = 'ชื่อกลุ่มผู้เรียน';
$string['users'] = 'ผู้ใช้';
$string['actions'] = 'การดำเนินการ';
$string['firstname'] = 'ชื่อ';
$string['lastname'] = 'นามสกุล';
$string['email'] = 'อีเมล';

// Status badges
$string['status_synced'] = 'เชื่อมโยงแล้ว';
$string['status_unlinked'] = 'ไม่ได้เชื่อมโยง';

// Actions
$string['view'] = 'ดูรายชื่อ';
$string['delete'] = 'ลบ';
$string['syncmembers'] = 'ซิงค์รายชื่อผู้ใช้';

// Custom cohort
$string['customcohortname'] = 'ตั้งชื่อกลุ่มใหม่';
$string['createcustomcohort'] = 'สร้างกลุ่มและเพิ่มสมาชิก';

// CSV Upload
$string['uploadfile'] = 'อัปโหลดไฟล์ CSV';
$string['downloadtemplate'] = 'ดาวน์โหลดไฟล์ตัวอย่าง';
$string['selectfile'] = 'เลือกไฟล์ CSV';
$string['uploadprocessing'] = 'ประมวลผลและสร้างกลุ่ม';
$string['uploadhelp'] = 'อัปโหลดไฟล์ CSV ที่มีคอลัมน์: email, cohortname แต่ละแถวจะเพิ่มผู้ใช้ 1 คนเข้ากลุ่ม 1 กลุ่ม';

// Modal
$string['userspreview'] = 'รายชื่อผู้ใช้';

// Notifications (used in PHP)
$string['memberssynced'] = 'ซิงค์รายชื่อผู้ใช้เรียบร้อยแล้ว';
$string['uploadsuccess'] = 'อัปโหลดสำเร็จเรียบร้อยแล้ว';
$string['cohortdeleted'] = 'ลบกลุ่มผู้เรียนเรียบร้อยแล้ว';
$string['nocohortsfound'] = 'ไม่พบกลุ่มผู้เรียน';
$string['nousersfound'] = 'ไม่พบผู้ใช้';
$string['syncall'] = 'ซิงค์ทั้งหมด';
$string['syncallconfirm'] = 'คุณต้องการซิงค์ทุกกลุ่มใช่หรือไม่?';
$string['syncallcomplete'] = 'ซิงค์ทุกกลุ่มเรียบร้อยแล้ว';

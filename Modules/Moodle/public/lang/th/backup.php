<?php
// This file is part of Moodle - http://moodle.org/
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
 * Strings for component 'core_backup', language 'en'.
 *
 * @package   core
 * @copyright 2010 Eloy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['asyncbackupcomplete'] = 'กระบวนการสำรองข้อมูลเสร็จสมบูรณ์';
$string['asyncbackupcompletebutton'] = 'ดำเนินการต่อ';
$string['asyncbackupcompletedetail'] = 'กระบวนการสำรองข้อมูลเสร็จสมบูรณ์แล้ว <br/> คุณสามารถเข้าถึงข้อมูลสำรองได้ที่ <a href="{$a}">หน้าการคืนค่า</a>';
$string['asyncbackuperror'] = 'กระบวนการสำรองข้อมูลล้มเหลว';
$string['asyncbackuperrordetail'] = 'กระบวนการสำรองข้อมูลล้มเหลว โปรดติดต่อผู้ดูแลระบบของคุณ';
$string['asyncbackuppending'] = 'กระบวนการสำรองข้อมูลกำลังรอการดำเนินการ';
$string['asyncbackupprocessing'] = 'กำลังดำเนินการสำรองข้อมูล';
$string['asyncbadexecution'] = 'การดำเนินการตัวควบคุมการสำรองข้อมูลไม่ถูกต้อง ค่าคือ {$a} แต่ควรเป็น 2';
$string['asynccheckprogress'] = 'คุณสามารถตรวจสอบความคืบหน้าได้ทุกเมื่อที่ <a href="{$a}">หน้าการคืนค่า</a>';
$string['asyncgeneralsettings'] = 'การสำรอง/คืนค่าแบบไม่พร้อมกัน (Asynchronous)';
$string['asyncemailenable'] = 'เปิดใช้งานการแจ้งเตือน';
$string['asyncemailenabledetail'] = 'หากเปิดใช้งาน ผู้ใช้จะได้รับการแจ้งเตือนเมื่อการสำรองหรือคืนค่าแบบไม่พร้อมกันเสร็จสิ้น';
$string['asyncmessagebody'] = 'การแจ้งเตือน';
$string['asyncmessagebodydefault'] = '{operation} (ID: {backupid}) เสร็จสมบูรณ์แล้ว เข้าถึงได้ที่นี่: <a href="{link}">{link}</a>';
$string['asyncmessagebodydetail'] = 'การแจ้งเตือนที่จะส่งเมื่อการสำรองหรือคืนค่าแบบไม่พร้อมกันเสร็จสิ้น';
$string['asyncmessagesubject'] = 'หัวข้อ';
$string['asyncmessagesubjectdetail'] = 'หัวข้อการแจ้งเตือน';
$string['asyncmessagesubjectdefault'] = 'Moodle {operation} เสร็จสมบูรณ์แล้ว';
$string['asyncnowait'] = 'คุณไม่จำเป็นต้องรอที่นี่ เนื่องจากกระบวนการจะดำเนินต่อไปในพื้นหลัง';
$string['asyncprocesspending'] = 'กระบวนการกำลังรอการดำเนินการ';
$string['asyncrestorecomplete'] = 'กระบวนการคืนค่าเสร็จสมบูรณ์';
$string['asyncrestorecompletebutton'] = 'ดำเนินการต่อ';
$string['asyncrestorecompletedetail'] = 'กระบวนการคืนค่าเสร็จสมบูรณ์แล้ว การคลิกดำเนินการต่อจะนำคุณไปสู่ <a href="{$a}">รายวิชาสำหรับรายการที่คืนค่า</a>';
$string['asyncrestoreerror'] = 'กระบวนการคืนค่าล้มเหลว';
$string['asyncrestoreerrordetail'] = 'กระบวนการคืนค่าล้มเหลว โปรดติดต่อผู้ดูแลระบบของคุณ';
$string['asyncrestorepending'] = 'กระบวนการคืนค่ากำลังรอการดำเนินการ';
$string['asyncrestoreprocessing'] = 'กำลังดำเนินการคืนค่า';
$string['asyncreturn'] = 'กลับไปยังรายวิชา';
$string['asyncrestoreinprogress'] = 'การคืนค่าที่กำลังดำเนินการ';
$string['asyncrestoreinprogress_help'] = 'การคืนค่ารายวิชาแบบไม่พร้อมกันที่กำลังดำเนินการจะแสดงที่นี่';
$string['autoactivedisabled'] = 'ปิดใช้งาน';
$string['autoactiveenabled'] = 'เปิดใช้งาน';
$string['autoactivemanual'] = 'ด้วยตนเอง';
$string['autoactivedescription'] = 'เลือกว่าจะทำการสำรองข้อมูลอัตโนมัติหรือไม่ หากเลือกแบบ "ด้วยตนเอง" การสำรองข้อมูลอัตโนมัติจะทำได้ผ่านสคริปต์ CLI เท่านั้น ซึ่งสามารถทำได้ทั้งการพิมพ์คำสั่งเองหรือผ่าน cron';
$string['automatedbackupschedule'] = 'กำหนดเวลา';
$string['automatedbackupschedulehelp'] = 'เลือกวันในสัปดาห์ที่จะทำการสำรองข้อมูลอัตโนมัติ';
$string['automatedbackupsinactive'] = 'ผู้ดูแลระบบยังไม่ได้เปิดใช้งานการสำรองข้อมูลอัตโนมัติ';
$string['automatedbackupstatus'] = 'สถานะการสำรองข้อมูลอัตโนมัติ';
$string['automateddeletedays'] = 'ลบข้อมูลสำรองที่เก่ากว่า';
$string['automatedmaxkept'] = 'จำนวนข้อมูลสำรองสูงสุดที่เก็บไว้';
$string['automatedmaxkepthelp'] = 'ระบุจำนวนสูงสุดของข้อมูลสำรองอัตโนมัติล่าสุดที่จะเก็บไว้ในแต่ละรายวิชา ข้อมูลสำรองที่เก่ากว่าจะถูกลบโดยอัตโนมัติ';
$string['automatedminkept'] = 'จำนวนข้อมูลสำรองขั้นต่ำที่เก็บไว้';
$string['automatedminkepthelp'] = 'หากข้อมูลสำรองที่เก่ากว่าจำนวนวันที่กำหนดถูกลบไป อาจทำให้รายวิชาที่ไม่มีความเคลื่อนไหวไม่มีข้อมูลสำรองเหลืออยู่เลย เพื่อป้องกันกรณีนี้ ควรระบุจำนวนขั้นต่ำของข้อมูลสำรองที่จะเก็บไว้';
$string['automatedsetup'] = 'ตั้งค่าการสำรองข้อมูลอัตโนมัติ';
$string['automatedsettings'] = 'การตั้งค่าการสำรองข้อมูลอัตโนมัติ';
$string['automatedstorage'] = 'ที่เก็บข้อมูลสำรองอัตโนมัติ';
$string['automatedstoragehelp'] = 'เลือกตำแหน่งที่คุณต้องการเก็บข้อมูลสำรองเมื่อถูกสร้างขึ้นโดยอัตโนมัติ';
$string['backupactivity'] = 'กิจกรรมการสำรองข้อมูล: {$a}';
$string['backupautoactivitiesdescription'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมกิจกรรมในการสำรองข้อมูล เพื่อให้ถังขยะ (recycle bin) ทำงานได้ ต้องเปิดใช้งานการตั้งค่านี้';
$string['backupcoursedetails'] = 'รายละเอียดรายวิชา';
$string['backupcoursesection'] = 'ส่วน: {$a}';
$string['backupcoursesections'] = 'ส่วนของรายวิชา';
$string['backupdate'] = 'วันที่ทำ';
$string['backupdetails'] = 'รายละเอียดการสำรองข้อมูล';
$string['backupdetailsnonstandardinfo'] = 'ไฟล์ที่เลือกไม่ใช่ไฟล์สำรองข้อมูลมาตรฐานของ Moodle กระบวนการคืนค่าจะพยายามแปลงไฟล์สำรองข้อมูลให้เป็นรูปแบบมาตรฐานแล้วจึงทำการคืนค่า';
$string['backupfile'] = 'ไฟล์สำรองข้อมูล';
$string['backupformat'] = 'รูปแบบ';
$string['backupformatmoodle1'] = 'Moodle 1';
$string['backupformatmoodle2'] = 'Moodle 2';
$string['backupformatimscc1'] = 'IMS Common Cartridge 1.0';
$string['backupformatimscc11'] = 'IMS Common Cartridge 1.1';
$string['backupformatunknown'] = 'ไม่ทราบรูปแบบ';
$string['backuplog'] = 'ข้อมูลทางเทคนิคและคำเตือน';
$string['backupmode'] = 'โหมด';
$string['backupmode10'] = 'ทั่วไป';
$string['backupmode20'] = 'นำเข้า';
$string['backupmode30'] = 'ฮับ (Hub)';
$string['backupmode40'] = 'ไซต์เดียวกัน';
$string['backupmode50'] = 'อัตโนมัติ';
$string['backupmode60'] = 'ที่แปลงแล้ว';
$string['backupmode70'] = 'แบบไม่พร้อมกัน';
$string['backupsection'] = 'สำรองข้อมูลส่วนของรายวิชา: {$a}';
$string['backupsettings'] = 'การตั้งค่าการสำรองข้อมูล';
$string['backupsitedetails'] = 'รายละเอียดไซต์';
$string['backupstage1action'] = 'ถัดไป';
$string['backupstage2action'] = 'ถัดไป';
$string['backupstage4action'] = 'ดำเนินการสำรองข้อมูล';
$string['backupstage8action'] = 'ดำเนินการต่อ';
$string['backupstage16action'] = 'ดำเนินการต่อ';
$string['backupthenrestore'] = 'การสำรองข้อมูลเสร็จสิ้น เริ่มการคืนค่า';
$string['backuptype'] = 'ประเภท';
$string['backuptypeactivity'] = 'กิจกรรม';
$string['backuptypecourse'] = 'รายวิชา';
$string['backuptypesection'] = 'ส่วน';
$string['backupversion'] = 'เวอร์ชันของการสำรองข้อมูล';
$string['cannotfindassignablerole'] = 'บทบาท {$a} ในไฟล์สำรองข้อมูลไม่สามารถจับคู่กับบทบาทใดๆ ที่คุณได้รับอนุญาตให้กำหนดได้';
$string['choosefilefromcoursebackup'] = 'พื้นที่เก็บข้อมูลสำรองของรายวิชา';
$string['choosefilefromcoursebackup_help'] = 'ไฟล์สำรองข้อมูลสำหรับรายวิชานี้';
$string['choosefilefromuserbackup'] = 'พื้นที่เก็บข้อมูลสำรองส่วนตัวของผู้ใช้';
$string['choosefilefromuserbackup_help'] = 'ไฟล์สำรองข้อมูลส่วนตัวสำหรับทุกรายวิชา โดยข้อมูลผู้ใช้จะไม่ระบุตัวตน';
$string['choosefilefromactivitybackup'] = 'พื้นที่เก็บข้อมูลสำรองของกิจกรรม';
$string['choosefilefromactivitybackup_help'] = 'ไฟล์สำรองข้อมูลสำหรับกิจกรรมนี้';
$string['choosefilefromautomatedbackup'] = 'การสำรองข้อมูลอัตโนมัติ';
$string['choosefilefromautomatedbackup_help'] = 'ไฟล์สำรองข้อมูลที่สร้างขึ้นโดยอัตโนมัติ';
$string['config_keep_groups_and_groupings'] = 'ค่าเริ่มต้นจะเก็บกลุ่มและการจัดกลุ่มปัจจุบันไว้';
$string['config_keep_roles_and_enrolments'] = 'ค่าเริ่มต้นจะเก็บบทบาทและการลงทะเบียนปัจจุบันไว้';
$string['config_overwrite_conf'] = 'อนุญาตให้ผู้ใช้เขียนทับการกำหนดค่ารายวิชาปัจจุบัน';
$string['config_overwrite_course_fullname'] = 'ค่าเริ่มต้นจะเขียนทับชื่อเต็มของรายวิชาด้วยชื่อจากไฟล์สำรองข้อมูล ซึ่งต้องเลือก "เขียนทับการกำหนดค่ารายวิชา" และผู้ใช้ปัจจุบันต้องมีสิทธิ์ในการเปลี่ยนชื่อเต็มของรายวิชา (moodle/course:changefullname)';
$string['config_overwrite_course_shortname'] = 'ค่าเริ่มต้นจะเขียนทับชื่อย่อของรายวิชาด้วยชื่อจากไฟล์สำรองข้อมูล ซึ่งต้องเลือก "เขียนทับการกำหนดค่ารายวิชา" และผู้ใช้ปัจจุบันต้องมีสิทธิ์ในการเปลี่ยนชื่อย่อของรายวิชา (moodle/course:changeshortname)';
$string['config_overwrite_course_startdate'] = 'ค่าเริ่มต้นจะเขียนทับวันที่เริ่มรายวิชาด้วยวันที่จากไฟล์สำรองข้อมูล ซึ่งต้องเลือก "เขียนทับการกำหนดค่ารายวิชา" และผู้ใช้ปัจจุบันต้องมีสิทธิ์ในเลื่อนวันที่รายวิชาเมื่อคืนค่า (moodle/restore:rolldates)';
$string['configgeneralactivities'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมกิจกรรมในการสำรองข้อมูล';
$string['configgeneralbadges'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมเหรียญตรา (badges) ในการสำรองข้อมูล';
$string['configgeneralanonymize'] = 'หากเปิดใช้งาน ข้อมูลทั้งหมดที่เกี่ยวกับผู้ใช้จะไม่ระบุตัวตนโดยค่าเริ่มต้น';
$string['configgeneralblocks'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมบล็อกในการสำรองข้อมูล';
$string['configgeneralcalendarevents'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมกิจกรรมในปฏิทินในการสำรองข้อมูล';
$string['configgeneralcomments'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมความคิดเห็นในการสำรองข้อมูล';
$string['configgeneralcompetencies'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมสมรรถนะ (competencies) ในการสำรองข้อมูล';
$string['configgeneralcontentbankcontent'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมเนื้อหาใน Content bank ในการสำรองข้อมูล';
$string['configgeneralcustomfield'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมฟิลด์ที่กำหนดเองในการสำรองข้อมูล';
$string['configgeneralfiles'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมไฟล์ในการสำรองข้อมูล หมายเหตุ: หากปิดการตั้งค่านี้ ข้อมูลสำรองจะมีเพียงการอ้างอิงถึงไฟล์เท่านั้น ซึ่งจะไม่มีปัญหาหากคืนค่าในไซต์เดิมและไฟล์ยังไม่ถูกลบตามการตั้งค่า \'ล้างไฟล์ในถังขยะ\' (filescleanupperiod)';
$string['configgeneralfilters'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมตัวกรองในการสำรองข้อมูล';
$string['configgeneralhistories'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมประวัติผู้ใช้ในการสำรองข้อมูล';
$string['configgenerallogs'] = 'หากเปิดใช้งาน บันทึกกิจกรรม (logs) จะถูกรวมในข้อมูลสำรองโดยค่าเริ่มต้น';
$string['configgeneralgroups'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมกลุ่มและการจัดกลุ่มในการสำรองข้อมูล';
$string['configgeneralroleassignments'] = 'หากเปิดใช้งาน การกำหนดบทบาทจะถูกสำรองไว้โดยค่าเริ่มต้น';
$string['configgeneralpermissions'] = 'หากเปิดใช้งาน สิทธิ์ของบทบาทจะถูกนำเข้า ซึ่งอาจเขียนทับสิทธิ์ที่มีอยู่สำหรับผู้ใช้ที่ลงทะเบียนแล้ว';
$string['configgeneraluserscompletion'] = 'หากเปิดใช้งาน ข้อมูลการเรียนจบของผู้ใช้จะถูกรวมในข้อมูลสำรองโดยค่าเริ่มต้น';
$string['configgeneralusers'] = 'ตั้งค่าเริ่มต้นว่าจะรวมผู้ใช้ในข้อมูลสำรองหรือไม่';
$string['configgeneralxapistate'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมสถานะของผู้ใช้ในเนื้อหา เช่น กิจกรรม H5P ในการสำรองข้อมูล';
$string['configlegacyfiles'] = 'ตั้งค่าเริ่มต้นสำหรับการรวมไฟล์รายวิชาแบบเก่า (legacy) ในการสำรองข้อมูลไฟล์เก่าคือไฟล์จาก Moodle เวอร์ชันก่อน 2.0';
$string['configloglifetime'] = 'ระบุระยะเวลาที่คุณต้องการเก็บข้อมูลบันทึกการสำรองข้อมูล บันทึกที่เก่ากว่านี้จะถูกลบโดยอัตโนมัติ แนะนำให้ตั้งค่านี้เป็นค่าที่น้อย เนื่องจากข้อมูลบันทึกการสำรองข้อมูลสามารถมีขนาดใหญ่มาก';
$string['configrestoreactivities'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่ากิจกรรม';
$string['configrestorebadges'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าเหรียญตรา';
$string['configrestoreblocks'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าบล็อก';
$string['configrestorecalendarevents'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่ากิจกรรมในปฏิทิน';
$string['configrestorecomments'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าความคิดเห็น';
$string['configrestorecompetencies'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าสมรรถนะ';
$string['configrestorecontentbankcontent'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าเนื้อหาใน Content bank';
$string['configrestorecustomfield'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าฟิลด์ที่กำหนดเอง';
$string['configrestoreenrolments'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าวิธีการลงทะเบียน';
$string['configrestorefilters'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าตัวกรอง';
$string['configrestorehistories'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าประวัติผู้ใช้หากรวมอยู่ในข้อมูลสำรอง';
$string['configrestorelogs'] = 'หากเปิดใช้งาน บันทึกกิจกรรมจะถูกคืนค่าโดยค่าเริ่มต้นหากรวมอยู่ในข้อมูลสำรอง';
$string['configrestoregroups'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่ากลุ่มและการจัดกลุ่มหากรวมอยู่ในข้อมูลสำรอง';
$string['configrestoreroleassignments'] = 'หากเปิดใช้งาน การกำหนดบทบาทจะถูกคืนค่าโดยค่าเริ่มต้นหากรวมอยู่ในข้อมูลสำรอง';
$string['configrestorepermissions'] = 'หากเปิดใช้งาน สิทธิ์ของบทบาทจะถูกคืนค่า ซึ่งอาจเขียนทับสิทธิ์ที่มีอยู่สำหรับผู้ใช้ที่ลงทะเบียนแล้ว';
$string['configrestoreuserscompletion'] = 'หากเปิดใช้งาน ข้อมูลการเรียนจบของผู้ใช้จะถูกคืนค่าโดยค่าเริ่มต้นหากรวมอยู่ในข้อมูลสำรอง';
$string['configrestoreusers'] = 'ตั้งค่าเริ่มต้นว่าจะคืนค่าผู้ใช้หรือไม่หากรวมอยู่ในข้อมูลสำรอง';
$string['configrestorexapistate'] = 'ตั้งค่าเริ่มต้นสำหรับการคืนค่าสถานะของผู้ใช้ในเนื้อหา เช่น กิจกรรม H5P';
$string['confirmcancel'] = 'ยกเลิกการสำรองข้อมูล';
$string['confirmcancelrestore'] = 'ยกเลิกการคืนค่า';
$string['confirmcancelimport'] = 'ยกเลิกการนำเข้า';
$string['confirmcancelquestion'] = 'คุณแน่ใจหรือไม่ว่าต้องการยกเลิก? ข้อมูลที่คุณป้อนไปแล้วจะสูญหาย';
$string['confirmcancelyes'] = 'ยกเลิกการสำรองข้อมูล';
$string['confirmcancelno'] = 'ไม่ยกเลิก';
$string['confirmnewcoursecontinue'] = 'คำเตือนรายวิชาใหม่';
$string['confirmnewcoursecontinuequestion'] = 'รายวิชาชั่วคราว (ที่ถูกซ่อน) จะถูกสร้างขึ้นโดยกระบวนการคืนค่ารายวิชา หากต้องการยกเลิกการคืนค่าให้คลิกยกเลิก อย่าปิดเบราว์เซอร์ขณะกำลังทำการคืนค่า';
$string['copiesinprogress'] = 'รายวิชานี้มีการคัดลอกที่กำลังดำเนินการอยู่ <a href="{$a}">ดูการคัดลอกที่กำลังดำเนินการ</a>';
$string['copycoursedesc'] = 'สร้างสำเนาของรายวิชานี้ในหมวดหมู่รายวิชาใดก็ได้';
$string['copycoursetitle'] = 'คัดลอกรายวิชา: {$a}';
$string['copydest'] = 'ปลายทาง';
$string['copyingcourse'] = 'กำลังคัดลอกรายวิชา';
$string['copyingcourseshortname'] = 'กำลังคัดลอก';
$string['copyfieldnotfound'] = 'ไม่พบข้อมูลฟิลด์ที่จำเป็นสำหรับฟิลด์ต่อไปนี้: {$a}';
$string['copyformfail'] = 'การส่งแบบฟอร์มคัดลอกรายวิชาผ่าน AJAX ล้มเหลว';
$string['copyop'] = 'การดำเนินการปัจจุบัน';
$string['copyprogressheading'] = 'การคัดลอกรายวิชาที่กำลังดำเนินการ';
$string['copyprogressheading_help'] = 'ตารางนี้แสดงสถานะของการคัดลอกรายวิชาทั้งหมดของคุณที่ยังไม่เสร็จสิ้น';
$string['copyprogresstitle'] = 'ความคืบหน้าการคัดลอกรายวิชา';
$string['copyreturn'] = 'คัดลอกและกลับไป';
$string['copysource'] = 'แหล่งที่มา';
$string['copyview'] = 'คัดลอกและดู';
$string['coursecategory'] = 'หมวดหมู่ที่รายวิชาจะถูกคืนค่าเข้าไป';
$string['courseid'] = 'ID เดิม';
$string['coursesettings'] = 'การตั้งค่ารายวิชา';
$string['coursetitle'] = 'ชื่อเรื่อง';
$string['currentstage1'] = 'การตั้งค่าเริ่มต้น';
$string['currentstage2'] = 'การตั้งค่าสคีมา (Schema)';
$string['currentstage4'] = 'การยืนยันและการตรวจสอบ';
$string['currentstage8'] = 'ดำเนินการสำรองข้อมูล';
$string['currentstage16'] = 'เสร็จสมบูรณ์';
$string['defaultbackupfilenameactivity'] = 'ชื่อไฟล์เริ่มต้นของการสำรองข้อมูลกิจกรรม';
$string['defaultbackupfilenameactivity_desc'] = 'ข้อมูลเพิ่มเติมสำหรับการสำรองกิจกรรม:
<ul>
<li><code>activity.name</code> - ข้อความ - ชื่อกิจกรรม</li>
<li><code>activity.modname</code> - ตัวเลข - ชื่อโมดูล</li>
</ul>
';
$string['defaultbackupfilenamecourse'] = 'ชื่อไฟล์เริ่มต้นของการสำรองข้อมูลรายวิชา';
$string['defaultbackupfilenamecourse_desc'] = 'ข้อมูลเพิ่มเติมสำหรับการสำรองรายวิชา:
<ul>
<li><code>course.shortname</code> - ข้อความ - ชื่อย่อรายวิชา</li>
<li><code>course.fullname</code> - ข้อความ - ชื่อเต็มรายวิชา</li>
<li><code>course.startdate</code> - ข้อความ - วันที่เริ่มรายวิชาในรูปแบบที่ระบุโดยภาษา <code>backupnameformat</code></li>
<li><code>course.endddate</code> - ข้อความ - วันที่สิ้นสุดรายวิชาในรูปแบบที่ระบุโดยภาษา <code>backupnameformat</code></li>
</ul>
';
$string['defaultbackupfilenamesection'] = 'ชื่อไฟล์เริ่มต้นของการสำรองข้อมูลส่วน';
$string['defaultbackupfilenamesection_desc'] = 'ข้อมูลเพิ่มเติมสำหรับการสำรองส่วน:
<ul>
<li><code>section.name</code> - ข้อความ - ชื่อส่วน</li>
<li><code>section.section</code> - ตัวเลข - หมายเลขส่วน</li>
</ul>
';
$string['defaultbackupfilenamesettings'] = 'ชื่อไฟล์เริ่มต้นของการสำรองข้อมูล';
$string['defaultbackupfilenamesettings_help'] = 'เทมเพลต Mustache ที่ใช้ประเมินเพื่อให้ชื่อไฟล์เริ่มต้นสำหรับการสำรองข้อมูล
ทุกเทมเพลตการสำรองข้อมูลมีข้อมูลต่อไปนี้ให้ใช้งาน:
<ul>
<li><code>format</code> - ข้อความ - รูปแบบการสำรองข้อมูล ปกติจะเป็น moodle2</li>
<li><code>type</code> - ข้อความ - หนึ่งใน course (รายวิชา), section (ส่วน) หรือ activity (กิจกรรม)</li>
<li><code>id</code> - ตัวเลข - ID ของรายการในฐานข้อมูล</li>
<li><code>useidonly</code> - บูลีน - หากสร้างการสำรองข้อมูลโดยไม่ได้เปิดใช้งาน <code>backup_shortname</code></li>
<li><code>date</code> - ข้อความ - วันที่ในรูปแบบที่ระบุโดยภาษา <code>backupnameformat</code></li>
<li><code>users</code> - บูลีน - หากรวมข้อมูลผู้ใช้</li>
<li><code>anonymised</code> - บูลีน - หากข้อมูลผู้ใช้ไม่ระบุตัวตน</li>
<li><code>files</code> - บูลีน - หากรวมไฟล์</li>
</ul>
นอกเหนือจากค่าพื้นที่สำรองที่ระบุไว้นี้แล้ว คุณยังสามารถใช้ {{#str}} เพื่อเพิ่มภาษาได้ด้วย นามสกุล .mbz จะถูกเพิ่มเสมอ ชื่อไฟล์จะถูกตัดให้เหลือ 251 ตัวอักษร';
$string['enableasyncbackup'] = 'เปิดใช้งานการสำรองข้อมูลแบบไม่พร้อมกัน';
$string['enableasyncbackup_help'] = 'หากเปิดใช้งาน การสำรองข้อมูลและการคืนค่าจะทำแบบไม่พร้อมกัน ซึ่งจะช่วยให้ผู้ใช้ได้รับประสบการณ์ที่ดียิ่งขึ้นโดยอนุญาตให้ทำกิจกรรมอื่นๆ ได้ในขณะที่กำลังสำรองข้อมูลหรือคืนค่าอยู่ การตั้งค่านี้ไม่มีผลต่อการนำเข้าและส่งออก';
$string['enterasearch'] = 'ป้อนการค้นหา';
$string['error_block_for_module_not_found'] = 'พบบล็อกที่ไม่มีที่ไป (id: {$a->bid}) สำหรับโมดูลรายวิชา (id: {$a->mid}) บล็อกนี้จะไม่ถูกสำรองข้อมูล';
$string['error_course_module_not_found'] = 'พบโมดูลรายวิชาที่ไม่มีที่ไป (id: {$a}) โมดูลนี้จะไม่ถูกสำรองข้อมูล';
$string['error_delegate_section_not_found'] = 'ส่วนที่ได้รับมอบหมายหายไปจากโมดูลรายวิชา (ID {$a}) ส่วนนี้จะไม่ถูกสำรองข้อมูล';
$string['errorcopyingbackupfile'] = "คัดลอกไฟล์สำรองข้อมูลไปยังโฟลเดอร์ชั่วคราวก่อนเริ่มการคืนค่าล้มเหลว";
$string['errorfilenamerequired'] = 'คุณต้องป้อนชื่อไฟล์ที่ถูกต้องสำหรับการสำรองข้อมูลนี้';
$string['errorfilenametoolong'] = 'ชื่อไฟล์ต้องมีความยาวน้อยกว่า 255 ตัวอักษร';
$string['errorfilenamemustbezip'] = 'ชื่อไฟล์ที่คุณป้อนต้องเป็นไฟล์ ZIP และมีนามสกุล .mbz';
$string['errorminbackup20version'] = 'ไฟล์สำรองข้อมูลนี้สร้างขึ้นด้วย Moodle เวอร์ชันพัฒนา ({$a->backup}) เวอร์ชันขั้นต่ำที่ต้องการคือ {$a->min} ไม่สามารถคืนค่าได้';
$string['errorinvalidformat'] = 'ไม่ทราบรูปแบบการสำรองข้อมูล';
$string['errorinvalidformatinfo'] = 'ไฟล์ที่เลือกไม่ใช่ไฟล์สำรองของ Moodle ที่ถูกต้อง และไม่สามารถคืนค่าได้';
$string['errorrestorefrontpagebackup'] = 'คุณสามารถคืนค่าข้อมูลสำรองหน้าแรกของไซต์ได้ที่หน้าแรกของไซต์เท่านั้น';
$string['executionsuccess'] = 'สร้างไฟล์สำรองข้อมูลเสร็จสมบูรณ์';
$string['extractingbackupfileto'] = 'กำลังแยกไฟล์สำรองข้อมูลไปยัง: {$a}';
$string['failed'] = 'การสำรองข้อมูลล้มเหลว';
$string['filename'] = 'ชื่อไฟล์';
$string['filealiasesrestorefailures'] = 'การคืนค่า Alias ล้มเหลว';
$string['filealiasesrestorefailuresinfo'] = 'บาง alias ที่รวมอยู่ในไฟล์สำรองข้อมูลไม่สามารถคืนค่าได้ รายการต่อไปนี้ประกอบด้วยตำแหน่งที่คาดไว้และไฟล์ต้นทางที่อ้างอิงถึงในไซต์เดิม';
$string['filealiasesrestorefailures_help'] = 'Aliases คือลิงก์สัญลักษณ์ไปยังไฟล์อื่นๆ รวมถึงไฟล์ที่เก็บไว้ในที่เก็บข้อมูลภายนอก ในบางกรณี Moodle ไม่สามารถคืนค่าได้ เช่น เมื่อคืนค่าข้อมูลสำรองในไซต์อื่น หรือเมื่อไม่มีไฟล์ที่อ้างอิงถึง

รายละเอียดเพิ่มเติมและสาเหตุที่แท้จริงของความล้มเหลวสามารถพบได้ในไฟล์บันทึกการคืนค่า';
$string['filealiasesrestorefailures_link'] = 'restore/filealiases';
$string['filereferencesincluded'] = 'ไฟล์อ้างอิงไปยังเนื้อหาภายนอกรวมอยู่ในไฟล์สำรอง ข้อมูลเหล่านี้จะไม่ทำงานหากคืนค่าข้อมูลสำรองบนไซต์อื่น';
$string['filereferencessamesite'] = 'ไฟล์สำรองข้อมูลมาจากไซต์นี้ ดังนั้นจึงสามารถคืนค่าไฟล์อ้างอิงได้';
$string['filereferencesnotsamesite'] = 'ไฟล์สำรองข้อมูลมาจากไซต์อื่น ดังนั้นจึงไม่สามารถคืนค่าไฟล์อ้างอิงได้';
$string['generalactivities'] = 'รวมกิจกรรมและแหล่งข้อมูล';
$string['generalanonymize'] = 'ทำให้ข้อมูลเป็นแบบไม่ระบุตัวตน';
$string['generalbackdefaults'] = 'ค่าเริ่มต้นการสำรองข้อมูลทั่วไป';
$string['generalbadges'] = 'รวมเหรียญตรา';
$string['generalblocks'] = 'รวมบล็อก';
$string['generalcalendarevents'] = 'รวมกิจกรรมในปฏิทิน';
$string['generalcomments'] = 'รวมความคิดเห็น';
$string['generalcompetencies'] = 'รวมสมรรถนะ';
$string['generalcontentbankcontent'] = 'รวมเนื้อหาใน Content bank';
$string['generalcustomfield'] = 'รวมฟิลด์ที่กำหนดเอง';
$string['generalenrolments'] = 'รวมวิธีการลงทะเบียน';
$string['generalfiles'] = 'รวมไฟล์';
$string['generalfilters'] = 'รวมตัวกรอง';
$string['generalhistories'] = 'รวมประวัติ';
$string['generalgradehistories'] = 'รวมประวัติ';
$string['generallegacyfiles'] = 'รวมไฟล์รายวิชาแบบเก่า';
$string['generallogs'] = 'รวมบันทึกกิจกรรม';
$string['generalgroups'] = 'รวมกลุ่มและการจัดกลุ่ม';
$string['generalrestoredefaults'] = 'ค่าเริ่มต้นการคืนค่าทั่วไป';
$string['mergerestoredefaults'] = 'คืนค่าพื้นฐานเมื่อรวมเข้ากับรายวิชาอื่น';
$string['replacerestoredefaults'] = 'คืนค่าพื้นฐานเมื่อคืนค่าเข้าสู่รายวิชาอื่นโดยการลบเนื้อหา';
$string['generalrestoresettings'] = 'การตั้งค่าการคืนค่าทั่วไป';
$string['generalroleassignments'] = 'รวมการกำหนดบทบาท';
$string['generalpermissions'] = 'รวมการเขียนทับสิทธิ์';
$string['generalsettings'] = 'การตั้งค่าการสำรองข้อมูลทั่วไป';
$string['generaluserscompletion'] = 'รวมข้อมูลการเรียนจบของผู้ใช้';
$string['generalusers'] = 'รวมผู้ใช้';
$string['generalxapistate'] = 'รวมสถานะของผู้ใช้ในเนื้อหา เช่น กิจกรรม H5P';
$string['hidetypes'] = 'ซ่อนตัวเลือกประเภท';
$string['importgeneralsettings'] = 'ค่าเริ่มต้นการนำเข้าทั่วไป';
$string['importgeneralmaxresults'] = 'จำนวนรายวิชาสูงสุดที่แสดงสำหรับการนำเข้า';
$string['importgeneralmaxresults_desc'] = 'ส่วนนี้ควบคุมจำนวนของรายวิชาที่แสดงในช่วงขั้นตอนแรกของกระบวนการนำเข้า';
$string['importgeneralduplicateadminallowed'] = 'อนุญาตการแก้ปัญหา admin ซ้ำ';
$string['importgeneralduplicateadminallowed_desc'] = 'หากไซต์มีบัญชีที่มีชื่อผู้ใช้ว่า \'admin\' การพยายามคืนค่าไฟล์สำรองที่มีบัญชีชื่อผู้ใช้ว่า \'admin\' อาจทำให้เกิดข้อขัดแย้ง หากเปิดใช้งานการตั้งค่านี้ ข้อขัดแย้งจะได้รับการแก้ไขโดยเปลี่ยนชื่อผู้ใช้ในไฟล์สำรองเป็น \'admin_xyz\'';
$string['importfile'] = 'อัปโหลดไฟล์สำรองข้อมูล';
$string['importbackupstage1action'] = 'ถัดไป';
$string['importbackupstage2action'] = 'ถัดไป';
$string['importbackupstage4action'] = 'ดำเนินการนำเข้า';
$string['importbackupstage8action'] = 'ดำเนินการต่อ';
$string['importbackupstage16action'] = 'ดำเนินการต่อ';
$string['importcurrentstage0'] = 'การเลือกรายวิชา';
$string['importcurrentstage1'] = 'การตั้งค่าเริ่มต้น';
$string['importcurrentstage2'] = 'การตั้งค่าสคีมา';
$string['importcurrentstage4'] = 'การยืนยันและการตรวจสอบ';
$string['importcurrentstage8'] = 'ดำเนินการนำเข้า';
$string['importcurrentstage16'] = 'เสร็จสมบูรณ์';
$string['importfromccmidtocourse'] = 'นำเข้าจาก cmid:{$a->srccmid} เข้าสู่รายวิชา:{$a->dstcourseid}';
$string['importfromcoursetocourse'] = 'นำเข้าจากรายวิชา:{$a->srccourseid} เข้าสู่รายวิชา:{$a->dstcourseid}';
$string['importrootsettings'] = 'การตั้งค่าการนำเข้า';
$string['importsettings'] = 'การตั้งค่าการนำเข้าทั่วไป';
$string['importsuccess'] = 'การนำเข้าเสร็จสมบูรณ์ คลิกดำเนินการต่อเพื่อกลับไปยังรายวิชา';
$string['inprogress'] = 'กำลังดำเนินการสำรองข้อมูล';
$string['includeactivities'] = 'รวม:';
$string['includeditems'] = 'รายการที่รวมอยู่:';
$string['includesection'] = 'ส่วนที่ {$a}';
$string['includeuserinfo'] = 'ข้อมูลผู้ใช้';
$string['includeuserinfo_instance'] = 'รวมข้อมูลผู้ใช้ {$a}';
$string['includefilereferences'] = 'ไฟล์อ้างอิงไปยังเนื้อหาภายนอก';
$string['jumptofinalstep'] = 'ข้ามไปขั้นตอนสุดท้าย';
$string['keep'] = 'เก็บไว้';
$string['locked'] = 'ถูกล็อก';
$string['lockedbypermission'] = 'คุณมีสิทธิ์ไม่เพียงพอในการเปลี่ยนการตั้งค่านี้';
$string['lockedbyconfig'] = 'การตั้งค่านี้ถูกล็อกโดยการตั้งค่าเริ่มต้นของการสำรองข้อมูล';
$string['lockedbyhierarchy'] = 'ถูกล็อกโดยการพึ่งพาเนื้อหา';
$string['loglifetime'] = 'เก็บประวัติบันทึกเป็นเวลา';
$string['managefiles'] = 'จัดการไฟล์สำรองข้อมูล';
$string['managefiles_activity'] = 'จัดการไฟล์สำรองข้อมูลกิจกรรม';
$string['managefiles_course'] = 'จัดการไฟล์สำรองข้อมูลรายวิชา';
$string['managefiles_backup'] = 'จัดการไฟล์สำรองข้อมูลส่วนตัว';
$string['managefiles_automated'] = 'จัดการไฟล์สำรองข้อมูลอัตโนมัติ';
$string['keptroles'] = 'รวมการลงทะเบียนตามบทบาท';
$string['keptroles_help'] = 'ผู้ใช้ที่มีบทบาทที่เลือกจะถูกลงทะเบียนเข้าสู่รายวิชาใหม่ ไม่มีการคัดลอกข้อมูลผู้ใช้เว้นแต่จะเปิดใช้งาน "รวมข้อมูลผู้ใช้"';
$string['missingfilesinpool'] = 'ไม่สามารถบันทึกไฟล์บางไฟล์ได้ในระหว่างการสำรองข้อมูล ดังนั้นจะไม่สามารถคืนค่าไฟล์เหล่านั้นได้';
$string['moodleversion'] = 'เวอร์ชัน Moodle';
$string['moreresults'] = 'มีผลลัพธ์มากเกินไป โปรดป้อนคำค้นหาที่เฉพาะเจาะจงมากขึ้น';
$string['nomatchingcourses'] = 'ไม่มีรายวิชาที่จะแสดง';
$string['norestoreoptions'] = 'ไม่มีหมวดหมู่หรือรายวิชาที่มีอยู่ให้คุณคืนค่าได้';
$string['originalwwwroot'] = 'URL ของข้อมูลสำรอง';
$string['overwrite'] = 'เขียนทับ';
$string['pendingasyncdetail'] = 'การสำรองข้อมูลแบบไม่พร้อมกันอนุญาตให้ผู้ใช้มีข้อมูลสำรองที่รอกระบวนการเพียงรายการเดียวต่อแหล่งข้อมูลในแต่ละครั้ง <br/> ไม่สามารถคิวการสำรองข้อมูลแบบไม่พร้อมกันหลายรายการของแหล่งข้อมูลเดียวกันได้ เนื่องจากอาจส่งผลให้ได้ข้อมูลสำรองที่มีเนื้อหาเดียวกันหลายชุด';
$string['pendingasyncdeletedetail'] = 'รายวิชานี้มีกระบวนการสำรองข้อมูลแบบไม่พร้อมกันที่รอการดำเนินการอยู่ <br/> รายวิชาไม่สามารถลบได้จนกว่าการสำรองข้อมูลนี้จะเสร็จสิ้น';
$string['pendingasyncedit'] = 'มีการขอสำรองข้อมูลหรือคัดลอกรายวิชานี้ที่ยังค้างอยู่ โปรดอย่าแก้ไขรายวิชาจนกว่ากระบวนการนี้จะเสร็จสิ้น';
$string['pendingasyncerror'] = 'ข้อมูลสำรองกำลังรอการดำเนินการสำหรับแหล่งข้อมูลนี้';
$string['previousstage'] = 'ก่อนหน้า';
$string['preparingui'] = 'กำลังเตรียมแสดงหน้าเว็บ';
$string['preparingdata'] = 'กำลังเตรียมข้อมูล';
$string['privacy:metadata:backup:detailsofarchive'] = 'ข้อมูลที่เก็บถาวรนี้อาจมีข้อมูลผู้ใช้ต่างๆ ที่เกี่ยวข้องกับรายวิชา เช่น คะแนน การลงทะเบียน และข้อมูลกิจกรรม';
$string['privacy:metadata:backup:externalpurpose'] = 'วัตถุประสงค์ของข้อมูลที่เก็บถาวรนี้คือเพื่อเก็บข้อมูลที่เกี่ยวข้องกับรายวิชา ซึ่งอาจนำกลับคืนมาใช้งานในอนาคต';
$string['privacy:metadata:backup_controllers'] = 'รายการการดำเนินการสำรองข้อมูล';
$string['privacy:metadata:backup_controllers:itemid'] = 'ID ของรายวิชา';
$string['privacy:metadata:backup_controllers:operation'] = 'การดำเนินการที่ทำ เช่น การคืนค่า';
$string['privacy:metadata:backup_controllers:timecreated'] = 'เวลาที่เริ่มการดำเนินการ';
$string['privacy:metadata:backup_controllers:timemodified'] = 'เวลาที่มีการแก้ไขการดำเนินการ';
$string['privacy:metadata:backup_controllers:type'] = 'ประเภทของรายการที่ถูกดำเนินการ เช่น กิจกรรม';
$string['qcategory2coursefallback'] = 'หมวดหมู่คำถาม "{$a->name}" ซึ่งเดิมอยู่ในบริบท system|course|course_category ในไฟล์สำรอง จะถูกสร้างขึ้นในบริบทโมดูลคลังข้อสอบเมื่อทำการคืนค่า';
$string['qcategorycannotberestored'] = 'ไม่สามารถสร้างหมวดหมู่คำถาม "{$a->name}" ได้จากการคืนค่า';
$string['question2coursefallback'] = 'หมวดหมู่คำถาม "{$a->name}" ซึ่งเดิมอยู่ในบริบท system|course|course_category ในไฟล์สำรอง จะถูกสร้างขึ้นในบริบทโมดูลคลังข้อสอบเมื่อทำการคืนค่า';
$string['questioncannotberestored'] = 'ไม่สามารถสร้างคำถาม "{$a->name}" ได้จากการคืนค่า';
$string['restoreactivity'] = 'คืนค่ากิจกรรม';
$string['restorecourse'] = 'คืนค่ารายวิชา';
$string['restorecoursesettings'] = 'การตั้งค่ารายวิชา';
$string['restoredcourseid'] = 'ID รายวิชาที่คืนค่าแล้ว: {$a}';
$string['restoreexecutionsuccess'] = 'คืนค่ารายวิชาเสร็จสมบูรณ์';
$string['restorefileweremissing'] = 'ไม่สามารถคืนค่าไฟล์บางไฟล์ได้เนื่องจากไฟล์เหล่านั้นหายไปจากข้อมูลสำรอง';
$string['restorenewcoursefullname'] = 'ชื่อรายวิชาใหม่';
$string['restorenewcourseshortname'] = 'ชื่อย่อรายวิชาใหม่';
$string['restorenewcoursestartdate'] = 'วันที่เริ่มต้นใหม่';
$string['restorenofilesbackuparea'] = 'ยังไม่มีไฟล์สำรอง';
$string['restorenofilesbackuparea_activity'] = 'ยังไม่มีไฟล์สำรองสำหรับกิจกรรมนี้';
$string['restorenofilesbackuparea_course'] = 'ยังไม่มีไฟล์สำรองสำหรับรายวิชานี้';
$string['restorenofilesbackuparea_backup'] = 'คุณยังไม่มีไฟล์สำรองส่วนตัว';
$string['restorenofilesbackuparea_automated'] = 'ยังไม่มีไฟล์สำรองอัตโนมัติ';
$string['restorerootsettings'] = 'การตั้งค่าการคืนค่า';
$string['restoresection'] = 'คืนค่าส่วน';
$string['restorestage1'] = 'ยืนยัน';
$string['restorestage1action'] = 'ถัดไป';
$string['restorestage2'] = 'ปลายทาง';
$string['restorestage2action'] = 'ถัดไป';
$string['restorestage4'] = 'การตั้งค่า';
$string['restorestage4action'] = 'ถัดไป';
$string['restorestage8'] = 'สคีมา (Schema)';
$string['restorestage8action'] = 'ถัดไป';
$string['restorestage16'] = 'ตรวจสอบ';
$string['restorestage16action'] = 'ดำเนินการคืนค่า';
$string['restorestage32'] = 'ประมวลผล';
$string['restorestage32action'] = 'ดำเนินการต่อ';
$string['restorestage64'] = 'เสร็จสมบูรณ์';
$string['restorestage64action'] = 'ดำเนินการต่อ';
$string['restoretarget'] = 'เป้าหมายการคืนค่า';
$string['restoretocourse'] = 'คืนค่าไปยังรายวิชา: ';
$string['restoretocurrentcourse'] = 'คืนค่าเข้าสู่รายวิชานี้';
$string['restoretocurrentcourseadding'] = 'รวมรายวิชาที่สำรองไว้เข้ากับรายวิชานี้';
$string['restoretocurrentcoursedeleting'] = 'ลบเนื้อหาในรายวิชานี้แล้วทำการคืนค่า';
$string['restoretoexistingcourse'] = 'คืนค่าเข้าสู่รายวิชาที่มีอยู่';
$string['restoretoexistingcourseadding'] = 'รวมรายวิชาที่สำรองไว้เข้ากับรายวิชาที่มีอยู่';
$string['restoretoexistingcoursedeleting'] = 'ลบเนื้อหาของรายวิชาที่มีอยู่แล้วทำการคืนค่า';
$string['restoretonewcourse'] = 'คืนค่าเป็นรายวิชาใหม่';
$string['restoringcourse'] = 'กำลังดำเนินการคืนค่ารายวิชา';
$string['restoringcourseshortname'] = 'กำลังคืนค่า';
$string['restorerolemappings'] = 'การจับคู่บทบาทในการคืนค่า';
$string['rootenrolmanual'] = 'คืนค่าเป็นการลงทะเบีบนด้วยตนเอง (manual)';
$string['rootsettingcustomfield'] = 'รวมฟิลด์ที่กำหนดเอง';
$string['rootsettingenrolments'] = 'รวมวิธีการลงทะเบียน';
$string['rootsettingenrolments_always'] = 'ใช่ เสมอ';
$string['rootsettingenrolments_never'] = 'ไม่ คืนค่าผู้ใช้เป็นการลงทะเบียนด้วยตนเอง';
$string['rootsettingenrolments_withusers'] = 'ใช่ เฉพาะในกรณีนที่มีผู้ใช้รวมอยู่ด้วยเท่านั้น';
$string['rootsettings'] = 'การตั้งค่าการสำรองข้อมูล';
$string['rootsettingusers'] = 'รวมผู้ใช้ที่ลงทะเบียน';
$string['rootsettinganonymize'] = 'ทำให้ข้อมูลผู้ใช้เป็นแบบไม่ระบุตัวตน';
$string['rootsettingroleassignments'] = 'รวมการกำหนดบทบาทผู้ใช้';
$string['rootsettingpermissions'] = 'รวมการเขียนทับสิทธิ์';
$string['rootsettingactivities'] = 'รวมกิจกรรมและแหล่งข้อมูล';
$string['rootsettingbadges'] = 'รวมเหรียญตรา';
$string['rootsettingblocks'] = 'รวมบล็อก';
$string['rootsettingcompetencies'] = 'รวมสมรรถนะ';
$string['rootsettingfilters'] = 'รวมตัวกรอง';
$string['rootsettingfiles'] = 'รวมไฟล์';
$string['rootsettingcomments'] = 'รวมความคิดเห็น';
$string['rootsettingcalendarevents'] = 'รวมกิจกรรมในปฏิทิน';
$string['rootsettingcontentbankcontent'] = 'รวมเนื้อหาใน Content bank';
$string['rootsettinguserscompletion'] = 'รวมรายละเอียดการเรียนจบของผู้ใช้';
$string['rootsettingquestionbank'] = 'รวมคลังข้อสอบ';
$string['rootsettinglegacyfiles'] = 'รวมไฟล์รายวิชาแบบเก่า';
$string['rootsettinglogs'] = 'รวมบันทึกกิจกรรมรายวิชา';
$string['rootsettinggradehistories'] = 'รวมประวัติเกรด';
$string['rootsettinggroups'] = 'รวมกลุ่มและการจัดกลุ่ม';
$string['rootsettingimscc1'] = 'แปลงเป็น IMS Common Cartridge 1.0';
$string['rootsettingimscc11'] = 'แปลงเป็น IMS Common Cartridge 1.1';
$string['rootsettingxapistate'] = 'รวมสถานะของผู้ใช้ในเนื้อหา เช่น กิจกรรม H5P';
$string['samesitenotification'] = 'ข้อมูลสำรองนี้ถูกสร้างขึ้นโดยมีการอ้างอิงถึงไฟล์เท่านั้น ไม่ใช่ตัวไฟล์เอง การคืนค่าจะใช้งานได้บนไซต์นี้เท่านั้น';
$string['section_prefix'] = 'ส่วนที่ {$a}: ';
$string['sitecourseformatwarning'] = 'นี่คือข้อมูลสำรองหน้าแรกของไซต์ สามารถคืนค่าได้ที่หน้าแรกของไซต์เท่านั้น';
$string['storagecourseonly'] = 'พื้นที่ไฟล์สำรองข้อมูลรายวิชา';
$string['storagecourseandexternal'] = 'พื้นที่ไฟล์สำรองข้อมูลรายวิชาและโฟลเดอร์ที่ระบุ';
$string['storageexternalonly'] = 'โฟลเดอร์ที่ระบุสำหรับการสำรองข้อมูลอัตโนมัติ';
$string['sectionincanduser'] = 'รวมอยู่ในการสำรองข้อมูลพร้อมข้อมูลผู้ใช้';
$string['sectioninc'] = 'รวมอยู่ในการสำรองข้อมูล (ไม่มีข้อมูลผู้ใช้)';
$string['sectionactivities'] = 'กิจกรรม';
$string['selectacategory'] = 'เลือกหมวดหมู่';
$string['selectacourse'] = 'เลือกรายวิชา';
$string['setting_course_fullname'] = 'ชื่อรายวิชา';
$string['setting_course_shortname'] = 'ชื่อย่อรายวิชา';
$string['setting_course_startdate'] = 'วันที่เริ่มรายวิชา';
$string['setting_keep_roles_and_enrolments'] = 'เก็บบทบาทและการลงทะเบียนปัจจุบันไว้';
$string['setting_keep_groups_and_groupings'] = 'เก็บกลุ่มและการจัดกลุ่มปัจจุบันไว้';
$string['setting_overwrite_conf'] = 'เขียนทับการกำหนดค่ารายวิชา';
$string['setting_overwrite_course_fullname'] = 'เขียนทับชื่อเต็มของรายวิชา';
$string['setting_overwrite_course_shortname'] = 'เขียนทับชื่อย่อของรายวิชา';
$string['setting_overwrite_course_startdate'] = 'เขียนทับวันที่เริ่มรายวิชา';
$string['showtypes'] = 'แสดงตัวเลือกประเภท';
$string['skiphidden'] = 'ข้ามรายวิชาที่ถูกซ่อน';
$string['skiphiddenhelp'] = 'เลือกว่าจะข้ามรายวิชาที่ถูกซ่อนหรือไม่';
$string['skipmodifdays'] = 'ข้ามรายวิชาที่ไม่มีการแก้ไขตั้งแต่วันที่';
$string['skipmodifdayshelp'] = 'เลือกเพื่อข้ามรายวิชาที่ไม่มีการแก้ไขเป็นเวลาจำนวนวันที่กำหนด';
$string['skipmodifprev'] = 'ข้ามรายวิชาที่ไม่มีการแก้ไขตั้งแต่การสำรองข้อมูลครั้งก่อน';
$string['skipmodifprevhelp'] = 'เลือกว่าจะข้ามรายวิชาที่ไม่มีการแก้ไขตั้งแต่การสำรองข้อมูลอัตโนมัติครั้งล่าสุดหรือไม่ ซึ่งจำเป็นต้องเปิดใช้งานการบันทึกกิจกรรม';
$string['status'] = 'สถานะ';
$string['subsectioncontent'] = 'เนื้อหาในส่วนย่อย';
$string['successful'] = 'การสำรองข้อมูลสำเร็จ';
$string['successfulcopy'] = 'การคัดลอกสำเร็จ';
$string['successfulrestore'] = 'การคืนค่าสำเร็จ';
$string['timetaken'] = 'เวลาที่ใช้';
$string['title'] = 'ชื่อเรื่อง';
$string['totalcategorysearchresults'] = 'หมวดหมู่ทั้งหมด: {$a}';
$string['totalcoursesearchresults'] = 'รายวิชาทั้งหมด: {$a}';
$string['undefinedrolemapping'] = 'ไม่มีการกำหนดการจับคู่บทบาทสำหรับต้นแบบ \'{$a}\'';
$string['unnamedsection'] = 'ส่วนที่ไม่มีชื่อ';
$string['userdata'] = 'รวมข้อมูลผู้ใช้';
$string['userdata_help'] = 'หากเปิดใช้งาน ข้อมูลเช่น โพสต์ในฟอรัม การส่งงาน ฯลฯ จะถูกคัดลอกเข้าสู่รายวิชาใหม่สำหรับผู้ใช้ที่มีบทบาทที่เลือกใน "รวมการลงทะเบียนตามบทบาท"';
$string['userinfo'] = 'ข้อมูลผู้ใช้';
$string['module'] = 'โมดูล';
$string['morecoursesearchresults'] = 'พบรายวิชามากกว่า {$a} รายการ กำลังแสดงผลลัพธ์ {$a} รายการแรก';
$string['recyclebin_desc'] = 'โปรดทราบว่าการตั้งค่าเหล่านี้จะใช้สำหรับถังขยะด้วย';

// เลิกใช้งานตั้งแต่ Moodle 5.0.
$string['configgeneralquestionbank'] = 'หากเปิดใช้งาน คลังข้อสอบจะถูกรวมในข้อมูลสำรองโดยค่าเริ่มต้น หมายเหตุ: หากปิดการตั้งค่านี้จะทำให้ไม่สามารถสำรองข้อมูลกิจกรรมที่ใช้คลังข้อสอบได้ เช่น ควิซ (Quiz)';
$string['generalquestionbank'] = 'รวมคลังข้อสอบ';

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
 * Strings for component 'core_enrol', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_enrol
 * @subpackage enrol
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actenrolshhdr'] = 'ปลั๊กอินการลงทะเบียนรายวิชาที่มีอยู่';
$string['addinstance'] = 'เพิ่มวิธีการ';
$string['addinstanceanother'] = 'เพิ่มวิธีการและสร้างอีกหนึ่งรายการ';
$string['ajaxoneuserfound'] = 'พบ 1 ผู้ใช้';
$string['ajaxxusersfound'] = 'พบ {$a} ผู้ใช้';
$string['ajaxxmoreusersfound'] = 'พบมากกว่า {$a} ผู้ใช้';
$string['ajaxnext25'] = '25 รายการถัดไป...';
$string['assignnotpermitted'] = 'คุณไม่มีสิทธิ์หรือไม่สามารถกำหนดบทบาทในรายวิชานี้ได้';
$string['bulkuseroperation'] = 'การดำเนินการผู้ใช้จำนวนมาก';
$string['configenrolplugins'] = 'โปรดเลือกปลั๊กอินทั้งหมดที่ต้องการและจัดเรียงตามลำดับที่เหมาะสม';
$string['custominstancename'] = 'ชื่ออินสแตนซ์ที่กำหนดเอง';
$string['customwelcomemessage'] = 'ข้อความต้อนรับที่กำหนดเอง';
$string['customwelcomemessage_help'] = 'รูปแบบที่รองรับ: ข้อความธรรมดาหรือรูปแบบอัตโนมัติของ Moodle สามารถใช้แท็ก HTML และแท็กหลายภาษาได้ รวมถึงตัวแทน (placeholders) ดังนี้:
<ul>
<li>ชื่อวิชา {$a->coursename}</li>
<li>ลิงก์ไปยังหน้าวิชา {$a->courselink}</li>
<li>วันที่เริ่มวิชา {$a->coursestartdate}</li>
<li>ลิงก์ไปยังหน้าโปรไฟล์ของผู้ใช้ {$a->profileurl}</li>
<li>อีเมลของผู้ใช้ {$a->email}</li>
<li>ชื่อเต็มของผู้ใช้ {$a->fullname}</li>
<li>ชื่อจริงของผู้ใช้ {$a->firstname}</li>
<li>นามสกุลของผู้ใช้ {$a->lastname}</li>
<li>บทบาทในวิชาของผู้ใช้ {$a->courserole}</li>
</ul>';
$string['customwelcomemessageplaceholder'] = 'สวัสดีคุณ {$a->firstname} คุณได้ลงทะเบียนในรายวิชา {$a->coursename} แล้ว';
$string['defaultenrol'] = 'เพิ่มอินสแตนซ์ในรายวิชาใหม่';
$string['defaultenrol_desc'] = 'สามารถกำหนดให้เพิ่มปลั๊กอินนี้ในรายวิชาใหม่ทั้งหมดโดยอัตโนมัติ';
$string['deleteinstanceconfirm'] = 'คุณกำลังจะลบวิธีการลงทะเบียน "{$a->name}" ผู้ใช้จำนวน {$a->users} รายที่ลงทะเบียนผ่านวิธีนี้จะถูกถอนชื่อออก และข้อมูลที่เกี่ยวข้องกับรายวิชา เช่น เกรด สมาชิกกลุ่ม หรือการติดตามฟอรัมจะถูกลบออกด้วย

คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?';
$string['deleteinstanceconfirmself'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบอินสแตนซ์ "{$a->name}" ที่ให้คุณเข้าถึงรายวิชานี้? หากลบออกคุณอาจไม่สามารถเข้าถึงรายวิชานี้ได้อีก';
$string['deleteinstancenousersconfirm'] = 'คุณกำลังจะลบวิธีการลงทะเบียน "{$a->name}" คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?';
$string['disableinstanceconfirmself'] = 'คุณแน่ใจหรือไม่ว่าต้องการปิดการใช้งานอินสแตนซ์ "{$a->name}" ที่ให้คุณเข้าถึงรายวิชานี้? หากปิดการใช้งานคุณอาจไม่สามารถเข้าถึงรายวิชานี้ได้อีก';
$string['durationdays'] = '{$a} วัน';
$string['editenrolment'] = 'แก้ไขการลงทะเบียน';
$string['edituserenrolment'] = 'แก้ไขการลงทะเบียนของ {$a}';
$string['enrol'] = 'ลงทะเบียน';
$string['enrolcandidates'] = 'ผู้ใช้ที่ยังไม่ได้ลงทะเบียน';
$string['enrolcandidatesmatching'] = 'ผู้ใช้ที่ยังไม่ได้ลงทะเบียนที่ตรงกับเงื่อนไข';
$string['enrolcohort'] = 'ลงทะเบียนกลุ่ม (Cohort)';
$string['enrolcohortusers'] = 'ลงทะเบียนผู้ใช้';
$string['enroldetails'] = 'รายละเอียดการลงทะเบียน';
$string['eventenrolinstancecreated'] = 'สร้างอินสแตนซ์การลงทะเบียนแล้ว';
$string['eventenrolinstancedeleted'] = 'ลบอินสแตนซ์การลงทะเบียนแล้ว';
$string['eventenrolinstanceupdated'] = 'อัปเดตอินสแตนซ์การลงทะเบียนแล้ว';
$string['enrollednewusers'] = 'ลงทะเบียนผู้ใช้ใหม่สำเร็จ {$a} ราย';
$string['enrolledusers'] = 'ผู้ใช้ที่ลงทะเบียนแล้ว';
$string['enrolledusersmatching'] = 'ผู้ใช้ที่ลงทะเบียนแล้วที่ตรงกับเงื่อนไข';
$string['enrolme'] = 'ลงทะเบียนฉันในรายวิชานี้';
$string['enrolment'] = 'การลงทะเบียน';
$string['enrolmentinstances'] = 'วิธีการลงทะเบียน';
$string['enrolmentnew'] = 'การลงทะเบียนใหม่ใน {$a}';
$string['enrolmentnewuser'] = '{$a->user} ได้ลงทะเบียนในรายวิชา "{$a->course}"';
$string['enrolmentmethod'] = 'วิธีการลงทะเบียน';
$string['enrolments'] = 'การลงทะเบียน';
$string['enrolmentoptions'] = 'ตัวเลือกการลงทะเบียน';
$string['enrolmentupdatedforuser'] = 'อัปเดตการลงทะเบียนสำหรับผู้ใช้ "{$a->fullname}" แล้ว';
$string['enrolnotpermitted'] = 'คุณไม่มีสิทธิ์หรือไม่ได้รับอนุญาตให้ลงทะเบียนผู้อื่นในรายวิชานี้';
$string['enrolperiod'] = 'ระยะเวลาการลงทะเบียน';
$string['enrolusage'] = 'อินสแตนซ์ / การลงทะเบียน';
$string['enrolusers'] = 'ลงทะเบียนผู้ใช้';
$string['enrolxusers'] = 'ลงทะเบียนผู้ใช้ {$a} ราย';
$string['enroltimecreated'] = 'สร้างการลงทะเบียนแล้ว';
$string['enroltimeend'] = 'สิ้นสุดการลงทะเบียน';
$string['enroltimeendinvalid'] = 'วันที่สิ้นสุดการลงทะเบียนต้องอยู่หลังวันที่เริ่มการลงทะเบียน';
$string['enroltimestart'] = 'เริ่มการลงทะเบียน';
$string['errajaxfailedenrol'] = 'ไม่สามารถลงทะเบียนผู้ใช้ได้';
$string['errajaxsearch'] = 'เกิดข้อผิดพลาดในการค้นหาผู้ใช้';
$string['erroreditenrolment'] = 'เกิดข้อผิดพลาดขณะพยายามแก้ไขการลงทะเบียนผู้ใช้';
$string['errorenrolcohort'] = 'เกิดข้อผิดพลาดในการสร้างอินสแตนซ์การลงทะเบียนแบบซิงค์กลุ่มในรายวิชานี้';
$string['errorenrolcohortusers'] = 'เกิดข้อผิดพลาดในการลงทะเบียนสมาชิกกลุ่มในรายวิชานี้';
$string['errorthresholdlow'] = 'เกณฑ์การแจ้งเตือนต้องไม่น้อยกว่า 1 วัน';
$string['errorwithbulkoperation'] = 'เกิดข้อผิดพลาดขณะประมวลผลการเปลี่ยนการลงทะเบียนจำนวนมากของคุณ';
$string['eventuserenrolmentcreated'] = 'ผู้ใช้ลงทะเบียนในรายวิชาแล้ว';
$string['eventuserenrolmentdeleted'] = 'ถอนชื่อผู้ใช้ออกจากรายวิชาแล้ว';
$string['eventuserenrolmentupdated'] = 'อัปเดตการลงทะเบียนผู้ใช้แล้ว';
$string['expirynotify'] = 'แจ้งเตือนก่อนการลงทะเบียนหมดอายุ';
$string['expirynotify_help'] = 'การตั้งค่านี้กำหนดว่าจะส่งข้อความแจ้งเตือนการหมดอายุการลงทะเบียนหรือไม่';
$string['expirynotifyall'] = 'ผู้ลงทะเบียนและผู้ใช้ที่ลงทะเบียน';
$string['expirynotifyenroller'] = 'ผู้ลงทะเบียนเท่านั้น';
$string['expirynotifyhour'] = 'ชั่วโมงที่จะส่งการแจ้งเตือนการหมดอายุการลงทะเบียน';
$string['expirythreshold'] = 'เกณฑ์การแจ้งเตือน';
$string['expirythreshold_help'] = 'ควรเตือนผู้ใช้ล่วงหน้านานแค่ไหนก่อนที่การลงทะเบียนจะหมดอายุ?';
$string['finishenrollingusers'] = 'เสร็จสิ้นการลงทะเบียนผู้ใช้';
$string['foundxcohorts'] = 'พบ {$a} กลุ่ม (Cohorts)';
$string['instanceadded'] = 'เพิ่มวิธีการแล้ว';
$string['instanceeditselfwarning'] = 'คำเตือน:';
$string['instanceeditselfwarningtext'] = 'คุณลงทะเบียนในรายวิชานี้ผ่านวิธีนี้ การเปลี่ยนแปลงอาจส่งผลต่อการเข้าถึงรายวิชานี้ของคุณ';
$string['invalidenrolinstance'] = 'อินสแตนซ์การลงทะเบียนไม่ถูกต้อง';
$string['invalidenrolduration'] = 'ระยะเวลาการลงทะเบียนไม่ถูกต้อง';
$string['invalidrole'] = 'บทบาทไม่ถูกต้อง';
$string['invalidrequest'] = 'คำขอไม่ถูกต้อง';
$string['manageenrols'] = 'จัดการปลั๊กอินการลงทะเบียน';
$string['manageinstance'] = 'จัดการ';
$string['method'] = 'วิธีการ';
$string['migratetomanual'] = 'ย้ายไปยังการลงทะเบียนแบบกำหนดเอง (Manual)';
$string['nochange'] = 'ไม่มีการเปลี่ยนแปลง';
$string['noexistingparticipants'] = 'ไม่มีผู้เข้าร่วมที่มีอยู่';
$string['nogroup'] = 'ไม่มีกลุ่ม';
$string['noguestaccess'] = 'บุคคลทั่วไปไม่สามารถเข้าถึงวิชานี้ได้ โปรดเข้าสู่ระบบ';
$string['none'] = 'ไม่มี';
$string['notenrollable'] = 'คุณไม่สามารถลงทะเบียนด้วยตัวเองในวิชานี้ได้';
$string['notenrolledusers'] = 'ผู้ใช้รายอื่น';
$string['otheruserdesc'] = 'ผู้ใช้ดังต่อไปนี้ไม่ได้ลงทะเบียนในวิชานี้ แต่มีบทบาทที่สืบทอดหรือกำหนดให้ภายในวิชา';
$string['participationactive'] = 'ใช้งานอยู่';
$string['participationnotcurrent'] = 'ปัจจุบันไม่ได้ใช้งาน';
$string['participationstatus'] = 'สถานะ';
$string['participationsuspended'] = 'ระงับการใช้งาน';
$string['periodend'] = 'จนถึง {$a}';
$string['periodnone'] = 'ลงทะเบียนแล้ว {$a}';
$string['periodstart'] = 'จาก {$a}';
$string['periodstartend'] = 'จาก {$a->start} จนถึง {$a->end}';
$string['plugindisabled'] = 'ปลั๊กอินการลงทะเบียน {$a} ถูกปิดใช้งาน';
$string['recovergrades'] = 'กู้คืนเกรดเก่าของผู้ใช้หากเป็นไปได้';
$string['rolefromthiscourse'] = '{$a->role} (กำหนดในวิชานี้)';
$string['rolefrommetacourse'] = '{$a->role} (สืบทอดจากวิชาแม่)';
$string['rolefromcategory'] = '{$a->role} (สืบทอดจากหมวดบัญชีวิชา)';
$string['rolefromsystem'] = '{$a->role} (กำหนดในระดับเมนูหลัก)';
$string['sendfromcoursecontact'] = 'จากผู้ติดต่อของวิชา';
$string['sendfromkeyholder'] = 'จากผู้ถือกุญแจ';
$string['sendfromnoreply'] = 'จากอีเมลแบบไม่ต้องตอบกลับ (no-reply)';
$string['sendcoursewelcomemessage'] = 'ส่งข้อความต้อนรับของรายวิชา';
$string['sendcoursewelcomemessage_help'] = 'เมื่อลงทะเบียนผู้ใช้หรือกลุ่มในวิชา สามารถเลือกส่งอีเมลข้อความต้อนรับได้ หากส่งจากผู้ติดต่อของวิชา (โดยค่าเริ่มต้นคือครู) และหากมีผู้มีบทบาทนี้มากกว่าหนึ่งคน อีเมลจะถูกส่งจากผู้ใช้คนแรกที่ได้รับบทบาทนี้';
$string['startdatetoday'] = 'วันนี้';
$string['synced'] = 'ซิงค์แล้ว';
$string['testsettings'] = 'ทดสอบการตั้งค่า';
$string['testsettingsheading'] = 'ทดสอบการตั้งค่าการลงทะเบียน - {$a}';
$string['timeended'] = 'เวลาที่สิ้นสุด';
$string['timeenrolled'] = 'เวลาที่ลงทะเบียน';
$string['timereaggregated'] = 'เวลาที่รวมยอดใหม่';
$string['timestarted'] = 'เวลาที่เริ่ม';
$string['totalenrolledusers'] = 'ผู้ใช้ที่ลงทะเบียนทั้งหมด {$a} ราย';
$string['totalunenrolledusers'] = 'ผู้ใช้ที่ถอนการลงทะเบียนทั้งหมด {$a} ราย';
$string['totalotherusers'] = 'ผู้ใช้รายอื่นทั้งหมด {$a} ราย';
$string['unassignnotpermitted'] = 'คุณไม่มีสิทธิ์ยกเลิกการกำหนดบทบาทในรายวิชานี้';
$string['unenrol'] = 'ถอนชื่อ';
$string['unenrolleduser'] = 'ถอนชื่อผู้ใช้ "{$a->fullname}" ออกจากรายวิชาแล้ว';
$string['unenrolconfirm'] = 'คุณต้องการถอนชื่อ "{$a->user}" (ซึ่งเดิมลงทะเบียนผ่าน "{$a->enrolinstancename}") ออกจากวิชา "{$a->course}" หรือไม่?';
$string['unenrolme'] = 'ถอนชื่อฉันออกจากรายวิชานี้';
$string['unenrolnotpermitted'] = 'คุณไม่มีสิทธิ์ถอนชื่อผู้ใช้นี้ออกจากรายวิชา';
$string['unenrolroleusers'] = 'ถอนชื่อผู้ใช้';
$string['uninstallmigrating'] = 'กำลังย้ายการลงทะเบียน "{$a}"';
$string['unknowajaxaction'] = 'ร้องขอการดำเนินการที่ไม่รู้จัก';
$string['unlimitedduration'] = 'ไม่จำกัด';
$string['userremovedfromselectiona'] = 'นำผู้ใช้ "{$a}" ออกจากการเลือกแล้ว';
$string['usersearch'] = 'ค้นหา ';
$string['withselectedusers'] = 'พร้อมผู้ใช้ที่เลือก';
$string['extremovedaction'] = 'การดำเนินการถอนชื่อจากภายนอก';
$string['extremovedaction_help'] = 'เลือกการดำเนินการที่จะทำเมื่อการลงทะเบียนผู้ใช้หายไปจากแหล่งข้อมูลภายนอก โปรดทราบว่าข้อมูลและตั้งค่าของผู้ใช้บางอย่างจะถูกลบออกจากวิชาเมื่อมีการถอนชื่อ';
$string['extremovedsuspend'] = 'ระงับการเข้าถึงรายวิชา';
$string['extremovedsuspendnoroles'] = 'ระงับการเข้าถึงและนำบทบาทออก';
$string['extremovedkeep'] = 'คงสถานะการลงทะเบียนของผู้ใช้ไว้';
$string['extremovedunenrol'] = 'ถอนชื่อผู้ใช้ออกจากรายวิชา';
$string['privacy:metadata:user_enrolments'] = 'การลงทะเบียน';
$string['privacy:metadata:user_enrolments:enrolid'] = 'อินสแตนซ์ของปลั๊กอินการลงทะเบียน';
$string['privacy:metadata:user_enrolments:modifierid'] = 'ID ของผู้ใช้ที่แก้ไขการลงทะเบียนล่าสุด';
$string['privacy:metadata:user_enrolments:status'] = 'สถานะการลงทะเบียนของผู้ใช้ในรายวิชา';
$string['privacy:metadata:user_enrolments:tableexplanation'] = 'ปลั๊กอินการลงทะเบียนหลักเก็บข้อมูลผู้ใช้ที่ลงทะเบียน';
$string['privacy:metadata:user_enrolments:timecreated'] = 'เวลาที่สร้างการลงทะเบียน';
$string['privacy:metadata:user_enrolments:timeend'] = 'เวลาที่การลงทะเบียนสิ้นสุด';
$string['privacy:metadata:user_enrolments:timestart'] = 'เวลาที่เริ่มการลงทะเบียน';
$string['privacy:metadata:user_enrolments:timemodified'] = 'เวลาที่มีการแก้ไขการลงทะเบียน';
$string['privacy:metadata:user_enrolments:userid'] = 'ID ของผู้ใช้';
$string['youenrolledincourse'] = 'คุณลงทะเบียนในรายวิชาแล้ว';
$string['youunenrolledfromcourse'] = 'คุณถอนชื่อออกจากรายวิชา "{$a}" แล้ว';

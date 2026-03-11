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
 * Strings for component 'debug', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['authpluginnotfound'] = 'ไม่พบปลั๊กอินการยืนยันตัวตน {$a}';
$string['cannotbenull'] = '{$a} ไม่สามารถเป็นค่าว่าง (null) ได้!';
$string['cannotdowngrade'] = 'ไม่สามารถลดรุ่นของ {$a->plugin} จาก {$a->oldversion} เป็น {$a->newversion} ได้';
$string['cannotfindadmin'] = 'ไม่พบผู้ใช้ระดับผู้ดูแลระบบ!';
$string['cannotinitpage'] = 'ไม่สามารถเริ่มต้นหน้าเว็บได้อย่างสมบูรณ์: {$a->name} ID {$a->id} ไม่ถูกต้อง';
$string['cannotsetuptable'] = 'ไม่สามารถตั้งค่าตาราง {$a} ได้สำเร็จ!';
$string['codingerror'] = 'ตรวจพบข้อผิดพลาดในการเขียนโปรแกรม ซึ่งต้องได้รับการแก้ไขโดยนักโปรแกรมเมอร์: {$a}';
$string['configmoodle'] = 'Moodle ยังไม่ได้รับการกำหนดค่า คุณต้องแก้ไขไฟล์ config.php ก่อน';
$string['debuginfo'] = 'ข้อมูลการดีบั๊ก';
$string['erroroccur'] = 'เกิดข้อผิดพลาดขึ้นในระหว่างกระบวนการนี้';
$string['invalidarraysize'] = 'ขนาดของอาร์เรย์ในพารามิเตอร์ของ {$a} ไม่ถูกต้อง';
$string['invalideventdata'] = 'ข้อมูลเหตุการณ์ที่ส่งเข้ามาไม่ถูกต้อง: {$a}';
$string['invalidparameter'] = 'ตรวจพบค่าพารามิเตอร์ที่ไม่ถูกต้อง';
$string['invalidresponse'] = 'ตรวจพบค่าการตอบกลับ (response) ที่ไม่ถูกต้อง';
$string['line'] = 'บรรทัด';
$string['missingconfigversion'] = 'ตารางการกำหนดค่าไม่มีข้อมูลเวอร์ชัน คุณไม่สามารถดำเนินการต่อได้';
$string['morethanonerecordinfetch'] = 'พบมากกว่าหนึ่งเรคคอร์ดใน fetch() !';
$string['mustbeoveride'] = 'ต้องมีการเขียนทับ (override) เมธอด abstract {$a}';
$string['noadminrole'] = 'ไม่พบสิทธิ์ของผู้ดูแลระบบ';
$string['noblocks'] = 'ไม่มีการติดตั้งบล็อก!';
$string['nocate'] = 'ไม่มีหมวดหมู่!';
$string['nomodules'] = 'ไม่พบโมดูล!!';
$string['nopageclass'] = 'นำเข้า {$a} แล้วแต่ไม่พบคลาสของหน้า (page classes)';
$string['noreports'] = 'ไม่สามารถเข้าถึงรายงานได้';
$string['notables'] = 'ไม่มีตาราง!';
$string['outputbuffer'] = 'บัฟเฟอร์เอาต์พุต (Output buffer)';
$string['phpvaroff'] = 'ตัวแปรเซิร์ฟเวอร์ PHP \'{$a->name}\' ควรเป็น Off - {$a->link}';
$string['phpvaron'] = 'ตัวแปรเซิร์ฟเวอร์ PHP \'{$a->name}\' ไม่ได้เปลี่ยนเป็น On - {$a->link}';
$string['reactive_instances'] = 'อินสแตนซ์รีแอคทีฟ:';
$string['reactive_noinstances'] = 'หน้านี้ไม่มีอินสแตนซ์รีแอคทีฟ';
$string['reactive_pin'] = 'ปักหมุด';
$string['reactive_unpin'] = 'ถอนหมุด';
$string['reactive_highlightoff'] = 'ปิดไฮไลท์';
$string['reactive_highlighton'] = 'เปิดไฮไลท์';
$string['reactive_readmodeon'] = 'เปิดโหมดอ่าน';
$string['reactive_readmodeoff'] = 'ปิดโหมดอ่าน';
$string['reactive_resetpanel'] = 'รีเซ็ตพาเนล';
$string['reactive_statedata'] = 'ข้อมูลสถานะ';
$string['reactive_saveingwarning'] = 'คำเตือน: การแก้ไขสถานะอาจทำให้เกิดผลลัพธ์ที่ไม่คาดคิด';
$string['sessionmissing'] = 'ไม่มีออบเจกต์ {$a} ในเซสชัน';
$string['sqlrelyonobsoletetable'] = 'SQL นี้อาศัยตารางที่เลิกใช้แล้ว: {$a}! โค้ดของคุณต้องได้รับการแก้ไขโดยนักพัฒนา';
$string['stacktrace'] = 'การย้อนรอยสแต็ก (Stack trace)';
$string['withoutversion'] = 'ไฟล์ main version.php หายไป อ่านไม่ได้ หรือเสียหาย';
$string['xmlizeunavailable'] = 'ไม่มีฟังก์ชัน xmlize ให้ใช้งาน';

// Deprecated since Moodle 4.5.
$string['blocknotexist'] = 'บล็อก {$a} ไม่มีอยู่';
$string['modulenotexist'] = 'โมดูล {$a} ไม่มีอยู่';

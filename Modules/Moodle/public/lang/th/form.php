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
 * Strings for component 'form', language 'th', branch 'MOODLE_20_STABLE'
 *
 * @package    core
 * @subpackage form
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addfields'] = 'เพิ่ม {$a} ฟิลด์ในแบบฟอร์ม';
$string['close'] = 'ปิด';
$string['custom'] = 'กำหนดเอง';
$string['day'] = 'วัน';
$string['default'] = 'ค่าเริ่มต้น';
$string['display'] = 'แสดงผล';
$string['err_alphanumeric'] = 'คุณต้องกรอกเฉพาะตัวอักษรหรือตัวเลขเท่านั้น';
$string['err_email'] = 'คุณต้องกรอกที่อยู่อีเมลที่ถูกต้อง';
$string['err_lettersonly'] = 'คุณต้องกรอกเฉพาะตัวอักษรเท่านั้น';
$string['err_maxfiles'] = 'คุณต้องไม่แนบไฟล์เกิน {$a} ไฟล์';
$string['err_maxlength'] = 'คุณต้องกรอกไม่เกิน {$a->format} ตัวอักษร';
$string['err_minlength'] = 'คุณต้องกรอกอย่างน้อย {$a->format} ตัวอักษร';
$string['err_nonzero'] = 'คุณต้องกรอกตัวเลขที่ไม่ขึ้นต้นด้วย 0';
$string['err_nopunctuation'] = 'คุณต้องไม่กรอกเครื่องหมายวรรคตอน';
$string['err_numeric'] = 'คุณต้องกรอกตัวเลข';
$string['err_positiveduration'] = 'ระยะเวลานี้ไม่สามารถเป็นค่าลบได้';
$string['err_positiveint'] = 'คุณต้องกรอกจำนวนเต็มที่มีค่ามากกว่า 0';
$string['err_rangelength'] = 'คุณต้องกรอกระหว่าง {$a->format[0]} ถึง {$a->format[1]} ตัวอักษร';
$string['err_required'] = 'คุณต้องระบุค่าที่นี่';
$string['err_wrappingwhitespace'] = 'ค่าต้องไม่เริ่มต้นหรือลงท้ายด้วยช่องว่าง';
$string['err_wrongfileextension'] = 'บางไฟล์ ({$a->wrongfiles}) ไม่สามารถอัปโหลดได้ อนุญาตเฉพาะประเภทไฟล์ {$a->allowlist} เท่านั้น';
$string['filesofthesetypes'] = 'ประเภทไฟล์ที่ยอมรับ:';
$string['filetypesany'] = 'ทุกประเภทไฟล์';
$string['filetypesnotall'] = 'ไม่อนุญาตให้เลือก \'ทุกประเภทไฟล์\' ที่นี่';
$string['filetypesnotallowed'] = 'ประเภทไฟล์เหล่านี้ไม่อนุญาตที่นี่: {$a}';
$string['filetypesothers'] = 'ไฟล์อื่นๆ';
$string['filetypesunknown'] = 'ไม่รู้จักประเภทไฟล์: {$a}';
$string['formactions'] = 'การดำเนินการของแบบฟอร์ม';
$string['general'] = 'ทั่วไป';
$string['hideadvanced'] = 'ซ่อนการตั้งค่าขั้นสูง';
$string['hour'] = 'ชั่วโมง';
$string['minute'] = 'นาที';
$string['miscellaneoussettings'] = 'การตั้งค่าเบ็ดเตล็ด';
$string['modstandardels'] = 'การตั้งค่าโมดูลทั่วไป';
$string['month'] = 'เดือน';
$string['mustbeoverriden'] = 'เมธอด Abstract form_definition() ในคลาส {$a} ต้องถูกโอเวอร์ไรด์ (overridden) โปรดแก้ไขโค้ด';
$string['newvaluefor'] = 'ค่าใหม่สำหรับ {$a}';
$string['nomethodforaddinghelpbutton'] = 'ไม่มีเมธอดสำหรับเพิ่มปุ่มช่วยเหลือให้กับองค์ประกอบฟอร์ม {$a->name} (คลาส {$a->classname})';
$string['nonexistentformelements'] = 'พยายามเพิ่มปุ่มช่วยเหลือให้กับองค์ประกอบฟอร์มที่ไม่มีอยู่จริง: {$a}';
$string['nopermissionform'] = 'คุณไม่มีสิทธิ์เข้าถึงแบบฟอร์มนี้';
$string['noselection'] = 'ไม่มีการเลือก';
$string['nosuggestions'] = 'ไม่มีคำแนะนำ';
$string['novalue'] = 'ไม่ได้กรอกข้อมูล';
$string['novalueclicktoset'] = 'คลิกเพื่อกรอกข้อความ';
$string['optional'] = 'ไม่บังคับ';
$string['othersettings'] = 'การตั้งค่าอื่นๆ';
$string['passwordunmaskedithint'] = 'แก้ไขรหัสผ่าน';
$string['passwordunmaskrevealhint'] = 'แสดง';
$string['passwordunmaskinstructions'] = 'กด Enter เพื่อบันทึกการเปลี่ยนแปลง';
$string['privacy:metadata:preference:filemanager_recentviewmode'] = 'โหมดการดูที่เลือกล่าสุดขององค์ประกอบตัวเลือกไฟล์ (file picker)';
$string['privacy:preference:filemanager_recentviewmode'] = 'วิธีที่คุณต้องการให้แสดงไฟล์ในตัวเลือกไฟล์คือ: {$a}';
$string['requiredelement'] = 'ฟิลด์ที่จำเป็นต้องกรอก';
$string['security'] = 'ความปลอดภัย';
$string['selectallornone'] = 'เลือกทั้งหมด/ไม่เลือกเลย';
$string['selected'] = 'เลือกแล้ว';
$string['selecteditems'] = 'รายการที่เลือก:';
$string['showadvanced'] = 'แสดงการตั้งค่าขั้นสูง';
$string['showless'] = 'แสดงน้อยลง...';
$string['showmore'] = 'แสดงเพิ่มเติม...';
$string['somefieldsrequired'] = '{$a} จำเป็น';
$string['suggestions'] = 'คำแนะนำ';
$string['time'] = 'เวลา';
$string['timeunit'] = 'หน่วยเวลา';
$string['timing'] = 'กำหนดเวลา';
$string['togglesensitive'] = 'สลับการแสดงข้อมูลอ่อนไหว';
$string['unmaskpassword'] = 'ยกเลิกการปิดบัง';
$string['year'] = 'ปี';

// Deprecated since Moodle 5.0.
$string['advancedelement'] = 'องค์ประกอบขั้นสูง';

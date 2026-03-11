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
 * Core admin presets component to load some settings/plugins.
 *
 * @package          core_adminpresets
 * @copyright        2021 Sara Arjona (sara@moodle.com)
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['disabled'] = 'ปิดใช้งาน';
$string['disabledwithvalue'] = 'ปิดใช้งาน ({$a})';
$string['enabled'] = 'เปิดใช้งาน';
$string['errordeleting'] = 'เกิดข้อผิดพลาดในการลบจากฐานข้อมูล';
$string['errorinserting'] = 'เกิดข้อผิดพลาดในการนำเข้าฐานข้อมูล';
$string['errornopreset'] = 'ไม่พบค่าที่ตั้งไว้ล่วงหน้าด้วยชื่อนี้';
$string['fullpreset'] = 'แบบเต็ม';
$string['fullpresetdescription'] = 'มีคุณสมบัติ Starter ทั้งหมด รวมกับเครื่องมือภายนอก (LTI), SCORM, Workshop, การวิเคราะห์, เหรียญตรา, สมรรถนะ, แผนการเรียนรู้ และอื่น ๆ อีกมากมาย';
$string['markedasadvanced'] = 'ทำเครื่องหมายเป็นขั้นสูง';
$string['markedasforced'] = 'ทำเครื่องหมายเป็นบังคับ';
$string['markedaslocked'] = 'ทำเครื่องหมายเป็นล็อค';
$string['markedasnonadvanced'] = 'ไม่ได้ทำเครื่องหมายเป็นขั้นสูง';
$string['markedasnonforced'] = 'ไม่ได้ทำเครื่องหมายเป็นบังคับ';
$string['markedasnonlocked'] = 'ไม่ได้ทำเครื่องหมายเป็นล็อค';
$string['privacy:metadata:adminpresets'] = 'รายชื่อการกำหนดค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets:comments'] = 'คำอธิบายของค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets:moodlerelease'] = 'เวอร์ชันของ Moodle ที่ใช้ในการสร้างค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets:name'] = 'ชื่อของค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets:site'] = 'เว็บไซต์ Moodle ที่สร้างค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets:timecreated'] = 'เวลาที่ทำการเปลี่ยนแปลง';
$string['privacy:metadata:adminpresets:userid'] = 'ผู้ใช้ที่สร้างค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets_app'] = 'การกำหนดค่าที่ตั้งไว้ล่วงหน้าที่มีผลใช้งานแล้ว';
$string['privacy:metadata:adminpresets_app:adminpresetid'] = 'ไอดีของค่าที่ตั้งไว้ล่วงหน้าที่ใช้งาน';
$string['privacy:metadata:adminpresets_app:time'] = 'เวลาที่มีการใช้ค่าที่ตั้งไว้ล่วงหน้า';
$string['privacy:metadata:adminpresets_app:userid'] = 'ผู้ที่ใช้ค่าที่ตั้งไว้ล่วงหน้า';
$string['sensiblesettings'] = 'การตั้งค่าที่มีรหัสผ่าน';
$string['sensiblesettingstext'] = 'การตั้งค่าที่มีรหัสผ่านหรือข้อมูลที่ละเอียดอ่อนสามารถแยกออกได้เมื่อสร้างค่าที่ตั้งไว้ล่วงหน้าสำหรับผู้ดูแลระบบ ใส่การตั้งค่าเพิ่มเติมในรูปแบบ SETTINGNAME@@PLUGINNAME คั่นด้วยเครื่องหมายจุลภาค';
$string['siteadminpresetspluginname'] = 'ค่าที่ตั้งไว้ล่วงหน้าของผู้ดูแลระบบไซต์';
$string['starterpreset'] = 'แบบเริ่มต้น (Starter)';
$string['starterpresetdescription'] = 'Moodle พร้อมด้วยคุณสมบัติยอดนิยมทั้งหมด รวมถึงการบ้าน (Assignment), ข้อเสนอแนะ (Feedback), กระดานเสวนา (Forum), H5P, แบบทดสอบ (Quiz) และการติดตามรายการที่สำเร็จ (Completion tracking)';
$string['wrongid'] = 'ไอดีผิดพลาด';

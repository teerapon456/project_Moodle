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
 * Strings for component 'dbtransfer', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core
 * @subpackage dbtransfer
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['copyingtable'] = 'กำลังคัดลอกตาราง {$a}';
$string['copyingtables'] = 'กำลังคัดลอกเนื้อหาในตาราง';
$string['creatingtargettables'] = 'กำลังสร้างตารางในฐานข้อมูลปลายทาง';
$string['dbexport'] = 'การส่งออกฐานข้อมูล';
$string['dbtransfer'] = 'การโอนย้ายฐานข้อมูล';
$string['differenttableexception'] = 'โครงสร้างของตาราง {$a} ไม่ตรงกัน';
$string['done'] = 'เสร็จสิ้น';
$string['exportschemaexception'] = 'โครงสร้างฐานข้อมูลปัจจุบันไม่ตรงกับไฟล์ install.xml ทั้งหมด <br /> {$a}';
$string['checkingsourcetables'] = 'กำลังตรวจสอบโครงสร้างตารางต้นทาง';
$string['importschemaexception'] = 'โครงสร้างฐานข้อมูลปัจจุบันไม่ตรงกับไฟล์ install.xml ทั้งหมด <br /> {$a}';
$string['importversionmismatchexception'] = 'เวอร์ชันปัจจุบัน {$a->currentver} ไม่ตรงกับเวอร์ชันที่ส่งออก {$a->schemaver}';
$string['malformedxmlexception'] = 'พบ XML ที่รูปแบบไม่ถูกต้อง ไม่สามารถดำเนินการต่อได้';
$string['tablex'] = 'ตาราง {$a}:';
$string['unknowntableexception'] = 'พบตารางที่ไม่รู้จัก {$a} ในไฟล์ที่ส่งออก';

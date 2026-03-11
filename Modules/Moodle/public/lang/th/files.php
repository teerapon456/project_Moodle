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
 * Language file.
 *
 * @package    core_files
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['contenthash'] = 'แฮชเนื้อหา';
$string['eventfileaddedtodraftarea'] = 'เพิ่มไฟล์ไปยังพื้นที่ร่างแล้ว';
$string['eventfiledeletedfromdraftarea'] = 'ลบไฟล์ออกจากพื้นที่ร่างแล้ว';
$string['redactor'] = 'การแก้ไขข้อความลับในไฟล์ (File redaction)';
$string['redactor:exifremover'] = 'ตัวลบข้อมูล EXIF';
$string['redactor:exifremover:emptyremovetags'] = 'แท็กที่จะลบต้องไม่ว่างเปล่า!';
$string['redactor:exifremover:enabled'] = 'เปิดใช้งานตัวลบข้อมูล EXIF';
$string['redactor:exifremover:enabled_desc'] = 'โดยปกติแล้ว ตัวลบ EXIF จะรองรับเฉพาะไฟล์ JPG โดยใช้ PHP GD หรือ ExifTool หากกำหนดค่าไว้
การใช้ PHP GD เพื่อจุดประสงค์นี้อาจทำให้คุณภาพของภาพลดลง

หากต้องการเพิ่มประสิทธิภาพของตัวลบ EXIF โปรดกำหนดค่าการตั้งค่า ExifTool ด้านล่าง

สามารถดูข้อมูลเพิ่มเติมเกี่ยวกับการติดตั้ง ExifTool ได้ที่ {$a->link}';
$string['redactor:exifremover:failedprocessexiftool'] = 'การแก้ไขล้มเหลว: ไม่สามารถประมวลผลไฟล์ด้วย ExifTool!';
$string['redactor:exifremover:failedprocessgd'] = 'การแก้ไขล้มเหลว: ไม่สามารถประมวลผลไฟล์ด้วย PHP GD!';
$string['redactor:exifremover:heading'] = 'ExifTool';
$string['redactor:exifremover:mimetype'] = 'ประเภท MIME ที่รองรับ';
$string['redactor:exifremover:mimetype_desc'] = 'หากต้องการเพิ่มประเภท MIME ใหม่ ตรวจสอบให้แน่ใจว่าได้รวมไว้ใน <a href="./tool/filetypes/index.php">ประเภทไฟล์</a> แล้ว';
$string['redactor:exifremover:removetags'] = 'แท็ก EXIF ที่ต้องการลบ';
$string['redactor:exifremover:removetags_desc'] = 'แท็ก EXIF ที่จำเป็นต้องลบ';
$string['redactor:exifremover:tag:all'] = 'ทั้งหมด';
$string['redactor:exifremover:tag:gps'] = 'เฉพาะ GPS';
$string['redactor:exifremover:tooldoesnotexist'] = 'การแก้ไขล้มเหลว: ไม่พบ ExifTool!';
$string['redactor:exifremover:toolpath'] = 'พาธไปยัง ExifTool';
$string['redactor:exifremover:toolpath_desc'] = 'หากต้องการใช้ ExifTool โปรดระบุพาธไปยังไฟล์ที่รันได้ของ ExifTool
โดยทั่วไป บนระบบ Unix/Linux พาธคือ /usr/bin/exiftool';
$string['privacy:metadata:file_conversions'] = 'บันทึกการแปลงไฟล์ที่ดำเนินการโดยผู้ใช้';
$string['privacy:metadata:file_conversion:usermodified'] = 'ผู้ใช้ที่เริ่มการแปลงไฟล์';
$string['privacy:metadata:files'] = 'บันทึกไฟล์ที่อัปโหลดหรือแชร์โดยผู้ใช้';
$string['privacy:metadata:files:author'] = 'ผู้สร้างเนื้อหาของไฟล์';
$string['privacy:metadata:files:contenthash'] = 'ค่าแฮชของเนื้อหาไฟล์';
$string['privacy:metadata:files:filename'] = 'ชื่อไฟล์ในพื้นที่จัดเก็บไฟล์';
$string['privacy:metadata:files:filepath'] = 'พาธไปยังไฟล์ในพื้นที่จัดเก็บไฟล์';
$string['privacy:metadata:files:filesize'] = 'ขนาดของไฟล์';
$string['privacy:metadata:files:license'] = 'สัญญาอนุญาตของเนื้อหาไฟล์';
$string['privacy:metadata:files:mimetype'] = 'ประเภท MIME ของไฟล์';
$string['privacy:metadata:files:source'] = 'แหล่งที่มาของไฟล์';
$string['privacy:metadata:files:timecreated'] = 'เวลาที่สร้างไฟล์';
$string['privacy:metadata:files:timemodified'] = 'เวลาที่แก้ไขไฟล์ล่าสุด';
$string['privacy:metadata:files:userid'] = 'ผู้ใช้ที่สร้างไฟล์';
$string['privacy:metadata:core_userkey'] = 'มีการสร้างและจัดเก็บโทเคนส่วนตัว ซึ่งสามารถใช้เพื่อเข้าถึงไฟล์ Moodle โดยไม่ต้องเข้าสู่ระบบ';

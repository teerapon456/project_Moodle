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
 * Strings for component 'antivirus', language 'en'
 *
 * @package   core_antivirus
 * @copyright 2015 Ruslan Kabalin, Lancaster University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actantivirushdr'] = 'ปลั๊กอินป้องกันไวรัสที่มีอยู่';
$string['antiviruses'] = 'ปลั๊กอินป้องกันไวรัส';
$string['antiviruscommonsettings'] = 'การตั้งค่าป้องกันไวรัสทั่วไป';
$string['antivirussettings'] = 'จัดการปลั๊กอินป้องกันไวรัส';
$string['configantivirusplugins'] = 'กรุณาเลือกปลั๊กอินป้องกันไวรัสที่คุณต้องการใช้และจัดเรียงตามลำดับการใช้งาน.';
$string['datastream'] = 'ข้อมูล';
$string['dataerrordesc'] = 'เกิดข้อผิดพลาดในการสแกนข้อมูล.';
$string['dataerrorname'] = 'ข้อผิดพลาดของตัวสแกนข้อมูล';
$string['datainfecteddesc'] = 'ตรวจพบข้อมูลที่ติดไวรัส.';
$string['datainfectedname'] = 'ข้อมูลติดไวรัส';
$string['emailadditionalinfo'] = 'รายละเอียดเพิ่มเติมที่ส่งคืนจากเครื่องมือป้องกันไวรัส: ';
$string['emailauthor'] = 'อัปโหลดโดย: ';
$string['emailcontenthash'] = 'แฮชเนื้อหา: ';
$string['emailcontenttype'] = 'ประเภทเนื้อหา: ';
$string['emaildate'] = 'วันที่อัปโหลด: ';
$string['emailfilename'] = 'ชื่อไฟล์: ';
$string['emailfilesize'] = 'ขนาดไฟล์: ';
$string['emailgeoinfo'] = 'ตำแหน่งทางภูมิศาสตร์: ';
$string['emailinfectedfiledetected'] = 'ตรวจพบไฟล์ที่ติดไวรัส';
$string['emailipaddress'] = 'ที่อยู่ IP:';
$string['emailreferer'] = 'อ้างอิง: ';
$string['emailreport'] = 'รายงาน: ';
$string['emailscanner'] = 'ตัวสแกน: ';
$string['emailscannererrordetected'] = 'เกิดข้อผิดพลาดของตัวสแกน';
$string['emailsubject'] = '{$a} :: การแจ้งเตือนป้องกันไวรัส';
$string['enablequarantine_help'] = 'หากเปิดใช้งาน ไฟล์ใดๆ ที่ตรวจพบว่าเป็นไวรัสจะถูกวางในโฟลเดอร์กักกัน ([dataroot]/{$a}) เพื่อตรวจสอบในภายหลัง การอัปโหลดไปยัง Moodle จะล้มเหลว หากคุณมีระบบสแกนไวรัสระดับไฟล์ระบบใดๆ อยู่ โฟลเดอร์กักกันควรได้รับการยกเว้นจากการตรวจสอบป้องกันไวรัสเพื่อหลีกเลี่ยงการตรวจพบไฟล์ที่ถูกกักกัน.';
$string['enablequarantine'] = 'เปิดใช้งานการกักกัน';
$string['fileerrordesc'] = 'เกิดข้อผิดพลาดในการสแกนไฟล์.';
$string['fileerrorname'] = 'ข้อผิดพลาดของตัวสแกนไฟล์';
$string['fileinfecteddesc'] = 'ตรวจพบไฟล์ที่ติดไวรัส.';
$string['fileinfectedname'] = 'ไฟล์ติดไวรัส';
$string['notifyemail_help'] = 'ที่อยู่อีเมลสำหรับการแจ้งเตือนเมื่อตรวจพบไวรัส หากเว้นว่างไว้ ผู้ดูแลไซต์ทั้งหมดจะได้รับการแจ้งเตือน.';
$string['notifyemail'] = 'อีเมลแจ้งเตือนการเตือนป้องกันไวรัส';
$string['notifylevel_help'] = 'ระดับข้อมูลที่แตกต่างกันที่คุณต้องการรับการแจ้งเตือน';
$string['notifylevel'] = 'ระดับการแจ้งเตือน';
$string['notifylevelfound'] = 'เฉพาะภัยคุกคามที่ตรวจพบ';
$string['notifylevelerror'] = 'ภัยคุกคามที่ตรวจพบและข้อผิดพลาดของตัวสแกน';
$string['privacy:metadata'] = 'ระบบป้องกันไวรัสไม่เก็บข้อมูลส่วนบุคคลใดๆ.';
$string['quarantinedfiles'] = 'ไฟล์ที่ถูกกักกันโดยระบบป้องกันไวรัส';
$string['quarantinedisabled'] = 'การกักกันถูกปิดใช้งาน ไฟล์ไม่ถูกเก็บ.';
$string['quarantinetime_desc'] = 'ไฟล์ที่ถูกกักกันที่มีอายุเก่ากว่าช่วงเวลาที่กำหนดจะถูกลบ.';
$string['quarantinetime'] = 'เวลากักกันสูงสุด';
$string['threshold_desc'] = 'ตรวจสอบย้อนหลังเท่าไรเพื่อหาผลลัพธ์ก่อนหน้านี้สำหรับข้อผิดพลาด ฯลฯ ตามที่รายงานใน {$a}.';
$string['threshold'] = 'เกณฑ์สำหรับการตรวจสอบสถานะ';
$string['taskcleanup'] = 'ล้างไฟล์ที่ถูกกักกัน.';
$string['unknown'] = 'ไม่รู้จัก';
$string['virusfound'] = '{$a->item} ได้ถูกสแกนโดยตัวตรวจสอบไวรัสและพบว่าติดไวรัส!';


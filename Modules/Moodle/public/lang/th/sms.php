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
 * Strings for component 'sms', language 'en'
 *
 * @package    core
 * @category   string
 * @copyright  2024 Andrew Lyons <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['actions'] = 'การดำเนินการ';
$string['countrycode'] = 'รหัสประเทศเริ่มต้น';
$string['countrycode_help'] = 'รหัสประเทศที่จะเพิ่มลงในหมายเลขโทรศัพท์หากผู้ใช้ไม่ได้ระบุรหัสประเทศเอง ให้ระบุตัวเลขโดยไม่ต้องมีเครื่องหมาย \'+\' นำหน้า';
$string['createnewgateway'] = 'สร้างเกตเวย์ SMS ใหม่';
$string['delete_sms_gateway'] = 'ลบเกตเวย์ SMS';
$string['delete_sms_gateway_confirmation'] = 'การดำเนินการนี้จะลบเกตเวย์ SMS {$a->gateway}';
$string['edit_sms_gateway'] = 'แก้ไขเกตเวย์ SMS {$a->gateway}';
$string['gateway'] = 'เกตเวย์';
$string['gateway_name'] = 'ชื่อเกตเวย์';
$string['manage_sms_gateways'] = 'จัดการเกตเวย์ SMS';
$string['phonenumbernotvalid'] = 'ไม่รู้จักรูปแบบของหมายเลขโทรศัพท์: {$a->message}';
$string['privacy:metadata:sms_messages'] = 'จัดเก็บข้อความที่ส่งผ่าน SMS';
$string['privacy:metadata:sms_messages:content'] = 'ข้อความ';
$string['privacy:metadata:sms_messages:id'] = 'ไอดีของข้อความ';
$string['privacy:metadata:sms_messages:recipient'] = 'หมายเลขโทรศัพท์ที่ส่งข้อความถึง';
$string['privacy:metadata:sms_messages:recipientuserid'] = 'ผู้ใช้ที่ได้รับข้อความ (หากทราบ)';
$string['privacy:metadata:sms_messages:status'] = 'สถานะของข้อความ';
$string['privacy:metadata:sms_messages:timecreated'] = 'เวลาที่สร้างข้อความ';
$string['privacy:sms:sensitive_not_shown'] = 'เนื้อหาของข้อความนี้ไม่ได้รับการจัดเก็บเนื่องจากระบุว่ามีเนื้อหาที่ละเอียดอ่อน';
$string['select_sms_gateways'] = 'ผู้ให้บริการเกตเวย์ SMS';
$string['sms'] = 'SMS';
$string['status:gateway_failed'] = 'เกตเวย์ส่งข้อความไม่สำเร็จ';
$string['status:gateway_not_available'] = 'เกตเวย์ไม่พร้อมสำหรับการส่งข้อความ';
$string['status:gateway_queued'] = 'ข้อความอยู่ในคิวรอการส่งโดยเกตเวย์';
$string['status:gateway_rejected'] = 'เกตเวย์ปฏิเสธข้อความ';
$string['status:gateway_sent'] = 'ข้อความถูกส่งโดยเกตเวย์แล้ว';
$string['status:message_over_size'] = 'ข้อความมีขนาดใหญ่เกินกว่าที่เกตเวย์จะส่งได้';
$string['status:unknown'] = 'ไม่สามารถระบุสถานะของข้อความได้';
$string['sms_gateway_deleted'] = 'ลบเกตเวย์ SMS {$a->gateway} แล้ว';
$string['sms_gateway_delete_failed'] = 'ไม่สามารถลบเกตเวย์ SMS {$a->gateway} ได้ เนื่องจากเกตเวย์กำลังถูกใช้งานอยู่หรือมีปัญหาเกี่ยวกับฐานข้อมูล โปรดตรวจสอบว่าเกตเวย์ยังทำงานอยู่หรือไม่ หรือติดต่อผู้ดูแลระบบฐานข้อมูลเพื่อขอความช่วยเหลือ';
$string['sms_gateway_disable_failed'] = 'ไม่สามารถปิดใช้งานเกตเวย์ SMS ได้ เนื่องจากเกตเวย์กำลังถูกใช้งานอยู่หรือมีปัญหาเกี่ยวกับฐานข้อมูล โปรดตรวจสอบว่าเกตเวย์ยังทำงานอยู่หรือไม่ หรือติดต่อผู้ดูแลระบบฐานข้อมูลเพื่อขอความช่วยเหลือ';
$string['sms_gateways'] = 'เกตเวย์ SMS';
$string['sms_gateways_info'] = 'สร้างและจัดการเกตเวย์ SMS เพื่อส่งข้อความ SMS จากไซต์ของคุณ';

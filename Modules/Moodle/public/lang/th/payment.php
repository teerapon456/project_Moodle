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
 * Strings for component 'payment', language 'en'
 *
 * @package   core_payment
 * @copyright 2019 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accountarchived'] = 'เก็บถาวรแล้ว';
$string['accountdeleteconfirm'] = 'หากบัญชีนี้มีรายการชำระเงินเดิมอยู่ บัญชีจะถูกเก็บถาวร มิฉะนั้นข้อมูลการตั้งค่าจะถูกลบอย่างถาวร ยืนยันว่าต้องการดำเนินการต่อหรือไม่?';
$string['accountconfignote'] = 'ช่องทางการชำระเงินสำหรับบัญชีนี้จะถูกตั้งค่าแยกกัน';
$string['accountidnumber'] = 'หมายเลขประจำตัว (ID number)';
$string['accountidnumber_help'] = 'หมายเลขประจำตัวนี้ใช้สำหรับตรวจสอบกับระบบภายนอกเท่านั้น และจะไม่แสดงที่ใดบนเว็บไซต์ หากบัญชีมีชื่อรหัสที่เป็นทางการสามารถระบุได้ มิฉะนั้นสามารถปล่อยว่างไว้ได้';
$string['accountname'] = 'ชื่อบัญชี';
$string['accountname_help'] = 'ชื่อที่ใช้อ้างอิงสำหรับอาจารย์หรือผู้จัดการที่จะตั้งค่าการชำระเงิน (ตัวอย่างเช่น ในปลั๊กอินการลงทะเบียนคอร์สเรียน)';
$string['accountnotavailable'] = 'ไม่พร้อมใช้งาน';
$string['paymentaccountsexplained'] = 'สร้างบัญชีการชำระเงินอย่างน้อยหนึ่งบัญชีสำหรับไซต์นี้ แต่ละบัญชีจะประกอบด้วยการตั้งค่าสำหรับช่องทางการชำระเงินที่มีให้เลือกใช้งาน ผู้ที่ตั้งค่าการชำระเงินในไซต์ (เช่น การชำระเงินเพื่อลงทะเบียนคอร์สเรียน) จะสามารถเลือกจากบัญชีที่มีให้ใช้งานได้';
$string['createaccount'] = 'สร้างบัญชีการชำระเงิน';
$string['deleteorarchive'] = 'ลบหรือเก็บถาวร';
$string['editpaymentaccount'] = 'แก้ไขบัญชีการชำระเงิน';
$string['eventaccountcreated'] = 'สร้างบัญชีการชำระเงินแล้ว';
$string['eventaccountdeleted'] = 'ลบบัญชีการชำระเงินแล้ว';
$string['eventaccountupdated'] = 'อัปเดตบัญชีการชำระเงินแล้ว';
$string['feeincludesurcharge'] = '{$a->fee} (รวมค่าธรรมเนียมเพิ่มเติม {$a->surcharge}% สำหรับการชำระเงินประเภทนี้)';
$string['gatewaycannotbeenabled'] = 'ไม่สามารถเปิดใช้งานช่องทางการชำระเงินได้เนื่องจากการตั้งค่าไม่สมบูรณ์';
$string['gatewaydisabled'] = 'ปิดใช้งาน';
$string['gatewayenabled'] = 'เปิดใช้งาน';
$string['gatewaynotfound'] = 'ไม่พบช่องทางการชำระเงิน';
$string['gotomanageplugins'] = 'เปิดหรือปิดใช้งานช่องทางการชำระเงิน และตั้งค่าค่าธรรมเนียมเพิ่มเติมผ่านทาง {$a}';
$string['gotopaymentaccounts'] = 'สร้างบัญชีการชำระเงินโดยใช้ช่องทางใดก็ได้เหล่านี้ใน {$a}';
$string['hidearchived'] = 'ซ่อนรายการที่เก็บถาวร';
$string['noaccountsavilable'] = 'ไม่มีบัญชีการชำระเงินให้ใช้งาน';
$string['nocurrencysupported'] = 'ไม่รองรับการชำระเงินในสกุลเงินใดๆ โปรดตรวจสอบให้แน่ใจว่าได้เปิดใช้งานช่องทางการชำระเงินไว้อย่างน้อยหนึ่งช่องทาง';
$string['nogateway'] = 'ไม่มีช่องทางการชำระเงินที่สามารถใช้งานได้';
$string['nogatewayselected'] = 'คุณต้องเลือกช่องทางการชำระเงินก่อน';
$string['payments'] = 'การชำระเงิน';
$string['paymentaccount'] = 'บัญชีการชำระเงิน';
$string['paymentaccounts'] = 'บัญชีการชำระเงิน';
$string['privacy:metadata:database:payments'] = 'ข้อมูลเกี่ยวกับการชำระเงิน';
$string['privacy:metadata:database:payments:amount'] = 'ยอดเงินที่ชำระ';
$string['privacy:metadata:database:payments:currency'] = 'สกุลเงินที่ชำระ';
$string['privacy:metadata:database:payments:gateway'] = 'ช่องทางการชำระเงินที่ใช้';
$string['privacy:metadata:database:payments:timecreated'] = 'เวลาที่มีการชำระเงิน';
$string['privacy:metadata:database:payments:timemodified'] = 'เวลาที่มีการอัปเดตบันทึกการชำระเงินล่าสุด';
$string['privacy:metadata:database:payments:userid'] = 'ผู้ใช้ที่ทำการชำระเงิน';
$string['restoreaccount'] = 'กู้คืน';
$string['selectpaymenttype'] = 'เลือกประเภทการชำระเงิน';
$string['showarchived'] = 'แสดงรายการที่เก็บถาวร';
$string['supportedcurrencies'] = 'สกุลเงินที่รองรับ';
$string['surcharge'] = 'ค่าธรรมเนียมเพิ่มเติม (เปอร์เซ็นต์)';
$string['surcharge_desc'] = 'ค่าธรรมเนียมเพิ่มเติมคือเปอร์เซ็นต์ที่เรียกเก็บเพิ่มจากผู้ใช้ที่เลือกชำระเงินผ่านช่องทางนี้';

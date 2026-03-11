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
 * Strings for component 'core_webservice', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core_webservice
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accessexception'] = 'ข้อยกเว้นการควบคุมการเข้าถึง';
$string['actwebserviceshhdr'] = 'โปรโตคอลเว็บเซอร์วิสที่ใช้งานอยู่';
$string['addaservice'] = 'เพิ่มบริการ';
$string['addcapabilitytousers'] = 'ตรวจสอบสิทธิ์ของผู้ใช้';
$string['addcapabilitytousersdescription'] = 'ผู้ใช้ควรมีสิทธิ์สองอย่างคือ - webservice:createtoken และสิทธิ์ที่ตรงกับโปรโตคอลที่ใช้ เช่น webservice/rest:use, webservice/soap:use เพื่อให้บรรลุเป้าหมายนี้ ให้สร้างบทบาทเว็บเซอร์วิสที่มีสิทธิ์ที่เหมาะสมและมอบหมายให้กับผู้ใช้เว็บเซอร์วิสในฐานะบทบาทระบบ';
$string['addexternalservice'] = 'เพิ่มบริการภายนอก';
$string['addfunction'] = 'เพิ่มฟังก์ชัน';
$string['addfunctionhelp'] = 'เลือกฟังก์ชันที่จะเพิ่มในบริการ';
$string['addfunctions'] = 'เพิ่มฟังก์ชัน';
$string['addfunctionsdescription'] = 'เลือกฟังก์ชันที่จำเป็นสำหรับบริการที่สร้างขึ้นใหม่';
$string['addrequiredcapability'] = 'มอบหมาย/ยกเลิกการมอบหมายสิทธิ์ที่จำเป็น';
$string['addservice'] = 'เพิ่มบริการใหม่: {$a->name} (id: {$a->id})';
$string['addservicefunction'] = 'เพิ่มฟังก์ชันลงในบริการ "{$a}"';
$string['allusers'] = 'ผู้ใช้ทั้งหมด';
$string['apiexplorer'] = 'ตัวสำรวจ API';
$string['apiexplorernotavalaible'] = 'ตัวสำรวจ API ยังไม่พร้อมใช้งาน';
$string['arguments'] = 'อาร์กิวเมนต์';
$string['authmethod'] = 'วิธีการยืนยันตัวตน';
$string['callablefromajax'] = 'สามารถเรียกใช้งานจาก AJAX';
$string['cannotcreatetoken'] = 'ไม่มีสิทธิ์ในการสร้างโทเค็นเว็บเซอร์วิสสำหรับบริการ {$a}';
$string['configwebserviceplugins'] = 'เพื่อความปลอดภัย ควรเปิดใช้งานเฉพาะโปรโตคอลที่ใช้งานจริงเท่านั้น';
$string['context'] = 'บริบท';
$string['createservicedescription'] = 'บริการคือกลุ่มของฟังก์ชันเว็บเซอร์วิส คุณจะอนุญาตให้ผู้ใช้เข้าถึงบริการใหม่ ในหน้า <strong>เพิ่มบริการ</strong> ให้เลือกช่อง \'เปิดใช้งาน\' และ \'ผู้ใช้ที่ได้รับอนุญาต\' และเลือก \'ไม่ต้องมีสิทธิ์พิเศษ\'';
$string['createserviceforusersdescription'] = 'บริการคือกลุ่มของฟังก์ชันเว็บเซอร์วิส คุณจะอนุญาตให้ผู้ใช้เข้าถึงบริการใหม่ ในหน้า <strong>เพิ่มบริการ</strong> ให้เลือกช่อง \'เปิดใช้งาน\' และไม่เลือกช่อง \'ผู้ใช้ที่ได้รับอนุญาต\' และเลือก \'ไม่ต้องมีสิทธิ์พิเศษ\'';
$string['createtoken'] = 'สร้างโทเค็น';
$string['createtokenforuser'] = 'สร้างโทเค็นสำหรับผู้ใช้';
$string['createtokenforuserdescription'] = 'สร้างโทเค็นสำหรับผู้ใช้เว็บเซอร์วิส';
$string['createuser'] = 'สร้างผู้ใช้เฉพาะ';
$string['createuserdescription'] = 'จำเป็นต้องมีผู้ใช้เว็บเซอร์วิสเพื่อเป็นตัวแทนของระบบที่ควบคุม Moodle';
$string['criteriaerror'] = 'ไม่มีสิทธิ์ในการค้นหาตามเกณฑ์';
$string['default'] = 'ค่าเริ่มต้นเป็น "{$a}"';
$string['deleteaservice'] = 'ลบบริการ';
$string['deleteservice'] = 'ลบบริการ: {$a->name} (id: {$a->id})';
$string['deleteserviceconfirm'] = 'การลบบริการจะลบโทเค็นที่เกี่ยวข้องกับบริการนี้ด้วย คุณต้องการลบบริการภายนอก "{$a}" ใช่หรือไม่?';
$string['deletetoken'] = 'ลบโทเค็น';
$string['deletetokenconfirm'] = 'คุณต้องการลบโทเค็นเว็บเซอร์วิสของ <strong>{$a->user}</strong> ในบริการ <strong>{$a->service}</strong> ใช่หรือไม่?';
$string['deprecated'] = 'เลิกใช้งานแล้ว';
$string['disabledwarning'] = 'โปรโตคอลเว็บเซอร์วิสทั้งหมดถูกปิดใช้งาน การตั้งค่า "เปิดใช้งานเว็บเซอร์วิส" สามารถพบได้ในฟีเจอร์ขั้นสูง';
$string['doc'] = 'เอกสารประกอบ';
$string['docaccessrefused'] = 'คุณไม่ได้รับอนุญาตให้ดูเอกสารประกอบสำหรับโทเค็นนี้';
$string['downloadfiles'] = 'สามารถดาวน์โหลดไฟล์ได้';
$string['downloadfiles_help'] = 'หากเปิดใช้งาน ผู้ใช้ทุกคนสามารถดาวน์โหลดไฟล์ด้วยกุญแจความปลอดภัยของตนเองได้ แน่นอนว่าพวกเขาจะถูกจำกัดเฉพาะไฟล์ที่ได้รับอนุญาตให้ดาวน์โหลดในไซต์';
$string['editaservice'] = 'แก้ไขบริการ';
$string['editexternalservice'] = 'แก้ไขบริการภายนอก';
$string['editservice'] = 'แก้ไขบริการ: {$a->name} (id: {$a->id})';
$string['enabled'] = 'เปิดใช้งาน';
$string['enabledocumentation'] = 'เปิดใช้งานเอกสารประกอบสำหรับนักพัฒนา';
$string['enabledocumentationdescription'] = 'เอกสารประกอบเว็บเซอร์วิสโดยละเอียดมีให้สำหรับโปรโตคอลที่เปิดใช้งาน';
$string['enableprotocols'] = 'เปิดใช้งานโปรโตคอล';
$string['enableprotocolsdescription'] = 'ควรเปิดใช้งานอย่างน้อยหนึ่งโปรโตคอล เพื่อความปลอดภัย ควรเปิดใช้งานเฉพาะโปรโตคอลที่ใช้งานจริงเท่านั้น';
$string['enablews'] = 'เปิดใช้งานเว็บเซอร์วิส';
$string['enablewsdescription'] = 'ต้องเปิดใช้งานเว็บเซอร์วิสในฟีเจอร์ขั้นสูง';
$string['entertoken'] = 'ระบุกุญแจความปลอดภัย/โทเค็น:';
$string['error'] = 'ข้อผิดพลาด: {$a}';
$string['errorcatcontextnotvalid'] = 'คุณไม่สามารถเรียกใช้งานฟังก์ชันในบริบทของหมวดหมู่ได้ (ไอดีหมวดหมู่:{$a->catid}) ข้อความแสดงข้อผิดพลาดของบริบทคือ: {$a->message}';
$string['errorcodes'] = 'ข้อความแสดงข้อผิดพลาด';
$string['errorcoursecontextnotvalid'] = 'คุณไม่สามารถเรียกใช้งานฟังก์ชันในบริบทของรายวิชาได้ (ไอดีรายวิชา:{$a->courseid}) ข้อความแสดงข้อผิดพลาดของบริบทคือ: {$a->message}';
$string['errorinvalidparam'] = 'พารามิเตอร์ "{$a}" ไม่ถูกต้อง';
$string['errornotemptydefaultparamarray'] = 'พารามิเตอร์รายละเอียดเว็บเซอร์วิสที่ชื่อ \'{$a}\' เป็นโครงสร้างเดี่ยวหรือหลายโครงสร้าง ค่าเริ่มต้นสามารถเป็นอาร์เรย์ว่างได้เท่านั้น ตรวจสอบรายละเอียดเว็บเซอร์วิส';
$string['erroroptionalparamarray'] = 'พารามิเตอร์รายละเอียดเว็บเซอร์วิสที่ชื่อ \'{$a}\' เป็นโครงสร้างเดี่ยวหรือหลายโครงสร้าง ไม่สามารถตั้งค่าเป็น VALUE_OPTIONAL ได้ ตรวจสอบรายละเอียดเว็บเซอร์วิส';
$string['eventwebservicefunctioncalled'] = 'เรียกใช้งานฟังก์ชันเว็บเซอร์วิส';
$string['eventwebserviceloginfailed'] = 'การเข้าสู่ระบบเว็บเซอร์วิสล้มเหลว';
$string['eventwebserviceservicecreated'] = 'สร้างเว็บเซอร์วิสแล้ว';
$string['eventwebserviceservicedeleted'] = 'ลบเว็บเซอร์วิสแล้ว';
$string['eventwebserviceserviceupdated'] = 'อัปเดตเว็บเซอร์วิสแล้ว';
$string['eventwebserviceserviceuseradded'] = 'เพิ่มผู้ใช้เว็บเซอร์วิสแล้ว';
$string['eventwebserviceserviceuserremoved'] = 'ลบผู้ใช้เว็บเซอร์วิสแล้ว';
$string['eventwebservicetokencreated'] = 'สร้างโทเค็นเว็บเซอร์วิสแล้ว';
$string['eventwebservicetokensent'] = 'ส่งโทเค็นเว็บเซอร์วิสแล้ว';
$string['execute'] = 'ดำเนินการ';
$string['executewarnign'] = 'คำเตือน: หากคุณกดดำเนินการ ฐานข้อมูลของคุณจะถูกแก้ไขและการเปลี่ยนแปลงไม่สามารถย้อนกลับได้โดยอัตโนมัติ!';
$string['externalservice'] = 'บริการภายนอก';
$string['externalservicefunctions'] = 'ฟังก์ชันบริการภายนอก';
$string['externalservices'] = 'บริการภายนอก';
$string['externalserviceusers'] = 'ผู้ใช้บริการภายนอก';
$string['failedtolog'] = 'การบันทึกล็อกล้มเหลว';
$string['filenameexist'] = 'มีชื่อไฟล์นี้อยู่แล้ว: {$a}';
$string['forbiddenwsuser'] = 'ไม่สามารถสร้างโทเค็นสำหรับผู้ใช้ที่ยังไม่ได้รับการยืนยัน ถูกลบ ถูกระงับ หรือผู้ใช้ทั่วไป';
$string['function'] = 'ฟังก์ชัน';
$string['functions'] = 'ฟังก์ชัน';
$string['generalstructure'] = 'โครงสร้างทั่วไป';
$string['checkusercapability'] = 'ตรวจสอบสิทธิ์ของผู้ใช้';
$string['checkusercapabilitydescription'] = 'ผู้ใช้ควรมีสิทธิ์ที่เหมาะสมตามโปรโตคอลที่ใช้ เช่น webservice/rest:use, webservice/soap:use เพื่อให้บรรลุเป้าหมายนี้ ให้สร้างบทบาทเว็บเซอร์วิสที่มีสิทธิ์โปรโตคอลที่ได้รับอนุญาตและมอบหมายให้กับผู้ใช้เว็บเซอร์วิสในฐานะบทบาทระบบ';
$string['information'] = 'ข้อมูล';
$string['installserviceshortnameerror'] = 'ข้อผิดพลาดในการเขียนโค้ด: ชื่อย่อบริการ "{$a}" สามารถประกอบด้วยตัวอักษรและตัวเลข ขีดล่าง (_) ขีดคั่น (-) หรือจุด (.) เท่านั้น';
$string['installexistingserviceshortnameerror'] = 'มีเว็บเซอร์วิสที่มีชื่อย่อ "{$a}" อยู่แล้ว ไม่สามารถติดตั้ง/อัปเดตเว็บเซอร์วิสอื่นด้วยชื่อย่อนี้ได้';
$string['invalidextparam'] = 'พารามิเตอร์ API ภายนอกไม่ถูกต้อง: {$a}';
$string['invalidextresponse'] = 'การตอบกลับ API ภายนอกไม่ถูกต้อง: {$a}';
$string['invalidiptoken'] = 'โทเค็นไม่ถูกต้อง - ไม่รองรับ IP ของคุณ';
$string['invalidtimedtoken'] = 'โทเค็นไม่ถูกต้อง - โทเค็นหมดอายุ';
$string['invalidtoken'] = 'โทเค็นไม่ถูกต้อง - ไม่พบโทเค็น';
$string['iprestriction'] = 'การจำกัด IP';
$string['iprestriction_help'] = 'ผู้ใช้จะต้องเรียกใช้งานเว็บเซอร์วิสจาก IP ที่ระบุ (คั่นด้วยจุลภาค)';
$string['key'] = 'กุญแจ';
$string['keyshelp'] = 'กุญแจถูกใช้เพื่อเข้าถึงบัญชี Moodle ของคุณจากแอปพลิเคชันภายนอก';
$string['loginrequired'] = 'จำกัดเฉพาะผู้ใช้ที่เข้าสู่ระบบแล้ว';
$string['manageprotocols'] = 'จัดการโปรโตคอล';
$string['managetokens'] = 'จัดการโทเค็น';
$string['missingcaps'] = 'สิทธิ์ที่ขาดหายไป';
$string['missingcaps_help'] = 'รายการสิทธิ์ที่ระบุโดยบริการที่ผู้ใช้ไม่มี ฟังก์ชันการทำงานบางอย่างของบริการอาจไม่พร้อมใช้งานหากไม่มีสิทธิ์เหล่านี้';
$string['missingpassword'] = 'รหัสผ่านขาดหายไป';
$string['missingrequiredcapability'] = 'จำเป็นต้องมีสิทธิ์ {$a}';
$string['missingusername'] = 'ชื่อผู้ใช้ขาดหายไป';
$string['nameexists'] = 'ชื่อนี้ถูกใช้งานโดยบริการอื่นแล้ว';
$string['nocapabilitytouseparameter'] = 'ผู้ใช้ไม่มีสิทธิ์ที่จำเป็นในการใช้พารามิเตอร์ {$a}';
$string['nofunctions'] = 'บริการนี้ไม่มีฟังก์ชัน';
$string['norequiredcapability'] = 'ไม่ต้องมีสิทธิ์พิเศษ';
$string['notoken'] = 'รายการโทเค็นว่างเปล่า';
$string['onesystemcontrolling'] = 'อนุญาตให้ระบบภายนอกควบคุม Moodle';
$string['onesystemcontrollingdescription'] = 'ขั้นตอนต่อไปนี้จะช่วยให้คุณตั้งค่าเว็บเซอร์วิสของ Moodle เพื่ออนุญาตให้ระบบภายนอกโต้ตอบกับ Moodle ได้ ซึ่งรวมถึงการตั้งค่าวิธีการยืนยันตัวตนด้วยโทเค็น (กุญแจความปลอดภัย)';
$string['onlyseecreatedtokens'] = 'คุณสามารถดูได้เฉพาะโทเค็นที่คุณสร้างขึ้นเท่านั้น';
$string['operation'] = 'การดำเนินการ';
$string['optional'] = 'ไม่บังคับ';
$string['passwordisexpired'] = 'รหัสผ่านหมดอายุแล้ว';
$string['phpparam'] = 'XML-RPC (โครงสร้าง PHP)';
$string['phpresponse'] = 'XML-RPC (โครงสร้าง PHP)';
$string['postrestparam'] = 'โค้ด PHP สำหรับ REST (POST request)';
$string['potusers'] = 'ผู้ใช้ที่ไม่ได้รับอนุญาต';
$string['potusersmatching'] = 'ผู้ใช้ที่ไม่ได้รับอนุญาตที่ตรงกับ';
$string['print'] = 'พิมพ์ทั้งหมด';
$string['privacy:metadata'] = 'WebService API ไม่ได้จัดเก็บข้อมูลใดๆ';
$string['protocol'] = 'โปรโตคอล';
$string['removefunction'] = 'นำออก';
$string['removefunctionconfirm'] = 'คุณต้องการนำฟังก์ชัน "{$a->function}" ออกจากบริการ "{$a->service}" ใช่หรือไม่?';
$string['requireauthentication'] = 'วิธีนี้ต้องการการยืนยันตัวตนด้วยสิทธิ์ xxx';
$string['required'] = 'จำเป็น';
$string['requiredcapability'] = 'สิทธิ์ที่จำเป็น';
$string['requiredcapability_help'] = 'หากตั้งค่าไว้ เฉพาะผู้ใช้ที่มีสิทธิ์ตามที่กำหนดเท่านั้นที่สามารถเข้าถึงบริการได้';
$string['requiredcaps'] = 'สิทธิ์ที่จำเป็น';
$string['resettokencomplete'] = 'รีเซ็ตโทเค็นที่เลือกแล้ว';
$string['resettokenconfirm'] = 'คุณต้องการรีเซ็ตกุญแจเว็บเซอร์วิสนี้สำหรับ <strong>{$a->user}</strong> ในบริการ <strong>{$a->service}</strong> ใช่หรือไม่?';
$string['resettokenconfirmsimple'] = 'คุณต้องการรีเซ็ตกุญแจนี้ใช่หรือไม่? ลิงก์ที่บันทึกไว้ซึ่งมีกุญแจเก่าจะไม่สามารถใช้งานได้อีกต่อไป';
$string['response'] = 'การตอบกลับ';
$string['restcode'] = 'REST';
$string['restexception'] = 'ข้อยกเว้น REST';
$string['restparam'] = 'REST (พารามิเตอร์ POST)';
$string['restrictedusers'] = 'เฉพาะผู้ใช้ที่ได้รับอนุญาตเท่านั้น';
$string['restrictedusers_help'] = 'การตั้งค่านี้กำหนดว่าผู้ใช้ทุกคนที่มีสิทธิ์สร้างโทเค็นเว็บเซอร์วิสจะสามารถสร้างโทเค็นสำหรับบริการนี้ผ่านหน้ากุญแจความปลอดภัยของตนเองได้หรือไม่ หรือเฉพาะผู้ใช้ที่ได้รับอนุญาตเท่านั้นที่สามารถทำได้';
$string['restoredaccountresetpassword'] = 'บัญชีที่ได้รับการกู้คืนจำเป็นต้องรีเซ็ตรหัสผ่านก่อนรับโทเค็น';
$string['securitykey'] = 'กุญแจความปลอดภัย (โทเค็น)';
$string['securitykeys'] = 'กุญแจความปลอดภัย';
$string['selectauthorisedusers'] = 'เลือกผู้ใช้ที่ได้รับอนุญาต';
$string['selectedcapability'] = 'ที่เลือก';
$string['selectedcapabilitydoesntexit'] = 'สิทธิ์ที่จำเป็นที่ตั้งไว้ในปัจจุบัน ({$a}) ไม่มีอยู่อีกต่อไป โปรดเปลี่ยนและบันทึกการเปลี่ยนแปลง';
$string['selectservice'] = 'เลือกบริการ';
$string['selectspecificuser'] = 'เลือกผู้ใช้เฉพาะ';
$string['selectspecificuserdescription'] = 'เพิ่มผู้ใช้เว็บเซอร์วิสเป็นผู้ใช้ที่ได้รับอนุญาต';
$string['service'] = 'บริการ';
$string['servicehelpexplanation'] = 'บริการคือกลุ่มของฟังก์ชัน บริการสามารถเข้าถึงได้โดยผู้ใช้ทุกคนหรือเฉพาะผู้ใช้ที่ระบุเท่านั้น';
$string['servicename'] = 'ชื่อบริการ';
$string['servicenotavailable'] = 'เว็บเซอร์วิสไม่พร้อมใช้งาน (ไม่มีอยู่หรืออาจถูกปิดใช้งาน)';
$string['servicerequireslogin'] = 'เว็บเซอร์วิสไม่พร้อมใช้งาน (ออกจากระบบแล้วหรือเซสชันหมดอายุ)';
$string['servicesbuiltin'] = 'บริการที่มีมาให้';
$string['servicescustom'] = 'บริการที่กำหนดเอง';
$string['serviceusers'] = 'ผู้ใช้ที่ได้รับอนุญาต';
$string['serviceusersettings'] = 'การตั้งค่าผู้ใช้';
$string['serviceusersmatching'] = 'ผู้ใช้ที่ได้รับอนุญาตที่ตรงกับ';
$string['serviceuserssettings'] = 'เปลี่ยนการตั้งค่าสำหรับผู้ใช้ที่ได้รับอนุญาต';
$string['shortnametaken'] = 'ชื่อย่อถูกใช้โดยบริการอื่นแล้ว ({$a})';
$string['simpleauthlog'] = 'การเข้าสู่ระบบแบบยืนยันตัวตนอย่างง่าย';
$string['step'] = 'ขั้นตอน';
$string['supplyinfo'] = 'รายละเอียดเพิ่มเติม';
$string['testauserwithtestclientdescription'] = 'จำลองการเข้าถึงบริการจากภายนอกโดยใช้ตัวทดสอบเว็บเซอร์วิส ก่อนดำเนินการดังกล่าว ให้เข้าสู่ระบบในฐานะผู้ใช้ที่มีสิทธิ์ moodle/webservice:createtoken และขอรับกุญแจความปลอดภัย (โทเค็น) ผ่านหน้าการตั้งค่าของผู้ใช้ คุณจะใช้โทเค็นนี้ในตัวทดสอบ ในตัวทดสอบให้เลือกโปรโตคอลที่เปิดใช้งานด้วยการยืนยันตัวตนด้วยโทเค็น <strong>คำเตือน: ฟังก์ชันที่คุณทดสอบจะถูกดำเนินการจริงสำหรับผู้ใช้รายนี้ ดังนั้นโปรดใช้ความระมัดระวังในการเลือกทดสอบ!</strong>';
$string['testclient'] = 'ตัวทดสอบเว็บเซอร์วิส';
$string['testclientdescription'] = '* ตัวทดสอบเว็บเซอร์วิส <strong>ดำเนินการจริง</strong> กับฟังก์ชันต่างๆ อย่าทดสอบฟังก์ชันที่คุณไม่รู้จัก <br/>* ฟังก์ชันเว็บเซอร์วิสที่มีอยู่ทั้งหมดอาจยังไม่ได้ถูกนำมาใช้ในตัวทดสอบ <br/>* หากต้องการตรวจสอบว่าผู้ใช้ไม่สามารถเข้าถึงบางฟังก์ชันได้ คุณสามารถทดสอบฟังก์ชันบางอย่างที่คุณไม่อนุญาตได้ <br/>* หากต้องการดูข้อความแสดงข้อผิดพลาดที่ชัดเจนยิ่งขึ้น ให้ตั้งค่าการดีบักเป็น <strong>{$a->mode}</strong> ใน {$a->atag}';
$string['testwithtestclient'] = 'ทดสอบบริการ';
$string['testwithtestclientdescription'] = 'จำลองการเข้าถึงบริการจากภายนอกโดยใช้ตัวทดสอบเว็บเซอร์วิส ใช้โปรโตคอลที่เปิดใช้งานพร้อมกับการยืนยันตัวตนด้วยโทเค็น <strong>คำเตือน: ฟังก์ชันที่คุณทดสอบจะถูกดำเนินการจริง ดังนั้นโปรดใช้ความระมัดระวังในการเลือกทดสอบ!</strong>';
$string['token'] = 'โทเค็น';
$string['tokenauthlog'] = 'การยืนยันตัวตนด้วยโทเค็น';
$string['tokencopied'] = 'คัดลอกข้อความลงในคลิปบอร์ดแล้ว';
$string['tokencreatedbyadmin'] = 'สามารถรีเซ็ตได้โดยผู้ดูแลระบบเท่านั้น (*)';
$string['tokencreator'] = 'ผู้สร้าง';
$string['tokenfilter'] = 'ตัวกรองโทเค็น';
$string['tokenfiltersubmit'] = 'แสดงเฉพาะโทเค็นที่ตรงกัน';
$string['tokenfilterreset'] = 'แสดงโทเค็นทั้งหมด';
$string['tokenname'] = 'ชื่อ';
$string['tokennamehint'] = 'หากคุณไม่ระบุชื่อ ระบบจะสุ่มชื่อให้';
$string['tokennameprefix'] = 'Webservice-{$a}';
$string['tokennewmessage'] = 'คัดลอกโทเค็นตอนนี้ ระบบจะไม่แสดงอีกเมื่อคุณออกจากหน้านี้';
$string['unknownoptionkey'] = 'คีย์ตัวเลือกที่ไม่รู้จัก ({$a})';
$string['unnamedstringparam'] = 'พารามิเตอร์สตริงที่ไม่ได้ระบุชื่อ';
$string['updateusersettings'] = 'อัปเดต';
$string['uploadfiles'] = 'สามารถอัปโหลดไฟล์ได้';
$string['uploadfiles_help'] = 'หากเปิดใช้งาน ผู้ใช้ทุกคนสามารถอัปโหลดไฟล์ด้วยกุญแจความปลอดภัยของตนเองไปยังพื้นที่ไฟล์ส่วนตัวหรือพื้นที่ไฟล์ร่างได้ โดยจะเป็นไปตามข้อกำหนดความจุไฟล์ของผู้ใช้';
$string['userasclients'] = 'ผู้ใช้ในฐานะไคลเอนต์ที่มีโทเค็น';
$string['userasclientsdescription'] = 'ขั้นตอนต่อไปนี้จะช่วยคุณตั้งค่าเว็บเซอร์วิสของ Moodle สำหรับผู้ใช้ในฐานะไคลเอนต์ ขั้นตอนเหล่านี้ยังช่วยตั้งค่าวิธีการยืนยันตัวตนด้วยโทเค็น (กุญแจความปลอดภัย) ที่แนะนำด้วย ในกรณีนี้ ผู้ใช้จะสร้างโทเค็นจากหน้ากุญแจความปลอดภัยผ่านหน้าการตั้งค่าของตนเอง';
$string['usermissingcaps'] = 'สิทธิ์ที่ขาดหายไป: {$a}';
$string['usernameorid'] = 'ชื่อผู้ใช้ / ไอดีผู้ใช้';
$string['usernameorid_help'] = 'ระบุชื่อผู้ใช้หรือไอดีผู้ใช้';
$string['usernotallowed'] = 'ผู้ใช้ไม่ได้รับอนุญาตให้ใช้บริการนี้ ก่อนอื่นคุณต้องอนุญาตผู้ใช้รายนี้ในหน้าการจัดการผู้ใช้ที่ได้รับอนุญาตของ {$a}';
$string['userservices'] = 'บริการสำหรับผู้ใช้: {$a}';
$string['usersettingssaved'] = 'บันทึกการตั้งค่าผู้ใช้แล้ว';
$string['validuntil'] = 'ใช้งานได้จนถึง';
$string['validuntil_empty'] = 'โทเค็นนี้ไม่มีวันหมดอายุ';
$string['validuntil_help'] = 'หากตั้งค่าไว้ บริการจะถูกปิดใช้งานหลังจากวันที่นี้สำหรับผู้ใช้รายนี้';
$string['webservice'] = 'เว็บเซอร์วิส';
$string['webservices'] = 'เว็บเซอร์วิส';
$string['webservicesoverview'] = 'ภาพรวม';
$string['webservicetokens'] = 'โทเค็นเว็บเซอร์วิส';
$string['wrongusernamepassword'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
$string['wsaccessuserdeleted'] = 'ปฏิเสธการเข้าถึงเว็บเซอร์วิสสำหรับชื่อผู้ใช้ที่ถูกลบ: {$a}';
$string['wsaccessuserexpired'] = 'ปฏิเสธการเข้าถึงเว็บเซอร์วิสสำหรับชื่อผู้ใช้ที่รหัสผ่านหมดอายุ: {$a}';
$string['wsaccessusernologin'] = 'ปฏิเสธการเข้าถึงเว็บเซอร์วิสสำหรับชื่อผู้ใช้ที่มีสถานะ nologin: {$a}';
$string['wsaccessusersuspended'] = 'ปฏิเสธการเข้าถึงเว็บเซอร์วิสสำหรับชื่อผู้ใช้ที่ถูกระงับ: {$a}';
$string['wsaccessuserunconfirmed'] = 'ปฏิเสธการเข้าถึงเว็บเซอร์วิสสำหรับชื่อผู้ใช้ที่ยังไม่ได้รับการยืนยัน: {$a}';
$string['wsclientdoc'] = 'เอกสารประกอบไคลเอนต์เว็บเซอร์วิสของ Moodle';
$string['wsdocapi'] = 'เอกสารประกอบ API';
$string['wsdocumentation'] = 'เอกสารประกอบเว็บเซอร์วิส';
$string['wsdocumentationdisable'] = 'เอกสารประกอบเว็บเซอร์วิสถูกปิดใช้งาน';
$string['wsdocumentationintro'] = 'ในการสร้างไคลเอนต์ เราแนะนำให้คุณอ่าน {$a->doclink}';
$string['wsdocumentationlogin'] = 'หรือระบุชื่อผู้ใช้และรหัสผ่านเว็บเซอร์วิสของคุณ:';
$string['wspassword'] = 'รหัสผ่านเว็บเซอร์วิส';
$string['wsusername'] = 'ชื่อผู้ใช้เว็บเซอร์วิส';

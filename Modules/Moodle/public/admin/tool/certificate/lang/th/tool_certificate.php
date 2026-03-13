<?php
// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
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
 * Language strings for the certificate tool.
 *
 * @package    tool_certificate
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Thai translation for Moodle certificate strings
$string['addcertpage'] = 'หน้าใหม่';
$string['addelement'] = 'เพิ่มองค์ประกอบ';
$string['addelementwithname'] = 'เพิ่มองค์ประกอบ \'{$a}\'';
$string['after'] = 'หลัง';
$string['aissueswerecreated'] = 'ออกใบรับรอง {$a} ใบแล้ว';
$string['aligncentre'] = 'กึ่งกลาง';
$string['alignleft'] = 'ซ้าย';
$string['alignment'] = 'การจัดแนวข้อความ';
$string['alignment_help'] = 'การจัดแนวข้อความด้านขวาจะหมายความว่าพิกัดขององค์ประกอบ (ตำแหน่ง X และตำแหน่ง Y) จะอ้างอิงถึงมุมบนขวาของกล่องข้อความ ในการจัดแนวกึ่งกลางจะอ้างอิงถึงมุมบนกลาง และในการจัดแนวซ้ายจะอ้างอิงถึงมุมบนซ้าย';
$string['alignright'] = 'ขวา';
$string['allowfilters'] = 'ตัวกรองที่อนุญาตสำหรับเนื้อหา PDF';
$string['allowfilters_desc'] = 'เฉพาะตัวกรองที่เลือก (ถ้าเปิดใช้งาน) เท่านั้นที่จะใช้กับข้อความภายในไฟล์ PDF ของใบรับรอง';
$string['archived'] = 'เก็บถาวรแล้ว';
$string['availableincourses'] = 'พร้อมใช้งานในหมวดหมู่ย่อยและหลักสูตร';
$string['availableincourses_help'] = 'การเปิดใช้งานตัวเลือกนี้ ผู้ใช้ (ที่มีสิทธิ์ออกใบรับรอง) จะสามารถใช้เทมเพลตนี้ในทุกหลักสูตรภายในหมวดหมู่ที่เลือกและหลักสูตรภายในหมวดหมู่ย่อยของหมวดหมู่นั้นได้ หากปิดใช้งานตัวเลือกนี้ เทมเพลตนี้จะพร้อมใช้งานเฉพาะผู้ใช้ที่มีสิทธิ์ออกใบรับรองในหมวดหมู่ที่เลือกเท่านั้น';
$string['certificate'] = 'ใบรับรอง';
$string['certificate:image'] = 'จัดการรูปภาพใบรับรอง';
$string['certificate:issue'] = 'ออกใบรับรองให้ผู้ใช้';
$string['certificate:manage'] = 'จัดการใบรับรอง';
$string['certificate:verify'] = 'ตรวจสอบใบรับรองใดๆ';
$string['certificate:viewallcertificates'] = 'ดูใบรับรองและเทมเพลตที่ออกทั้งหมด';
$string['certificate_customfield'] = 'ฟิลด์กำหนดเองของใบรับรอง';
$string['certificatecopy'] = '{$a} (สำเนา)';
$string['certificateelement'] = 'องค์ประกอบใบรับรอง';
$string['certificateimages'] = 'รูปภาพใบรับรอง';
$string['certificates'] = 'ใบรับรอง';
$string['certificatesettings'] = 'การตั้งค่าใบรับรอง';
$string['certificatedashboard'] = 'แดชบอร์ดใบรับรอง';
$string['certificatedashboard_totalissued'] = 'ใบรับรองที่ออกทั้งหมด';
$string['certificatedashboard_totaltemplates'] = 'เทมเพลตใบรับรองทั้งหมด';
$string['certificatedashboard_revoke'] = 'เพิกถอนใบรับรอง';
$string['certificatedashboard_revokeconfirm'] = 'คุณแน่ใจหรือไม่ที่จะลบใบรับรองที่ออกแล้วนี้ถาวร?';
$string['certificatedashboard_revokesuccess'] = 'เพิกถอนใบรับรองสำเร็จแล้ว';
$string['certificatedashboard_revokeerror'] = 'ข้อผิดพลาด: ไม่สามารถเพิกถอนใบรับรองได้';
$string['certificatemanagement'] = 'การจัดการใบรับรอง';
$string['certificatedashboard_delete'] = 'ลบใบรับรอง';
$string['certificatedashboard_deleteconfirm'] = 'คุณแน่ใจหรือไม่ที่จะลบใบรับรองที่ออกแล้วนี้?';
$string['certificatedashboard_deletesuccess'] = 'ลบใบรับรองสำเร็จแล้ว';
$string['certificatedashboard_deleteerror'] = 'ข้อผิดพลาด: ไม่สามารถลบใบรับรองได้';
$string['certificatedashboard_updatesuccess'] = 'อัปเดตใบรับรองสำเร็จแล้ว';
$string['certificatesissues'] = 'ใบรับรองที่ออกแล้ว';
$string['certificatesissues_user'] = 'ผู้ใช้';
$string['certificatesissues_email'] = 'อีเมล';
$string['certificatesissues_course'] = 'หลักสูตร';
$string['certificatesissues_template'] = 'เทมเพลต';
$string['certificatesissues_issueddate'] = 'วันที่ออก';
$string['certificatesissues_code'] = 'รหัส';
$string['certificatesissues_actions'] = 'การดำเนินการ';
$string['certificatesissues_noissues'] = 'ยังไม่มีใบรับรองที่ออก';
$string['certificatetemplate'] = 'เทมเพลตใบรับรอง';
$string['certificatetemplatename'] = 'ชื่อเทมเพลตใบรับรอง';
$string['certificatetemplates'] = 'เทมเพลตใบรับรอง';
$string['changeelementsequence'] = 'เลื่อนขึ้นหรือเลื่อนลง';
$string['code'] = 'รหัส';
$string['codewithlink'] = 'รหัสพร้อมลิงก์';
$string['coursecategorywithlink'] = 'หมวดหมู่หลักสูตรพร้อมลิงก์';
$string['createtemplate'] = 'เทมเพลตใบรับรองใหม่';
$string['customfield_previewvalue'] = 'ค่าตัวอย่าง';
$string['customfield_previewvalue_help'] = 'ค่าที่แสดงเมื่อดูตัวอย่างเทมเพลตใบรับรอง';
$string['customfield_visible'] = 'มองเห็นได้';
$string['customfield_visible_help'] = 'อนุญาตให้เลือกฟิลด์นี้บนเทมเพลตใบรับรอง';
$string['customfieldsettings'] = 'การตั้งค่าฟิลด์กำหนดเองทั่วไปของใบรับรอง';
$string['deleteelement'] = 'ลบองค์ประกอบ';
$string['deleteelementconfirm'] = 'คุณแน่ใจหรือไม่ที่จะลบองค์ประกอบ \'{$a}\'?';
$string['deletepage'] = 'ลบหน้า';
$string['deletepageconfirm'] = 'คุณแน่ใจหรือไม่ที่จะลบหน้าใบรับรองนี้?';
$string['deletetemplateconfirm'] = 'คุณแน่ใจหรือไม่ที่จะลบเทมเพลตใบรับรอง \'{$a}\' และข้อมูลที่เกี่ยวข้องทั้งหมด? การกระทำนี้ไม่สามารถยกเลิกได้';
$string['demotmpl'] = 'เทมเพลตสาธิตใบรับรอง';
$string['demotmplawardedon'] = 'มอบให้เมื่อ';
$string['demotmplawardedto'] = 'ใบรับรองนี้มอบให้แก่';
$string['demotmplbackground'] = 'รูปภาพพื้นหลัง';
$string['demotmplcoursefullname'] = 'ชื่อเต็มของหลักสูตร';
$string['demotmpldirector'] = 'ผู้อำนวยการโรงเรียน';
$string['demotmplforcompleting'] = 'สำหรับการทำหลักสูตร';
$string['demotmplissueddate'] = 'วันที่ออก';
$string['demotmplqrcode'] = 'รหัส QR';
$string['demotmplsignature'] = 'ลายเซ็น';
$string['demotmplusername'] = 'ชื่อผู้ใช้';
$string['do_not_show'] = 'ไม่ต้องแสดง';
$string['duplicate'] = 'ทำสำเนา';
$string['duplicatetemplateconfirm'] = 'คุณแน่ใจหรือไม่ที่จะทำสำเนาเทมเพลต \'{$a}\'?';
$string['editelement'] = 'แก้ไข \'{$a}\'';
$string['editelementname'] = 'แก้ไขชื่อองค์ประกอบ';
$string['editpage'] = 'แก้ไขหน้า {$a}';
$string['edittemplatename'] = 'แก้ไขชื่อเทมเพลต';
$string['elementname'] = 'ชื่อองค์ประกอบ';
$string['elementname_help'] = 'นี่จะเป็นชื่อที่ใช้ในการระบุองค์ประกอบนี้เมื่อแก้ไขใบรับรอง โปรดทราบว่าจะไม่แสดงบน PDF';
$string['elementwidth'] = 'ความกว้าง';
$string['elementwidth_help'] = 'ระบุความกว้างขององค์ประกอบ ศูนย์ (0) หมายความว่าไม่มีข้อจำกัดความกว้าง';
$string['entitycertificate'] = 'ใบรับรอง';
$string['entitycertificateissue'] = 'ใบรับรองที่ออกแล้ว';
$string['eventcertificateissued'] = 'ออกใบรับรองแล้ว';
$string['eventcertificateregenerated'] = 'สร้างใบรับรองใหม่แล้ว';
$string['eventcertificaterevoked'] = 'เพิกถอนใบรับรองแล้ว';
$string['eventcertificateverified'] = 'ตรวจสอบใบรับรองแล้ว';
$string['eventtemplatecreated'] = 'สร้างเทมเพลตแล้ว';
$string['eventtemplatedeleted'] = 'ลบเทมเพลตแล้ว';
$string['eventtemplateupdated'] = 'อัปเดตเทมเพลตแล้ว';
$string['expired'] = 'หมดอายุแล้ว';
$string['expiredcertificate'] = 'ใบรับรองนี้หมดอายุแล้ว';
$string['expirydate'] = 'วันหมดอายุ';
$string['expirydatetype'] = 'ประเภทวันหมดอายุ';
$string['font'] = 'แบบอักษร';
$string['font_help'] = 'แบบอักษรที่ใช้เมื่อสร้างองค์ประกอบนี้';
$string['fontcolour'] = 'สี';
$string['fontcolour_help'] = 'สีของแบบอักษร';
$string['fontsize'] = 'ขนาด';
$string['fontsize_help'] = 'ขนาดของแบบอักษรเป็นพอยต์';
$string['hideshow'] = 'ซ่อน/แสดง';
$string['invalidcolour'] = 'เลือกสีไม่ถูกต้อง กรุณาป้อนชื่อสี HTML ที่ถูกต้อง หรือเลขฐานสิบหกหกหลัก หรือสามหลัก';
$string['invalidelementwidth'] = 'กรุณาป้อนตัวเลขบวก';
$string['invalidheight'] = 'ความสูงต้องเป็นตัวเลขที่ถูกต้องมากกว่า 0';
$string['invalidmargin'] = 'ระยะขอบต้องเป็นตัวเลขที่ถูกต้องมากกว่า 0';
$string['invalidposition'] = 'กรุณาเลือกตัวเลขบวกสำหรับตำแหน่ง {$a}';
$string['invalidwidth'] = 'ความกว้างต้องเป็นตัวเลขที่ถูกต้องมากกว่า 0';
$string['issuecertificates'] = 'ออกใบรับรอง';
$string['issuedcertificates'] = 'ใบรับรองที่ออกแล้ว';
$string['issueddate'] = 'วันที่ออก';
$string['issuelang'] = 'ออกใบรับรองในภาษาของผู้ใช้';
$string['issuelangdesc'] = 'บนเว็บไซต์หลายภาษาเมื่อภาษาของผู้ใช้แตกต่างจากภาษาของเว็บไซต์ ใบรับรองจะถูกสร้างในภาษาของผู้ใช้ มิฉะนั้นใบรับรองทั้งหมดจะถูกสร้างในภาษาเริ่มต้นของเว็บไซต์';
$string['issuenotallowed'] = 'คุณไม่ได้รับอนุญาตให้ออกใบรับรองจากเทมเพลตนี้';
$string['issueormangenotallowed'] = 'คุณไม่ได้รับอนุญาตให้ออกใบรับรองจากหรือจัดการเทมเพลตนี้';
$string['leftmargin'] = 'ระยะขอบซ้าย';
$string['leftmargin_help'] = 'นี่คือระยะขอบซ้ายของไฟล์ PDF ใบรับรองเป็นมิลลิเมตร';
$string['linkedinorganizationid'] = 'รหัสองค์กร LinkedIn';
$string['linkedinorganizationid_desc'] = 'รหัสขององค์กร LinkedIn ที่ออกใบรับรอง

ฉันจะหารหัสองค์กร LinkedIn ของฉันได้ที่ไหน?

1.    เข้าสู่ระบบ LinkedIn เป็นผู้ดูแลสำหรับหน้าองค์กรของธุรกิจคุณ
2.    ตรวจสอบ URL ที่ใช้เมื่อคุณเข้าสู่ระบบเป็นผู้ดูแล (URL ควรเป็น "https://linkedin.com/company/xxxxxxx/admin")
3.    รหัสองค์กร LinkedIn ของคุณจะเป็นเลขเจ็ดหลักใน URL (แสดงเป็น "xxxxxxx" ในขั้นตอนข้างบน)';
$string['manageelementplugins'] = 'จัดการปลั๊กอินองค์ประกอบใบรับรอง';
$string['managetemplates'] = 'จัดการเทมเพลตใบรับรอง';
$string['messageprovider:certificateissued'] = 'ได้รับใบรับรอง';
$string['milimeter'] = 'มม.';
$string['mycertificates'] = 'ใบรับรองของฉัน';
$string['mycertificatesdescription'] = 'นี่คือใบรับรองที่คุณได้รับทั้งทางอีเมลและการดาวน์โหลดด้วยตนเอง';
$string['name'] = 'ชื่อ';
$string['nametoolong'] = 'คุณได้เกินความยาวสูงสุดที่อนุญาตสำหรับชื่อ';
$string['never'] = 'ไม่เคย';
$string['noimage'] = 'ไม่มีรูปภาพ';
$string['noissueswerecreated'] = 'ไม่มีการออกใบรับรอง';
$string['notificationmsgcertificateissued'] = 'สวัสดี {$a->fullname},<br /><br />ใบรับรองของคุณพร้อมใช้งานแล้ว! คุณจะพบมันได้ที่นี่:
<a href="{$a->url}">ใบรับรองของฉัน</a>';
$string['notificationsubjectcertificateissued'] = 'ใบรับรองของคุณพร้อมใช้งานแล้ว!';
$string['notverified'] = 'ยังไม่ได้ตรวจสอบ';
$string['numberofpages'] = 'จำนวนหน้า';
$string['oneissuewascreated'] = 'สร้างใบรับรอง 1 ใบแล้ว';
$string['page'] = 'หน้า {$a}';
$string['pageheight'] = 'ความสูงหน้า';
$string['pageheight_help'] = 'นี่คือความสูงของไฟล์ PDF ใบรับรองเป็นมิลลิเมตร เพื่อเป็นข้อมูลอ้างอิงกระดาษ A4 สูง 297 มม. และกระดาษจดหมายสูง 279 มม.';
$string['pagewidth'] = 'ความกว้างหน้า';
$string['pagewidth_help'] = 'นี่คือความกว้างของไฟล์ PDF ใบรับรองเป็นมิลลิเมตร เพื่อเป็นข้อมูลอ้างอิงกระดาษ A4 กว้าง 210 มม. และกระดาษจดหมายกว้าง 216 มม.';
$string['pluginname'] = 'ตัวจัดการใบรับรอง';
$string['posx'] = 'ตำแหน่ง X';
$string['posx_help'] = 'นี่คือตำแหน่งเป็นมิลลิเมตรจากมุมบนซ้ายที่คุณต้องการให้จุดอ้างอิงขององค์ประกอบอยู่ในทิศทาง x';
$string['posy'] = 'ตำแหน่ง Y';
$string['posy_help'] = 'นี่คือตำแหน่งเป็นมิลลิเมตรจากมุมบนซ้ายที่คุณต้องการให้จุดอ้างอิงขององค์ประกอบอยู่ในทิศทาง y';
$string['privacy:metadata:tool_certificate:issues'] = 'รายการใบรับรองที่ออกแล้ว';
$string['privacy:metadata:tool_certificate_issues:code'] = 'รหัสที่เป็นของใบรับรอง';
$string['privacy:metadata:tool_certificate_issues:expires'] = 'การประทับเวลาเมื่อใบรับรองหมดอายุ 0 ถ้าไม่หมดอายุ';
$string['privacy:metadata:tool_certificate_issues:templateid'] = 'รหัสของใบรับรอง';
$string['privacy:metadata:tool_certificate_issues:timecreated'] = 'เวลาที่ออกใบรับรอง';
$string['privacy:metadata:tool_certificate_issues:userid'] = 'รหัสของผู้ใช้ที่ได้รับใบรับรอง';
$string['reg_wpcertificates'] = 'จำนวนใบรับรอง ({$a})';
$string['reg_wpcertificatesissues'] = 'จำนวนใบรับรองที่ออกแล้ว ({$a})';
$string['regenerate'] = 'สร้างใหม่';
$string['regenerateall'] = 'สร้างใบรับรองที่ออกแล้วทั้งหมดใหม่';
$string['regeneratefileconfirmallusers'] = '<p>คุณแน่ใจหรือไม่ที่จะสร้างใบรับรองที่ออกแล้วทั้งหมดใหม่สำหรับผู้ใช้?</br>การกระทำนี้ไม่สามารถยกเลิกได้</p>';
$string['regeneratefileconfirmselectedusers'] = '<p>คุณแน่ใจหรือไม่ที่จะสร้างใบรับรองที่ออกแล้วทั้งหมดใหม่สำหรับผู้ใช้ที่เลือก?</br>การกระทำนี้ไม่สามารถยกเลิกได้</p>';
$string['regeneratefileconfirmsingleuser'] = '<p>คุณแน่ใจหรือไม่ที่จะสร้างใบรับรองที่ออกแล้วใหม่สำหรับผู้ใช้คนนี้?</br>การกระทำนี้ไม่สามารถยกเลิกได้</p>';
$string['regenerateissuedcertificates'] = 'สร้างใบรับรองที่ออกแล้วใหม่';
$string['regenerateissuefile'] = 'สร้างใบรับรองที่ออกแล้วใหม่';
$string['regeneratenotification'] = 'กำลังสร้างใบรับรองที่ออกแล้วใหม่ อาจใช้เวลาสักครู่ก่อนที่ผู้ใช้ที่ได้รับผลกระทบจะเห็นข้อมูลที่อัปเดต ปลอดภัยที่จะทำงานต่อบนเว็บไซต์';
$string['regenerateselected'] = 'สร้างใบรับรองที่เลือกใหม่';
$string['regeneratesinglenotification'] = 'สร้างใบรับรองใหม่เรียบร้อยแล้ว';
$string['revoke'] = 'เพิกถอน';
$string['revokecertificateconfirm'] = 'คุณแน่ใจหรือไม่ที่จะเพิกถอนการออกใบรับรองนี้จากผู้ใช้คนนี้?';
$string['rightmargin'] = 'ระยะขอบขวา';
$string['rightmargin_help'] = 'นี่คือระยะขอบขวาของไฟล์ PDF ใบรับรองเป็นมิลลิเมตร';
$string['selectdate'] = 'เลือกวันที่';
$string['selectuserstoissuecertificatefor'] = 'เลือกผู้ใช้เพื่อออกใบรับรอง';
$string['sendnotificationaffectedusers'] = 'ส่งข้อความแจ้งเตือนให้ผู้ใช้ที่ได้รับผลกระทบทั้งหมดด้วย';
$string['sendnotificationaffectedusers_help'] = 'ข้อความที่จะส่งจะเป็นข้อความเดียวกับที่ส่งให้ผู้ใช้เมื่อพวกเขาได้รับใบรับรองครั้งแรก';
$string['sendnotificationsingleuser'] = 'ส่งข้อความแจ้งเตือนให้ผู้ใช้ด้วย';
$string['shared'] = 'แชร์แล้ว';
$string['shareonlinkedin'] = 'แชร์บน LinkedIn';
$string['show_link_to_certificate_page'] = 'แสดงลิงก์ไปยังหน้าใบรับรอง';
$string['show_link_to_verification_page'] = 'แสดงลิงก์ไปยังหน้าตรวจสอบ';
$string['show_shareonlinkedin'] = 'แสดงการแชร์บน LinkedIn';
$string['show_shareonlinkedin_desc'] = 'หากปุ่ม "แชร์บน LinkedIn" ควรแสดงบนหน้าใบรับรองของฉัน การเชื่อมโยงโดยตรงไปยังไฟล์ PDF ใบรับรองจะดูดีกว่าแต่อาจแสดงข้อผิดพลาดสำหรับใบรับรองที่หมดอายุ';
$string['status'] = 'สถานะ';
$string['subplugintype_certificateelement'] = 'ปลั๊กอินองค์ประกอบใบรับรอง';
$string['subplugintype_certificateelement_plural'] = 'ปลั๊กอินองค์ประกอบใบรับรอง';
$string['template'] = 'เทมเพลต';
$string['templatepermission'] = 'สิทธิ์ในการเข้าถึงเทมเพลต';
$string['templatepermissionany'] = 'ไม่ต้องตรวจสอบ';
$string['templatepermissionyes'] = 'ตรวจสอบสิทธิ์ของผู้ใช้ปัจจุบัน';
$string['timecreated'] = 'เวลาที่สร้าง';
$string['uploadimage'] = 'อัปโหลดรูปภาพ';
$string['valid'] = 'ถูกต้อง';
$string['validcertificate'] = 'ใบรับรองนี้ถูกต้อง';
$string['verified'] = 'ตรวจสอบแล้ว';
$string['verify'] = 'ตรวจสอบ';
$string['verifycertificates'] = 'ตรวจสอบใบรับรอง';
$string['verifynotallowed'] = 'คุณไม่ได้รับอนุญาตให้ตรวจสอบใบรับรอง';
$string['viewcertificate'] = 'ดูใบรับรอง';
$string['viewmore'] = 'ดูเพิ่มเติมบนเว็บ';

// Deprecated since 4.2.
$string['editcertificate'] = 'แก้ไขเทมเพลตใบรับรอง \'{$a}\'';
$string['issuenewcertificate'] = 'ออกใบรับรองจากเทมเพลตนี้';
$string['nopermissionform'] = 'คุณไม่มีสิทธิ์เข้าถึงแบบฟอร์มนี้';

// Deprecated since 5.0.3.
$string['regeneratefileconfirm'] = 'คุณแน่ใจหรือไม่ที่จะสร้างใบรับรองที่ออกแล้วใหม่สำหรับผู้ใช้คนนี้?';

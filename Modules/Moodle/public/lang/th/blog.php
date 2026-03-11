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
 * Strings for core subsystem 'blog'
 *
 * @package    core_blog
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addnewentry'] = 'เพิ่มบันทึกใหม่';
$string['addnewexternalblog'] = 'จดทะเบียนบล็อกภายนอก';
$string['assocdescription'] = 'หากคุณกำลังเขียนเกี่ยวกับรายวิชา และ/หรือ โมดูลกิจกรรม ให้เลือกที่นี่';
$string['associated'] = 'เชื่อมโยงกับ {$a}';
$string['associatewithcourse'] = 'บล็อกเกี่ยวกับรายวิชา {$a->coursename}';
$string['associatewithmodule'] = 'บล็อกเกี่ยวกับ {$a->modtype}: {$a->modname}';
$string['association'] = 'ความเชื่อมโยง';
$string['associations'] = 'ความเชื่อมโยง';
$string['associationunviewable'] = 'ผู้อื่นจะไม่สามารถดูบันทึกนี้ได้จนกว่าจะมีการเชื่อมโยงรายวิชากับบันทึกนี้ หรือมีการเปลี่ยนฟิลด์ \'เผยแพร่ไปยัง\'';
$string['author'] = 'ผู้เขียน';
$string['autotags'] = 'เพิ่มแท็กเหล่านี้';
$string['autotags_help'] = 'ระบุแท็กท้องถิ่นหนึ่งแท็กหรือมากกว่า (แยกด้วยเครื่องหมายจุลภาค) ที่คุณต้องการเพิ่มโดยอัตโนมัติในแต่ละบันทึกของบล็อกที่คัดลอกมาจากบล็อกภายนอกมายังบล็อกท้องถิ่นของคุณ';
$string['backupblogshelp'] = 'หากเปิดใช้งาน บล็อกจะถูกรวมอยู่ในการสำรองข้อมูลอัตโนมัติของไซต์';
$string['blockexternalstitle'] = 'บล็อกภายนอก';
$string['blog'] = 'บล็อก';
$string['blogaboutthis'] = 'บล็อกเกี่ยวกับ {$a->type} นี้';
$string['blogaboutthiscourse'] = 'เพิ่มบันทึกเกี่ยวกับรายวิชานี้';
$string['blogaboutthismodule'] = 'เพิ่มบันทึกเกี่ยวกับ {$a} นี้';
$string['blogadministration'] = 'การจัดการบล็อก';
$string['blogattachment'] = 'สิ่งที่แนบมากับบล็อก';
$string['blogdeleteconfirm'] = 'ลบบันทึกบล็อก \'{$a}\' นี้หรือไม่?';
$string['blogdisable'] = 'การเขียนบล็อกถูกปิดใช้งาน!';
$string['blogentries'] = 'บันทึกบล็อก';
$string['blogentriesabout'] = 'บันทึกบล็อกเกี่ยวกับ {$a}';
$string['blogentriesbygroupaboutcourse'] = 'บันทึกบล็อกเกี่ยวกับ {$a->course} โดย {$a->group}';
$string['blogentriesbygroupaboutmodule'] = 'บันทึกบล็อกเกี่ยวกับ {$a->mod} โดย {$a->group}';
$string['blogentriesbyuseraboutcourse'] = 'บันทึกบล็อกเกี่ยวกับ {$a->course} โดย {$a->user}';
$string['blogentriesbyuseraboutmodule'] = 'บันทึกบล็อกเกี่ยวกับ {$a->mod} นี้โดย {$a->user}';
$string['blogentrybyuser'] = 'บันทึกบล็อกโดย {$a}';
$string['blogpreferences'] = 'การตั้งค่าบล็อก';
$string['blogs'] = 'บล็อก';
$string['blogscourse'] = 'บล็อกรายวิชา';
$string['blogssite'] = 'บล็อกไซต์';
$string['blogtags'] = 'แท็กบล็อก';
$string['cannoteditentryorblog'] = 'คุณไม่สามารถแก้ไขบันทึกนี้หรือบล็อกนี้ได้';
$string['cannotviewcourseblog'] = 'คุณไม่มีสิทธิ์ในการดูบล็อกในรายวิชานี้';
$string['cannotviewcourseorgroupblog'] = 'คุณไม่มีสิทธิ์ในการดูบล็อกในรายวิชา/กลุ่มนี้';
$string['cannotviewsiteblog'] = 'คุณไม่มีสิทธิ์ในการดูบล็อกทั้งหมดของไซต์';
$string['cannotviewuserblog'] = 'คุณไม่มีสิทธิ์ในการอ่านบล็อกของผู้ใช้';
$string['configexternalblogcrontime'] = 'ความถี่ที่ Moodle จะตรวจสอบบล็อกภายนอกเพื่อหาบันทึกใหม่';
$string['configmaxexternalblogsperuser'] = 'จำนวนบล็อกภายนอกสูงสุดที่ผู้ใช้แต่ละคนสามารถเชื่อมโยงกับบล็อก Moodle ของตนได้';
$string['configuseblogassociations'] = 'เปิดใช้งานการเชื่อมโยงบันทึกบล็อกกับรายวิชาและโมดูลรายวิชา';
$string['configuseexternalblogs'] = 'อนุญาตให้ผู้ใช้ระบุฟีดบล็อกภายนอก Moodle จะตรวจสอบฟีดบล็อกเหล่านี้อย่างสม่ำเสมอและคัดลอกบันทึกใหม่ไปยังบล็อกท้องถิ่นของผู้ใช้รายนั้น';
$string['courseblog'] = 'บล็อกรายวิชา: {$a}';
$string['courseblogdisable'] = 'บล็อกรายวิชาไม่ได้ถูกเปิดใช้งาน';
$string['courseblogs'] = 'ผู้ใช้สามารถมองเห็นบล็อกเฉพาะของบุคคลที่มีรายวิชาร่วมกันเท่านั้น';
$string['deleteblogassociations'] = 'ลบความเชื่อมโยงบล็อก';
$string['deleteblogassociations_help'] = 'หากเลือก บันทึกบล็อกจะไม่เชื่อมโยงกับรายวิชานี้ หรือกิจกรรมหรือทรัพยากรรายวิชาใด ๆ อีกต่อไป บันทึกบล็อกนั้นจะไม่ถูกลบ';
$string['deleteentry'] = 'ลบบันทึก';
$string['deleteexternalblog'] = 'ยกเลิกการจดทะเบียนบล็อกภายนอกนี้';
$string['deleteotagswarn'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบแท็กเหล่านี้ออกจากโพสต์บล็อกทั้งหมดและลบออกจากระบบ?';
$string['description'] = 'คำอธิบาย';
$string['description_help'] = 'ป้อนประโยคหนึ่งหรือสองประโยคสรุปเนื้อหาของบล็อกภายนอกของคุณ (หากไม่มีคำอธิบาย ระบบจะใช้คำอธิบายที่บันทึกไว้ในบล็อกภายนอกของคุณ)';
$string['donothaveblog'] = 'ขออภัย คุณไม่มีบล็อกเป็นของตัวเอง';
$string['editentry'] = 'แก้ไขบันทึกบล็อก';
$string['editexternalblog'] = 'แก้ไขบล็อกภายนอกนี้';
$string['emptybody'] = 'เนื้อหาบันทึกบล็อกไม่สามารถว่างเปล่าได้';
$string['emptyrssfeed'] = 'URL ที่คุณระบุไม่ได้ชี้ไปยังฟีด RSS ที่ถูกต้อง';
$string['emptytitle'] = 'หัวข้อบันทึกบล็อกไม่สามารถว่างเปล่าได้';
$string['emptyurl'] = 'คุณต้องระบุ URL ไปยังฟีด RSS ที่ถูกต้อง';
$string['entrybody'] = 'เนื้อหาบันทึกบล็อก';
$string['entrybodyonlydesc'] = 'คำอธิบายบันทึก';
$string['entryerrornotyours'] = 'บันทึกนี้ไม่ใช่ของคุณ';
$string['entrysaved'] = 'บันทึกของคุณถูกจัดเก็บแล้ว';
$string['entrytitle'] = 'ชื่อบันทึก';
$string['entrytitlewithlink'] = 'ชื่อบันทึกพร้อมลิงก์';
$string['eventblogentriesviewed'] = 'มีการเข้าดูบันทึกบล็อก';
$string['eventblogassociationadded'] = 'มีการสร้างการเชื่อมโยงบล็อก';
$string['eventblogassociationdeleted'] = 'มีการลบการเชื่อมโยงบล็อก';
$string['eventblogexternaladded'] = 'มีการจดทะเบียนบล็อกภายนอก';
$string['eventblogexternalremoved'] = 'มีการยกเลิกการจดทะเบียนบล็อกภายนอก';
$string['eventblogexternalupdated'] = 'มีการอัปเดตบล็อกภายนอก';
$string['evententryadded'] = 'มีการเพิ่มบันทึกบล็อก';
$string['evententrydeleted'] = 'มีการลบบันทึกบล็อก';
$string['evententryupdated'] = 'มีการอัปเดตบันทึกบล็อก';
$string['externalblogcrontime'] = 'ตารางเวลา cron ของบล็อกภายนอก';
$string['externalblogdeleted'] = 'ยกเลิกการจดทะเบียนบล็อกภายนอกแล้ว';
$string['externalblogs'] = 'บล็อกภายนอก';
$string['eventexternalblogsviewed'] = 'มีการเข้าดูบล็อกภายนอกที่จดทะเบียนแล้ว';
$string['feedisinvalid'] = 'ฟีดนี้ไม่ถูกต้อง';
$string['feedisvalid'] = 'ฟีดนี้ถูกต้อง';
$string['filterblogsby'] = 'กรองบันทึกตาม...';
$string['filtertags'] = 'ตัวกรองแท็ก';
$string['filtertags_help'] = 'คุณสามารถใช้คุณลักษณะนี้เพื่อกรองบันทึกที่คุณต้องการใช้ หากคุณระบุแท็กที่นี่ (แยกด้วยเครื่องหมายจุลภาค) เฉพาะบันทึกที่มีแท็กเหล่านี้เท่านั้นที่จะถูกคัดลอกจากบล็อกภายนอก';
$string['groupblog'] = 'บล็อกกลุ่ม: {$a}';
$string['groupblogdisable'] = 'บล็อกกลุ่มไม่ได้ถูกเปิดใช้งาน';
$string['groupblogentries'] = 'บันทึกบล็อกที่เชื่อมโยงกับ {$a->coursename} โดยกลุ่ม {$a->groupname}';
$string['groupblogs'] = 'ผู้ใช้สามารถมองเห็นบล็อกเฉพาะของคนที่เป็นสมาชิกในกลุ่มเดียวกันเท่านั้น';
$string['incorrectblogfilter'] = 'ระบุประเภทตัวกรองบล็อกไม่ถูกต้อง';
$string['intro'] = 'ฟีด RSS นี้ถูกสร้างขึ้นโดยอัตโนมัติจากบล็อกตั้งแต่หนึ่งแห่งขึ้นไป';
$string['invalidgroupid'] = 'รหัสกลุ่มไม่ถูกต้อง';
$string['invalidurl'] = 'ไม่สามารถเข้าถึง URL นี้ได้';
$string['linktooriginalentry'] = 'บันทึกบล็อกต้นฉบับ';
$string['maxexternalblogsperuser'] = 'จำนวนบล็อกภายนอกสูงสุดต่อผู้ใช้';
$string['myprofileuserblogs'] = 'ดูบันทึกบล็อกทั้งหมด';
$string['name'] = 'ชื่อ';
$string['name_help'] = 'ป้อนชื่อที่สื่อความหมายสำหรับบล็อกภายนอกของคุณ (หากไม่มีชื่อกำหนดไว้ จะมีการใช้ชื่อเรื่องของบล็อกภายนอกของคุณแทน)';
$string['noentriesyet'] = 'ไม่มีบันทึกที่มองเห็นได้ในตอนนี้';
$string['noguestpost'] = 'บุคคลทั่วไปไม่สามารถโพสต์บล็อกได้!';
$string['nopermissionstodeleteentry'] = 'คุณไม่มีสิทธิ์ในการลบบันทึกบล็อกนี้';
$string['norighttodeletetag'] = 'คุณไม่มีสิทธิ์ในการลบแท็กนี้ - {$a}';
$string['nosuchentry'] = 'ไม่มีบันทึกบล็อกนี้';
$string['notallowedtoedit'] = 'คุณไม่ได้รับอนุญาตให้แก้ไขบันทึกนี้';
$string['numberofentries'] = 'จำนวนบันทึก: {$a}';
$string['numberoftags'] = 'จำนวนแท็กที่จะแสดง';
$string['pagesize'] = 'จำนวนบันทึกบล็อกต่อหน้า';
$string['permalink'] = 'ลิงก์ถาวร';
$string['personalblogs'] = 'ผู้ใช้สามารถมองเห็นเฉพาะบล็อกของตัวเองเท่านั้น';
$string['preferences'] = 'การตั้งค่าบล็อก';
$string['privacy:metadata:core_comments'] = 'ความคิดเห็นที่เชื่อมโยงกับบันทึกบล็อก';
$string['privacy:metadata:core_files'] = 'ไฟล์ที่แนบมากับบันทึกบล็อก';
$string['privacy:metadata:core_tag'] = 'แท็กที่เชื่อมโยงกับบันทึกบล็อก';
$string['privacy:metadata:external'] = 'ลิงก์ไปยังฟีด RSS ภายนอก';
$string['privacy:metadata:external:userid'] = 'รหัสของผู้ใช้ที่เพิ่มบันทึกบล็อกภายนอก';
$string['privacy:metadata:external:name'] = 'ชื่อของฟีด';
$string['privacy:metadata:external:description'] = 'คำอธิบายของฟีด';
$string['privacy:metadata:external:url'] = 'URL ของฟีด';
$string['privacy:metadata:external:filtertags'] = 'รายการของแท็กที่ใช้กรองบันทึก';
$string['privacy:metadata:external:timemodified'] = 'เวลาที่มีการแก้ไขความเชื่อมโยงบล็อกล่าสุด';
$string['privacy:metadata:external:timefetched'] = 'เวลาที่ระบบดึงข้อมูลจากฟีดครั้งล่าสุด';
$string['privacy:metadata:post'] = 'ข้อมูลที่เกี่ยวข้องกับบันทึกบล็อก';
$string['privacy:metadata:post:userid'] = 'รหัสของผู้ใช้ที่เพิ่มบันทึกบล็อก';
$string['privacy:metadata:post:subject'] = 'หัวข้อบันทึกบล็อก';
$string['privacy:metadata:post:summary'] = 'เนื้อหาบันทึกบล็อก';
$string['privacy:metadata:post:content'] = 'เนื้อหาของบันทึกบล็อกภายนอก';
$string['privacy:metadata:post:uniquehash'] = 'ตัวระบุเฉพาะสำหรับบันทึกภายนอก โดยปกติจะเป็น URL';
$string['privacy:metadata:post:publishstate'] = 'ระบุว่าคนอื่นสามารถมองเห็นบันทึกนั้นได้หรือไม่';
$string['privacy:metadata:post:created'] = 'วันที่สร้างบันทึกบล็อก';
$string['privacy:metadata:post:lastmodified'] = 'วันที่แก้ไขบันทึกบล็อกล่าสุด';
$string['privacy:metadata:post:usermodified'] = 'ผู้ใช้ที่แก้ไขบันทึกเป็นคนล่าสุด';
$string['privacy:path:blogassociations'] = 'โพสต์บล็อกที่เชื่อมโยง';
$string['privacy:unknown'] = 'ไม่ทราบ';
$string['published'] = 'เผยแพร่แล้ว';
$string['publishto'] = 'เผยแพร่ไปยัง';
$string['publishto_help'] = 'มี 3 ทางเลือก:

* ตัวคุณเอง (แบบร่าง) - เฉพาะคุณและผู้ดูแลระบบเท่านั้นที่สามารถเห็นบันทึกนี้
* ทุกคนในไซต์นี้ - ทุกคนที่ลงทะเบียนในไซต์นี้สามารถอ่านบันทึกนี้ได้
* ทุกคนในโลก - ทุกคนรวมถึงบุคคลทั่วไปสามารถอ่านบันทึกนี้ได้';
$string['publishtocourse'] = 'ผู้ใช้ที่เรียนในรายวิชาเดียวกับคุณ';
$string['publishtocourseassoc'] = 'สมาชิกของรายวิชาที่เชื่อมโยง';
$string['publishtocourseassocparam'] = 'สมาชิกของ {$a}';
$string['publishtodraft'] = 'แบบร่าง';
$string['publishtogroup'] = 'ผู้ใช้ที่อยู่ในกลุ่มเดียวกับคุณ';
$string['publishtogroupassoc'] = 'สมาชิกกลุ่มของคุณในรายวิชาที่เชื่อมโยง';
$string['publishtogroupassocparam'] = 'สมาชิกกลุ่มของคุณใน {$a}';
$string['publishtonoone'] = 'ตัวคุณเอง (แบบร่าง)';
$string['publishtosite'] = 'ทุกคนในไซต์นี้';
$string['publishtoworld'] = 'ทุกคนในโลก';
$string['readfirst'] = 'อ่านอันนี้ก่อน';
$string['relatedblogentries'] = 'บันทึกบล็อกที่เกี่ยวข้อง';
$string['retrievedfrom'] = 'ดึงข้อมูลจาก';
$string['rssfeed'] = 'ฟีด RSS ของบล็อก';
$string['searchterm'] = 'ค้นหา: {$a}';
$string['settingsupdatederror'] = 'เกิดข้อผิดพลาด ไม่สามารถอัปเดตการตั้งค่าบล็อกได้';
$string['siteblogheading'] = 'บล็อกไซต์';
$string['siteblogdisable'] = 'บล็อกไซต์ไม่ได้ถูกเปิดใช้งาน';
$string['siteblogs'] = 'ผู้ใช้ทุกคนในไซต์สามารถมองเห็นบันทึกบล็อกทั้งหมด';
$string['tagdatelastused'] = 'วันที่ใช้งานแท็กครั้งล่าสุด';
$string['tagparam'] = 'แท็ก: {$a}';
$string['tags'] = 'แท็ก';
$string['tagsort'] = 'จัดเรียงการแสดงผลแท็กตาม';
$string['tagtext'] = 'ข้อความแท็ก';
$string['timefetched'] = 'เวลาของการซิงค์ล่าสุด';
$string['timewithin'] = 'แสดงแท็กที่ใช้งานภายในจำนวนวันที่ระบุ';
$string['updateentrywithid'] = 'กำลังอัปเดตบันทึก';
$string['url'] = 'URL ฟีด RSS';
$string['url_help'] = 'ระบุ URL ฟีด RSS สำหรับบล็อกภายนอกของคุณ';
$string['useblogassociations'] = 'เปิดใช้งานการเชื่อมโยงบล็อก';
$string['useexternalblogs'] = 'เปิดใช้งานบล็อกภายนอก';
$string['userblog'] = 'บล็อกผู้ใช้: {$a}';
$string['userblogentries'] = 'บันทึกบล็อกโดย {$a}';
$string['valid'] = 'ถูกต้อง';
$string['viewallblogentries'] = 'บันทึกทั้งหมดเกี่ยวกับ {$a} นี้';
$string['viewallmodentries'] = 'ดูบันทึกทั้งหมดเกี่ยวกับ {$a->type} นี้';
$string['viewallmyentries'] = 'ดูบันทึกทั้งหมดของฉัน';
$string['viewentriesbyuseraboutcourse'] = 'ดูบันทึกเกี่ยวกับรายวิชานี้โดย {$a}';
$string['viewblogentries'] = 'บันทึกเกี่ยวกับ {$a->type} นี้';
$string['viewblogsfor'] = 'ดูบันทึกทั้งหมดสำหรับ...';
$string['viewcourseblogs'] = 'ดูบันทึกทั้งหมดสำหรับรายวิชานี้';
$string['viewgroupblogs'] = 'ดูบันทึกสำหรับกลุ่ม...';
$string['viewgroupentries'] = 'บันทึกกลุ่ม';
$string['viewmodblogs'] = 'ดูบันทึกสำหรับโมดูล...';
$string['viewmodentries'] = 'บันทึกโมดูล';
$string['viewmyentries'] = 'บันทึกของฉัน';
$string['viewmyentriesaboutmodule'] = 'ดูบันทึกของฉันเกี่ยวกับ {$a} นี้';
$string['viewmyentriesaboutcourse'] = 'ดูบันทึกของฉันเกี่ยวกับรายวิชานี้';
$string['viewsiteentries'] = 'ดูบันทึกทั้งหมด';
$string['viewuserentries'] = 'ดูบันทึกทั้งหมดโดย {$a}';
$string['worldblogs'] = 'บุคคลภายนอกสามารถอ่านบันทึกที่ตั้งค่าไว้ให้เข้าถึงแบบสาธารณะได้';
$string['wrongexternalid'] = 'รหัสบล็อกภายนอกไม่ถูกต้อง';
$string['page-blog-edit'] = 'หน้าแก้ไขบล็อก';
$string['page-blog-index'] = 'หน้าแสดงรายการบล็อก';
$string['page-blog-x'] = 'หน้าบล็อกทั้งหมด';

// Deprecated since Moodle 5.1.
$string['externalblogdeleteconfirm'] = 'ต้องการยกเลิกการจดทะเบียนบล็อกภายนอกนี้หรือไม่?';

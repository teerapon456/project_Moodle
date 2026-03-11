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
 * Strings for component 'portfolio', language 'th', branch 'MOODLE_20_STABLE'
 *
 * @package   core_portfolio
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activeexport'] = 'จัดการการส่งออกที่กำลังทำงานอยู่';
$string['activeportfolios'] = 'พอร์ตโฟลิโอที่เปิดใช้งานอยู่';
$string['addalltoportfolio'] = 'ส่งออกทั้งหมดไปยังพอร์ตโฟลิโอ';
$string['addnewportfolio'] = 'เพิ่มพอร์ตโฟลิโอใหม่';
$string['addtoportfolio'] = 'ส่งออกไปยังพอร์ตโฟลิโอ';
$string['alreadyalt'] = 'กำลังส่งออกอยู่แล้ว - โปรดคลิกที่นี่เพื่อจัดการการส่งออกนี้';
$string['alreadyexporting'] = 'คุณมีรายการการส่งออกพอร์ตโฟลิโอที่กำลังทำงานอยู่ในเซสชันนี้ ก่อนที่จะดำเนินการต่อคุณต้องอาร์ตการส่งออกนี้ให้เสร็จสิ้นหรือยกเลิก คุณต้องการดำเนินการต่อหรือไม่? (เลือก No เพื่อยกเลิก)';
$string['availableformats'] = 'รูปแบบการส่งออกที่มีอยู่';
$string['callbackclassinvalid'] = 'คลาส Callback ที่ระบุไม่ถูกต้องหรือไม่เป็นส่วนหนึ่งของลำดับชั้น portfolio_caller';
$string['callercouldnotpackage'] = 'ล้มเหลวในการรวมข้อมูลของคุณเพื่อส่งออก: ข้อผิดพลาดเดิมคือ {$a}';
$string['cannotsetvisible'] = 'ไม่สามารถตั้งค่าให้มองเห็นได้ - ปลั๊กอินถูกปิดใช้งานโดยสมบูรณ์เนื่องจากการตั้งค่าผิดพลาด';
$string['commonportfoliosettings'] = 'การตั้งค่าพอร์ตโฟลิโอทั่วไป';
$string['commonsettingsdesc'] = '<p>ไม่ว่าการถ่ายโอนข้อมูลจะใช้เวลา \'ปานกลาง\' หรือ \'มาก\' จะส่งผลต่อการรอของผู้ใช้จนกว่าการถ่ายโอนจะเสร็จสิ้น</p><p>ขนาดไม่เกินเกณฑ์ \'ปานกลาง\' จะเกิดขึ้นทันทีโดยไม่ถามผู้ใช้ และการถ่ายโอนแบบ \'ปานกลาง\' และ \'มาก\' หมายความว่าผู้ใช้จะมีตัวเลือกในการส่งออกแต่จะได้รับคำเตือนว่าอาจใช้เวลาสักครู่</p><p>นอกจากนี้ ปลั๊กอินพอร์ตโฟลิโอในบางตัวอาจละเว้นตัวเลือกนี้และบังคับให้การถ่ายโอนทั้งหมดเข้าสู่คิว</p>';
$string['configexport'] = 'กำหนดค่าข้อมูลที่ส่งออก';
$string['configplugin'] = 'กำหนดค่าปลั๊กอินพอร์ตโฟลิโอ';
$string['configure'] = 'กำหนดค่า';
$string['confirmcancel'] = 'คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการส่งออกนี้?';
$string['confirmexport'] = 'โปรดยืนยันการส่งออกนี้';
$string['confirmsummary'] = 'สรุปการส่งออกของคุณ';
$string['continuetoportfolio'] = 'ไปที่พอร์ตโฟลิโอของคุณ';
$string['deleteportfolio'] = 'ลบพอร์ตโฟลิโอ';
$string['destination'] = 'ปลายทาง';
$string['disabled'] = 'ขออภัย การส่งออกพอร์ตโฟลิโอไม่ได้เปิดใช้งานในไซต์นี้';
$string['disabledinstance'] = 'ปิดใช้งาน';
$string['displayarea'] = 'พื้นที่ส่งออก';
$string['displayexpiry'] = 'เวลาหมดอายุของการถ่ายโอน';
$string['displayinfo'] = 'ข้อมูลการส่งออก';
$string['dontwait'] = 'ไม่ต้องรอ';
$string['enabled'] = 'เปิดใช้งานพอร์ตโฟลิโอ';
$string['enableddesc'] = 'หากเปิดใช้งาน ผู้ใช้สามารถส่งออกเนื้อหา เช่น โพสต์ในกระดานเสวนา และงานที่ส่ง ไปยังพอร์ตโฟลิโอภายนอกหรือหน้าเว็บ HTML ได้';
$string['err_uniquename'] = 'ชื่อพอร์ตโฟลิโอต้องไม่ซ้ำกัน (ต่อปลั๊กอิน)';
$string['exportalreadyfinished'] = 'ส่งออกพอร์ตโฟลิโอเสร็จสมบูรณ์!';
$string['exportalreadyfinisheddesc'] = 'ส่งออกพอร์ตโฟลิโอเสร็จสมบูรณ์!';
$string['exportcomplete'] = 'ส่งออกพอร์ตโฟลิโอเสร็จสมบูรณ์!';
$string['exportedpreviously'] = 'การส่งออกก่อนหน้า';
$string['exportexceptionnoexporter'] = 'เกิด portfolio_export_exception พร้อมเซสชันที่กำลังทำงานอยู่แต่ไม่มีอ็อบเจกต์ผู้ส่งออก';
$string['exportexpired'] = 'การส่งออกพอร์ตโฟลิโอหมดอายุ';
$string['exportexpireddesc'] = 'คุณพยายามส่งออกข้อมูลซ้ำ หรือเริ่มการส่งออกที่ว่างเปล่า ในการดำเนินการดังกล่าวอย่างถูกต้อง คุณควรกลับไปที่ตำแหน่งเดิมและเริ่มใหม่อีกครั้ง บางครั้งสิ่งนี้เกิดขึ้นหากคุณใช้ปุ่มย้อนกลับหลังจากส่งออกเสร็จสิ้น หรือจากการทำบุ๊กมาร์ก URL ที่ไม่ถูกต้อง';
$string['exporting'] = 'กำลังส่งออกไปยังพอร์ตโฟลิโอ';
$string['exportingcontentfrom'] = 'กำลังส่งออกเนื้อหาจาก {$a}';
$string['exportingcontentto'] = 'กำลังส่งออกเนื้อหาไปยัง {$a}';
$string['exportqueued'] = 'การส่งออกพอร์ตโฟลิโอถูกเข้าคิวเพื่อถ่ายโอนข้อมูลสำเร็จแล้ว';
$string['exportqueuedforced'] = 'การส่งออกพอร์ตโฟลิโอถูกเข้าคิวเพื่อถ่ายโอนข้อมูลสำเร็จแล้ว (ระบบปลายทางบังคับให้การถ่ายโอนต้องเข้าคิว)';
$string['failedtopackage'] = 'ไม่พบไฟล์ที่จะรวบรวม';
$string['failedtosendpackage'] = 'ไม่สามารถส่งข้อมูลของคุณไปยังระบบพอร์ตโฟลิโอที่เลือกได้: ข้อผิดพลาดเดิมคือ {$a}';
$string['filedenied'] = 'ถูกปฏิเสธการเข้าถึงไฟล์นี้';
$string['filenotfound'] = 'ไม่พบไฟล์';
$string['fileoutputnotsupported'] = 'รูปแบบนี้ไม่รองรับการเขียนทับไฟล์ผลลัพธ์';
$string['format_document'] = 'เอกสาร';
$string['format_file'] = 'ไฟล์';
$string['format_image'] = 'รูปภาพ';
$string['format_leap2a'] = 'รูปแบบพอร์ตโฟลิโอ Leap2A';
$string['format_mbkp'] = 'รูปแบบการสำรองข้อมูล Moodle';
$string['format_pdf'] = 'PDF';
$string['format_plainhtml'] = 'HTML';
$string['format_presentation'] = 'การนำเสนอ (Presentation)';
$string['format_richhtml'] = 'HTML พร้อมไฟล์แนบ';
$string['format_spreadsheet'] = 'สเปรดชีต';
$string['format_text'] = 'ข้อความธรรมดา (Plain text)';
$string['format_video'] = 'วิดีโอ';
$string['highdbsizethreshold'] = 'เกณฑ์ขนาดฐานข้อมูลสำหรับการถ่ายโอนข้อมูลจำนวนมาก';
$string['highdbsizethresholddesc'] = 'จำนวนระเบียนฐานข้อมูลที่เกินกว่านี้จะถือว่าใช้เวลาในการถ่ายโอนนานมาก';
$string['highfilesizethreshold'] = 'เกณฑ์ขนาดไฟล์สำหรับการถ่ายโอนข้อมูลจำนวนมาก';
$string['highfilesizethresholddesc'] = 'ขนาดไฟล์ที่เกินเกณฑ์นี้จะถือว่าใช้เวลาในการถ่ายโอนนานมาก';
$string['insanebody'] = 'สวัสดี! คุณได้รับข้อความนี้ในฐานะผู้ดูแลระบบของ {$a->sitename}

ปลั๊กอินพอร์ตโฟลิโอบางรายการถูกปิดใช้งานโดยอัตโนมัติเนื่องจากการตั้งค่าไม่ถูกต้อง ซึ่งหมายความว่าผู้ใช้ไม่สามารถส่งออกเนื้อหาไปยังพอร์ตโฟลิโอเหล่านี้ได้ในขณะนี้

รายการปลั๊กอินพอร์ตโฟลิโอที่ถูกปิดใช้งานคือ:

{$a->textlist}

ควรได้รับการแก้ไขโดยเร็วที่สุด โดยไปที่ {$a->fixurl}';
$string['insanebodyhtml'] = '<p>สวัสดี! คุณได้รับข้อความนี้ในฐานะผู้ดูแลระบบของ {$a->sitename}</p>
<p>ปลั๊กอินพอร์ตโฟลิโอบางรายการถูกปิดใช้งานโดยอัตโนมัติเนื่องจากการตั้งค่าไม่ถูกต้อง ซึ่งหมายความว่าผู้ใช้ไม่สามารถส่งออกเนื้อหาไปยังพอร์ตโฟลิโอเหล่านี้ได้ในขณะนี้</p>
<p>รายการปลั๊กอินพอร์ตโฟลิโอที่ถูกปิดใช้งานคือ:</p>
{$a->htmllist}
<p>ควรได้รับการแก้ไขโดยเร็วที่สุด โดยไปที่ <a href="{$a->fixurl}">หน้านำเสนอการกำหนดค่าพอร์ตโฟลิโอ</a></p>';
$string['insanesubject'] = 'อินสแตนซ์บางรายการของพอร์ตโฟลิโอถูกปิดใช้งานโดยอัตโนมัติ';
$string['instancedeleted'] = 'ลบพอร์ตโฟลิโอสำเร็จแล้ว';
$string['instanceismisconfigured'] = 'การกำหนดค่าพอร์ตโฟลิโอไม่ถูกต้อง กำลังข้าม ข้อผิดพลาดคือ: {$a}';
$string['instancenotdelete'] = 'ลบพอร์ตโฟลิโอไม่สำเร็จ';
$string['instancenotsaved'] = 'บันทึกพอร์ตโฟลิโอไม่สำเร็จ';
$string['instancesaved'] = 'บันทึกพอร์ตโฟลิโอสำเร็จแล้ว';
$string['intro'] = 'เนื้อหาที่คุณสร้างขึ้น เช่น งานที่ส่ง โพสต์ในกระดานเสวนา และบล็อก สามารถส่งออกไปยังพอร์ตโฟลิโอหรือดาวน์โหลดได้<br>
พอร์ตโฟลิโอใด ๆ ที่คุณไม่ต้องการใช้อาจถูกซ่อนไว้เพื่อไม่ให้แสดงเป็นตัวเลือกในการส่งออกเนื้อหา';
$string['invalidaddformat'] = 'รูปแบบการเพิ่มที่ส่งไปยัง portfolio_add_button ไม่ถูกต้อง ({$a}) ต้องเป็นหนึ่งใน PORTFOLIO_ADD_XXX';
$string['invalidbuttonproperty'] = 'ไม่พบคุณสมบัติ ({$a}) ของ portfolio_button';
$string['invalidconfigproperty'] = 'ไม่พบคุณสมบัติการกำหนดค่า ({$a->property} ของ {$a->class})';
$string['invalidexportproperty'] = 'ไม่พบคุณสมบัติการกำหนดค่าการส่งออก ({$a->property} ของ {$a->class})';
$string['invalidfileareaargs'] = 'อาร์กิวเมนต์ที่ส่งไปยัง set_file_and_format_data ไม่ถูกต้อง - ต้องประกอบด้วย contextid, component, filearea และ itemid';
$string['invalidformat'] = 'มีบางสิ่งกำลังส่งออกในรูปแบบที่ไม่ถูกต้อง: {$a}';
$string['invalidinstance'] = 'ไม่พบอินสแตนซ์ของพอร์ตโฟลิโอนั้น';
$string['invalidpreparepackagefile'] = 'การเรียกใช้ prepare_package_file ไม่ถูกต้อง - ต้องกำหนดค่า single หรือ multifiles';
$string['invalidproperty'] = 'ไม่พบคุณสมบัติ ({$a->property} ของ {$a->class})';
$string['invalidsha1file'] = 'การเรียกใช้ get_sha1_file ไม่ถูกต้อง - ต้องกำหนดค่า single หรือ multifiles';
$string['invalidtempid'] = 'ตัวระบุการส่งออกไม่ถูกต้อง หรืออาจหมดอายุแล้ว';
$string['invaliduserproperty'] = 'ไม่พบคุณสมบัติการกำหนดค่าผู้ใช้ ({$a->property} ของ {$a->class})';
$string['leap2a_emptyselection'] = 'ไม่ได้รับค่าที่จำเป็น';
$string['leap2a_entryalreadyexists'] = 'คุณพยายามเพิ่มรายการ Leap2A ด้วยรหัส ({$a}) ที่มีอยู่แล้วในฟีดนี้';
$string['leap2a_feedtitle'] = 'ส่งออก Leap2A จาก Moodle สำหรับ {$a}';
$string['leap2a_filecontent'] = 'พยายามกำหนดเนื้อหาของรายการ Leap2A เป็นไฟล์ แทนที่จะใช้คลาสย่อยของไฟล์';
$string['leap2a_invalidentryfield'] = 'คุณพยายามกำหนดค่ารายการที่ไม่มีอยู่จริง ({$a}) หรือไม่สามารถกำหนดค่าได้โดยตรง';
$string['leap2a_invalidentryid'] = 'คุณพยายามเข้าถึงรายการด้วยรหัสที่ไม่มีอยู่จริง ({$a})';
$string['leap2a_missingfield'] = 'ฟิลด์ข้อมูลรายการ Leap2A ที่จำเป็น {$a} ขาดหายไป';
$string['leap2a_nonexistantlink'] = 'รายการ Leap2A ({$a->from}) พยายามลิงก์ไปยังรายการที่ไม่มีอยู่จริง ({$a->to}) ด้วยความสัมพันธ์ {$a->rel}';
$string['leap2a_overwritingselection'] = 'กำลังเขียนทับประเภทดั้งเดิมของรายการ ({$a}) ไปเป็นการเลือกใน make_selection';
$string['leap2a_selflink'] = 'รายการ Leap2A ({$a->id}) พยายามลิงก์ไปยังตัวเองด้วยความสัมพันธ์ {$a->rel}';
$string['logs'] = 'บันทึกประวัติการถ่ายโอน';
$string['logsummary'] = 'รายการการถ่ายโอนที่สำเร็จก่อนหน้านี้';
$string['manageportfolios'] = 'จัดการพอร์ตโฟลิโอ';
$string['manageyourportfolios'] = 'จัดการพอร์ตโฟลิโอของคุณ';
$string['mimecheckfail'] = 'ปลั๊กอินพอร์ตโฟลิโอ {$a->plugin} ไม่รองรับประเภทไฟล์ {$a->mimetype}';
$string['missingcallbackarg'] = 'อาร์กิวเมนต์ callback {$a->arg} สำหรับคลาส {$a->class} ขาดหายไป';
$string['moderatedbsizethreshold'] = 'เกณฑ์ขนาดฐานข้อมูลสำหรับการถ่ายโอนข้อมูลปานกลาง';
$string['moderatedbsizethresholddesc'] = 'จำนวนระเบียนฐานข้อมูลที่เกินกว่านี้จะถือว่าใช้เวลาในการถ่ายโอนปานกลาง';
$string['moderatefilesizethreshold'] = 'เกณฑ์ขนาดไฟล์สำหรับการถ่ายโอนข้อมูลปานกลาง';
$string['moderatefilesizethresholddesc'] = 'ขนาดไฟล์ที่เกินเกณฑ์นี้จะถือว่าใช้เวลาในการถ่ายโอนปานกลาง';
$string['multipleinstancesdisallowed'] = 'พยายามสร้างอินสแตนซ์อื่นของปลั๊กอินที่ไม่อนุญาตให้มีหลายอินสแตนซ์ ({$a})';
$string['mustsetcallbackoptions'] = 'คุณต้องตั้งค่าตัวเลือก callback ในตัวสร้าง portfolio_add_button หรือใช้วิธี set_callback_options';
$string['noavailableplugins'] = 'ขออภัย ไม่มีพอร์ตโฟลิโอที่คุณสามารถส่งออกไปได้';
$string['nocallbackclass'] = 'ไม่พบคลาส callback ที่จะใช้ ({$a})';
$string['nocallbackcomponent'] = 'ไม่พบส่วนประกอบที่ระบุ {$a}';
$string['nocallbackfile'] = 'มีบางอย่างในโมดูลที่คุณพยายามส่งออกขัดข้อง - ไม่พบไฟล์พอร์ตโฟลิโอที่ต้องการ';
$string['noclassbeforeformats'] = 'คุณต้องตั้งค่าคลาส callback ก่อนเรียกใช้ set_formats ใน portfolio_button';
$string['nocommonformats'] = 'ไม่มีรูปแบบร่วมกันระหว่างปลั๊กอินพอร์ตโฟลิโอที่มีอยู่กับตำแหน่งที่เรียกใช้งาน {$a->location} (ผู้เรียกใช้รองรับรูปแบบ {$a->formats})';
$string['noinstanceyet'] = 'ยังไม่ได้เลือก';
$string['nologs'] = 'ไม่มีประวัติบันทึกที่จะแสดง!';
$string['nomultipleexports'] = 'ขออภัย ปลายทางพอร์ตโฟลิโอ ({$a->plugin}) ไม่รองรับการส่งออกพร้อมกันหลายรายการ โปรด <a href="{$a->link}">ดำเนินการรายการปัจจุบันให้เสร็จสิ้นก่อน</a> แล้วลองใหม่อีกครั้ง';
$string['nonprimative'] = 'มีการส่งค่าที่ไม่เป็นค่าพื้นฐาน (non primitive) เป็นอาร์กิวเมนต์ callback ไปยัง portfolio_add_button ระบบไม่สามารถดำเนินการต่อได้ คีย์คือ {$a->key} และค่าคือ {$a->value}';
$string['nopermissions'] = 'ขออภัย คุณไม่มีสิทธิ์ที่จำเป็นในการส่งออกไฟล์จากพื้นที่นี้';
$string['notexportable'] = 'ขออภัย ประเภทเนื้อหาที่คุณกำลังพยายามส่งออกไม่สามารถส่งออกได้';
$string['notimplemented'] = 'ขออภัย คุณกำลังพยายามส่งออกเนื้อหาในรูปแบบที่ยังไม่ได้ดำเนินการ ({$a})';
$string['notyetselected'] = 'ยังไม่ได้เลือก';
$string['notyours'] = 'คุณกำลังพยายามดำเนินการส่งออกพอร์ตโฟลิโอต่อซึ่งไม่ได้เป็นของคุณ!';
$string['nouploaddirectory'] = 'ไม่สามารถสร้างไดเรกทอรีชั่วคราวเพื่อรวบรวมข้อมูลของคุณได้';
$string['off'] = 'เปิดใช้งานแต่ซ่อนไว้';
$string['on'] = 'เปิดใช้งานและมองเห็น';
$string['plugin'] = 'ปลั๊กอินพอร์ตโฟลิโอ';
$string['plugincouldnotpackage'] = 'ไม่สามารถรวบรวมข้อมูลของคุณเพื่อส่งออกได้: ข้อผิดพลาดเดิมคือ {$a}';
$string['pluginismisconfigured'] = 'การกำหนดค่าปลั๊กอินพอร์ตโฟลิโอไม่ถูกต้อง กำลังข้าม ข้อผิดพลาดคือ: {$a}';
$string['portfolio'] = 'พอร์ตโฟลิโอ';
$string['portfolios'] = 'พอร์ตโฟลิโอ';
$string['privacy:metadata'] = 'ระบบย่อยพอร์ตโฟลิโอทำหน้าที่เป็นช่องทางส่งคำขอจากปลั๊กอินต่าง ๆ ไปยังปลั๊กอินพอร์ตโฟลิโอ';
$string['privacy:metadata:name'] = 'ชื่อการตั้งค่า';
$string['privacy:metadata:instance'] = 'ตัวระบุพอร์ตโฟลิโอ';
$string['privacy:metadata:instancesummary'] = 'เก็บข้อมูลเกี่ยวกับอินสแตนซ์และการตั้งค่าพอร์ตโฟลิโอ';
$string['privacy:metadata:portfolio_log'] = 'ประวัติการถ่ายโอนพอร์ตโฟลิโอ (ใช้ตรวจสอบรายการซ้ำในภายหลัง)';
$string['privacy:metadata:portfolio_log:caller_class'] = 'ชื่อของคลาสที่ใช้สร้างการถ่ายโอน';
$string['privacy:metadata:portfolio_log:caller_component'] = 'ชื่อส่วนประกอบที่รับผิดชอบการส่งออก';
$string['privacy:metadata:portfolio_log:time'] = 'เวลาในการถ่ายโอน (ในกรณีของการถ่ายโอนแบบเข้าคิว นี่คือเวลาที่การถ่ายโอนถูกทำงานจริง ไม่ใช่เวลาที่ผู้ใช้เริ่มสั่งการ)';
$string['privacy:metadata:portfolio_log:userid'] = 'ID ของผู้ใช้ที่ส่งออกเนื้อหา';
$string['privacy:metadata:portfolio_tempdata'] = 'เก็บข้อมูลชั่วคราวสำหรับการส่งออกพอร์ตโฟลิโอ';
$string['privacy:metadata:portfolio_tempdata:data'] = 'ข้อมูลการส่งออก';
$string['privacy:metadata:portfolio_tempdata:expirytime'] = 'เวลาที่บันทึกนี้จะหมดอายุ';
$string['privacy:metadata:portfolio_tempdata:instance'] = 'อินสแตนซ์ปลั๊กอินพอร์ตโฟลิโอที่ถูกใช้งานอยู่';
$string['privacy:metadata:portfolio_tempdata:userid'] = 'ผู้ใช้ที่กำลังดำเนินการส่งออก';
$string['privacy:metadata:value'] = 'ค่าสำหรับการตั้งค่า';
$string['privacy:metadata:userid'] = 'ID ของผู้ใช้';
$string['privacy:path'] = 'อินสแตนซ์พอร์ตโฟลิโอ';
$string['queuesummary'] = 'รายการถ่ายโอนที่อยู่ในคิวขณะนี้';
$string['returntowhereyouwere'] = 'กลับไปที่ตำแหน่งเดิมที่คุณอยู่';
$string['save'] = 'บันทึก';
$string['selectedformat'] = 'รูปแบบการส่งออกที่เลือก';
$string['selectedwait'] = 'เลือกที่จะรอ?';
$string['selectplugin'] = 'เลือกปลายทาง';
$string['showhide'] = 'แสดง / ซ่อน';
$string['singleinstancenomultiallowed'] = 'มีปลั๊กอินพอร์ตโฟลิโอเพียงรายการเดียวเท่านั้นที่พร้อมใช้งาน และไม่รองรับการส่งออกหลายรายการพร้อมกันในหนึ่งเซสชัน และมีการส่งออกที่กำลังทำงานอยู่ในเซสชันที่ใช้ปลั๊กอินนี้อยู่แล้ว!';
$string['somepluginsdisabled'] = 'ปลั๊กอินพอร์ตโฟลิโอบางรายการถูกปิดใช้งานเนื่องจากการกำหนดค่าไม่ถูกต้องหรือพึ่งพาอย่างอื่นที่ขัดข้อง:';
$string['sure'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบ \'{$a}\'? ไม่สามารถย้อนกลับการกระทำนี้ได้';
$string['thirdpartyexception'] = 'เกิดข้อผิดพลาดจากซอฟต์แวร์บุคคลที่สามในระหว่างการส่งออกพอร์ตโฟลิโอ ({$a}) แม้ตรวจพบและเริ่มใหม่แล้ว แต่ควรได้รับการแก้ไขให้ถูกต้องจริง ๆ';
$string['transfertime'] = 'เวลาที่ใช้ถ่ายโอน';
$string['unknownplugin'] = 'ไม่รู้จัก (อาจถูกลบออกโดยผู้ดูแลระบบแล้ว)';
$string['wait'] = 'รอ';
$string['wanttowait_high'] = 'ไม่แนะนำให้คุณรอจนการถ่ายโอนนี้เสร็จสิ้น แต่คุณสามารถเลือกที่จะรอได้หากคุณมั่นใจ';
$string['wanttowait_moderate'] = 'คุณต้องการรอสำหรับการถ่ายโอนนี้หรือไม่? อาจใช้เวลาไม่เกินสองสามนาที';
$string['insanebodysmall'] = 'สวัสดี! คุณได้รับข้อความนี้ในฐานะผู้ดูแลระบบของ {$a->sitename} อินสแตนซ์บางรายการของปลั๊กอินพอร์ตโฟลิโอถูกปิดใช้งานโดยอัตโนมัติเนื่องจากการตั้งค่าไม่ถูกต้อง ซึ่งหมายความว่าผู้ใช้ไม่สามารถส่งออกเนื้อหาไปยังพอร์ตโฟลิโอเหล่านี้ได้ในขณะนี้ ควรได้รับการแก้ไขโดยเร็วที่สุด โดยไปที่ {$a->fixurl}';

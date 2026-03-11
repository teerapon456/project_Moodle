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
 * Cache language strings
 *
 * This file is part of Moodle's cache API, affectionately called MUC.
 * It contains the components that are requried in order to use caching.
 *
 * @package    core_cache
 * @category   cache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'การดำเนินการ';
$string['addinstance'] = 'เพิ่มอินสแตนซ์';
$string['addnewlockinstance'] = 'เพิ่มอินสแตนซ์การล็อกใหม่';
$string['addlocksuccess'] = 'เพิ่มอินสแตนซ์การล็อกใหม่สำเร็จ';
$string['addstore'] = 'เพิ่มที่จัดเก็บ {$a}';
$string['addstoresuccess'] = 'เพิ่มที่จัดเก็บ {$a} ใหม่สำเร็จ';
$string['area'] = 'พื้นที่';
$string['caching'] = 'การทำแคช';
$string['cacheadmin'] = 'การจัดการแคช';
$string['cacheconfig'] = 'การตั้งค่าโปรแกรม';
$string['cachedef_ai_policy'] = 'การยอมรับนโยบาย AI ของผู้ใช้';
$string['cachedef_ai_ratelimit'] = 'การจำกัดอัตราการเรียกใช้ผู้ให้บริการ AI';
$string['cachedef_calculablesinfo'] = 'ข้อมูลการคำนวณการวิเคราะห์';
$string['cachedef_calendar_subscriptions'] = 'การสมัครรับข้อมูลปฏิทิน';
$string['cachedef_calendar_categories'] = 'หมวดหมู่รายวิชาในปฏิทินที่ผู้ใช้สามารถเข้าถึงได้';
$string['cachedef_capabilities'] = 'รายการความสามารถของระบบ';
$string['cachedef_config'] = 'การตั้งค่าคอนฟิก';
$string['cachedef_contentbank_allowed_categories'] = 'หมวดหมู่รายวิชาที่มีสิทธิ์เข้าถึงคลังเนื้อหาสำหรับผู้ใช้ปัจจุบัน';
$string['cachedef_contentbank_allowed_courses'] = 'รายวิชาที่มีสิทธิ์เข้าถึงคลังเนื้อหาสำหรับผู้ใช้ปัจจุบัน';
$string['cachedef_contentbank_enabled_extensions'] = 'นามสกุลไฟล์ที่อนุญาตและปลั๊กอินสนับสนุนในคลังเนื้อหา';
$string['cachedef_contentbank_context_extensions'] = 'นามสกุลไฟล์ที่อนุญาตและปลั๊กอินสนับสนุนในบริบทคลังเนื้อหา';
$string['cachedef_courseactionsinstances'] = 'อินสแตนซ์การดำเนินการรายวิชาที่โหลดแล้ว';
$string['cachedef_coursecat'] = 'รายการหมวดหมู่รายวิชาสำหรับผู้ใช้เฉพาะราย';
$string['cachedef_coursecatrecords'] = 'ระเบียนหมวดหมู่รายวิชา';
$string['cachedef_coursesectionspreferences'] = 'การตั้งค่าส่วนประกอบรายวิชา';
$string['cachedef_coursecattree'] = 'โครงสร้างต้นไม้หมวดหมู่รายวิชา';
$string['cachedef_coursecompletion'] = 'สถานะการเรียนจบรายวิชา';
$string['cachedef_coursecontacts'] = 'รายการผู้ติดต่อรายวิชา';
$string['cachedef_coursehiddengroups'] = 'จำนวนกลุ่มในรายวิชาที่จำกัดการมองเห็น';
$string['cachedef_coursemodinfo'] = 'ข้อมูลสะสมเกี่ยวกับโมดูลและส่วนต่าง ๆ ในแต่ละรายวิชา';
$string['cachedef_courseeditorstate'] = 'คีย์แคชสถานะรายวิชาในเซสชัน เพื่อตรวจหาการเปลี่ยนแปลงรายวิชาในส่วนหน้า (frontend)';
$string['cachedef_course_image'] = 'รูปภาพรายวิชา';
$string['cachedef_course_user_dates'] = 'วันที่ของผู้ใช้สำหรับรายวิชาที่ตั้งค่าโหมดวันที่แบบสัมพันธ์';
$string['cachedef_completion'] = 'สถานะการทำกิจกรรมเสร็จสมบูรณ์';
$string['cachedef_databasemeta'] = 'ข้อมูล Meta ของฐานข้อมูล';
$string['cachedef_eventinvalidation'] = 'การยกเลิกเหตุการณ์';
$string['cachedef_externalbadges'] = 'แบดจ์ภายนอกสำหรับผู้ใช้เฉพาะราย';
$string['cachedef_fontawesomeiconmapping'] = 'การจับคู่ไอคอนสำหรับ Font Awesome';
$string['cachedef_file_imageinfo'] = 'ข้อมูลรูปภาพไฟล์ เช่น ขนาดความกว้างยาว';
$string['cachedef_routes'] = 'ข้อมูลเส้นทาง (Route)';
$string['cachedef_suspended_userids'] = 'รายการผู้ใช้ที่ถูกระงับในแต่ละรายวิชา';
$string['cachedef_groupdata'] = 'ข้อมูลกลุ่มรายวิชา';
$string['cachedef_h5p_content_type_translations'] = 'การแปลคำศัพท์ไลบรารีชนิดเนื้อหา H5P';
$string['cachedef_h5p_libraries'] = 'ไลบรารี H5P';
$string['cachedef_h5p_library_files'] = 'ไฟล์ไลบรารี H5P';
$string['cachedef_htmlpurifier'] = 'ตัวทำความสะอาด HTML (HTML Purifier) - เนื้อหาที่ทำความสะอาดแล้ว';
$string['cachedef_langmenu'] = 'รายการภาษาที่มีให้ใช้งาน';
$string['cachedef_license'] = 'รายการใบอนุญาต';
$string['cachedef_message_time_last_message_between_users'] = 'เวลาที่สร้างข้อความล่าสุดในการสนทนา';
$string['cachedef_modelfirstanalyses'] = 'การวิเคราะห์ครั้งแรกตามโมเดลและสิ่งที่วิเคราะห์ได้';
$string['cachedef_moodlenet_usercanshare'] = 'ผู้ใช้สามารถแชร์ทรัพยากรไปยัง MoodleNet ได้';
$string['cachedef_locking'] = 'การล็อก';
$string['cachedef_message_processors_enabled'] = "สถานะการเปิดใช้งานตัวประมวลผลข้อความ";
$string['cachedef_contextwithinsights'] = 'บริบทที่มีอินไซต์ (Insight)';
$string['cachedef_navigation_cache'] = 'แคชการนำทาง';
$string['cachedef_navigation_expandcourse'] = 'รายวิชาที่สามารถขยายได้ในการนำทาง';
$string['cachedef_observers'] = 'ตัวสังเกตการณ์เหตุการณ์ (Event observers)';
$string['cachedef_plugin_functions'] = 'Callbacks ของปลั๊กอินที่มีให้ใช้งาน';
$string['cachedef_plugin_manager'] = 'ตัวจัดการข้อมูลปลั๊กอิน';
$string['cachedef_presignup'] = 'ข้อมูลก่อนการลงทะเบียนสำหรับผู้ใช้ที่ยังไม่ได้จดทะเบียนเฉพาะราย';
$string['cachedef_portfolio_add_button_portfolio_instances'] = 'อินสแตนซ์พอร์ตโฟลิโอสำหรับคลาส portfolio_add_button';
$string['cachedef_postprocessedcss'] = 'CSS ที่ผ่านการประมวลผลแล้ว (Post processed)';
$string['cachedef_tagindexbuilder'] = 'ผลการค้นหาสำหรับรายการที่มีแท็ก';
$string['cachedef_questiondata'] = 'คำจำกัดความของคำถาม';
$string['cachedef_recommendation_favourite_course_content_items'] = 'คำแนะนำรายการเนื้อหารายวิชา';
$string['cachedef_reportbuilder_allowed_reports'] = 'รางานที่อนุญาตให้ผู้ใช้เข้าถึงตามกลุ่มเป้าหมาย';
$string['cachedef_repositories'] = 'ข้อมูลอินสแตนซ์ของคลังเก็บไฟล์ (Repositories)';
$string['cachedef_roledefs'] = 'คำจำกัดความบทบาท';
$string['cachedef_grade_categories'] = 'การสืบค้นหมวดหมู่เกรด';
$string['cachedef_grade_letters'] = 'การสืบค้นเกรดที่เป็นตัวอักษร';
$string['cachedef_string'] = 'แคชของข้อความภาษา';
$string['cachedef_tags'] = 'ชุดสะสมและพื้นที่ของแท็ก';
$string['cachedef_temp_tables'] = 'แคชตารางชั่วคราว';
$string['cachedef_theme_usedincontext'] = 'ธีมที่ถูกใช้ในบริบทเพื่อแทนที่ธีมเริ่มต้น';
$string['cachedef_userselections'] = 'ข้อมูลที่ใช้ในการคงค่าการเลือกของผู้ใช้ทั่วทั้ง Moodle';
$string['cachedef_user_favourite_course_content_items'] = 'รายการที่ติดดาวของผู้ใช้';
$string['cachedef_user_group_groupings'] = 'กลุ่มและการจัดกลุ่มของผู้ใช้ต่อรายวิชา';
$string['cachedef_user_course_content_items'] = 'รายการเนื้อหาของผู้ใช้ (กิจกรรม ทรัพยากร และประเภทย่อย) ต่อรายวิชา';
$string['cachedef_yuimodules'] = 'คำจำกัดความโมดูล YUI';
$string['cachedef_gradesetting'] = 'การตั้งค่าเกรดรายวิชา';
$string['cachelock_file_default'] = 'การล็อกไฟล์เริ่มต้น';
$string['cachestores'] = 'ที่จัดเก็บแคช';
$string['cacheusage'] = 'การใช้งานแคช';
$string['canuselocalstore'] = 'สามารถใช้ที่จัดเก็บท้องถิ่นได้';
$string['component'] = 'ส่วนประกอบ';
$string['confirmlockdeletion'] = 'ยืนยันการลบการล็อก';
$string['confirmstoredeletion'] = 'ยืนยันการลบที่จัดเก็บ';
$string['defaultmappings'] = 'ที่จัดเก็บที่ใช้เมื่อไม่มีการจับคู่ (Mapping)';
$string['defaultmappings_help'] = 'เหล่านี้คือที่จัดเก็บเริ่มต้นที่จะถูกใช้งาน หากคุณไม่ได้จับคู่ที่จัดเก็บอย่างน้อยหนึ่งที่เข้ากับคำจำกัดความแคช';
$string['defaultstoreactions'] = 'ไม่สามารถแก้ไขที่จัดเก็บเริ่มต้นได้';
$string['default_application'] = 'ที่จัดเก็บแอปพลิเคชันเริ่มต้น';
$string['default_request'] = 'ที่จัดเก็บคำขอ (Request) เริ่มต้น';
$string['default_session'] = 'ที่จัดเก็บเซสชันเริ่มต้น';
$string['definition'] = 'คำจำกัดความ';
$string['definitionsummaries'] = 'คำจำกัดความแคชที่รู้จัก';
$string['delete'] = 'ลบ';
$string['deletelock'] = 'ลบการล็อก';
$string['deletelockconfirmation'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบการล็อก {$a}?';
$string['deletelockhasuses'] = 'คุณไม่สามารถลบอินสแตนซ์การล็อกนี้ได้ เนื่องจากมีการใช้งานโดยที่จัดเก็บอย่างน้อยหนึ่งที่';
$string['deletelocksuccess'] = 'ลบการล็อกสำเร็จ';
$string['deletestore'] = 'ลบที่จัดเก็บ';
$string['deletestoreconfirmation'] = 'คุณแน่ใจหรือไม่ว่าต้องการลบที่จัดเก็บ "{$a}"?';
$string['deletestorehasmappings'] = 'คุณไม่สามารถลบบที่จัดเก็บนี้ได้เนื่องจากมีการจับคู่ (Mapping) อยู่ กรุณาลบการจับคู่ทั้งหมดก่อนลบที่จัดเก็บ';
$string['deletestoresuccess'] = 'ลบที่จัดเก็บแคชสำเร็จ';
$string['editmappings'] = 'แก้ไขการจับคู่';
$string['editsharing'] = 'แก้ไขการแชร์';
$string['editstore'] = 'แก้ไขที่จัดเก็บ';
$string['editstoresuccess'] = 'แก้ไขที่จัดเก็บแคชสำเร็จ';
$string['editdefinitionmapping'] = 'แก้ไขการจับคู่คำจำกัดความ';
$string['editdefinitionmappings'] = 'การจับคู่ที่จัดเก็บคำจำกัดความ {$a}';
$string['editdefinitionsharing'] = 'แก้ไขการแชร์คำจำกัดความสำหรับ {$a}';
$string['ex_configcannotsave'] = 'ไม่สามารถบันทึกการตั้งค่าแคชลงในไฟล์ได้';
$string['ex_nodefaultlock'] = 'ไม่พบอินสแตนซ์การล็อกเริ่มต้น';
$string['ex_unabletolock'] = 'ไม่สามารถขอสิทธิ์การล็อกสำหรับการทำแคชได้';
$string['ex_unmetstorerequirements'] = 'คุณไม่สามารถใช้ที่จัดเก็บนี้ได้ในขณะนี้ กรุณาอ้างอิงเอกสารประกอบเพื่อตรวจสอบความต้องการของระบบ';
$string['gethit'] = 'ดึงข้อมูล - พบ (Hit)';
$string['getmiss'] = 'ดึงข้อมูล - ไม่พบ (Miss)';
$string['inadequatestoreformapping'] = 'ที่จัดเก็บนี้ไม่ตรงตามความต้องการสำหรับคำจำกัดความที่รู้จักทั้งหมด คำจำกัดความที่ที่จัดเก็บนี้ไม่รองรับจะถูกกำหนดให้ใช้ที่จัดเก็บเริ่มต้นดั้งเดิมแทนที่จัดเก็บที่เลือกไว้';
$string['invalidlock'] = 'การล็อกไม่ถูกต้อง';
$string['invalidplugin'] = 'ปลั๊กอินไม่ถูกต้อง';
$string['invalidstore'] = 'ที่จัดเก็บแคชที่ระบุไม่ถูกต้อง';
$string['localstorenotification'] = 'แคชนี้สามารถจับคู่เข้ากับที่จัดเก็บที่เป็นแบบท้องถิ่น (Local) สำหรับแต่ละเว็บเซิร์ฟเวอร์ได้อย่างปลอดภัย';
$string['lockdefault'] = 'เริ่มต้น';
$string['locking'] = 'การล็อก';
$string['locking_help'] = 'การล็อกคือกลไกที่จำกัดการเข้าถึงข้อมูลที่ทำแคชไว้ให้เข้าถึงได้เพียงหนึ่งกระบวนการ (process) ในเวลาเดียวกัน เพื่อป้องกันไม่ให้ข้อมูลถูกเขียนทับ วิธีการล็อกจะเป็นตัวกำหนดวิธีขอสิทธิ์และตรวจสอบการล็อก';
$string['lockname'] = 'ชื่อ';
$string['locknamedesc'] = 'ชื่อต้องไม่ซ้ำกันและประกอบด้วยตัวอักษร a-zA-Z_ เท่านั้น';
$string['locknamenotunique'] = 'ชื่อที่คุณเลือกซ้ำกับที่มีอยู่แล้ว กรุณาเลือกชื่ออื่นที่ไม่ซ้ำกัน';
$string['locksummary'] = 'สรุปอินสแตนซ์การล็อกแคช';
$string['locktype'] = 'ประเภท';
$string['lockuses'] = 'การใช้งาน';
$string['mappings'] = 'การจับคู่ที่จัดเก็บ';
$string['mappingdefault'] = '(เริ่มต้น)';
$string['mappingprimary'] = 'ที่จัดเก็บหลัก';
$string['mappingfinal'] = 'ที่จัดเก็บสุดท้าย';
$string['mode'] = 'โหมด';
$string['modes'] = 'โหมดต่าง ๆ';
$string['mode_1'] = 'แอปพลิเคชัน';
$string['mode_2'] = 'เซสชัน';
$string['mode_4'] = 'คำขอ (Request)';
$string['nativelocking'] = 'ปลั๊กอินนี้จัดการการล็อกของตัวเอง';
$string['none'] = 'ไม่มี';
$string['plugin'] = 'ปลั๊กอิน';
$string['pluginsummaries'] = 'ที่จัดเก็บแคชที่ติดตั้งแล้ว';
$string['privacy:metadata:cachestore'] = 'ระบบย่อยแคชจะจัดเก็บข้อมูลไว้ชั่วคราวในนามของส่วนอื่น ๆ ของ Moodle ข้อมูลนี้ไม่สามารถระบุตัวตนได้โดยง่ายและมีอายุการใช้งานสั้นมาก โดยทำหน้าที่เป็นแคชของข้อมูลที่จัดเก็บไว้ที่อื่นใน Moodle ดังนั้นจึงควรได้รับการจัดการโดยส่วนประกอบต้นทางของ Moodle เหล่านั้นอยู่แล้ว';
$string['purge'] = 'ล้างข้อมูล (Purge)';
$string['purgeagain'] = 'ล้างข้อมูลอีกครั้ง';
$string['purgexdefinitionsuccess'] = 'ล้างข้อมูลแคช "{$a->name}" ({$a->component}/{$a->area}) สำเร็จ';
$string['purgexstoresuccess'] = 'ล้างข้อมูลที่จัดเก็บ "{$a->store}" สำเร็จ';
$string['requestcount'] = 'ทดสอบด้วย {$a} คำขอ';
$string['rescandefinitions'] = 'สแกนคำจำกัดความใหม่';
$string['result'] = 'ผลลัพธ์';
$string['set'] = 'ตั้งค่า';
$string['sharedstorenotification'] = 'แคชนี้ต้องจับคู่เข้ากับที่จัดเก็บที่มีการแชร์ร่วมกันไปยังทุกเว็บเซิร์ฟเวอร์';
$string['sharing'] = 'การแชร์';
$string['sharing_all'] = 'ทุกคน';
$string['sharing_input'] = 'คีย์ที่กำหนดเอง (ระบุด้านล่าง)';
$string['sharing_help'] = 'สิ่งนี้ช่วยให้คุณกำหนดวิธีแชร์ข้อมูลแคชได้หากคุณมีการตั้งค่าแบบคลัสเตอร์ หรือหากคุณมีหลายไซต์ที่ใช้ที่จัดเก็บเดียวกันและต้องการแชร์ข้อมูลร่วมกัน นี่คือการตั้งค่าระดับสูง โปรดตรวจสอบให้แน่ใจว่าคุณเข้าใจจุดประสงค์ของมันก่อนทำการเปลี่ยนแปลง';
$string['sharing_siteid'] = 'ไซต์ที่มีรหัสไซต์ (Site ID) เดียวกัน';
$string['sharing_version'] = 'ไซต์ที่รันเวอร์ชันเดียวกัน';
$string['sharingrequired'] = 'คุณต้องเลือกตัวเลือกการแชร์อย่างน้อยหนึ่งรายการ';
$string['sharingselected_all'] = 'ทุกคน';
$string['sharingselected_input'] = 'คีย์ที่กำหนดเอง';
$string['sharingselected_siteid'] = 'รหัสระบุไซต์';
$string['sharingselected_version'] = 'เวอร์ชัน';
$string['storeconfiguration'] = 'การตั้งค่าที่จัดเก็บ';
$string['storename'] = 'ชื่อที่จัดเก็บ';
$string['storename_help'] = 'ใช้ในการระบุชื่อที่จัดเก็บภายในระบบ สามารถประกอบด้วยตัวอักษร a-z A-Z 0-9 -_ และเว้นวรรค และต้องไม่ซ้ำกัน หากคุณใช้ชื่อที่ซ้ำกับที่มีอยู่แล้วระบบจะแจ้งข้อผิดพลาด';
$string['storenamealreadyused'] = 'คุณต้องเลือกชื่อที่ไม่ซ้ำกันสำหรับที่จัดเก็บนี้';
$string['storenameinvalid'] = 'ชื่อที่จัดเก็บไม่ถูกต้อง สามารถใช้ได้เฉพาะ a-z A-Z 0-9 -_ และเว้นวรรคเท่านั้น';
$string['storeperformance'] = 'รายงานประสิทธิภาพที่จัดเก็บแคช - {$a} คำขอที่ไม่ซ้ำกันต่อการดำเนินการ';
$string['storeready'] = 'พร้อมใช้งาน';
$string['storenotready'] = 'ที่จัดเก็บไม่พร้อมใช้งาน';
$string['storerequiresattention'] = 'ต้องได้รับการตรวจสอบ';
$string['storerequiresattention_help'] = 'อินสแตนซ์ที่จัดเก็บนี้ยังไม่พร้อมใช้งานแต่มีการจับคู่ (Mapping) อยู่ การแก้ไขปัญหานี้จะช่วยเพิ่มประสิทธิภาพของระบบ กรุณาตรวจสอบว่าที่จัดเก็บส่วนหลัง (backend) พร้อมใช้งานและตรงตามความต้องการของ PHP หรือไม่';
$string['storeresults_application'] = 'คำขอที่จัดเก็บเมื่อใช้เป็นแคชแอปพลิเคชัน';
$string['storeresults_request'] = 'คำขอที่จัดเก็บเมื่อใช้เป็นแคชคำขอ (Request)';
$string['storeresults_session'] = 'คำขอที่จัดเก็บเมื่อใช้เป็นแคชเซสชัน';
$string['stores'] = 'ที่จัดเก็บ';
$string['store_default_application'] = 'ที่จัดเก็บไฟล์เริ่มต้นสำหรับแคชแอปพลิเคชัน';
$string['store_default_request'] = 'ที่จัดเก็บแบบคงที่ (Static) เริ่มต้นสำหรับแคชคำขอ (Request)';
$string['store_default_session'] = 'ที่จัดเก็บเซสชันเริ่มต้นสำหรับแคชเซสชัน';
$string['storesummaries'] = 'อินสแตนซ์ที่จัดเก็บที่ตั้งค่าไว้';
$string['supports'] = 'รองรับ';
$string['supports_multipleidentifiers'] = 'ตัวระบุหลายตัว (Multiple identifiers)';
$string['supports_dataguarantee'] = 'การรับประกันข้อมูล (Data guarantee)';
$string['supports_nativettl'] = 'อายุข้อมูล (TTL)';
$string['supports_nativelocking'] = 'การล็อก';
$string['supports_keyawareness'] = 'การรับรู้คีย์ (Key awareness)';
$string['supports_searchable'] = 'การค้นหาด้วยคีย์';
$string['tested'] = 'ทดสอบแล้ว';
$string['testperformance'] = 'ทดสอบประสิทธิภาพ';
$string['updatedefinitionmapping'] = 'แก้ไขการจับคู่คำจำกัดความ';
$string['updatedefinitionsharing'] = 'แก้ไขการแชร์คำจำกัดความ';
$string['unsupportedmode'] = 'โหมดที่ไม่รองรับ';
$string['untestable'] = 'ไม่สามารถทดสอบได้';
$string['usage_items'] = 'จำนวนรายการ';
$string['usage_mean'] = 'ขนาดเฉลี่ยต่อรายการ';
$string['usage_samples'] = 'ข้อมูลตัวอย่างต่อแคช';
$string['usage_sd'] = 'ส่วนเบี่ยงเบนมาตรฐาน (Std. dev.)';
$string['usage_total'] = 'ผลรวมโดยประมาณ';
$string['usage_totalmargin'] = 'ระยะคลาดเคลื่อน (95%)';
$string['usage_realtotal'] = 'การใช้งานจริง (หากทราบข้อมูล)';
$string['userinputsharingkey'] = 'คีย์ส่วนตัวสำหรับการแชร์';
$string['userinputsharingkey_help'] = 'ป้อนคีย์ส่วนตัวของคุณที่นี่ เมื่อคุณตั้งค่าที่จัดเก็บอื่นบนไซต์อื่นที่ต้องการแชร์ข้อมูลด้วย โปรดตรวจสอบว่าได้ใช้คีย์เดียวกันทุกประการ';

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
 * Strings for the advanced grading methods subsystem
 *
 * @package    core_grading
 * @subpackage grading
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activemethodinfo'] = '\'{$a->method}\' ถูกเลือกให้เป็นวิธีการให้คะแนนที่ใช้งานอยู่สำหรับพื้นที่ \'{$a->area}\'';
$string['activemethodinfonone'] = 'ไม่มีวิธีการให้คะแนนแบบขั้นสูงที่เลือกสำหรับพื้นที่ \'{$a->area}\' จะใช้การให้คะแนนแบบส่งตรงปกติ';
$string['changeactivemethod'] = 'เปลี่ยนวิธีการให้คะแนนที่ใช้งานอยู่เป็น';
$string['clicktoclose'] = 'คลิกเพื่อปิด';
$string['exc_gradingformelement'] = 'ไม่สามารถสร้างองค์ประกอบแบบฟอร์มการให้คะแนนได้';
$string['formnotavailable'] = 'เลือกวิธีการให้คะแนนแบบขั้นสูงแล้ว แต่ยังต้องกำหนดแบบฟอร์มการให้คะแนน';
$string['gradingformunavailable'] = 'โปรดทราบ: แบบฟอร์มการให้คะแนนแบบขั้นสูงยังไม่พร้อมใช้งานในขณะนี้ จะใช้วิธีการให้คะแนนแบบปกติจนกว่าแบบฟอร์มจะมีสถานะที่ถูกต้อง';
$string['gradingmanagement'] = 'การให้คะแนนขั้นสูง';
$string['gradingmanagementtitle'] = 'การให้คะแนนขั้นสูง: {$a->component} ({$a->area})';
$string['gradingmethod'] = 'วิธีการให้คะแนน';
$string['gradingmethod_help'] = 'เลือกวิธีการให้คะแนนแบบขั้นสูงที่ควรใช้ในการคำวณคะแนนในบริบทที่กำหนด

หากต้องการปิดใช้งานการให้คะแนนแบบขั้นสูงและสลับกลับไปใช้วิธีการให้คะแนนเริ่มต้น ให้เลือก \'การให้คะแนนแบบส่งตรงปกติ\'';
$string['gradingmethodnone'] = 'การให้คะแนนแบบส่งตรงปกติ';
$string['gradingmethods'] = 'วิธีการให้คะแนน';
$string['manageactionclone'] = 'สร้างแบบฟอร์มการให้คะแนนใหม่จากเทมเพลต';
$string['manageactiondelete'] = 'ลบแบบฟอร์มที่กำหนดไว้ในปัจจุบัน';
$string['manageactiondeleteconfirm'] = 'คุณกำลังจะลบแบบฟอร์มการให้คะแนน \'{$a->formname}\' และข้อมูลทั้งหมดที่เกี่ยวข้องจาก \'{$a->component} ({$a->area})\' โปรดตรวจสอบให้แน่ใจว่าคุณเข้าใจผลที่จะตามมาดังนี้:

* ไม่สามารถยกเลิกการทำงานนี้ได้
* คุณสามารถสลับไปยังวิธีการให้คะแนนอื่นรวมถึง \'การให้คะแนนแบบส่งตรงปกติ\' โดยไม่ต้องลบแบบฟอร์มนี้
* ข้อมูลทั้งหมดเกี่ยวกับวิธีการกรอกแบบฟอร์มการให้คะแนนจะสูญหาย
* เกรดผลลัพธ์ที่คำนวณได้ซึ่งเก็บไว้ในสมุดคะแนนจะไม่ได้รับผลกระทบ อย่างไรก็ตาม คำอธิบายเกี่ยวกับวิธีการคำนวณจะไม่พร้อมใช้งาน
* การทำงานนี้ไม่มีผลต่อสำเนามวนของแบบฟอร์มนี้ในกิจกรรมอื่น ๆ';
$string['manageactiondeletedone'] = 'ลบแบบฟอร์มสำเร็จแล้ว';
$string['manageactionedit'] = 'แก้ไขคำจำกัดความของแบบฟอร์มปัจจุบัน';
$string['manageactionnew'] = 'กำหนดแบบฟอร์มการให้คะแนนใหม่ตั้งแต่ต้น';
$string['manageactionshare'] = 'เผยแพร่แบบฟอร์มเป็นเทมเพลตใหม่';
$string['manageactionshareconfirm'] = 'คุณกำลังจะบันทึกสำเนาของแบบฟอร์มการให้คะแนน \'{$a}\' เป็นเทมเพลตสาธารณะใหม่ ผู้ใช้อื่นในไซต์ของคุณจะสามารถสร้างแบบฟอร์มการให้คะแนนใหม่ในกิจกรรมของพวกเขาจากเทมเพลตนั้นได้';
$string['manageactionsharedone'] = 'บันทึกแบบฟอร์มเป็นเทมเพลตสำเร็จแล้ว';
$string['noitemid'] = 'ไม่สามารถให้คะแนนได้ รายการที่ได้รับคะแนนไม่มีอยู่';
$string['nosharedformfound'] = 'ไม่พบเทมเพลต';
$string['privacy:metadata:gradingformpluginsummary'] = 'ข้อมูลสำหรับวิธีการให้คะแนน';
$string['privacy:metadata:grading_definitions'] = 'ข้อมูลเบื้องต้นเกี่ยวกับแบบฟอร์มการให้คะแนนขั้นสูงที่กำหนดไว้ในพื้นที่ที่สามารถให้คะแนนได้';
$string['privacy:metadata:grading_definitions:areaid'] = 'ID พื้นที่ที่มีการกำหนดแบบฟอร์มการให้คะแนนขั้นสูง';
$string['privacy:metadata:grading_definitions:copiedfromid'] = 'ID คำจำกัดความการให้คะแนนที่คัดลอกมา';
$string['privacy:metadata:grading_definitions:description'] = 'คำอธิบายของวิธีการให้คะแนนขั้นสูง';
$string['privacy:metadata:grading_definitions:method'] = 'วิธีการให้คะแนนที่รับผิดชอบคำจำกัดความ';
$string['privacy:metadata:grading_definitions:name'] = 'ชื่อของคำจำกัดความการให้คะแนนขั้นสูง';
$string['privacy:metadata:grading_definitions:options'] = 'การตั้งค่าบางอย่างของคำจำกัดความการให้คะแนนนี้';
$string['privacy:metadata:grading_definitions:status'] = 'สถานะของคำจำกัดความการให้คะแนนขั้นสูงนี้';
$string['privacy:metadata:grading_definitions:timecopied'] = 'เวลาที่คัดลอกคำจำกัดความการให้คะแนน';
$string['privacy:metadata:grading_definitions:timecreated'] = 'เวลาที่สร้างคำจำกัดความการให้คะแนน';
$string['privacy:metadata:grading_definitions:timemodified'] = 'เวลาที่มีการแก้ไขคำจำกัดความการให้คะแนนล่าสุด';
$string['privacy:metadata:grading_definitions:usercreated'] = 'ID ของผู้ใช้ที่สร้างคำจำกัดความการให้คะแนน';
$string['privacy:metadata:grading_definitions:usermodified'] = 'ID ของผู้ใช้ที่แก้ไขคำจำกัดความการให้คะแนนล่าสุด';
$string['privacy:metadata:grading_instances'] = 'บันทึกการประเมินสำหรับรายการที่สามารถให้คะแนนได้หนึ่งรายการที่ประเมินโดยผู้ให้คะแนนหนึ่งคน';
$string['privacy:metadata:grading_instances:feedback'] = 'ข้อเสนอแนะที่ผู้ใช้ให้';
$string['privacy:metadata:grading_instances:feedbackformat'] = 'รูปแบบข้อความของข้อเสนอแนะที่ผู้ใช้ให้';
$string['privacy:metadata:grading_instances:raterid'] = 'ID ของผู้ใช้ที่ให้คะแนนอินสแตนซ์การให้คะแนน';
$string['privacy:metadata:grading_instances:rawgrade'] = 'คะแนนสำหรับอินสแตนซ์การให้คะแนน';
$string['privacy:metadata:grading_instances:status'] = 'สถานะของอินสแตนซ์การให้คะแนนนี้';
$string['privacy:metadata:grading_instances:timemodified'] = 'เวลาที่มีการแก้ไขอินสแตนซ์การให้คะแนนล่าสุด';
$string['searchtemplate'] = 'ค้นหาแบบฟอร์มการให้คะแนน';
$string['searchtemplate_help'] = 'คุณสามารถค้นหาแบบฟอร์มการให้คะแนนและใช้เป็นเทมเพลตสำหรับแบบฟอร์มการให้คะแนนใหม่ได้ที่นี่ เพียงพิมพ์คำที่ควรปรากฏในชื่อแบบฟอร์ม คำอธิบาย หรือตัวแบบฟอร์มเอง หากต้องการค้นหาตามวลี ให้ใส่เครื่องหมายคำพูดรอบข้อความค้นหา

โดยค่าเริ่มต้น เฉพาะแบบฟอร์มการให้คะแนนที่ถูกบันทึกเป็นเทมเพลตที่ใช้ร่วมกันเท่านั้นที่จะรวมอยู่ในผลการค้นหา คุณปรับเปลี่ยนได้ให้รวมแบบฟอร์มการให้คะแนนของคุณเองทั้งหมดในผลการค้นหาด้วยวิธีนี้ คุณสามารถนำแบบฟอร์มการให้คะแนนของคุณกลับมาใช้ใหม่ได้โดยไม่ต้องแชร์ เฉพาะแบบฟอร์มที่ทำเครื่องหมายว่า \'พร้อมใช้งาน\' เท่านั้นที่สามารถนำกลับมาใช้ใหม่ด้วยวิธีนี้ได้';
$string['searchownforms'] = 'รวมแบบฟอร์มของฉันเอง';
$string['statusdraft'] = 'ฉบับร่าง';
$string['statusready'] = 'พร้อมใช้งาน';
$string['templatedelete'] = 'ลบ';
$string['templatedeleteconfirm'] = 'คุณกำลังจะลบเทมเพลตที่ใช้ร่วมกัน \'{$a}\' การลบเทมเพลตไม่มีผลต่อแบบฟอร์มที่มีอยู่ซึ่งสร้างขึ้นจากเทมเพลตนั้น';
$string['templateedit'] = 'แก้ไข';
$string['templatepick'] = 'ใช้เทมเพลตนี้';
$string['templatepickconfirm'] = 'คุณต้องการใช้แบบฟอร์มการให้คะแนน \'{$a->formname}\' เป็นเทมเพลตสำหรับแบบฟอร์มการให้คะแนนใหม่ใน \'{$a->component} ({$a->area})\' หรือไม่?';
$string['templatepickownform'] = 'ใช้แบบฟอร์มนี้เป็นเทมเพลต';
$string['templatetypeown'] = 'แบบฟอร์มของตัวเอง';
$string['templatetypeshared'] = 'เทมเพลตที่ใช้ร่วมกัน';
$string['templatesource'] = 'สถานที่: {$a->component} ({$a->area})';
$string['error:notinrange'] = 'คะแนน \'{$a->grade}\' ที่ระบุไม่ถูกต้อง คะแนนต้องอยู่ระหว่าง 0 ถึง {$a->maxgrade}';
$string['error:gradingunavailable'] = 'วิธีการให้คะแนนแบบขั้นสูงไม่ได้ถูกตั้งค่าอย่างถูกต้อง โปรดตรวจสอบตัวเลือกการให้คะแนนฟอรัมทั้งหมดในการตั้งค่าฟอรัม';

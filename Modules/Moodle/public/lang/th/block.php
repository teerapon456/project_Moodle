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
 * Strings for component 'block', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core_block
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addblock'] = 'เพิ่มบล็อก {$a}';
$string['anypagematchingtheabove'] = 'หน้าใดก็ได้ที่ตรงกับด้านบนนี้';
$string['appearsinsubcontexts'] = 'ปรากฏในบริบทย่อย';
$string['assignrolesinblock'] = 'กำหนดบทบาทในบล็อก {$a}';
$string['blocksdrawertoggle'] = 'ซ่อน/แสดง แถบบล็อกด้านข้าง';
$string['blocksettings'] = 'การตั้งค่าบล็อก';
$string['bracketfirst'] = '{$a} (แรกสุด)';
$string['bracketlast'] = '{$a} (สุดท้าย)';
$string['configureblock'] = 'ตั้งค่าบล็อก {$a}';
$string['contexts'] = 'บริบทของหน้า';
$string['contexts_help'] = 'บริบทคือประเภทหน้าเว็บที่เฉพาะเจาะจงมากขึ้น ซึ่งสามารถแสดงบล็อกนี้ได้ภายในตำแหน่งบล็อกเดิม คุณจะมีตัวเลือกที่แตกต่างกันที่นี่ ขึ้นอยู่กับตำแหน่งเดิมของบล็อกและตำแหน่งปัจจุบันของคุณ ตัวอย่างเช่น คุณสามารถจำกัดการแสดงบล็อกให้ปรากฏเฉพาะในหน้าฟอรัมในรายวิชาได้โดยเพิ่มบล็อกลงในรายวิชา (ทำให้ปรากฏในหน้าย่อยทั้งหมด) จากนั้นเข้าไปที่ฟอรัมแล้วแก้ไขการตั้งค่าบล็อกอีกครั้งเพื่อจำกัดการแสดงผลให้เหลือเพียงหน้าฟอรัมเท่านั้น';
$string['createdat'] = 'ตำแหน่งบล็อกเดิม';
$string['createdat_help'] = 'ตำแหน่งเดิมที่มีการสร้างบล็อกขึ้น การตั้งค่าบล็อกอาจทำให้บล็อกปรากฏในตำแหน่ง (บริบท) อื่นๆ ภายในตำแหน่งเดิม ตัวอย่างเช่น บล็อกที่สร้างบนหน้าของรายวิชาอาจแสดงในกิจกรรมภายในรายวิชานั้น บล็อกที่สร้างบนหน้าแรกของไซต์สามารถแสดงได้ทั่วทั้งไซต์';
$string['defaultregion'] = 'พื้นที่แสดงผลเริ่มต้น';
$string['defaultregion_help'] = 'ธีมอาจกำหนดพื้นที่แสดงผลบล็อกที่มีชื่อเรียกได้หนึ่งชื่อหรือมากกว่านั้น ซึ่งเป็นจุดที่บล็อกจะถูกแสดงผล การตั้งค่านี้จะกำหนดว่าคุณต้องการให้บล็อกปรากฏในพื้นที่ใดเป็นค่าเริ่มต้น ซึ่งพื้นที่นี้สามารถเปลี่ยนแปลงได้ในหน้าเฉพาะหากต้องการ';
$string['defaultweight'] = 'ลำดับความสำคัญเริ่มต้น (Weight)';
$string['defaultweight_help'] = 'ลำดับความสำคัญเริ่มต้นช่วยให้คุณเลือกตำแหน่งโดยคร่าว ๆ ว่าต้องการให้บล็อกปรากฏที่ส่วนบนหรือส่วนล่างของพื้นที่ที่เลือก ตำแหน่งสุดท้ายจะถูกคำนวณจากบล็อกทั้งหมดในพื้นที่นั้น (ตัวอย่างเช่น บล็อกเดียวเท่านั้นที่สามารถอยู่ด้านบนสุดได้จริง ๆ) ค่านี้สามารถเปลี่ยนแปลงได้ในหน้าเฉพาะหากต้องการ';
$string['deletecheck'] = 'ลบบล็อก {$a} หรือไม่?';
$string['deletecheck_modal'] = 'ลบบล็อกหรือไม่?';
$string['deleteblock'] = 'ลบบล็อก {$a}';
$string['deleteblockcheck'] = 'การดำเนินการนี้จะลบบล็อก {$a}';
$string['deleteblockinprogress'] = 'กำลังลบบล็อก {$a}...';
$string['deleteblockwarning'] = '<p>คุณกำลังจะลบบล็อกที่ปรากฏอยู่ในที่อื่นด้วย</p><p>ตำแหน่งบล็อกเดิม: {$a->location}<br />แสดงในประเภทหน้า: {$a->pagetype}</p><p>คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?</p>';
$string['hideblock'] = 'ซ่อนบล็อก {$a}';
$string['hidepanel'] = 'ซ่อนแผงหน้าจอ';
$string['moveblock'] = 'ย้ายบล็อก {$a}';
$string['moveblockafter'] = 'ย้ายบล็อกไปไว้หลังบล็อก {$a}';
$string['moveblockbefore'] = 'ย้ายบล็อกไปไว้ก่อนบล็อก {$a}';
$string['moveblockinregion'] = 'ย้ายบล็อกไปยังพื้นที่ {$a}';
$string['movingthisblockcancel'] = 'กำลังย้ายบล็อกนี้ ({$a})';
$string['myblocks'] = 'บล็อกของฉัน';
$string['onthispage'] = 'ในหน้านี้';
$string['pagetypes'] = 'ประเภทของหน้า';
$string['pagetypewarning'] = 'ประเภทหน้าที่ระบุไว้ก่อนหน้านี้ไม่สามารถเลือกได้อีกต่อไป โปรดเลือกประเภทหน้าที่เหมาะสมที่สุดด้านล่าง';
$string['privacy:metadata:userpref:dockedinstance'] = 'บันทึกเมื่อผู้ใช้ทำการเก็บ (dock) บล็อก';
$string['privacy:metadata:userpref:hiddenblock'] = 'บันทึกเมื่อผู้ใช้ทำการยุบหรือซ่อนบล็อก';
$string['privacy:request:blockisdocked'] = 'ระบุว่าบล็อกถูกเก็บ (dock) ไว้หรือไม่';
$string['privacy:request:blockishidden'] = 'ระบุว่าบล็อกถูกซ่อนหรือยุบไว้หรือไม่';
$string['region'] = 'พื้นที่แสดงผล';
$string['showblock'] = 'แสดงบล็อก {$a}';
$string['showoncontextandsubs'] = 'แสดงบน \'{$a}\' และหน้าใด ๆ ภายในนั้น';
$string['showoncontextonly'] = 'แสดงบน \'{$a}\' เท่านั้น';
$string['showonentiresite'] = 'แสดงทั่วทั้งไซต์';
$string['showonfrontpageandsubs'] = 'แสดงบนหน้าแรกของไซต์และหน้าใด ๆ ที่เพิ่มเข้าไปในหน้าแรก';
$string['showonfrontpageonly'] = 'แสดงบนหน้าแรกของไซต์เท่านั้น';
$string['site-*'] = 'หน้าไซต์ระดับบนสุดใด ๆ';
$string['subpages'] = 'เลือกหน้า';
$string['restrictpagetypes'] = 'แสดงในประเภทหน้า';
$string['thisspecificpage'] = 'หน้านี้โดยเฉพาะ';
$string['visible'] = 'การมองเห็น';
$string['weight'] = 'ลำดับความสำคัญ (Weight)';
$string['wherethisblockappears'] = 'ตำแหน่งที่บล็อกนี้ปรากฏ';

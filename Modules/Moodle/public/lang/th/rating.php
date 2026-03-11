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
 * Strings for component 'rating', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core_rating
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['aggregatetype'] = 'ประเภทการรวมคะแนน';
$string['aggregateavg'] = 'ค่าเฉลี่ยของการให้คะแนน';
$string['aggregatecount'] = 'จำนวนการให้คะแนน';
$string['aggregatemax'] = 'คะแนนสูงสุด';
$string['aggregatemin'] = 'คะแนนต่ำสุด';
$string['aggregatenone'] = 'ไม่มีการให้คะแนน';
$string['aggregatesum'] = 'ผลรวมของการให้คะแนน';
$string['aggregatetype_help'] = 'ประเภทการรวมคะแนนกำหนดวิธีการรวมคะแนนเพื่อใช้เป็นเกรดสุดท้ายในสมุดคะแนน

* ค่าเฉลี่ยของการให้คะแนน - ค่าเฉลี่ยของการให้คะแนนทั้งหมด
* จำนวนการให้คะแนน - จำนวนรายการที่ได้รับการให้คะแนนจะกลายเป็นเกรดสุดท้าย โปรดทราบว่าผลรวมต้องไม่เกินเกรดสูงสุดสำหรับกิจกรรม
* สูงสุด - การให้คะแนนสูงสุดจะกลายเป็นเกรดสุดท้าย
* ต่ำสุด - การให้คะแนนที่น้อยที่สุดจะกลายเป็นเกรดสุดท้าย
* ผลรวม - การให้คะแนนทั้งหมดจะถูกนำมารวมกัน โปรดทราบว่าผลรวมต้องไม่เกินเกรดสูงสุดสำหรับกิจกรรม

หากเลือก "ไม่มีการให้คะแนน" กิจกรรมนั้นจะไม่ปรากฏในสมุดคะแนน';
$string['allowratings'] = 'อนุญาตให้มีการให้คะแนนรายการหรือไม่?';
$string['allratingsforitem'] = 'การให้คะแนนทั้งหมดที่ส่งมา';
$string['capabilitychecknotavailable'] = 'การตรวจสอบความสามารถไม่สามารถใช้งานได้จนกว่าจะบันทึกกิจกรรม';
$string['couldnotdeleteratings'] = 'ขออภัย ไม่สามารถลบได้เนื่องจากมีการให้คะแนนไปแล้ว';
$string['norate'] = 'ไม่อนุญาตให้มีการให้คะแนนรายการ!';
$string['noratings'] = 'ไม่มีการส่งการให้คะแนน';
$string['noviewanyrate'] = 'คุณสามารถดูผลลัพธ์สำหรับรายการที่คุณสร้างขึ้นเองเท่านั้น';
$string['noviewrate'] = 'คุณไม่มีความสามารถในการดูการให้คะแนนรายการ';
$string['rate'] = 'ให้คะแนน';
$string['ratepermissiondenied'] = 'คุณไม่มีสิทธิ์ในการให้คะแนนรายการนี้';
$string['rating'] = 'การให้คะแนน';
$string['ratinginvalid'] = 'การให้คะแนนไม่ถูกต้อง';
$string['ratingtime'] = 'จำกัดการให้คะแนนเฉพาะรายการที่มีวันที่อยู่ในช่วงนี้:';
$string['ratings'] = 'การให้คะแนน';
$string['rolewarning'] = 'บทบาทที่มีสิทธิ์ในการให้คะแนน';
$string['rolewarning_help'] = 'บทบาทที่มีสิทธิ์ในการให้คะแนนคือบทบาทที่มีความสามารถ moodle/rating:rate รวมถึงความสามารถในการให้คะแนนเฉพาะกิจกรรมใด ๆ คุณสามารถให้สิทธิ์แก่บทบาทอื่นเพื่อการให้คะแนนผ่านหน้าสิทธิ์การใช้งาน (Permissions)';
$string['scaleselectionrequired'] = 'เมื่อเลือกประเภทการรวมคะแนน คุณต้องเลือกใช้งานสเกลหรือกำหนดคะแนนสูงสุดด้วย';
$string['privacy:metadata:rating'] = 'คะแนนที่ผู้ใช้ป้อนจะถูกจัดเก็บร่วมกับการแมปของรายการที่ได้รับการให้คะแนน';
$string['privacy:metadata:rating:userid'] = 'ผู้ใช้ที่ทำการให้คะแนน';
$string['privacy:metadata:rating:rating'] = 'คะแนนตัวเลขที่ผู้ใช้ป้อน';
$string['privacy:metadata:rating:timecreated'] = 'เวลาที่มีการให้คะแนนครั้งแรก';
$string['privacy:metadata:rating:timemodified'] = 'เวลาที่มีการอัปเดตการให้คะแนนล่าสุด';

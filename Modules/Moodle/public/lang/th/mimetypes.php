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
 * Strings for component 'mimetypes', language 'th', branch 'MOODLE_20_STABLE'
 *
 * Strings are used to display human-readable name of mimetype. Some mimetypes share the same
 * string. The following attributes are passed in the parameter when processing the string:
 *   $a->ext - filename extension in lower case
 *   $a->EXT - filename extension, capitalized
 *   $a->Ext - filename extension with first capital letter
 *   $a->mimetype - file mimetype
 *   $a->mimetype1 - first chunk of mimetype (before /)
 *   $a->mimetype2 - second chunk of mimetype (after /)
 *   $a->Mimetype, $a->MIMETYPE, $a->Mimetype1, $a->Mimetype2, $a->MIMETYPE1, $a->MIMETYPE2
 *      - the same with capitalized first/all letters
 *
 * @see       get_mimetypes_array()
 * @see       get_mimetype_description()
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['application/dash_xml'] = 'การสตรีมแบบปรับเปลี่ยนได้แบบไดนามิกผ่าน HTTP (MPEG-DASH)';
$string['application/epub_zip'] = 'อีบุ๊ก EPUB';
$string['application/json'] = 'ข้อความ {$a->MIMETYPE2}';
$string['application/msword'] = 'เอกสาร Word';
$string['application/pdf'] = 'เอกสาร PDF';
$string['application/vnd.moodle.backup'] = 'การสำรองข้อมูล Moodle';
$string['application/vnd.ms-excel'] = 'สเปรดชีต Excel';
$string['application/vnd.ms-excel.sheet.macroEnabled.12'] = 'เวิร์กบุ๊ก Excel 2007 ที่เปิดใช้งานมาโคร';
$string['application/vnd.ms-powerpoint'] = 'งานนำเสนอ Powerpoint';
$string['application/vnd.oasis.opendocument.spreadsheet'] = 'สเปรดชีต OpenDocument';
$string['application/vnd.oasis.opendocument.spreadsheet-template'] = 'เทมเพลตสเปรดชีต OpenDocument';
$string['application/vnd.oasis.opendocument.text'] = 'เอกสารข้อความ OpenDocument';
$string['application/vnd.oasis.opendocument.text-template'] = 'เทมเพลตข้อความ OpenDocument';
$string['application/vnd.oasis.opendocument.text-web'] = 'เทมเพลตหน้าเว็บ OpenDocument';
$string['application/vnd.openxmlformats-officedocument.presentationml.presentation'] = 'งานนำเสนอ Powerpoint 2007';
$string['application/vnd.openxmlformats-officedocument.presentationml.slideshow'] = 'สไลด์โชว์ Powerpoint 2007';
$string['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] = 'สเปรดชีต Excel 2007';
$string['application/vnd.openxmlformats-officedocument.spreadsheetml.template'] = 'เทมเพลต Excel 2007';
$string['application/vnd.openxmlformats-officedocument.wordprocessingml.document'] = 'เอกสาร Word 2007';
$string['application/x-iwork-keynote-sffkey'] = 'งานนำเสนอ iWork Keynote';
$string['application/x-iwork-numbers-sffnumbers'] = 'สเปรดชีต iWork Numbers';
$string['application/x-iwork-pages-sffpages'] = 'เอกสาร iWork Pages';
$string['application/x-javascript'] = 'ซอร์สโค้ด JavaScript';
$string['application/x-mpegURL'] = 'การสตรีมสด HTTP (HLS)';
$string['application/x-mspublisher'] = 'เอกสาร Publisher';
$string['application/x-shockwave-flash'] = 'แอนิเมชั่น Flash';
$string['application/xhtml_xml'] = 'เอกสาร XHTML';
$string['archive'] = 'ไฟล์จัดเก็บ ({$a->EXT})';
$string['audio'] = 'ไฟล์เสียง ({$a->EXT})';
$string['default'] = '{$a->mimetype}';
$string['document/unknown'] = 'ไฟล์';
$string['group:archive'] = 'ไฟล์เก็บถาวร';
$string['group:audio'] = 'ไฟล์เสียง';
$string['group:document'] = 'ไฟล์เอกสาร';
$string['group:html_audio'] = 'ไฟล์เสียงที่รองรับโดยเบราว์เซอร์โดยตรง';
$string['group:html_track'] = 'ไฟล์แทร็ก HTML';
$string['group:html_video'] = 'ไฟล์วิดีโอที่รองรับโดยเบราว์เซอร์โดยตรง';
$string['group:image'] = 'ไฟล์รูปภาพ';
$string['group:media_source'] = 'สื่อสตรีมมิ่ง';
$string['group:optimised_image'] = 'ไฟล์รูปภาพที่จะถูกปรับให้เหมาะสม เช่น ป้ายตรา';
$string['group:presentation'] = 'ไฟล์งานนำเสนอ';
$string['group:sourcecode'] = 'ซอร์สโค้ด';
$string['group:spreadsheet'] = 'ไฟล์สเปรดชีต';
$string['group:video'] = 'ไฟล์วิดีโอ';
$string['group:web_audio'] = 'ไฟล์เสียงที่ใช้บนเว็บ';
$string['group:web_file'] = 'ไฟล์เว็บ';
$string['group:web_image'] = 'ไฟล์รูปภาพที่ใช้บนเว็บ';
$string['group:web_video'] = 'ไฟล์วิดีโอที่ใช้บนเว็บ';
$string['image'] = 'รูปภาพ ({$a->MIMETYPE2})';
$string['image/vnd.microsoft.icon'] = 'ไอคอน Windows';
$string['text/css'] = 'สไตล์ชีต (CSS)';
$string['text/csv'] = 'ค่าที่คั่นด้วยเครื่องหมายจุลภาค';
$string['text/html'] = 'เอกสาร HTML';
$string['text/plain'] = 'ไฟล์ข้อความ';
$string['text/rtf'] = 'เอกสาร RTF';
$string['text/vtt'] = 'แทร็กข้อความวิดีโอเว็บ (VTT)';
$string['video'] = 'ไฟล์วิดีโอ ({$a->EXT})';

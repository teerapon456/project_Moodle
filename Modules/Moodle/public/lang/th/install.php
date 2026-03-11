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
 * Strings for component 'install', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['admindirerror'] = 'ไดเรกทอรี admin ที่ระบุไม่ถูกต้อง';
$string['admindirname'] = 'ไดเรกทอรี Admin';
$string['admindirsetting'] = 'เว็บโฮสต์จำนวนน้อยมากใช้ /admin เป็น URL พิเศษสำหรับคุณในการเข้าถึงแผงควบคุมหรืออย่างอื่น แต่น่าเสียดายที่นี่ขัดแย้งกับตำแหน่งมาตรฐานสำหรับหน้าผู้ดูแลระบบของ Moodle คุณสามารถแก้ไขได้โดยการเปลี่ยนชื่อไดเรกทอรี admin ในการติดตั้งของคุณ และใส่ชื่อใหม่นั้นที่นี่ ตัวอย่างเช่น: <br /> <br /><b>moodleadmin</b><br /> <br /> ซึ่งจะแก้ไขลิงก์ผู้ดูแลระบบใน Moodle';
$string['admindirsettinghead'] = 'กำลังตั้งค่าไดเรกทอรี admin ...';
$string['admindirsettingsub'] = 'เว็บโฮสต์จำนวนน้อยมากใช้ /admin เป็น URL พิเศษสำหรับคุณในการเข้าถึงแผงควบคุมหรืออย่างอื่น แต่น่าเสียดายที่นี่ขัดแย้งกับตำแหน่งมาตรฐานสำหรับหน้าผู้ดูแลระบบของ Moodle คุณสามารถแก้ไขได้โดยการเปลี่ยนชื่อไดเรกทอรี admin ในการติดตั้งของคุณ และใส่ชื่อใหม่นั้นที่นี่ ตัวอย่างเช่น: <br /> <br /><b>moodleadmin</b><br /> <br /> ซึ่งจะแก้ไขลิงก์ผู้ดูแลระบบใน Moodle';
$string['availablelangs'] = 'ชุดภาษาที่พร้อมใช้งาน';
$string['caution'] = 'คำเตือน';
$string['cliadminemail'] = 'ที่อยู่อีเมลของผู้ดูแลระบบคนใหม่';
$string['cliadminpassword'] = 'รหัสผ่านของผู้ดูแลระบบคนใหม่';
$string['cliadminusername'] = 'ชื่อผู้ใช้งานบัญชีผู้ดูแลระบบ';
$string['clialreadyconfigured'] = 'ไฟล์กำหนดค่า config.php มีอยู่แล้ว โปรดใช้ admin/cli/install_database.php เพื่อติดตั้ง Moodle สำหรับไซต์นี้';
$string['clialreadyinstalled'] = 'ไฟล์กำหนดค่า config.php มีอยู่แล้ว โปรดใช้ admin/cli/install_database.php เพื่ออัปเกรด Moodle สำหรับไซต์นี้';
$string['cliinstallfinished'] = 'การติดตั้งเสร็จสมบูรณ์';
$string['cliinstallheader'] = 'โปรแกรมการติดตั้ง Moodle {$a} ผ่านบรรทัดคำสั่ง';
$string['climustagreelicense'] = 'ในโหมด non-interactive คุณต้องยอมรับใบอนุญาตโดยระบุตัวเลือก --agree-license';
$string['clinoreplyemail'] = 'ที่อยู่ Noreply';
$string['cliskipdatabase'] = 'ข้ามการติดตั้งฐานข้อมูล';
$string['clisupportemail'] = 'ที่อยู่อีเมลสนับสนุน';
$string['clitablesexist'] = 'มีตารางฐานข้อมูลอยู่แล้ว การติดตั้งผ่าน CLI ไม่สามารถดำเนินการต่อได้';
$string['compatibilitysettings'] = 'กำลังตรวจสอบการตั้งค่า PHP ของคุณ ...';
$string['compatibilitysettingshead'] = 'กำลังตรวจสอบการตั้งค่า PHP ของคุณ ...';
$string['compatibilitysettingssub'] = 'เซิร์ฟเวอร์ของคุณควรผ่านการทดสอบเหล่านี้เพื่อให้ Moodle ทำงานได้อย่างถูกต้อง';
$string['configfilenotwritten'] = 'สคริปต์การติดตั้งไม่สามารถสร้างไฟล์ config.php โดยอัตโนมัติที่มีการตั้งค่าที่คุณเลือกได้ อาจเป็นเพราะไดเรกทอรี Moodle ไม่สามารถเขียนข้อมูลได้ คุณสามารถคัดลอกรหัสต่อไปนี้ลงในไฟล์ชื่อ config.php ภายในไดเรกทอรีหลักของ Moodle ได้ด้วยตนเอง';
$string['configfilewritten'] = 'สร้าง config.php สำเร็จแล้ว';
$string['configurationcomplete'] = 'การกำหนดค่าเสร็จสมบูรณ์';
$string['configurationcompletehead'] = 'การกำหนดค่าเสร็จสมบูรณ์';
$string['configurationcompletesub'] = 'Moodle พยายามบันทึกการกำหนดค่าของคุณในไฟล์ในโฟลเดอร์หลักของการติดตั้ง Moodle ของคุณ';
$string['database'] = 'ฐานข้อมูล';
$string['databasehead'] = 'การตั้งค่าฐานข้อมูล';
$string['databasehost'] = 'โฮสต์ฐานข้อมูล';
$string['databasename'] = 'ชื่อฐานข้อมูล';
$string['databasepass'] = 'รหัสผ่านฐานข้อมูล';
$string['databaseport'] = 'พอร์ตฐานข้อมูล';
$string['databasesocket'] = 'Unix socket';
$string['databasetypehead'] = 'เลือกไดรเวอร์ฐานข้อมูล';
$string['databasetypesub'] = 'Moodle รองรับเซิร์ฟเวอร์ฐานข้อมูลหลายประเภท โปรดติดต่อผู้ดูแลระบบเซิร์ฟเวอร์หากคุณไม่ทราบว่าควรใช้ประเภทใด';
$string['databaseuser'] = 'ผู้ใช้ฐานข้อมูล';
$string['dataroot'] = 'ไดเรกทอรีข้อมูล';
$string['datarooterror'] = 'ไม่พบหรือสร้าง \'ไดเรกทอรีข้อมูล\' ที่คุณระบุ โปรดแก้ไขเส้นทางหรือสร้างไดเรกทอรีนั้นด้วยตนเอง';
$string['datarootpermission'] = 'สิทธิ์ของไดเรกทอรีข้อมูล';
$string['datarootpublicerror'] = '\'ไดเรกทอรีข้อมูล\' ที่คุณระบุสามารถเข้าถึงได้โดยตรงผ่านเว็บ คุณต้องใช้ไดเรกทอรีอื่น';
$string['dbconnectionerror'] = 'เราไม่สามารถเชื่อมต่อกับฐานข้อมูลที่คุณระบุได้ โปรดตรวจสอบการตั้งค่าฐานข้อมูลของคุณ';
$string['dbcreationerror'] = 'ข้อผิดพลาดในการสร้างฐานข้อมูล ไม่สามารถสร้างชื่อฐานข้อมูลที่ระบุด้วยการตั้งค่าที่ให้ไว้';
$string['dbhost'] = 'เซิร์ฟเวอร์ที่โฮสต์';
$string['dbpass'] = 'รหัสผ่าน';
$string['dbport'] = 'พอร์ต';
$string['dbprefix'] = 'คำนำหน้าตาราง';
$string['dbtype'] = 'ประเภท';
$string['directorysettings'] = '<p>โปรดยืนยันตำแหน่งของการติดตั้ง Moodle นี้</p>

<p><b>ที่อยู่เว็บ:</b>
ระบุที่อยู่เว็บแบบเต็มที่จะเข้าถึง Moodle
หากเว็บไซต์ของคุณสามารถเข้าถึงได้ผ่านหลาย URL ให้เลือกอันที่
เป็นธรรมชาติที่สุดที่นักเรียนของคุณจะใช้ อย่าปิดท้ายด้วย
เครื่องหมายทับ (slash)</p>

<p><b>ไดเรกทอรี Moodle:</b>
ระบุเส้นทางไดเรกทอรีแบบเต็มไปยังการติดตั้งนี้
ตรวจสอบให้แน่ใจว่าตัวพิมพ์ใหญ่/เล็กถูกต้อง</p>

<p><b>ไดเรกทอรีข้อมูล:</b>
คุณต้องการสถานที่ที่ Moodle สามารถบันทึกไฟล์ที่อัปโหลดได้ ไดเรกทอรีนี้
ควรสามารถอ่านและเขียนได้โดยผู้ใช้เว็บเซิร์ฟเวอร์
(โดยปกติคือ \'nobody\' หรือ \'apache\') แต่ต้องไม่สามารถเข้าถึงได้
โดยตรงผ่านเว็บ ตัวติดตั้งจะพยายามสร้างขึ้นหากยังไม่มีอยู่</p>';
$string['directorysettingshead'] = 'โปรดยืนยันตำแหน่งของการติดตั้ง Moodle นี้';
$string['directorysettingssub'] = '<b>ที่อยู่เว็บ:</b>
ระบุที่อยู่เว็บแบบเต็มที่จะเข้าถึง Moodle
หากเว็บไซต์ของคุณสามารถเข้าถึงได้ผ่านหลาย URL ให้เลือกอันที่
เป็นธรรมชาติที่สุดที่นักเรียนของคุณจะใช้ อย่าปิดท้ายด้วย
เครื่องหมายทับ (slash)
<br />
<br />
<b>ไดเรกทอรี Moodle:</b>
ระบุเส้นทางไดเรกทอรีแบบเต็มไปยังการติดตั้งนี้
ตรวจสอบให้แน่ใจว่าตัวพิมพ์ใหญ่/เล็กถูกต้อง
<br />
<br />
<b>ไดเรกทอรีข้อมูล:</b>
คุณต้องการสถานที่ที่ Moodle สามารถบันทึกไฟล์ที่อัปโหลดได้ ไดเรกทอรีนี้
ต้องสามารถอ่านและเขียนได้โดยผู้ใช้เว็บเซิร์ฟเวอร์
(โดยปกติคือ \'nobody\' หรือ \'apache\') แต่ต้องไม่สามารถเข้าถึงได้
โดยตรงผ่านเว็บ ตัวติดตั้งจะพยายามสร้างขึ้นหากยังไม่มีอยู่';
$string['dirroot'] = 'ไดเรกทอรี Moodle';
$string['dirrooterror'] = 'ดูเหมือนว่าการตั้งค่า \'ไดเรกทอรี Moodle\' จะไม่ถูกต้อง เราไม่พบการติดตั้ง Moodle ที่นั่น ค่าด้านล่างได้รับการรีเซ็ตแล้ว';
$string['download'] = 'ดาวน์โหลด';
$string['downloadlanguagebutton'] = 'ดาวน์โหลดชุดภาษา &quot;{$a}&quot;';
$string['downloadlanguagehead'] = 'ดาวน์โหลดชุดภาษา';
$string['downloadlanguagenotneeded'] = 'คุณสามารถดำเนินการติดตั้งต่อโดยใช้ชุดภาษาเริ่มต้น "{$a}"';
$string['downloadlanguagesub'] = 'ตอนนี้คุณมีตัวเลือกในการดาวน์โหลดชุดภาษาและดำเนินการติดตั้งต่อด้วยภาษานี้<br /><br />หากคุณไม่สามารถดาวน์โหลดชุดภาษาได้ กระบวนการติดตั้งจะดำเนินการต่อเป็นภาษาอังกฤษ (เมื่อกระบวนการติดตั้งเสร็จสิ้น คุณจะมีโอกาสดาวน์โหลดและติดตั้งชุดภาษาเพิ่มเติม)';
$string['doyouagree'] = 'คุณตกลงหรือไม่? (ใช่/ไม่):';
$string['environmenthead'] = 'กำลังตรวจสอบสภาพแวดล้อมของคุณ ...';
$string['environmentsub'] = 'เรากำลังตรวจสอบว่าส่วนประกอบต่าง ๆ ของระบบของคุณเป็นไปตามข้อกำหนดของระบบหรือไม่';
$string['environmentsub2'] = 'Moodle แต่ละรุ่นมีข้อกำหนดเวอร์ชัน PHP ขั้นต่ำและส่วนขยาย PHP ที่จำเป็นจำนวนหนึ่ง การตรวจสอบสภาพแวดล้อมอย่างสมบูรณ์จะทำก่อนการติดตั้งและอัปเกรดแต่ละครั้ง โปรดติดต่อผู้ดูแลระบบเซิร์ฟเวอร์หากคุณไม่ทราบวิธีติดตั้งเวอร์ชันใหม่หรือเปิดใช้งานส่วนขยาย PHP';
$string['errorsinenvironment'] = 'การตรวจสอบสภาพแวดล้อมล้มเหลว!';
$string['fail'] = 'ล้มเหลว';
$string['fileuploads'] = 'การอัปโหลดไฟล์';
$string['fileuploadserror'] = 'ควรจะเปิดอยู่';
$string['fileuploadshelp'] = '<p>ดูเหมือนว่าการอัปโหลดไฟล์จะถูกปิดใช้งานบนเซิร์ฟเวอร์ของคุณ</p>

<p>Moodle ยังสามารถติดตั้งได้ แต่หากไม่มีความสามารถนี้ คุณจะไม่สามารถอัปโหลดไฟล์หลักสูตรหรือรูปภาพโปรไฟล์ผู้ใช้ใหม่ได้</p>

<p>ในการเปิดใช้งานการอัปโหลดไฟล์ คุณ (หรือผู้ดูแลระบบระบบของคุณ) จะต้องแก้ไขไฟล์ php.ini หลักในระบบของคุณและเปลี่ยนการตั้งค่าสำหรับ <b>file_uploads</b> เป็น \'1\'</p>';
$string['chooselanguage'] = 'เลือกภาษา';
$string['chooselanguagehead'] = 'เลือกภาษา';
$string['chooselanguagesub'] = 'โปรดเลือกภาษาสำหรับการติดตั้ง ภาษานี้จะถูกใช้เป็นภาษาเริ่มต้นสำหรับไซต์ด้วย แม้ว่าอาจจะเปลี่ยนภายหลังได้';
$string['inputdatadirectory'] = 'ไดเรกทอรีข้อมูล:';
$string['inputwebadress'] = 'ที่อยู่เว็บ:';
$string['inputwebdirectory'] = 'ไดเรกทอรี Moodle:';
$string['installation'] = 'การติดตั้ง';
$string['invaliddbprefix'] = 'คำนำหน้าไม่ถูกต้อง คำนำหน้าสามารถประกอบด้วยตัวอักษรพิมพ์เล็กและเครื่องหมายขีดล่างเท่านั้น';
$string['langdownloaderror'] = 'ขออภัย ไม่สามารถดาวน์โหลดภาษา "{$a}" ได้ กระบวนการติดตั้งจะดำเนินต่อไปเป็นภาษาอังกฤษ';
$string['langdownloadok'] = 'ติดตั้งภาษา "{$a}" สำเร็จแล้ว กระบวนการติดตั้งจะดำเนินต่อไปในภาษานี้';
$string['memorylimit'] = 'ขีดจำกัดหน่วยความจำ';
$string['memorylimiterror'] = 'ขีดจำกัดหน่วยความจำ PHP ถูกตั้งไว้ค่อนข้างต่ำ ... คุณอาจประสบปัญหาในภายหลัง';
$string['mysqliextensionisnotpresentinphp'] = 'PHP ยังไม่ได้รับการกำหนดค่าอย่างถูกต้องด้วยส่วนขยาย MySQLi เพื่อสื่อสารกับ MySQL โปรดตรวจสอบไฟล์ php.ini ของคุณหรือคอมไพล์ PHP ใหม่';
$string['nativeauroramysql'] = 'Aurora MySQL (native/auroramysql)';
$string['nativeauroramysqlhelp'] = '<p>ฐานข้อมูลเป็นที่สำหรับเก็บการตั้งค่าและข้อมูลของ Moodle ส่วนใหญ่และต้องกำหนดค่าที่นี่</p>
<p>ชื่อฐานข้อมูล ชื่อผู้ใช้ และรหัสผ่านเป็นฟิลด์ที่จำเป็น ส่วนคำนำหน้าตารางนั้นไม่บังคับ</p>
<p>ชื่อฐานข้อมูลสามารถประกอบด้วยตัวอักษรและตัวเลข เครื่องหมายดอลลาร์ ($) และขีดล่าง (_) เท่านั้น</p>
<p>หากฐานข้อมูลในปัจจุบันยังไม่มีอยู่ และผู้ใช้ที่คุณระบุมีสิทธิ์ Moodle จะพยายามสร้างฐานข้อมูลใหม่ด้วยสิทธิ์และการตั้งค่าที่ถูกต้อง</p>
<p>ไดรเวอร์นี้ไม่รองรับเอนจิน MyISAM รุ่นเก่า</p>';
$string['nativemariadb'] = 'MariaDB (native/mariadb)';
$string['nativemariadbhelp'] = '<p>ฐานข้อมูลเป็นที่สำหรับเก็บการตั้งค่าและข้อมูลของ Moodle ส่วนใหญ่และต้องกำหนดค่าที่นี่</p>
<p>ชื่อฐานข้อมูล ชื่อผู้ใช้ และรหัสผ่านเป็นฟิลด์ที่จำเป็น ส่วนคำนำหน้าตารางนั้นไม่บังคับ</p>
<p>ชื่อฐานข้อมูลสามารถประกอบด้วยตัวอักษรและตัวเลข เครื่องหมายดอลลาร์ ($) และขีดล่าง (_) เท่านั้น</p>
<p>หากฐานข้อมูลในปัจจุบันยังไม่มีอยู่ และผู้ใช้ที่คุณระบุมีสิทธิ์ Moodle จะพยายามสร้างฐานข้อมูลใหม่ด้วยสิทธิ์และการตั้งค่าที่ถูกต้อง</p>
<p>ไดรเวอร์นี้ไม่รองรับเอนจิน MyISAM รุ่นเก่า</p>';
$string['nativemysqli'] = 'Improved MySQL (native/mysqli)';
$string['nativemysqlihelp'] = '<p>ฐานข้อมูลเป็นที่สำหรับเก็บการตั้งค่าและข้อมูลของ Moodle ส่วนใหญ่และต้องกำหนดค่าที่นี่</p>
<p>ชื่อฐานข้อมูล ชื่อผู้ใช้ และรหัสผ่านเป็นฟิลด์ที่จำเป็น ส่วนคำนำหน้าตารางนั้นไม่บังคับ</p>
<p>ชื่อฐานข้อมูลสามารถประกอบด้วยตัวอักษรและตัวเลข เครื่องหมายดอลลาร์ ($) และขีดล่าง (_) เท่านั้น</p>
<p>หากฐานข้อมูลในปัจจุบันยังไม่มีอยู่ และผู้ใช้ที่คุณระบุมีสิทธิ์ Moodle จะพยายามสร้างฐานข้อมูลใหม่ด้วยสิทธิ์และการตั้งค่าที่ถูกต้อง</p>';
$string['nativepgsql'] = 'PostgreSQL (native/pgsql)';
$string['nativepgsqlhelp'] = '<p>ฐานข้อมูลเป็นที่สำหรับเก็บการตั้งค่าและข้อมูลของ Moodle ส่วนใหญ่และต้องกำหนดค่าที่นี่</p>
<p>ชื่อฐานข้อมูล ชื่อผู้ใช้ รหัสผ่าน และคำนำหน้าตารางเป็นฟิลด์ที่จำเป็น</p>
<p>ฐานข้อมูลต้องมีอยู่แล้วและผู้ใช้ต้องมีสิทธิ์เข้าถึงทั้งการอ่านและเขียนข้อมูล</p>';
$string['nativesqlsrv'] = 'SQL*Server Microsoft (native/sqlsrv)';
$string['nativesqlsrvhelp'] = 'ตอนนี้คุณต้องกำหนดค่าฐานข้อมูลที่ข้อมูลส่วนใหญ่ของ Moodle จะถูกเก็บไว้ ฐานข้อมูลนี้ต้องถูกสร้างขึ้นแล้วและต้องมีการสร้างชื่อผู้ใช้และรหัสผ่านเพื่อเข้าถึง คำนำหน้าตารางเป็นสิ่งที่จำเป็น';
$string['nativesqlsrvnodriver'] = 'ไม่ได้ติดตั้ง Microsoft Drivers สำหรับ SQL Server สำหรับ PHP หรือกำหนดค่าไม่ถูกต้อง';
$string['pass'] = 'ผ่าน';
$string['paths'] = 'เส้นทาง';
$string['pathserrcreatedataroot'] = 'ตัวติดตั้งไม่สามารถสร้างไดเรกทอรีข้อมูล ({$a->dataroot}) ได้';
$string['pathshead'] = 'ยืนยันเส้นทาง';
$string['pathsrodataroot'] = 'ไดเรกทอรี Dataroot ไม่สามารถเขียนข้อมูลได้';
$string['pathsroparentdataroot'] = 'ไดเรกทอรีหลัก ({$a->parent}) ไม่สามารถเขียนข้อมูลได้ ตัวติดตั้งไม่สามารถสร้างไดเรกทอรีข้อมูล ({$a->dataroot}) ได้';
$string['pathssubadmindir'] = 'เว็บโฮสต์จำนวนน้อยมากใช้ /admin เป็น URL พิเศษให้คุณเข้าถึงแผงควบคุมหรืออย่างอื่น แต่น่าเสียดายที่นี่ขัดแย้งกับตำแหน่งมาตรฐานสำหรับหน้าผู้ดูแลระบบของ Moodle คุณสามารถแก้ไขได้โดยการเปลี่ยนชื่อไดเรกทอรี admin ในการติดตั้งของคุณและใส่ชื่อใหม่นั้นที่นี่ ตัวอย่างเช่น: <em>moodleadmin</em> ซึ่งจะแก้ไขลิงก์ผู้ดูแลระบบใน Moodle';
$string['pathssubdataroot'] = '<p>ไดเรกทอรีที่ Moodle จะเก็บเนื้อหาไฟล์ทั้งหมดที่ผู้ใช่อัปโหลด</p>
<p>ไดเรกทอรีนี้ควรสามารถอ่านและเขียนได้โดยผู้ใช้เว็บเซิร์ฟเวอร์ (โดยปกติคือ \'www-data\', \'nobody\' หรือ \'apache\')</p>
<p>ต้องไม่สามารถเข้าถึงได้โดยตรงผ่านเว็บ</p>
<p>หากไดเรกทอรียังไม่มีอยู่ในขณะนี้ กระบวนการติดตั้งจะพยายามสร้างขึ้น</p>';
$string['pathssubdirroot'] = '<p>เส้นทางแบบเต็มไปยังไดเรกทอรีที่มีโค้ด Moodle</p>';
$string['pathssubwwwroot'] = '<p>ที่อยู่แบบเต็มที่จะเข้าถึง Moodle เช่น ที่อยู่ที่ผู้ใช้จะป้อนลงในแถบที่อยู่ของเบราว์เซอร์เพื่อเข้าถึง Moodle</p>
<p>ไม่สามารถเข้าถึง Moodle โดยใช้หลายที่อยู่ได้ หากไซต์ของคุณสามารถเข้าถึงได้ผ่านหลายที่อยู่ ให้เลือกที่อยู่ที่ง่ายที่สุดและตั้งค่าการเปลี่ยนเส้นทางแบบถาวรสำหรับที่อยู่อื่น ๆ แต่ละที่อยู่</p>
<p>หากไซต์ของคุณสามารถเข้าถึงได้ทั้งจากอินเทอร์เน็ตและจากเครือข่ายภายใน (บางครั้งเรียกว่าอินทราเน็ต) ให้ใช้ที่อยู่สาธารณะที่นี่</p>
<p>หากที่อยู่ปัจจุบันไม่ถูกต้อง โปรดเปลี่ยน URL ในแถบที่อยู่ของเบราว์เซอร์และเริ่มการติดตั้งใหม่</p>';
$string['pathsunsecuredataroot'] = 'ตำแหน่ง Dataroot ไม่ปลอดภัย';
$string['pathswrongadmindir'] = 'ไม่มีไดเรกทอรี Admin อยู่จริง';
$string['pgsqlextensionisnotpresentinphp'] = 'PHP ยังไม่ได้รับการกำหนดค่าอย่างถูกต้องด้วยส่วนขยาย PGSQL เพื่อให้สามารถสื่อสารกับ PostgreSQL โปรดตรวจสอบไฟล์ php.ini ของคุณหรือคอมไพล์ PHP ใหม่';
$string['phpextension'] = 'ส่วนขยาย PHP {$a}';
$string['phpversion'] = 'เวอร์ชัน PHP';
$string['releasenoteslink'] = 'สำหรับข้อมูลเกี่ยวกับ Moodle เวอร์ชันนี้ โปรดดูบันทึกประจำรุ่นที่ {$a}';
$string['safemode'] = 'เซฟโหมด';
$string['safemodeerror'] = 'Moodle อาจมีปัญหากับการเปิดเซฟโหมดไว้';
$string['safemodehelp'] = '<p>Moodle อาจมีปัญหาหลายประการเมื่อเปิดเซฟโหมดไว้ ที่สำคัญที่สุดคืออาจไม่ได้รับอนุญาตให้สร้างไฟล์ใหม่</p>
<p>เซฟโหมดมักจะเปิดใช้งานโดยผู้ให้บริการเว็บโฮสต์สาธารณะที่เข้มงวดเกินไป ดังนั้นคุณอาจต้องหาบริษัทเว็บโฮสติ้งใหม่สำหรับไซต์ Moodle ของคุณ</p>
<p>คุณสามารถลองติดตั้งต่อไปได้หากต้องการ แต่คาดว่าจะมีปัญหาบางอย่างตามมาในภายหลัง</p>';
$string['sessionautostart'] = 'เริ่มเซสชันอัตโนมัติ';
$string['sessionautostarterror'] = 'ควรจะปิดอยู่';
$string['sessionautostarthelp'] = '<p>Moodle ต้องการการสนับสนุนเซสชันและจะไม่ทำงานหากไม่มีมัน</p>
<p>สามารถเปิดใช้งานเซสชันได้ในไฟล์ php.ini ... มองหาพารามิเตอร์ session.auto_start</p>';
$string['upgradingqtypeplugin'] = 'กำลังอัปเกรดปลั๊กอินประเภทคำถาม';
$string['webserverconfigproblemdescription'] = 'เว็บเซิร์ฟเวอร์ของคุณไม่ได้กำหนดค่าเพื่อป้องกันการเข้าถึงไฟล์ภายนอกไดเรกทอรี /public สำหรับรายละเอียดวิธีการกำหนดค่าเว็บเซิร์ฟเวอร์ของคุณให้ถูกต้อง โปรดดูเอกสารประกอบ <a href="https://docs.moodle.org/en/Upgrading#Code_directories_restructure">Upgrading - Code directories restructure</a> เมื่อกำหนดค่าใหม่แล้วให้ <a href="{$a}">กลับไปที่เว็บรูทอีกครั้ง</a>';
$string['webservernotconfigured'] = 'เว็บเซิร์ฟเวอร์ไม่ได้กำหนดค่า';
$string['welcomep10'] = '{$a->installername} ({$a->installerversion})';
$string['welcomep20'] = 'คุณกำลังเห็นหน้านี้เพราะคุณติดตั้งและเริ่มใช้งานแพ็กเกจ <strong>{$a->packname} {$a->packversion}</strong> ในคอมพิวเตอร์ของคุณสำเร็จแล้ว ขอแสดงความยินดีด้วย!';
$string['welcomep30'] = '<strong>{$a->installername}</strong> รุ่นนี้รวมแอปพลิเคชันเพื่อสร้างสภาพแวดล้อมที่ <strong>Moodle</strong> จะทำงาน ได้แก่:';
$string['welcomep40'] = 'แพ็กเกจนี้ยังรวมถึง <strong>Moodle {$a->moodlerelease} ({$a->moodleversion})</strong> ด้วย';
$string['welcomep50'] = 'การใช้แอปพลิเคชันทั้งหมดในแพ็กเกจนี้อยู่ภายใต้ใบอนุญาตที่เกี่ยวข้อง แพ็กเกจ <strong>{$a->installername}</strong> ทั้งหมดเป็น <a href="https://www.opensource.org/docs/definition_plain.html">ซอฟต์แวร์โอเพนซอร์ซ</a> และเผยแพร่ภายใต้ใบอนุญาต <a href="https://www.gnu.org/copyleft/gpl.html">GPL</a>';
$string['welcomep60'] = 'หน้าต่อไปนี้จะนำคุณผ่านขั้นตอนง่าย ๆ เพื่อกำหนดค่าและตั้งค่า <strong>Moodle</strong> บนคอมพิวเตอร์ของคุณ คุณสามารถยอมรับการตั้งค่าเริ่มต้นหรือแก้ไขตามความต้องการของคุณก็ได้';
$string['welcomep70'] = 'คลิกปุ่ม "ถัดไป" ด้านล่างเพื่อดำเนินการตั้งค่า <strong>Moodle</strong> ต่อไป';
$string['wwwroot'] = 'ที่อยู่เว็บ';
$string['wwwrooterror'] = 'ดูเหมือนว่า \'ที่อยู่เว็บ\' จะไม่ถูกต้อง ดูเหมือนจะไม่มีการติดตั้ง Moodle อยู่ที่นั่น ค่าด้านล่างได้รับการรีเซ็ตแล้ว';

// Deprecated since Moodle 5.0.
$string['sqliteextensionisnotpresentinphp'] = 'PHP ยังไม่ได้รับการกำหนดค่าอย่างถูกต้องด้วยส่วนขยาย SQLite โปรดตรวจสอบไฟล์ php.ini ของคุณหรือคอมไพล์ PHP ใหม่';

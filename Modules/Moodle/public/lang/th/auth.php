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
 * Strings for component 'auth', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   core_auth
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actauthhdr'] = 'ปลั๊กอินการตรวจสอบสิทธิ์ที่มีอยู่';
$string['alternatelogin'] = 'หากคุณป้อน URL ที่นี่ จะใช้เป็นหน้าเข้าสู่ระบบสำหรับไซต์นี้ หน้านี้ควรมีฟอร์มที่มีคุณสมบัติการดำเนินการตั้งค่าเป็น <strong>\'{$a}\'</strong> และส่งคืนฟิลด์ <strong>ชื่อผู้ใช้</strong> และ <strong>รหัสผ่าน</strong>.<br />โปรดระวังอย่าป้อน URL ที่ไม่ถูกต้องเนื่องจากคุณอาจล็อกตัวเองออกจากไซต์นี้.<br />เว้นการตั้งค่านี้ว่างเปล่าเพื่อใช้หน้าเข้าสู่ระบบเริ่มต้น.';
$string['alternateloginurl'] = 'URL เข้าสู่ระบบสำรอง';
$string['auth_common_settings'] = 'การตั้งค่าทั่วไป';
$string['auth_data_mapping'] = 'การแมปข้อมูล';
$string['authenticationoptions'] = 'ตัวเลือกการตรวจสอบสิทธิ์';
$string['auth_fieldlock'] = 'ค่าล็อก';
$string['auth_fieldlockfield'] = 'ค่าล็อก ({$a})';
$string['auth_fieldlock_expl'] = '<p>ค่าล็อก: หากเปิดใช้งาน ผู้ใช้จะไม่สามารถแก้ไขฟิลด์ได้ ใช้ตัวเลือกนี้หากคุณดูแลรักษาข้อมูลนี้ในระบบตรวจสอบสิทธิ์ภายนอก.</p>';
$string['auth_fieldlocks'] = 'ล็อกฟิลด์ผู้ใช้';
$string['auth_fieldlocks_help'] = '<p>คุณสามารถล็อกฟิลด์ข้อมูลผู้ใช้ได้ สิ่งนี้มีประโยชน์สำหรับไซต์ที่ข้อมูลผู้ใช้ได้รับการดูแลรักษาโดยผู้ดูแลระบบด้วยตนเองโดยการแก้ไขบันทึกผู้ใช้หรืออัปโหลดโดยใช้สิ่งอำนวยความสะดวก \'อัปโหลดผู้ใช้\' หากคุณล็อกฟิลด์ที่จำเป็นสำหรับ Moodle ตรวจสอบให้แน่ใจว่าคุณจัดเตรียมข้อมูลนั้นเมื่อสร้างบัญชีผู้ใช้หรือบัญชีจะไม่สามารถใช้งานได้.</p><p>พิจารณาตั้งโหมดล็อกเป็น \'ปลดล็อกหากว่างเปล่า\' เพื่อหลีกเลี่ยงปัญหานี้.</p>';
$string['auth_fieldmapping'] = 'การแมปข้อมูล ({$a})';
$string['auth_forgotpasswordrecaptcha'] = 'เปิดใช้งาน reCAPTCHA สำหรับลืมรหัสผ่าน';
$string['auth_forgotpasswordrecaptcha_desc'] = 'เพิ่มองค์ประกอบฟอร์มยืนยันภาพ/เสียงไปยังหน้ากู้คืนรหัสผ่านที่ลืม สิ่งนี้ลดความเสี่ยงของการพยายามลืมรหัสผ่านที่ไม่สมเหตุสมผล สำหรับรายละเอียดเพิ่มเติมดู <a href="https://www.google.com/recaptcha">Google reCAPTCHA</a>.';
$string['auth_changepasswordhelp'] = 'ช่วยเหลือการเปลี่ยนรหัสผ่าน';
$string['auth_changepasswordhelp_expl'] = 'แสดงความช่วยเหลือรหัสผ่านที่สูญหายแก่ผู้ใช้ที่สูญเสียรหัสผ่าน {$a} ของตน สิ่งนี้จะแสดงได้ทั้งหรือแทนที่ <strong>URL เปลี่ยนรหัสผ่าน</strong> หรือการเปลี่ยนรหัสผ่านภายใน Moodle.'; 
$string['auth_changepasswordurl'] = 'URL เปลี่ยนรหัสผ่าน';
$string['auth_changepasswordurl_expl'] = 'ระบุ URL เพื่อส่งผู้ใช้ที่สูญเสียรหัสผ่าน {$a} ของตน ตั้ง <strong>ใช้หน้าเปลี่ยนรหัสผ่านมาตรฐาน</strong> เป็น <strong>ไม่</strong>.';
$string['auth_changingemailaddress'] = 'คุณขอเปลี่ยนที่อยู่อีเมล จาก {$a->oldemail} เป็น {$a->newemail}. เพื่อความปลอดภัย เราส่งข้อความไปยังที่อยู่ใหม่เพื่อยืนยันว่ามันเป็นของคุณ ที่อยู่อีเมลของคุณจะได้รับการอัปเดตทันทีที่คุณเปิด URL ที่ส่งไปยังคุณในข้อความ ลิงก์ยืนยันจะหมดอายุใน 10 นาที.';
$string['authinstructions'] = 'เว้นว่างนี้สำหรับคำแนะนำเข้าสู่ระบบเริ่มต้นที่จะแสดงบนหน้าเข้าสู่ระบบ หากคุณต้องการจัดเตรียมคำแนะนำเข้าสู่ระบบที่กำหนดเอง ป้อนที่นี่.';
$string['auth_invalidnewemailkey'] = 'ข้อผิดพลาด: หากคุณกำลังพยายามยืนยันการเปลี่ยนที่อยู่อีเมล คุณอาจทำผิดพลาดในการคัดลอก URL ที่เราส่งให้คุณทางอีเมล กรุณาคัดลอกที่อยู่และลองอีกครั้ง.';
$string['auth_loginpasswordtoggle'] = 'สลับการมองเห็นรหัสผ่าน';
$string['auth_loginpasswordtoggle_desc'] = 'เพิ่มไอคอนไปยังฟิลด์รหัสผ่านบนหน้าจอเข้าสู่ระบบที่อนุญาตให้ผู้ใช้แสดงหรือซ่อนรหัสผ่านที่ป้อน.';
$string['auth_loginrecaptcha'] = 'เปิดใช้งาน reCAPTCHA สำหรับเข้าสู่ระบบ';
$string['auth_loginrecaptcha_desc'] = 'เพิ่มองค์ประกอบฟอร์มยืนยันภาพ/เสียงไปยังหน้าเข้าสู่ระบบ สิ่งนี้ลดความเสี่ยงของการพยายามเข้าสู่ระบบที่ไม่สมเหตุสมผล สำหรับรายละเอียดเพิ่มเติมดู <a href="https://www.google.com/recaptcha">Google reCAPTCHA</a>.';
$string['auth_multiplehosts'] = 'โฮสต์หลายรายหรือที่อยู่สามารถระบุได้ (เช่น host1.com;host2.com;host3.com) หรือ (เช่น xxx.xxx.xxx.xxx;xxx.xxx.xxx.xxx)';
$string['auth_notconfigured'] = 'วิธีการตรวจสอบสิทธิ์ {$a} ไม่ได้กำหนดค่า.';
$string['auth_outofnewemailupdateattempts'] = 'คุณหมดจำนวนการพยายามที่อนุญาตเพื่ออัปเดตที่อยู่อีเมลของคุณ คำขออัปเดตของคุณถูกยกเลิก.';
$string['auth_passwordisexpired'] = 'รหัสผ่านของคุณหมดอายุแล้ว กรุณาเปลี่ยนตอนนี้.';
$string['auth_passwordwillexpire'] = 'รหัสผ่านของคุณจะหมดอายุใน {$a} วัน คุณต้องการเปลี่ยนรหัสผ่านตอนนี้หรือไม่?';
$string['auth_remove_delete'] = 'ลบภายในเต็มรูปแบบ';
$string['auth_remove_keep'] = 'เก็บภายใน';
$string['auth_remove_suspend'] = 'ระงับภายใน';
$string['auth_remove_user'] = 'ระบุสิ่งที่จะทำกับบัญชีผู้ใช้ภายในระหว่างการซิงโครไนซ์มวลเมื่อผู้ใช้ถูกลบออกจากแหล่งภายนอก เฉพาะผู้ใช้ที่ถูกระงับเท่านั้นที่จะได้รับการกู้คืนโดยอัตโนมัติหากพวกเขาปรากฏในแหล่งภายนอกอีกครั้ง.';
$string['auth_remove_user_key'] = 'ลบผู้ใช้ภายนอก';
$string['auth_sync_suspended'] = 'หากเปิดใช้งาน แอตทริบิวต์ที่ถูกระงับจะใช้เพื่ออัปเดตสถานะการระงับของบัญชีผู้ใช้ภายใน.';
$string['auth_sync_suspended_key'] = 'ซิงโครไนซ์สถานะการระงับผู้ใช้ภายใน';
$string['auth_sync_script'] = 'การซิงโครไนซ์บัญชีผู้ใช้';
$string['auth_updatelocal'] = 'อัปเดตภายใน';
$string['auth_updatelocalfield'] = 'อัปเดตภายใน ({$a})';
$string['auth_updatelocal_expl'] = '<p><b>อัปเดตภายใน:</b> หากเปิดใช้งาน ฟิลด์จะได้รับการอัปเดต (จากตรวจสอบสิทธิ์ภายนอก) ทุกครั้งที่ผู้ใช้เข้าสู่ระบบหรือมีซิงโครไนซ์ผู้ใช้ ฟิลด์ที่ตั้งให้อัปเดตภายในควรล็อก.</p>';
$string['auth_updateremote'] = 'อัปเดตภายนอก';
$string['auth_updateremotefield'] = 'อัปเดตภายนอก ({$a})';
$string['auth_updateremote_expl'] = '<p><b>อัปเดตภายนอก:</b> หากเปิดใช้งาน ตรวจสอบสิทธิ์ภายนอกจะได้รับการอัปเดตเมื่อบันทึกผู้ใช้ได้รับการอัปเดต ฟิลด์ควรปลดล็อกเพื่ออนุญาตการแก้ไข.</p>';
$string['auth_updateremote_ldap'] = '<p><b>หมายเหตุ:</b> การอัปเดตข้อมูล LDAP ภายนอกจำเป็นต้องตั้ง binddn และ bindpw เป็นผู้ใช้ผูกที่มีสิทธิ์แก้ไขไปยังบันทึกผู้ใช้ทั้งหมด ปัจจุบันไม่รักษาคุณสมบัติหลายค่า และจะลบค่าพิเศษในการอัปเดต.</p>';
$string['auth_user_create'] = 'เปิดใช้งานการสร้างผู้ใช้';
$string['auth_user_creation'] = 'ผู้ใช้ใหม่ (นิรนาม) สามารถสร้างบัญชีผู้ใช้บนแหล่งตรวจสอบสิทธิ์ภายนอกและยืนยันผ่านอีเมล หากคุณเปิดใช้งานนี้ จงจำไว้ว่าตั้งค่าตัวเลือกเฉพาะโมดูลสำหรับการสร้างผู้ใช้ด้วย.';
$string['auth_usernameexists'] = 'ชื่อผู้ใช้ที่เลือกมีอยู่แล้ว กรุณาเลือกใหม่.';
$string['auth_usernotexist'] = 'ไม่สามารถอัปเดตผู้ใช้ที่ไม่มีอยู่: {$a}';
$string['auto_add_remote_users'] = 'เพิ่มผู้ใช้ระยะไกลอัตโนมัติ';
$string['cannotmapfield'] = 'ฟิลด์ "{$a->fieldname}" ไม่สามารถแมปได้เนื่องจากชื่อสั้น "{$a->shortname}" ยาวเกินไป เพื่ออนุญาตให้แมปได้ คุณต้องลดชื่อสั้นเหลือ {$a->charlimit} อักขระ <a href="{$a->link}">แก้ไขฟิลด์โปรไฟล์ผู้ใช้</a>';
$string['createpassword'] = 'สร้างรหัสผ่านและแจ้งผู้ใช้';
$string['createpasswordifneeded'] = 'สร้างรหัสผ่านหากจำเป็นและส่งผ่านอีเมล';
$string['emailchangecancel'] = 'ยกเลิกการเปลี่ยนอีเมล';
$string['emailchangepending'] = 'การเปลี่ยนกำลังรอดำเนินการ เปิดลิงก์ที่ส่งไปยังคุณที่ {$a->preference_newemail}.';
$string['emailnowexists'] = 'ที่อยู่อีเมลที่คุณพยายามกำหนดให้กับโปรไฟล์ของคุณได้ถูกกำหนดให้กับคนอื่นตั้งแต่คำขอเดิมของคุณ คำขอการเปลี่ยนที่อยู่อีเมลของคุณจึงถูกยกเลิก แต่คุณอาจลองอีกครั้งด้วยที่อยู่อื่น.';
$string['emailupdate'] = 'การอัปเดตที่อยู่อีเมล';
$string['emailupdatemessage'] = 'สวัสดี {$a->firstname},

คุณขอเปลี่ยนที่อยู่อีเมลสำหรับบัญชีของคุณบน {$a->site}.

เพื่อยืนยันการเปลี่ยนนี้ กรุณาคลิกลิงก์ด้านล่าง:

<a href="{$a->url}">ยืนยันการเปลี่ยนอีเมล</a>


ลิงก์ยืนยันจะหมดอายุใน <strong>10 นาที</strong>.

{$a->supportemail}';
$string['emailupdatesuccess'] = 'ที่อยู่อีเมลของผู้ใช้ <em>{$a->fullname}</em> ได้รับการอัปเดตเป็น <em>{$a->email}</em> สำเร็จแล้ว.';
$string['emailupdatetitle'] = 'การยืนยันการอัปเดตอีเมลที่ {$a->site}';
$string['errormaxconsecutiveidentchars'] = 'รหัสผ่านต้องมีอักขระเหมือนกันติดต่อกันได้ไม่เกิน {$a} อักขระ.';
$string['errorminpassworddigits'] = 'รหัสผ่านต้องมีอย่างน้อย {$a} หลัก.';
$string['errorminpasswordlength'] = 'รหัสผ่านต้องยาวอย่างน้อย {$a} อักขระ.';
$string['errorminpasswordlower'] = 'รหัสผ่านต้องมีอย่างน้อย {$a} ตัวอักษรตัวเล็ก.';
$string['errorminpasswordnonalphanum'] = 'รหัสผ่านต้องมีอย่างน้อย {$a} อักขระพิเศษ เช่น *, -, หรือ #.';
$string['errorpasswordreused'] = 'รหัสผ่านนี้ได้ถูกใช้ไปแล้ว และไม่อนุญาตให้ใช้ซ้ำ';
$string['errorminpasswordupper'] = 'รหัสผ่านต้องมีอย่างน้อย {$a} ตัวอักษรตัวใหญ่.';
$string['errorpasswordupdate'] = 'ข้อผิดพลาดในการอัปเดตรหัสผ่าน รหัสผ่านไม่เปลี่ยน';
$string['eventuserloggedin'] = 'ผู้ใช้ได้เข้าสู่ระบบ';
$string['eventuserloggedinas'] = 'ผู้ใช้เข้าสู่ระบบในฐานะผู้ใช้อื่น';
$string['eventuserloginfailed'] = 'การเข้าสู่ระบบของผู้ใช้ล้มเหลว';
$string['forcechangepassword'] = 'บังคับเปลี่ยนรหัสผ่าน';
$string['forcechangepasswordfirst_help'] = 'บังคับผู้ใช้ให้เปลี่ยนรหัสผ่านในการเข้าสู่ระบบครั้งแรกของ Moodle.';
$string['forcechangepassword_help'] = 'บังคับผู้ใช้ให้เปลี่ยนรหัสผ่านในการเข้าสู่ระบบครั้งต่อไปของ Moodle.';
$string['forgottenpassword'] = 'หากคุณป้อน URL ที่นี่ จะใช้เป็นหน้ากู้คืนรหัสผ่านที่สูญหายสำหรับไซต์นี้ สิ่งนี้มีจุดมุ่งหมายสำหรับไซต์ที่จัดการรหัสผ่านทั้งหมดภายนอก Moodle เว้นว่างนี้เพื่อใช้การกู้คืนรหัสผ่านเริ่มต้น.';
$string['forgottenpasswordurl'] = 'URL รหัสผ่านที่ลืม';
$string['getrecaptchaapi'] = 'เพื่อใช้ reCAPTCHA คุณต้องได้รับ API key จาก <a href=\'https://www.google.com/recaptcha/admin\'>https://www.google.com/recaptcha/admin</a>';
$string['guestloginbutton'] = 'ปุ่มเข้าสู่ระบบสำหรับผู้เยี่ยมชม';
$string['changepassword'] = 'URL เปลี่ยนรหัสผ่าน';
$string['changepasswordhelp'] = 'URL ของหน้ากู้คืนรหัสผ่านที่สูญหาย ซึ่งจะส่งไปยังผู้ใช้ในอีเมล โปรดทราบว่าการตั้งค่านี้จะไม่มีผลหากตั้ง URL รหัสผ่านที่ลืมในการตั้งค่าการตรวจสอบสิทธิ์ทั่วไป.';
$string['chooseauthmethod'] = 'เลือกวิธีการตรวจสอบสิทธิ์';
$string['chooseauthmethod_help'] = 'การตั้งค่านี้กำหนดวิธีการตรวจสอบสิทธิ์ที่ใช้เมื่อผู้ใช้เข้าสู่ระบบ ควรเลือกเฉพาะปลั๊กอินการตรวจสอบสิทธิ์ที่เปิดใช้งานเท่านั้น มิฉะนั้นผู้ใช้จะไม่สามารถเข้าสู่ระบบได้ หากต้องการบล็อกผู้ใช้ไม่ให้เข้าสู่ระบบ ให้เลือก "ไม่อนุญาตให้เข้าสู่ระบบ".';
$string['incorrectpleasetryagain'] = 'ไม่ถูกต้อง กรุณาลองใหม่';
$string['infilefield'] = 'ฟิลด์ที่จำเป็นในไฟล์';
$string['informminpassworddigits'] = 'อย่างน้อย {$a} หลัก';
$string['informminpasswordlength'] = 'อย่างน้อย {$a} อักขระ';
$string['informminpasswordlower'] = 'อย่างน้อย {$a} ตัวอักษรตัวเล็ก';
$string['informminpasswordnonalphanum'] = 'อย่างน้อย {$a} อักขระพิเศษ เช่น *, -, หรือ #';
$string['informminpasswordreuselimit'] = 'รหัสผ่านสามารถใช้ซ้ำได้หลังจากการเปลี่ยน {$a} ครั้ง';
$string['informminpasswordupper'] = 'อย่างน้อย {$a} ตัวอักษรตัวใหญ่';
$string['informpasswordpolicy'] = 'รหัสผ่านต้องมี {$a}';
$string['instructions'] = 'คำแนะนำ';
$string['internal'] = 'ภายใน';
$string['limitconcurrentlogins'] = 'จำกัดการเข้าสู่ระบบพร้อมกัน';
$string['limitconcurrentlogins_desc'] = 'หากเปิดใช้งาน จำนวนการเข้าสู่ระบบพร้อมกันของแต่ละผู้ใช้จะถูกจำกัด เซสชันเก่าสุดจะสิ้นสุดหลังจากถึงขีดจำกัด โปรดทราบว่าผู้ใช้อาจสูญเสียงานที่ยังไม่ได้บันทึกทั้งหมด การตั้งค่านี้ไม่เข้ากันได้กับปลั๊กอินการตรวจสอบสิทธิ์ Single Sign-On (SSO)';
$string['locked'] = 'ถูกล็อก';
$string['authloginviaemail'] = 'อนุญาตให้เข้าสู่ระบบผ่านอีเมล';
$string['authloginviaemail_desc'] = 'อนุญาตให้ผู้ใช้ใช้ทั้งชื่อผู้ใช้และที่อยู่อีเมล (หากไม่ซ้ำกัน) สำหรับการเข้าสู่ระบบไซต์';
$string['allowaccountssameemail'] = 'อนุญาตให้มีบัญชีที่ใช้อีเมลเดียวกัน';
$string['allowaccountssameemail_desc'] = 'หากเปิดใช้งาน บัญชีผู้ใช้มากกว่าหนึ่งบัญชีสามารถใช้ที่อยู่อีเมลเดียวกันได้ ซึ่งอาจส่งผลให้เกิดปัญหาด้านความปลอดภัยหรือความเป็นส่วนตัว เช่น กับอีเมลยืนยันการเปลี่ยนรหัสผ่าน';
$string['md5'] = 'แฮช MD5';
$string['nopasswordchange'] = 'ไม่สามารถเปลี่ยนรหัสผ่านได้';
$string['nopasswordchangeforced'] = 'คุณไม่สามารถดำเนินการต่อได้โดยไม่เปลี่ยนรหัสผ่าน อย่างไรก็ตามไม่มีหน้าเพจสำหรับเปลี่ยนรหัสผ่านที่ใช้งานได้ กรุณาติดต่อผู้ดูแลระบบ Moodle ของคุณ';
$string['noprofileedit'] = 'ไม่สามารถแก้ไขโปรไฟล์ได้';
$string['ntlmsso_attempting'] = 'กำลังพยายาม Single Sign On ผ่าน NTLM...';
$string['ntlmsso_failed'] = 'การเข้าสู่ระบบอัตโนมัติล้มเหลว ลองใช้หน้าเข้าสู่ระบบปกติ...';
$string['ntlmsso_isdisabled'] = 'NTLM SSO ถูกปิดใช้งาน';
$string['passwordhandling'] = 'การจัดการฟิลด์รหัสผ่าน';
$string['plaintext'] = 'ข้อความธรรมดา';
$string['pluginnotenabled'] = 'ปลั๊กอินการตรวจสอบสิทธิ์ \'{$a}\' ไม่ได้เปิดใช้งาน';
$string['pluginnotinstalled'] = 'ปลั๊กอินการตรวจสอบสิทธิ์ \'{$a}\' ไม่ได้ติดตั้ง';
$string['privacy:metadata:userpref:createpassword'] = 'ระบุว่าควรสร้างรหัสผ่านสำหรับผู้ใช้';
$string['privacy:metadata:userpref:forcepasswordchange'] = 'ระบุว่าผู้ใช้ควรเปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบหรือไม่';
$string['privacy:metadata:userpref:loginfailedcount'] = 'จำนวนครั้งที่ผู้ใช้ล้มเหลวในการเข้าสู่ระบบ';
$string['privacy:metadata:userpref:loginfailedcountsincesuccess'] = 'จำนวนครั้งที่ผู้ใช้ล้มเหลวในการเข้าสู่ระบบนับตั้งแต่การเข้าสู่ระบบสำเร็จครั้งล่าสุด';
$string['privacy:metadata:userpref:loginfailedlast'] = 'วันที่บันทึกความพยายามในการเข้าสู่ระบบที่ล้มเหลวครั้งล่าสุด';
$string['privacy:metadata:userpref:loginlockout'] = 'ว่าบัญชีผู้ใช้ถูกล็อกเนื่องจากความพยายามในการเข้าสู่ระบบที่ล้มเหลวหรือไม่ และวันที่บัญชีถูกล็อก';
$string['privacy:metadata:userpref:loginlockoutignored'] = 'ระบุว่าบัญชีผู้ใช้ไม่ควรถูกล็อกเด็ดขาด';
$string['privacy:metadata:userpref:loginlockoutsecret'] = 'เมื่อถูกล็อก รหัสลับที่ผู้ใช้ต้องใช้สำหรับปลดล็อกบัญชีของตน';
$string['potentialidps'] = 'เข้าสู่ระบบโดยใช้บัญชีของคุณที่:';
$string['recaptcha'] = 'reCAPTCHA';
$string['recaptcha_help'] = 'CAPTCHA ใช้สำหรับป้องกันการใช้งานในทางที่ผิดจากโปรแกรมอัตโนมัติ ทำตามคำแนะนำเพื่อยืนยันว่าคุณเป็นมนุษย์ ซึ่งอาจเป็นช่องทำเครื่องหมาย อักขระที่แสดงในรูปภาพที่คุณต้องป้อน หรือชุดรูปภาพที่ต้องเลือก

หากคุณไม่แน่ใจว่ารูปภาพคืออะไร คุณสามารถลองขอ CAPTCHA อื่นหรือ CAPTCHA เสียงได้';
$string['recaptcha_link'] = 'auth/email';
$string['security_question'] = 'คำถามความปลอดภัย';
$string['selfregistration'] = 'การลงทะเบียนด้วยตนเอง';
$string['selfregistration_help'] = 'หากเลือกปลั๊กอินการตรวจสอบสิทธิ์ เช่นการลงทะเบียนด้วยตนเองผ่านอีเมล จะเปิดให้ผู้ใช้ที่อาจจะสนใจสามารถลงทะเบียนและสร้างบัญชีได้ด้วยตนเอง ซึ่งอาจทำให้สแปมเมอร์สามารถสร้างบัญชีเพื่อใช้โพสต์ฟอรัม บล็อก เอนทรี เป็นต้นเพื่อสแปมได้ หากต้องการหลีกเลี่ยงความเสี่ยงนี้ ควรปิดใช้งานการลงทะเบียนด้วยตนเองหรือจำกัดโดยการตั้งค่า <em>โดเมนอีเมลที่อนุญาต</em>';
$string['settingmigrationmismatch'] = 'ตรวจพบค่าไม่ตรงกันขณะแก้ไขชื่อการตั้งค่าปลั๊กอิน! ปลั๊กอินการตรวจสอบสิทธิ์ \'{$a->plugin}\' มีการตั้งค่า \'{$a->setting}\' ที่กำหนดเป็น \'{$a->legacy}\' ภายใต้ชื่อเดิมและเป็น \'{$a->current}\' ภายใต้ชื่อปัจจุบัน ค่าหลังได้ถูกตั้งเป็นค่าที่ถูกต้องแต่คุณควรตรวจสอบและยืนยันว่าเป็นไปตามที่คาดหวัง';
$string['sha1'] = 'แฮช SHA-1';
$string['showguestlogin'] = 'คุณสามารถซ่อนหรือแสดงปุ่มเข้าสู่ระบบสำหรับผู้เยี่ยมชมบนหน้าเข้าสู่ระบบได้';
$string['showloginform'] = 'แสดงแบบฟอร์มการเข้าสู่ระบบด้วยตนเอง';
$string['showloginform_desc'] = 'หากผู้ใช้ทั้งหมดในไซต์ใช้วิธีการตรวจสอบสิทธิ์ เช่น OAuth 2 ที่ไม่ต้องการให้พวกเขาป้อนชื่อผู้ใช้และรหัสผ่าน คุณสามารถซ่อนแบบฟอร์มการเข้าสู่ระบบด้วยตนเองได้ โปรดทราบว่าผู้ใช้ที่มีบัญชีด้วยตนเองจะไม่สามารถเข้าสู่ระบบได้

หากคุณซ่อนแบบฟอร์มการเข้าสู่ระบบด้วยตนเองแล้วไม่สามารถเข้าสู่ระบบได้ คุณสามารถแสดงมันอีกครั้งโดยใช้คำสั่ง php admin/cli/cfg.php --name=showloginform --set=1';
$string['stdchangepassword'] = 'ใช้หน้าเพจมาตรฐานสำหรับเปลี่ยนรหัสผ่าน';
$string['stdchangepassword_expl'] = 'หากระบบการตรวจสอบสิทธิ์ภายนอกอนุญาตให้เปลี่ยนรหัสผ่านผ่าน Moodle ให้เปลี่ยนเป็น ใช่ การตั้งค่านี้จะแทนที่ \'URL เปลี่ยนรหัสผ่าน\'';
$string['stdchangepassword_explldap'] = 'หมายเหตุ: ขอแนะนำให้คุณใช้ LDAP ผ่านอุโมงค์ที่เข้ารหัสด้วย SSL (ldaps://) หากเซิร์ฟเวอร์ LDAP อยู่ระยะไกล';
$string['suspended'] = 'บัญชีถูกระงับ';
$string['suspended_help'] = 'บัญชีผู้ใช้ที่ถูกระงับไม่สามารถเข้าสู่ระบบหรือใช้เว็บเซอร์วิสได้ และข้อความออกจะถูกทิ้ง';
$string['testsettings'] = 'ทดสอบการตั้งค่า';
$string['testsettingsheading'] = 'ทดสอบการตั้งค่าการตรวจสอบสิทธิ์ - {$a}';
$string['unlocked'] = 'ปลดล็อก';
$string['unlockedifempty'] = 'ปลดล็อกหากว่างเปล่า';
$string['update_never'] = 'ไม่เคย';
$string['update_oncreate'] = 'เมื่อสร้าง';
$string['update_onlogin'] = 'ทุกครั้งที่เข้าสู่ระบบ';
$string['update_onupdate'] = 'เมื่ออัปเดต';
$string['user_activatenotsupportusertype'] = 'auth: ldap user_activate() ไม่รองรับประเภทผู้ใช้ที่เลือก: {$a}';
$string['user_disablenotsupportusertype'] = 'auth: ldap user_disable() ไม่รองรับประเภทผู้ใช้ที่เลือก (ยังไม่รองรับ)';
$string['username'] = 'ชื่อผู้ใช้';
$string['username_help'] = 'โปรดทราบว่าปลั๊กอินการตรวจสอบสิทธิ์บางอย่างจะไม่อนุญาตให้คุณเปลี่ยนชื่อผู้ใช้';

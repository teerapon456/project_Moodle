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
 * Strings for component 'ai', language 'en'
 *
 * @package    core
 * @category   string
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['acceptai'] = 'ยอมรับและดำเนินการต่อ';
$string['action'] = 'การดำเนินการ';
$string['action_explain_text'] = 'อธิบายข้อความ';
$string['action_explain_text_desc'] = 'อธิบายเนื้อหาข้อความในหน้าหลักสูตร';
$string['action_explain_text_help'] = 'ให้คำอธิบายที่ขยายความแนวคิดหลัก ทำให้แนวคิดที่ซับซ้อนง่ายขึ้น และเพิ่มบริบทเพื่อให้เข้าใจข้อความได้ง่ายขึ้น';
$string['action_explain_text_instruction'] = 'คุณจะได้รับข้อความจากผู้ใช้ งานของคุณคืออธิบายข้อความที่ได้รับ โปรดปฏิบัติตามแนวทางเหล่านี้:
    1. ขยายความ: อธิบายแนวคิดหลักอย่างละเอียดเพื่อให้คำอธิบายมีความลึกซึ้งและมีความหมาย หลีกเลี่ยงการพูดซ้ำข้อความเดิมแบบคำต่อคำ
    2. ทำให้ง่าย: ย่อยคำศัพท์หรือแนวคิดที่ซับซ้อนให้เข้าใจง่ายสำหรับผู้ฟังทั่วไป รวมถึงผู้เรียน
    3. ให้บริบท: อธิบายว่าทำไมบางสิ่งถึงเกิดขึ้น ทำงานอย่างไร หรือมีจุดประสงค์อะไร รวมถึงยกตัวอย่างหรือการเปรียบเปรยที่เกี่ยวข้องเพื่อเสริมความเข้าใจตามความเหมาะสม
    4. จัดลำดับอย่างมีตรรกะ: โครงสร้างคำอธิบายของคุณควรไหลลื่นเป็นธรรมชาติ เริ่มจากแนวคิดพื้นฐานก่อนจะไปสู่รายละเอียดเชิงลึก

คำแนะนำสำคัญ:
    1. ส่งคืนสรุปในรูปแบบข้อความธรรมดาเท่านั้น
    2. ห้ามใช้การจัดรูปแบบ markdown, คำทักทาย หรือคำพูดที่เยิ่นเย้อ
    3. มุ่งเน้นเรื่องความชัดเจน ความกระชับ และการเข้าถึงข้อมูลได้ง่าย

ตรวจสอบให้แน่ใจว่าคำอธิบายอ่านง่ายและสื่อสารประเด็นหลักของข้อความต้นฉบับได้อย่างมีประสิทธิภาพ';
$string['action_generate_image'] = 'สร้างรูปภาพ';
$string['action_generate_image_desc'] = 'สร้างรูปภาพตามคำสั่งข้อความ (Prompt)';
$string['action_generate_image_help'] = 'สร้างรูปภาพตามคำสั่ง';
$string['action_generate_text'] = 'สร้างข้อความ';
$string['action_generate_text_desc'] = 'สร้างข้อความตามคำสั่งข้อความ (Prompt)';
$string['action_generate_text_help'] = 'สร้างข้อความตามคำสั่ง';
$string['action_generate_text_instruction'] = 'คุณจะได้รับข้อความจากผู้ใช้ งานของคุณคือสร้างข้อความตามคำขอของพวกเขา โปรดปฏิบัติตามคำแนะนำสำคัญเหล่านี้:
    1. ส่งคืนสรุปในรูปแบบข้อความธรรมดาเท่านั้น
    2. ห้ามใช้การจัดรูปแบบ markdown, คำทักทาย หรือคำพูดที่เยิ่นเย้อ';
$string['action_summarise_text'] = 'สรุปข้อความ';
$string['action_summarise_text_desc'] = 'สรุปเนื้อหาข้อความในหน้าหลักสูตร';
$string['action_summarise_text_help'] = 'สร้างสรุปเนื้อหาในหน้าเพจอย่างย่อ';
$string['action_summarise_text_instruction'] = 'คุณจะได้รับข้อความจากผู้ใช้ งานของคุณคือสรุปข้อความที่ได้รับ โปรดปฏิบัติตามแนวทางเหล่านี้:
    1. ย่อความ: ย่อข้อความยาวๆ ให้เหลือแต่ประเด็นสำคัญ
    2. ทำให้ง่าย: ทำให้ข้อมูลที่ซับซ้อนเข้าใจง่ายขึ้น โดยเฉพาะสำหรับผู้เรียน

คำแนะนำสำคัญ:
    1. ส่งคืนสรุปในรูปแบบข้อความธรรมดาเท่านั้น
    2. ห้ามใช้การจัดรูปแบบ markdown, คำทักทาย หรือคำพูดที่เยิ่นเย้อ
    3. มุ่งเน้นเรื่องความชัดเจน ความกระชับ และการเข้าถึงข้อมูลได้ง่าย

ตรวจสอบให้แน่ใจว่าการสรุปอ่านง่ายและสื่อสารประเด็นหลักของข้อความต้นฉบับได้อย่างมีประสิทธิภาพ';
$string['action_translate_text'] = 'แปลข้อความ';
$string['action_translate_text_desc'] = 'แปลข้อความที่ให้ไว้จากภาษาหนึ่งไปยังอีกภาษาหนึ่ง';
$string['actionsettingprovider'] = 'ตั้งค่าการดำเนินการ {$a}';
$string['actionsettingprovider_desc'] = 'การตั้งค่าเหล่านี้ควบคุมว่า {$a->providername} จะดำเนินการ {$a->actionname} อย่างไร';
$string['actionsettings'] = 'การตั้งค่าการดำเนินการ';
$string['actionsettings_desc'] = 'การตั้งค่าเหล่านี้ควบคุมการดำเนินการ AI สำหรับอินสแตนซ์ผู้ให้บริการนี้';
$string['ai'] = 'AI';
$string['aiactionshdr'] = 'เลือกคุณสมบัติ AI สำหรับกิจกรรมนี้:';
$string['aiactionregister'] = 'ทะเบียนการดำเนินการ AI';
$string['aiplacements'] = 'ตำแหน่งบริการ AI';
$string['aipolicyacceptance'] = 'การยอมรับนโยบาย AI';
$string['aipolicyregister'] = 'ทะเบียนนโยบาย AI';
$string['aiproviders'] = 'ผู้ให้บริการ AI';
$string['aireports'] = 'รายงาน AI';
$string['aitools'] = 'เครื่องมือ AI';
$string['aitoolsincourseactivitydesc'] = 'หากตั้งค่าเป็น ใช่ คุณสามารถระบุได้ว่าจะให้คุณสมบัติ AI ใดบ้างที่สามารถใช้งานได้';
$string['aitoolsincoursedesc'] = 'หากตั้งค่าเป็น ใช่ เครื่องมือ AI จะพร้อมใช้งานสำหรับกิจกรรมต่างๆ ในรายวิชานี้ สามารถตั้งค่าเครื่องมือ AI ได้ในการตั้งค่าของแต่ละกิจกรรม';
$string['aitoolsnotenabled'] = 'หากต้องการระบุว่าคุณสมบัติ AI ใดบ้างที่สามารถใช้งานได้ในกิจกรรมนี้ ให้ไปที่การตั้งค่ารายวิชาและเปิดให้ใช้เครื่องมือ AI';
$string['aiusage'] = 'การใช้งาน AI';
$string['aiusagepolicy'] = 'นโยบายการใช้งาน AI';
$string['availableplacements'] = 'เลือกจุดที่จะให้มีการดำเนินการ AI';
$string['availableplacements_desc'] = 'Placement กำหนดว่าการดำเนินการ AI จะสามารถใช้งานที่ไหนและอย่างไรในไซต์ของคุณ คุณสามารถเลือกได้ว่าจะให้มีการดำเนินการใดบ้างในแต่ละตำแหน่งผ่านเมนูการตั้งค่า';
$string['availableproviders'] = 'จัดการผู้ให้บริการ AI ที่เชื่อมต่อกับ LMS ของคุณ';
$string['availableproviders_desc'] = 'ผู้ให้บริการ AI จะเพิ่มฟังก์ชันการทำงานของ AI ให้กับไซต์ของคุณผ่าน \'การดำเนินการ\' เช่น การสรุปข้อความ หรือการสร้างรูปภาพ<br/>คุณสามารถจัดการการดำเนินการสำหรับผู้ให้บริการแต่ละรายได้ในการตั้งค่าของพวกเขา';
$string['btninstancecreate'] = 'สร้างอินสแตนซ์';
$string['btninstanceupdate'] = 'อัปเดตอินสแตนซ์';
$string['completiontokens'] = 'Completion tokens';
$string['completiontokens_help'] = 'Completion tokens คือหน่วยข้อความที่สร้างโดยโมเดล AI เพื่อตอบสนองต่อสิ่งที่คุณป้อนเข้าไป การตอบกลับที่ยาวขึ้นจะใช้โทเค็นมากขึ้น ซึ่งน่าจะมีค่าใช้จ่ายสูงขึ้นตามไปด้วย';
$string['configureprovider'] = 'กำหนดค่าอินสแตนซ์ผู้ให้บริการ';
$string['contentwatermark'] = 'สร้างโดย AI';
$string['createnewprovider'] = 'สร้างอินสแตนซ์ผู้ให้บริการใหม่';
$string['dateaccepted'] = 'วันที่ยอมรับ';
$string['declineaipolicy'] = 'ปฏิเสธ';
$string['enableaitoolsincourse'] = 'อนุญาตเครื่องมือ AI สำหรับรายวิชานี้';
$string['enableaitoolsincourseactivity'] = 'อนุญาตเครื่องมือ AI ในกิจกรรมนี้';
$string['enableglobalratelimit'] = 'ตั้งค่าจำกัดอัตราการใช้งานทั้งไซต์';
$string['enableglobalratelimit_help'] = 'จำกัดจำนวนคำขอที่ผู้ให้บริการ AI จะได้รับทั่วทั้งไซต์ในทุกๆ ชั่วโมง';
$string['enableuserratelimit'] = 'ตั้งค่าจำกัดอัตราการใช้งานต่อผู้ใช้';
$string['enableuserratelimit_help'] = 'จำกัดจำนวนคำขอที่ผู้ใช้แต่ละคนสามารถทำได้ไปยังผู้ให้บริการ AI ในทุกๆ ชั่วโมง';
$string['error:400'] = 'คำขอผิดพลาด (Bad request)';
$string['error:401'] = 'ไม่ได้รับอนุญาต (Unauthorised)';
$string['error:401:upstreamless'] = 'ไม่สามารถเชื่อมต่อกับบริการ AI ได้ โปรดลองอีกครั้งในภายหลัง';
$string['error:404'] = 'ไม่พบข้อมูล (Not found)';
$string['error:404:upstreamless'] = 'บริการ AI ไม่สามารถใช้งานได้ชั่วคราว โปรดลองอีกครั้งในภายหลัง';
$string['error:429'] = 'มีคำขอมากเกินไป (Too many requests)';
$string['error:429:internalsitewide'] = 'บริการ AI ถึงขีดจำกัดสูงสุดของคำขอทั่วทั้งไซต์ต่อชั่วโมงแล้ว โปรดลองอีกครั้งในภายหลัง';
$string['error:429:internaluser'] = 'คุณถึงขีดจำกัดสูงสุดของคำขอ AI ที่คุณสามารถทำได้ในหนึ่งชั่วโมงแล้ว โปรดลองอีกครั้งในภายหลัง';
$string['error:429:upstreamless'] = 'บริการ AI นี้ถึงขีดจำกัดคำขอแล้ว โปรดลองอีกครั้งในภายหลัง';
$string['error:500'] = 'ข้อผิดพลาดภายในเซิร์ฟเวอร์ (Internal server error)';
$string['error:503'] = 'บริการไม่พร้อมใช้งาน (Service unavailable)';
$string['error:actionnotfound'] = 'ไม่รองรับการดำเนินการ \'{$a}\'';
$string['error:defaultmessage'] = 'เกิดข้อผิดพลาดในการประมวลผลตามคำขอของคุณ โปรดลองอีกครั้งในภายหลัง';
$string['error:defaultmessageshort'] = 'โปรดลองอีกครั้งในภายหลัง';
$string['error:defaultname'] = 'มีบางอย่างผิดปกติ';
$string['error:noproviders'] = 'ไม่มีผู้ให้บริการที่พร้อมสำหรับการดำเนินการนี้';
$string['error:providernotfound'] = 'ไม่พบอินสแตนซ์ผู้ให้บริการ AI';
$string['error:unknown'] = 'ข้อผิดพลาดที่ไม่รู้จัก';
$string['globalratelimit'] = 'จำนวนคำขอสูงสุดทั่วทั้งไซต์';
$string['globalratelimit_help'] = 'จำนวนคำขอทั่วทั้งไซต์ที่อนุญาตต่อชั่วโมง';
$string['manageaiplacements'] = 'จัดการตำแหน่งบริการ AI';
$string['manageaiproviders'] = 'จัดการผู้ให้บริการ AI';
$string['noproviderplugins'] = 'ไม่มีปลั๊กอินผู้ให้บริการติดตั้งไว้ โปรดติดตั้งปลั๊กอินผู้ให้บริการเพื่อเปิดให้สร้างอินสแตนซ์ผู้ให้บริการ';
$string['noproviders'] = 'การดำเนินการนี้ไม่พร้อมใช้งาน เนื่องจากไม่มี <a href="{$a}">ผู้ให้บริการ AI</a> ใดถูกกำหนดค่าสำหรับการดำเนินการนี้';
$string['off'] = 'ปิด';
$string['on'] = 'เปิด';
$string['placement'] = 'ตำแหน่ง (Placement)';
$string['placementactionsettings'] = 'การดำเนินการ';
$string['placementactionsettings_desc'] = 'การดำเนินการ AI ที่มีให้สำหรับตำแหน่งนี้';
$string['placementsettings'] = 'การตั้งค่าเฉพาะตำแหน่ง';
$string['placementsettings_desc'] = 'การตั้งค่าเหล่านี้ควบคุมว่าตำแหน่ง AI นี้จะเชื่อมต่อกับบริการ AI และการดำเนินการที่เกี่ยวข้องอย่างไร';
$string['privacy:metadata:ai_action_explain_text'] = 'ตารางเก็บข้อมูลคำขอการอธิบายข้อความจากผู้ใช้';
$string['privacy:metadata:ai_action_explain_text:completiontoken'] = 'โทเค็นขาออก (Completion tokens) ที่ใช้ในการอธิบายข้อความ';
$string['privacy:metadata:ai_action_explain_text:fingerprint'] = 'รหัสแฮชที่ระบุสถานะหรือเวอร์ชันของโมเดลและเนื้อหา';
$string['privacy:metadata:ai_action_explain_text:generatedcontent'] = 'ข้อความจริงที่สร้างโดยโมเดล AI ตามคำสั่ง';
$string['privacy:metadata:ai_action_explain_text:prompt'] = 'คำสั่ง (Prompt) สำหรับคำขอการอธิบายข้อความ';
$string['privacy:metadata:ai_action_explain_text:prompttokens'] = 'โทเค็นขาเข้า (Prompt tokens) ที่ใช้ในการอธิบายข้อความ';
$string['privacy:metadata:ai_action_explain_text:responseid'] = 'ไอดีของการตอบสนอง';
$string['privacy:metadata:ai_action_generate_image'] = 'ตารางเก็บข้อมูลคำขอการสร้างรูปภาพจากผู้ใช้';
$string['privacy:metadata:ai_action_generate_image:aspectratio'] = 'อัตราส่วนภาพของรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_image:numberimages'] = 'จำนวนรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_image:prompt'] = 'คำสั่ง (Prompt) สำหรับคำขอการสร้างรูปภาพ';
$string['privacy:metadata:ai_action_generate_image:quality'] = 'คุณภาพของรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_image:revisedprompt'] = 'คำสั่งที่ได้รับการปรับปรุงของรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_image:sourceurl'] = 'URL ต้นทางของรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_image:style'] = 'สไตล์ของรูปภาพที่สร้างขึ้น';
$string['privacy:metadata:ai_action_generate_text'] = 'ตารางเก็บข้อมูลคำขอการสร้างข้อความจากผู้ใช้';
$string['privacy:metadata:ai_action_generate_text:completiontoken'] = 'โทเค็นขาออก (Completion tokens) ที่ใช้ในการสร้างข้อความ';
$string['privacy:metadata:ai_action_generate_text:fingerprint'] = 'รหัสแฮชที่ระบุสถานะหรือเวอร์ชันของโมเดลและเนื้อหา';
$string['privacy:metadata:ai_action_generate_text:generatedcontent'] = 'ข้อความจริงที่สร้างโดยโมเดล AI ตามคำสั่ง';
$string['privacy:metadata:ai_action_generate_text:prompt'] = 'คำสั่ง (Prompt) สำหรับคำขอการสร้างข้อความ';
$string['privacy:metadata:ai_action_generate_text:prompttokens'] = 'โทเค็นขาเข้า (Prompt tokens) ที่ใช้ในการสร้างข้อความ';
$string['privacy:metadata:ai_action_generate_text:responseid'] = 'ไอดีของการตอบสนอง';
$string['privacy:metadata:ai_action_register'] = 'ตารางเก็บข้อมูลคำขอการดำเนินการจากผู้ใช้';
$string['privacy:metadata:ai_action_register:actionid'] = 'ไอดีของคำขอการดำเนินการ';
$string['privacy:metadata:ai_action_register:actionname'] = 'ชื่อการดำเนินการของคำขอ';
$string['privacy:metadata:ai_action_register:model'] = 'โมเดลที่ใช้ในการสร้างการตอบสนอง';
$string['privacy:metadata:ai_action_register:provider'] = 'ชื่อของผู้ให้บริการที่จัดการคำขอ';
$string['privacy:metadata:ai_action_register:success'] = 'สถานะของคำขอการดำเนินการ';
$string['privacy:metadata:ai_action_register:timecompleted'] = 'เวลาที่คำขอเสร็จสมบูรณ์';
$string['privacy:metadata:ai_action_register:timecreated'] = 'เวลาที่สร้างคำขอ';
$string['privacy:metadata:ai_action_register:userid'] = 'ไอดีของผู้ใช้ที่ส่งคำขอ';
$string['privacy:metadata:ai_action_summarise_text'] = 'ตารางเก็บข้อมูลคำขอการสรุปข้อความจากผู้ใช้';
$string['privacy:metadata:ai_action_summarise_text:completiontoken'] = 'โทเค็นขาออก (Completion tokens) ที่ใช้ในการสรุปข้อความ';
$string['privacy:metadata:ai_action_summarise_text:fingerprint'] = 'รหัสแฮชที่ระบุสถานะหรือเวอร์ชันของโมเดลและเนื้อหา';
$string['privacy:metadata:ai_action_summarise_text:generatedcontent'] = 'ข้อความจริงที่สร้างโดยโมเดล AI ตามคำสั่ง';
$string['privacy:metadata:ai_action_summarise_text:prompt'] = 'คำสั่ง (Prompt) สำหรับคำขอการสรุปข้อความ';
$string['privacy:metadata:ai_action_summarise_text:prompttokens'] = 'โทเค็นขาเข้า (Prompt tokens) ที่ใช้ในการสรุปข้อความ';
$string['privacy:metadata:ai_action_summarise_text:responseid'] = 'ไอดีของการตอบสนอง';
$string['privacy:metadata:ai_policy_register'] = 'ตารางเก็บสถานะการยอมรับนโยบาย AI สำหรับผู้ใช้แต่ละคน';
$string['privacy:metadata:ai_policy_register:contextid'] = 'ไอดีของบริบทที่มีข้อมูลบันทึกไว้';
$string['privacy:metadata:ai_policy_register:timeaccepted'] = 'เวลาที่ผู้ใช้ยอมรับนโยบาย AI';
$string['privacy:metadata:ai_policy_register:userid'] = 'ไอดีของผู้ใช้ที่มีข้อมูลบันทึกไว้';
$string['prompttokens'] = 'Prompt tokens';
$string['prompttokens_help'] = 'Prompt tokens คือหน่วยข้อความที่เป็นสิ่งที่ส่งไปยังโมเดล AI สิ่งที่ใส่เข้าไปยาวขึ้นจะใช้โทเค็นมากขึ้น และมีแนวโน้มที่จะมีค่าใช้จ่ายสูงขึ้น';
$string['provider'] = 'ผู้ให้บริการ';
$string['provideractionsettings'] = 'การดำเนินการ';
$string['provideractionsettings_desc'] = 'เลือกและกำหนดค่าการดำเนินการที่ {$a} สามารถทำได้ในไซต์ของคุณ';
$string['providerinstanceactionupdated'] = 'อัปเดตการตั้งค่าการดำเนินการ {$a} แล้ว';
$string['providerinstancecreated'] = 'สร้างอินสแตนซ์ผู้ให้บริการ AI {$a} แล้ว';
$string['providerinstancedelete'] = 'ลบอินสแตนซ์ผู้ให้บริการ AI';
$string['providerinstancedeleteconfirm'] = 'คุณกำลังจะลบอินสแตนซ์ผู้ให้บริการ AI {$a->name} ({$a->provider}) คุณแน่ใจหรือไม่?';
$string['providerinstancedeleted'] = 'ลบอินสแตนซ์ผู้ให้บริการ AI {$a} แล้ว';
$string['providerinstancedeletefailed'] = 'ไม่สามารถลบอินสแตนซ์ผู้ให้บริการ AI {$a} ได้ เนื่องจากกำลังถูกใช้งานหรือมีปัญหาเกี่ยวกับฐานข้อมูล โปรดตรวจสอบว่าผู้ให้บริการเปิดใช้งานอยู่หรือไม่ หรือติดต่อผู้ดูแลระบบฐานข้อมูลเพื่อขอความช่วยเหลือ';
$string['providerinstancedisablefailed'] = 'ไม่สามารถปิดการใช้งานอินสแตนซ์ผู้ให้บริการ AI ได้ เนื่องจากกำลังถูกใช้งานหรือมีปัญหาเกี่ยวกับฐานข้อมูล โปรดตรวจสอบว่าผู้ให้บริการเปิดใช้งานอยู่หรือไม่ หรือติดต่อผู้ดูแลระบบฐานข้อมูลเพื่อขอความช่วยเหลือ';
$string['providerinstanceupdated'] = 'อัปเดตอินสแตนซ์ผู้ให้บริการ AI {$a} แล้ว';
$string['providermoveddown'] = '{$a} ย้ายลงด้านล่าง';
$string['providermovedup'] = '{$a} ย้ายขึ้นด้านบน';
$string['providername'] = 'ชื่อสำหรับอินสแตนซ์';
$string['providers'] = 'ผู้ให้บริการ';
$string['providersettings'] = 'การตั้งค่า';
$string['providertype'] = 'เลือกปลั๊กอินผู้ให้บริการ AI';
$string['timegenerated'] = 'เวลาที่สร้างสำเร็จ';
$string['unknownvalue'] = '—';
$string['userpolicy'] = '<h4><strong>ยินดีต้อนรับสู่คุณสมบัติ AI ใหม่!</strong></h4>
<p>คุณสมบัติปัญญาประดิษฐ์ (AI) นี้ใช้ Large Language Models (LLM) ภายนอกเท่านั้นเพื่อปรับปรุงประสบการณ์การเรียนรู้และการสอนของคุณ ก่อนที่คุณจะเริ่มใช้บริการ AI เหล่านี้ โปรดอ่านนโยบายการใช้งานนี้</p>
<h4><strong>ความแม่นยำของเนื้อหาที่สร้างโดย AI</strong></h4>
<p>AI สามารถให้ข้อเสนอแนะและข้อมูลที่มีประโยชน์ แต่ความแม่นยำอาจแตกต่างกันไป คุณควรตรวจสอบข้อมูลที่ได้รับซ้ำเสมอเพื่อให้แน่ใจว่าถูกต้อง สมบูรณ์ และเหมาะสมกับสถานการณ์เฉพาะของคุณ</p>
<h4><strong>วิธีการประมวลผลข้อมูลของคุณ</strong></h4>
<p>คุณสมบัติ AI นี้ใช้ Large Language Models (LLM) ภายนอก หากคุณใช้คุณสมบัตินี้ ข้อมูลหรือข้อมูลส่วนบุคคลใดๆ ที่คุณแบ่งปันจะถูกจัดการตามนโยบายความเป็นส่วนตัวของ LLM เหล่านั้น เราขอแนะนำให้คุณอ่านนโยบายความเป็นส่วนตัวของพวกเขาเพื่อทำความเข้าใจวิธีที่พวกเขาจะจัดการข้อมูลของคุณ นอกจากนี้ บันทึกการโต้ตอบของคุณกับคุณสมบัติ AI อาจถูกบันทึกไว้ในไซต์นี้</p>
<p>หากคุณมีคำถามเกี่ยวกับวิธีการประมวลผลข้อมูลของคุณ โปรดตรวจสอบกับครูหรือองค์กรการเรียนรู้ของคุณ</p>
<p>การดำเนินการต่อ ถือว่าคุณรับทราบว่าคุณเข้าใจและตกลงตามนโยบายนี้แล้ว</p>';
$string['userratelimit'] = 'จำนวนคำขอสูงสุดต่อผู้ใช้';
$string['userratelimit_help'] = 'จำนวนคำขอที่อนุญาตต่อชั่วโมง ต่อผู้ใช้หนึ่งราย';

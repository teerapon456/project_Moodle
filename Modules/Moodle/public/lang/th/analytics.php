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
 * Strings for core_analytics.
 *
 * @package core_analytics
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['analysablenotused'] = 'องค์ประกอบวิเคราะห์ {$a->analysableid} ไม่ได้ใช้งาน: {$a->errors}';
$string['analysablenotvalidfortarget'] = 'องค์ประกอบวิเคราะห์ {$a->analysableid} ไม่ถูกต้องสำหรับเป้าหมายนี้: {$a->result}';
$string['analysisinprogress'] = 'ยังคงอยู่ระหว่างการวิเคราะห์โดยการดำเนินการก่อนหน้า';
$string['analytics'] = 'การวิเคราะห์ข้อมูล';
$string['analyticsdisabled'] = 'การวิเคราะห์ข้อมูลถูกปิดใช้งาน คุณสามารถเปิดใช้งานได้ใน "การบริหารไซต์ > คุณสมบัติขั้นสูง".';
$string['analyticslogstore'] = 'ที่จัดเก็บบันทึกที่ใช้สำหรับการวิเคราะห์ข้อมูล';
$string['analyticslogstore_help'] = 'ที่จัดเก็บบันทึกที่จะใช้โดย Analytics API เพื่ออ่านกิจกรรมของผู้ใช้.';
$string['analyticssettings'] = 'การตั้งค่าการวิเคราะห์ข้อมูล';
$string['analyticssiteinfo'] = 'ข้อมูลไซต์';
$string['calclifetime'] = 'เก็บการคำนวณการวิเคราะห์ข้อมูลไว้เป็นเวลา';
$string['configlcalclifetime'] = 'นี่ระบุระยะเวลาที่คุณต้องการเก็บข้อมูลการคำนวณ - สิ่งนี้จะไม่ลบการคาดการณ์ แต่จะลบข้อมูลที่ใช้ในการสร้างการคาดการณ์ การใช้ตัวเลือกเริ่มต้นที่นี่เป็นสิ่งที่ดีที่สุดเนื่องจากช่วยควบคุมการใช้ดิสก์ของคุณ แต่หากคุณใช้ตารางการคำนวณเพื่อวัตถุประสงค์อื่น คุณอาจต้องการเพิ่มค่านี้.';
$string['defaulttimesplittingmethods'] = 'ช่วงเวลาวิเคราะห์เริ่มต้นสำหรับการประเมินแบบจำลอง';
$string['defaulttimesplittingmethods_help'] = 'ช่วงเวลาวิเคราะห์กำหนดเมื่อระบบจะคำนวณการคาดการณ์และส่วนของบันทึกกิจกรรมที่จะพิจารณาสำหรับการคาดการณ์เหล่านั้น กระบวนการประเมินแบบจำลองจะวนซ้ำผ่านช่วงเวลาวิเคราะห์เหล่านี้เว้นแต่จะระบุช่วงเวลาวิเคราะห์เฉพาะ.';
$string['defaultpredictionsprocessor'] = 'ตัวประมวลผลการคาดการณ์เริ่มต้น';
$string['defaultpredictoroption'] = 'ตัวประมวลผลเริ่มต้น ({$a})';
$string['disabledmodel'] = 'แบบจำลองที่ปิดใช้งาน';
$string['erroralreadypredict'] = 'ไฟล์ {$a} ได้ถูกใช้เพื่อสร้างการคาดการณ์แล้ว.';
$string['errorcannotreaddataset'] = 'ไม่สามารถอ่านไฟล์ชุดข้อมูล {$a} ได้.';
$string['errorcannotusetimesplitting'] = 'ช่วงเวลาวิเคราะห์ที่ให้มาจะใช้กับแบบจำลองนี้ไม่ได้.';
$string['errorcannotwritedataset'] = 'ไม่สามารถเขียนไฟล์ชุดข้อมูล {$a} ได้.';
$string['errorexportmodelresult'] = 'ไม่สามารถส่งออกผลลัพธ์แบบจำลองการเรียนรู้เครื่องได้.';
$string['errorimport'] = 'เกิดข้อผิดพลาดในการนำเข้าไฟล์ JSON ที่ให้มา.';
$string['errorimportmissingcomponents'] = 'แบบจำลองที่ให้มาจำเป็นต้องติดตั้งปลั๊กอินต่อไปนี้: {$a} โปรดทราบว่าเวอร์ชันไม่จำเป็นต้องตรงกับเวอร์ชันที่ติดตั้งในไซต์ของคุณ การติดตั้งปลั๊กอินเวอร์ชันเดียวกันหรือใหม่กว่านี้ควรใช้ได้ในกรณีส่วนใหญ่.';
$string['errorimportversionmismatches'] = 'เวอร์ชันของส่วนประกอบต่อไปนี้แตกต่างจากเวอร์ชันที่ติดตั้งในไซต์นี้: {$a} คุณสามารถใช้ตัวเลือก \'ละเว้นความไม่ตรงกันของเวอร์ชัน\' เพื่อละเว้นความแตกต่างเหล่านี้.';
$string['errorimportmissingclasses'] = 'ส่วนประกอบการวิเคราะห์ข้อมูลต่อไปนี้ไม่พร้อมใช้งานในไซต์นี้: {$a->missingclasses}.';
$string['errorinvalidindicator'] = 'ตัวชี้วัด {$a} ไม่ถูกต้อง';
$string['errorinvalidcontexts'] = 'บริบทที่เลือกบางรายการไม่สามารถใช้ได้ในเป้าหมายนี้.';
$string['errorinvalidtarget'] = 'เป้าหมาย {$a} ไม่ถูกต้อง';
$string['errorinvalidtimesplitting'] = 'ช่วงเวลาวิเคราะห์ไม่ถูกต้อง โปรดตรวจสอบว่าคุณได้เพิ่มชื่อคลาสที่มีคุณสมบัติครบถ้วน.';
$string['errornocontextrestrictions'] = 'เป้าหมายที่เลือกไม่สนับสนุนการจำกัดบริบท';
$string['errornoexportconfig'] = 'เกิดปัญหาในการส่งออกการกำหนดค่าแบบจำลอง.';
$string['errornoexportconfigrequirements'] = 'เฉพาะแบบจำลองที่ไม่คงที่และมีช่วงเวลาวิเคราะห์เท่านั้นที่สามารถส่งออกได้.';
$string['errornoindicators'] = 'แบบจำลองนี้ไม่มีตัวชี้วัดใดๆ.';
$string['errornopredictresults'] = 'ไม่มีผลลัพธ์ที่ส่งคืนจากตัวประมวลผลการคาดการณ์ ตรวจสอบเนื้อหาไดเร็กทอรีผลลัพธ์สำหรับข้อมูลเพิ่มเติม.';
$string['errornotimesplittings'] = 'แบบจำลองนี้ไม่มีช่วงเวลาวิเคราะห์.';
$string['errornoroles'] = 'บทบาทนักเรียนหรือครูยังไม่ได้กำหนด กำหนดในหน้าการตั้งค่าการวิเคราะห์ข้อมูล.';
$string['errorpredictioncontextnotavailable'] = 'บริบทการคาดการณ์นี้ไม่พร้อมใช้งานอีกต่อไป.';
$string['errorpredictionformat'] = 'รูปแบบการคำนวณการคาดการณ์ผิด';
$string['errorpredictionnotfound'] = 'ไม่พบการคาดการณ์';
$string['errorpredictionsprocessor'] = 'ข้อผิดพลาดของตัวประมวลผลการคาดการณ์: {$a}';
$string['errorpredictwrongformat'] = 'ไม่สามารถถอดรหัสผลลัพธ์ของตัวประมวลผลการคาดการณ์: "{$a}"';
$string['errorprocessornotready'] = 'ตัวประมวลผลการคาดการณ์ที่เลือกไม่พร้อม: {$a}';
$string['errorsamplenotavailable'] = 'ตัวอย่างที่คาดการณ์แล้วไม่พร้อมใช้งานอีกต่อไป.';
$string['errorunexistingtimesplitting'] = 'ช่วงเวลาวิเคราะห์ที่เลือกไม่พร้อมใช้งาน.';
$string['errorunexistingmodel'] = 'แบบจำลองที่ไม่มีอยู่ {$a}';
$string['errorunknownaction'] = 'การดำเนินการที่ไม่รู้จัก';
$string['eventpredictionactionstarted'] = 'กระบวนการคาดการณ์เริ่มต้น';
$string['eventinsightsviewed'] = 'มุมมองเชิงลึกที่มองเห็น';
$string['fixedack'] = 'ยอมรับ';
$string['incorrectlyflagged'] = 'ติดแท็กผิด';
$string['insightmessagesubject'] = 'มุมมองเชิงลึกใหม่สำหรับ "{$a}"';
$string['insightinfomessagehtml'] = 'ระบบสร้างมุมมองเชิงลึกสำหรับคุณ.';
$string['insightinfomessageplain'] = 'ระบบสร้างมุมมองเชิงลึกสำหรับคุณ: {$a}';
$string['insightinfomessageaction'] = '{$a->text}: {$a->url}';
$string['invalidtimesplitting'] = 'แบบจำลองที่มี ID {$a} ต้องมีช่วงเวลาวิเคราะห์ก่อนที่จะใช้ในการฝึก.';
$string['invalidanalysablefortimesplitting'] = 'ไม่สามารถวิเคราะห์ได้โดยใช้ช่วงเวลาวิเคราะห์ {$a}.';
$string['levelinstitution'] = 'ระดับการศึกษา';
$string['levelinstitutionisced0'] = 'การศึกษายุวชนชั้นต้น (\'ต่ำกว่าประถมศึกษา\' สำหรับการบรรลุการศึกษา)';
$string['levelinstitutionisced1'] = 'การศึกษาประถม';
$string['levelinstitutionisced2'] = 'การศึกษามัธยมต้น';
$string['levelinstitutionisced3'] = 'การศึกษามัธยมปลาย';
$string['levelinstitutionisced4'] = 'การศึกษาหลังมัธยมปลายที่ไม่ใช่ระดับอุดมศึกษา (อาจรวมถึงการฝึกอบรมองค์กรหรือชุมชน/NGO)';
$string['levelinstitutionisced5'] = 'การศึกษาอุดมศึกษาระยะสั้น (อาจรวมถึงการฝึกอบรมองค์กรหรือชุมชน/NGO)';
$string['levelinstitutionisced6'] = 'ระดับปริญญาตรีหรือเทียบเท่า';
$string['levelinstitutionisced7'] = 'ระดับปริญญาโทหรือเทียบเท่า';
$string['levelinstitutionisced8'] = 'ระดับปริญญาเอกหรือเทียบเท่า';
$string['nocourses'] = 'ไม่มีรายวิชาที่จะวิเคราะห์';
$string['modeinstruction'] = 'รูปแบบการสอน';
$string['modeinstructionfacetoface'] = 'แบบพบหน้า';
$string['modeinstructionblendedhybrid'] = 'แบบผสมผสานหรือไฮบริด';
$string['modeinstructionfullyonline'] = 'แบบออนไลน์เต็มรูปแบบ';
$string['modeloutputdir'] = 'ไดเร็กทอรีผลลัพธ์ของแบบจำลอง';
$string['modeloutputdirwithdefaultinfo'] = 'ไดเร็กทอรีที่ตัวประมวลผลการคาดการณ์เก็บข้อมูลการประเมินทั้งหมด มีประโยชน์สำหรับการดีบักและการวิจัย หากว่างเปล่า จะใช้ {$a} เป็นค่าเริ่มต้น.';
$string['modeltimelimit'] = 'ขีดจำกัดเวลาวิเคราะห์ต่อแบบจำลอง';
$string['modeltimelimitinfo'] = 'การตั้งค่านี้จำกัดเวลาที่แต่ละแบบจำลองใช้ในการวิเคราะห์เนื้อหาไซต์.';
$string['neutral'] = 'เป็นกลาง';
$string['neverdelete'] = 'ไม่ลบการคำนวณ';
$string['noevaluationbasedassumptions'] = 'แบบจำลองที่อิงสมมติฐานไม่สามารถประเมินได้.';
$string['nodata'] = 'ไม่มีข้อมูลที่จะวิเคราะห์';
$string['noinsightsmodel'] = 'แบบจำลองนี้ไม่สร้างมุมมองเชิงลึก';
$string['noinsights'] = 'ไม่มีมุมมองเชิงลึกที่รายงาน';
$string['nonewdata'] = 'ไม่มีข้อมูลใหม่ให้วิเคราะห์ แบบจำลองจะถูกวิเคราะห์หลังจากช่วงเวลาวิเคราะห์ครั้งต่อไป.';
$string['nonewranges'] = 'ไม่มีการคาดการณ์ใหม่ แบบจำลองจะถูกวิเคราะห์หลังจากช่วงเวลาวิเคราะห์ครั้งต่อไป.';
$string['nopredictionsyet'] = 'ไม่มีการคาดการณ์ให้ใช้งาน';
$string['noranges'] = 'ไม่มีการคาดการณ์';
$string['notapplicable'] = 'ไม่สามารถใช้งานได้';
$string['notrainingbasedassumptions'] = 'แบบจำลองที่อิงสมมติฐานไม่ต้องการการฝึกอบรม';
$string['notuseful'] = 'ไม่มีประโยชน์';
$string['novaliddata'] = 'ไม่มีข้อมูลที่ถูกต้องให้วิเคราะห์';
$string['novalidsamples'] = 'ไม่มีตัวอย่างที่ถูกต้องให้วิเคราะห์';
$string['onlycli'] = 'การประมวลผลข้อมูลวิเคราะห์ผ่านบรรทัดคำสั่งเท่านั้น';
$string['onlycliinfo'] = 'กระบวนการประมวลผลข้อมูลวิเคราะห์ เช่น การประเมินแบบจำลอง การฝึกอบรมอัลกอริทึมการเรียนรู้ของเครื่อง หรือการคาดการณ์อาจใช้เวลานานในการประมวลผล กระบวนการเหล่านี้จะทำงานเป็นงาน cron หรืออาจบังคับให้ทำงานผ่านบรรทัดคำสั่ง หากปิดใช้งาน กระบวนการประมวลผลข้อมูลวิเคราะห์สามารถทำงานผ่านอินเทอร์เฟซเว็บได้.';
$string['percentonline'] = 'เปอร์เซ็นต์ออนไลน์';
$string['percentonline_help'] = 'หากองค์กรของคุณมีรายวิชาผสมผสานหรือไฮบริด โปรดระบุเปอร์เซ็นต์ของงานนักเรียนที่ทำผ่านมูดูลในมูดูล.';
$string['predictionsprocessor'] = 'ตัวประมวลผลการคาดการณ์';
$string['predictionsprocessor_help'] = 'ตัวประมวลผลการคาดการณ์เป็นแบ็กเอนด์เครื่องเรียนรู้ที่ประมวลผลชุดข้อมูลที่สร้างโดยการคำนวณตัวชี้วัดและเป้าหมายของแบบจำลอง แต่ละแบบจำลองสามารถใช้ตัวประมวลผลที่แตกต่างกันได้ ตัวประมวลผลที่ระบุไว้ที่นี่จะเป็นตัวประมวลผลเริ่มต้น.';
$string['privacy:metadata:analytics:indicatorcalc'] = 'การคำนวณตัวชี้วัด';
$string['privacy:metadata:analytics:indicatorcalc:starttime'] = 'เวลาที่เริ่มคำนวณ';
$string['privacy:metadata:analytics:indicatorcalc:endtime'] = 'เวลาที่สิ้นสุดการคำนวณ';
$string['privacy:metadata:analytics:indicatorcalc:contextid'] = 'บริบท';
$string['privacy:metadata:analytics:indicatorcalc:sampleorigin'] = 'ต้นกำเนิดของตัวอย่าง';
$string['privacy:metadata:analytics:indicatorcalc:sampleid'] = 'ID ของตัวอย่าง';
$string['privacy:metadata:analytics:indicatorcalc:indicator'] = 'คลาสตัวชี้วัด';
$string['privacy:metadata:analytics:indicatorcalc:value'] = 'ค่าที่คำนวณได้';
$string['privacy:metadata:analytics:indicatorcalc:timecreated'] = 'เวลาที่สร้างการคาดการณ์';
$string['privacy:metadata:analytics:predictions'] = 'การคาดการณ์';
$string['privacy:metadata:analytics:predictions:modelid'] = 'ID ของแบบจำลอง';
$string['privacy:metadata:analytics:predictions:contextid'] = 'บริบท';
$string['privacy:metadata:analytics:predictions:sampleid'] = 'ID ของตัวอย่าง';
$string['privacy:metadata:analytics:predictions:rangeindex'] = 'ดัชนีช่วงเวลาวิเคราะห์';
$string['privacy:metadata:analytics:predictions:prediction'] = 'การคาดการณ์';
$string['privacy:metadata:analytics:predictions:predictionscore'] = 'คะแนนการคาดการณ์';
$string['privacy:metadata:analytics:predictions:calculations'] = 'การคำนวณตัวชี้วัด';
$string['privacy:metadata:analytics:predictions:timecreated'] = 'เวลาที่สร้างการคาดการณ์';
$string['privacy:metadata:analytics:predictions:timestart'] = 'เวลาที่เริ่มคำนวณ';
$string['privacy:metadata:analytics:predictions:timeend'] = 'เวลาที่สิ้นสุดการคำนวณ';
$string['privacy:metadata:analytics:predictionactions'] = 'การกระทำการคาดการณ์';
$string['privacy:metadata:analytics:predictionactions:predictionid'] = 'ID ของการคาดการณ์';
$string['privacy:metadata:analytics:predictionactions:userid'] = 'ID ของผู้ใช้ที่ทำการกระทำ';
$string['privacy:metadata:analytics:predictionactions:actionname'] = 'ชื่อการกระทำ';
$string['privacy:metadata:analytics:predictionactions:timecreated'] = 'เวลาที่ทำการกระทำ';
$string['privacy:metadata:analytics:analyticsmodels'] = 'แบบจำลองการวิเคราะห์ข้อมูล';
$string['privacy:metadata:analytics:analyticsmodels:usermodified'] = 'ผู้ใช้ที่แก้ไขแบบจำลอง';
$string['privacy:metadata:analytics:analyticsmodelslog'] = 'บันทึกแบบจำลองการวิเคราะห์ข้อมูล';
$string['privacy:metadata:analytics:analyticsmodelslog:usermodified'] = 'ผู้ใช้ที่แก้ไขบันทึกแบบจำลอง';
$string['processingsitecontents'] = 'ประมวลผลเนื้อหาเว็บไซต์';
$string['successfullyanalysed'] = 'ประมวลผลสำเร็จ';
$string['timesplittingmethod'] = 'ช่วงเวลาวิเคราะห์';
$string['timesplittingmethod_help'] = 'ช่วงเวลาวิเคราะห์กำหนดเมื่อระบบจะคำนวณการคาดการณ์และส่วนของบันทึกกิจกรรมที่จะพิจารณาสำหรับการคาดการณ์เหล่านั้น ตัวอย่างเช่น ระยะเวลาของรายวิชาอาจแบ่งออกเป็นส่วนๆ โดยมีการคาดการณ์หนึ่งรายการสร้างขึ้นที่สิ้นสุดของแต่ละส่วน.';
$string['typeinstitution'] = 'ประเภทสถาบัน';
$string['typeinstitutionacademic'] = 'สถาบันการศึกษา';
$string['typeinstitutiontraining'] = 'สถาบันฝึกอบรมองค์กร';
$string['typeinstitutionngo'] = 'องค์กรพัฒนาเอกชน (NGO)';
$string['useful'] = 'มีประโยชน์';
$string['viewdetails'] = 'ดูรายละเอียด';
$string['viewinsight'] = 'ดูมุมมองเชิงลึก';
$string['viewinsightdetails'] = 'ดูรายละเอียดมุมมองเชิงลึก';
$string['viewprediction'] = 'ดูรายละเอียดการคาดการณ์';
$string['washelpful'] = 'มีประโยชน์หรือไม่';
$string['nonewranges'] = 'ไม่มีการคาดการณ์ใหม่ แบบจำลองจะถูกวิเคราะห์หลังจากช่วงเวลาวิเคราะห์ครั้งต่อไป.';
$string['nopredictionsyet'] = 'ไม่มีการคาดการณ์ให้ใช้งาน';
$string['noranges'] = 'ไม่มีการคาดการณ์';
$string['notapplicable'] = 'ไม่สามารถใช้งานได้';
$string['notrainingbasedassumptions'] = 'แบบจำลองที่อิงสมมติฐานไม่ต้องการการฝึกอบรม';
$string['notuseful'] = 'ไม่มีประโยชน์';
$string['novaliddata'] = 'ไม่มีข้อมูลที่ถูกต้องให้วิเคราะห์';
$string['novalidsamples'] = 'ไม่มีตัวอย่างที่ถูกต้องให้วิเคราะห์';
$string['onlycli'] = 'การประมวลผลข้อมูลวิเคราะห์ผ่านบรรทัดคำสั่งเท่านั้น';
$string['onlycliinfo'] = 'กระบวนการประมวลผลข้อมูลวิเคราะห์ เช่น การประเมินแบบจำลอง การฝึกอบรมอัลกอริทึมการเรียนรู้ของเครื่อง หรือการคาดการณ์อาจใช้เวลานานในการประมวลผล กระบวนการเหล่านี้จะทำงานเป็นงาน cron หรืออาจบังคับให้ทำงานผ่านบรรทัดคำสั่ง หากปิดใช้งาน กระบวนการประมวลผลข้อมูลวิเคราะห์สามารถทำงานผ่านอินเทอร์เฟซเว็บได้.';
$string['percentonline'] = 'เปอร์เซ็นต์ออนไลน์';
$string['percentonline_help'] = 'หากองค์กรของคุณมีรายวิชาผสมผสานหรือไฮบริด โปรดระบุเปอร์เซ็นต์ของงานนักเรียนที่ทำผ่านมูดูลในมูดูล ป้อนตัวเลขระหว่าง 0 ถึง 100.';
$string['predictionsprocessor'] = 'ตัวประมวลผลการคาดการณ์';
$string['predictionsprocessor_help'] = 'ตัวประมวลผลการคาดการณ์เป็นแบ็กเอนด์เครื่องเรียนรู้ที่ประมวลผลชุดข้อมูลที่สร้างโดยการคำนวณตัวชี้วัดและเป้าหมายของแบบจำลอง แต่ละแบบจำลองสามารถใช้ตัวประมวลผลที่แตกต่างกันได้ ตัวประมวลผลที่ระบุไว้ที่นี่จะเป็นตัวประมวลผลเริ่มต้น.';
$string['privacy:metadata:analytics:indicatorcalc'] = 'การคำนวณตัวชี้วัด';
$string['privacy:metadata:analytics:indicatorcalc:starttime'] = 'เวลาที่เริ่มคำนวณ';
$string['privacy:metadata:analytics:indicatorcalc:endtime'] = 'เวลาที่สิ้นสุดการคำนวณ';
$string['privacy:metadata:analytics:indicatorcalc:contextid'] = 'บริบท';
$string['privacy:metadata:analytics:indicatorcalc:sampleorigin'] = 'ต้นกำเนิดของตัวอย่าง';
$string['privacy:metadata:analytics:indicatorcalc:sampleid'] = 'ID ของตัวอย่าง';
$string['privacy:metadata:analytics:indicatorcalc:indicator'] = 'คลาสตัวชี้วัด';
$string['privacy:metadata:analytics:indicatorcalc:value'] = 'ค่าที่คำนวณได้';
$string['privacy:metadata:analytics:indicatorcalc:timecreated'] = 'เวลาที่สร้างการคาดการณ์';
$string['privacy:metadata:analytics:predictions'] = 'การคาดการณ์';
$string['privacy:metadata:analytics:predictions:modelid'] = 'ID ของแบบจำลอง';
$string['privacy:metadata:analytics:predictions:contextid'] = 'บริบท';
$string['privacy:metadata:analytics:predictions:sampleid'] = 'ID ของตัวอย่าง';
$string['privacy:metadata:analytics:predictions:rangeindex'] = 'ดัชนีช่วงเวลาวิเคราะห์';
$string['privacy:metadata:analytics:predictions:prediction'] = 'การคาดการณ์';
$string['privacy:metadata:analytics:predictions:predictionscore'] = 'คะแนนการคาดการณ์';
$string['privacy:metadata:analytics:predictions:calculations'] = 'การคำนวณตัวชี้วัด';
$string['privacy:metadata:analytics:predictions:timecreated'] = 'เวลาที่สร้างการคาดการณ์';
$string['privacy:metadata:analytics:predictions:timestart'] = 'เวลาที่เริ่มคำนวณ';
$string['privacy:metadata:analytics:predictions:timeend'] = 'เวลาที่สิ้นสุดการคำนวณ';
$string['privacy:metadata:analytics:predictionactions'] = 'การกระทำการคาดการณ์';
$string['privacy:metadata:analytics:predictionactions:predictionid'] = 'ID ของการคาดการณ์';
$string['privacy:metadata:analytics:predictionactions:userid'] = 'ID ของผู้ใช้ที่ทำการกระทำ';
$string['privacy:metadata:analytics:predictionactions:actionname'] = 'ชื่อการกระทำ';
$string['privacy:metadata:analytics:predictionactions:timecreated'] = 'เวลาที่ทำการกระทำ';
$string['privacy:metadata:analytics:analyticsmodels'] = 'แบบจำลองการวิเคราะห์ข้อมูล';
$string['privacy:metadata:analytics:analyticsmodels:usermodified'] = 'ผู้ใช้ที่แก้ไขแบบจำลอง';
$string['privacy:metadata:analytics:analyticsmodelslog'] = 'บันทึกแบบจำลองการวิเคราะห์ข้อมูล';
$string['privacy:metadata:analytics:analyticsmodelslog:usermodified'] = 'ผู้ใช้ที่แก้ไขบันทึกแบบจำลอง';
$string['processingsitecontents'] = 'ประมวลผลเนื้อหาเว็บไซต์';
$string['successfullyanalysed'] = 'ประมวลผลสำเร็จ';
$string['timesplittingmethod'] = 'ช่วงเวลาวิเคราะห์';
$string['timesplittingmethod_help'] = 'ช่วงเวลาวิเคราะห์กำหนดเมื่อระบบจะคำนวณการคาดการณ์และส่วนของบันทึกกิจกรรมที่จะพิจารณาสำหรับการคาดการณ์เหล่านั้น ตัวอย่างเช่น ระยะเวลาของรายวิชาอาจแบ่งออกเป็นส่วนๆ โดยมีการคาดการณ์หนึ่งรายการสร้างขึ้นที่สิ้นสุดของแต่ละส่วน.';
$string['typeinstitution'] = 'ประเภทสถาบัน';
$string['typeinstitutionacademic'] = 'สถาบันการศึกษา';
$string['typeinstitutiontraining'] = 'สถาบันฝึกอบรมองค์กร';
$string['typeinstitutionngo'] = 'องค์กรพัฒนาเอกชน (NGO)';
$string['useful'] = 'มีประโยชน์';
$string['viewdetails'] = 'ดูรายละเอียด';
$string['viewinsight'] = 'ดูมุมมองเชิงลึก';
$string['viewinsightdetails'] = 'ดูรายละเอียดมุมมองเชิงลึก';
$string['viewprediction'] = 'ดูรายละเอียดการคาดการณ์';
$string['washelpful'] = 'มีประโยชน์หรือไม่';

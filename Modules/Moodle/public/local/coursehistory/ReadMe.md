# Walkthrough: ปลั๊กอิน "เพิ่มหลักสูตรที่เคยเรียนมาแล้ว"

## สิ่งที่สร้าง

สร้าง 2 plugins ทั้งหมด **16 ไฟล์**:

### Plugin 1: [local_coursehistory](file:///d:/programming/e-learning/moodle/public/local/coursehistory/lib.php#190-230) (11 ไฟล์)
Core logic: ฐานข้อมูล, form, file upload, course matching, admin review

| ไฟล์                                                                                                                              | หน้าที่                                               |
| -------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| [version.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/version.php)                                   | Plugin version                                     |
| [db/install.xml](file:///d:/programming/e-learning/moodle/public/local/coursehistory/db/install.xml)                             | สร้างตาราง `mdl_local_coursehistory`                |
| [db/access.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/db/access.php)                               | Capabilities: submit, review, viewall              |
| [classes/form/submit_form.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/classes/form/submit_form.php) | Moodle form (ชื่อหลักสูตร, วิทยากร, องค์กร, ไฟล์)         |
| [lib.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/lib.php)                                           | Helper functions + file serving + navigation hooks |
| [submit.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/submit.php)                                     | หน้าส่งข้อมูลหลักสูตร                                    |
| [review.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/review.php)                                     | หน้า admin ตรวจสอบ/อนุมัติ/ปฏิเสธ                       |
| [view.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/view.php)                                         | หน้าดูรายละเอียด + preview ใบรับรอง                    |
| [profile.php](file:///d:/programming/e-learning/moodle/public/local/coursehistory/profile.php)                                   | หน้า profile แสดงสถิติ + รายการทั้งหมด                  |
| [lang/en/](file:///d:/programming/e-learning/moodle/public/local/coursehistory/lang/en/local_coursehistory.php)                  | English strings                                    |
| [lang/th/](file:///d:/programming/e-learning/moodle/public/local/coursehistory/lang/th/local_coursehistory.php)                  | Thai strings                                       |

### Plugin 2: [block_coursehistory](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/block_coursehistory.php#18-145) (5 ไฟล์)
Dashboard block: ปุ่มเพิ่มหลักสูตร + สรุป + รายการล่าสุด

| ไฟล์                                                                                                                     | หน้าที่               |
| ----------------------------------------------------------------------------------------------------------------------- | ------------------ |
| [block_coursehistory.php](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/block_coursehistory.php) | Block class        |
| [version.php](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/version.php)                         | Block version      |
| [db/access.php](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/db/access.php)                     | Block capabilities |
| [lang/en/](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/lang/en/block_coursehistory.php)        | English strings    |
| [lang/th/](file:///d:/programming/e-learning/moodle/public/blocks/coursehistory/lang/th/block_coursehistory.php)        | Thai strings       |

---

## ฐานข้อมูล

สร้างตารางใหม่ 1 ตาราง: **`mdl_local_coursehistory`**

```sql
-- Fields:
id, userid, coursename, instructorname, organization,
certificatefile, matchedcourseid, status, reviewedby,
reviewcomment, timecreated, timemodified

-- Indexes: userid, status, (userid+status)
```

---

## Flow การใช้งาน

### ผู้เรียน
1. **Dashboard** → เห็น block "เพิ่มหลักสูตรที่เคยเรียนมาแล้ว"
2. กด **"เพิ่มหลักสูตรที่เคยเรียน"** → เปิด form
3. กรอกข้อมูล: ชื่อหลักสูตร, วิทยากร, สถาบัน/องค์กร
4. อัปโหลดใบรับรอง (PDF/JPG/PNG, max 5MB)
5. ระบบ **ตรวจสอบไฟล์** + **match ชื่อหลักสูตร** กับ `mdl_course`
6. ดูสถานะที่ **profile.php** หรือบน **block**

### ผู้ดูแลระบบ / ผู้สอน
1. เข้า **review.php** → ดูรายการที่ผู้เรียนส่งเข้ามา
2. Filter ตามสถานะ: ทั้งหมด / รอตรวจสอบ / อนุมัติ / ไม่อนุมัติ
3. กด **ดูรายละเอียด** → เห็นข้อมูล + preview ใบรับรอง
4. กด **อนุมัติ** หรือ **ปฏิเสธ** พร้อมเพิ่มความเห็น
5. ดูประวัติหลักสูตรของผู้เรียนแต่ละคนที่ **profile.php?userid=XX**

---

## การติดตั้ง

# ขั้นตอนที่ 1: Copy local plugin (ชื่อโฟลเดอร์ต้นทางมี prefix แต่ปลายทางไม่มี)
docker cp "d:\programming\e-learning\moodle\public\local\local_coursehistory" moodle-e-leanrning:/var/www/html/public/local/coursehistory
# ขั้นตอนที่ 2: Copy block plugin (block ต้องไปอยู่ใน blocks/ ไม่ใช่ local/)
docker cp "d:\programming\e-learning\moodle\public\local\block_coursehistory" moodle-e-leanrning:/var/www/html/public/blocks/coursehistory
# ขั้นตอนที่ 3: แก้ permissions
docker exec moodle-e-leanrning chown -R www-data:www-data /var/www/html/public/local/coursehistory
docker exec moodle-e-leanrning chown -R www-data:www-data /var/www/html/public/blocks/coursehistory
# ขั้นตอนที่ 4: Purge cache
docker exec moodle-e-leanrning php /var/www/html/public/admin/cli/purge_caches.php
# ขั้นตอนที่ 5: เปิดเบราว์เซอร์
http://localhost:8080/admin/index.php
→ Login admin → จะเห็น "Plugins requiring attention"
→ กด "Upgrade Moodle database now"
→ ตาราง mdl_local_coursehistory จะถูกสร้างอัตโนมัติ
# ขั้นตอนที่ 6: เพิ่ม Block บน Dashboard
→ Dashboard → Customise this page → Add a block
→ เลือก "Course History"
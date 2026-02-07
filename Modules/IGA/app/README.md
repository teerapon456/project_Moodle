# INTEQC GLOBAL ASSESSMENT

เอกสารสรุปการติดตั้งและใช้งานอย่างย่อ (Thai/English)

- เอกสารคู่มือฉบับเต็ม (พิมพ์สวยงาม): `docs/manual.html`
- โฟลเดอร์โปรเจกต์: `c:/xampp/htdocs/INTEQC_GLOBAL_ASSESMENT`

## การติดตั้ง (Setup)
- วางโฟลเดอร์ `INTEQC_GLOBAL_ASSESMENT/` ไว้ใน `c:/xampp/htdocs/`
- สร้างฐานข้อมูล MySQL (ชื่อฐานข้อมูลต้องตรงกับ `includes/db_connect.php` ตัวแปร `$db`)
- สร้างตารางอย่างน้อยตามที่อ้างอิงในโค้ด: `users`, `roles`, `remember_me_tokens`, `email_verification_tokens`, `tests`, `sections`, `questions`, `question_options`
- ตรวจสอบ Composer: หาก `vendor/` ไม่ครบ ให้ติดตั้งด้วย `composer install`
- เข้าถึงระบบ: http://localhost/INTEQC_GLOBAL_ASSESMENT/public/

## การตั้งค่า (Configs)
- ฐานข้อมูล: `includes/db_connect.php` (แก้ `$host`, `$db`, `$user`, `$pass`, `$charset`)
- อีเมล SMTP: `includes/config.php` (HOST/PORT/USERNAME/PASSWORD/FROM)
  - คำแนะนำ: ย้ายข้อมูลลับไปไว้ `.env` + ใช้ `vlucas/phpdotenv` แทนการฮาร์ดโค้ด
- Log: เก็บที่โฟลเดอร์ `logs/` และกำหนดผ่านค่าคงที่ `LOG_FILE`

## เส้นทางหลัก (Routes)
- เริ่มต้น: `public/index.php` → เปลี่ยนเส้นทางไป `login.php` หรือ `dashboard.php`
- เข้าสู่ระบบ: `/INTEQC_GLOBAL_ASSESMENT/login` (`public/login.php`)
- ลงทะเบียน: `/INTEQC_GLOBAL_ASSESMENT/register` (`public/register.php`)
- ยืนยันอีเมล: `public/verify_email.php?token=...`
- ผู้ดูแลระบบ: `/INTEQC_GLOBAL_ASSESMENT/admin` (หน้าใน `views/admin/`)
- ผู้ใช้: `/INTEQC_GLOBAL_ASSESMENT/user/guide` (หน้าใน `views/user/`)

## ฟีเจอร์หลัก
- ลงทะเบียน + ยืนยันอีเมล, เข้าสู่ระบบ (CSRF + Remember Me)
- จัดการแบบทดสอบ/หมวด/คำถาม (เฉพาะ Admin) ผ่าน `process/*.php`
- รองรับหลายภาษา: `languages/` (`th.php`, `en.php`, `my.php`)

## ความปลอดภัย (Security)
- ใช้ `password_hash`/`password_verify`
- CSRF Token: `generate_csrf_token()`/`verify_csrf_token()` ใน `includes/functions.php`
- แนะนำให้บังคับ HTTPS ทุกหน้า (บางไฟล์มีเช็คแล้ว)

## ปัญหาทั่วไป
- DB เชื่อมต่อไม่ได้: ตรวจ `includes/db_connect.php`, สิทธิ์ MySQL และไฟล์ Log ใน `logs/`
- อีเมลไม่ส่ง: ตรวจค่าที่ `includes/config.php` และสิทธิ์ SMTP/โปรไฟล์ส่งเมล

---
For detailed manual with print-friendly layout and two-column TOC, open `docs/manual.html` in a browser.

# Locale support (local_localesupport)

ปลั๊กอิน Moodle สำหรับช่วยให้ระบบใช้ locale ของภาษาอังกฤษ (en) และไทย (th) ในการจัดรูปแบบวันที่และตัวเลข ลดหรือกำจัดข้อความแจ้งเตือน *"Our server does not fully support the following languages: English (en), Thai (th)..."*

## การติดตั้งผ่าน Moodle GUI (แนะนำ)

1. สร้างไฟล์ zip ของปลั๊กอิน:
   - บนเครื่องที่มีโฟลเดอร์ `moodle` (ที่รวมโค้ด Moodle แล้ว): ไปที่โฟลเดอร์ `moodle` แล้วรัน:
     ```bash
     zip -r local_localesupport.zip local/localesupport
     ```
   - หรือบีบอัดโฟลเดอร์ `local/localesupport` ให้อยู่ภายใต้ path `local/localesupport/` (เมื่อแตก zip แล้วต้องได้โฟลเดอร์ `local` ข้างในมี `localesupport`)

2. ใน Moodle: **Site administration → Plugins → Install plugins**
3. อัปโหลดไฟล์ `local_localesupport.zip`
4. ตรวจสอบและดำเนินการติดตั้งตามที่ระบบแจ้ง

## การติดตั้งแบบคัดลอกไฟล์

1. คัดลอกโฟลเดอร์ `localesupport` ไปไว้ที่ `moodle/local/localesupport`
2. เข้า **Site administration → Notifications** เพื่อให้ Moodle รู้จักปลั๊กอินและอัปเดตฐานข้อมูล

## การตั้งค่า

หลังติดตั้งแล้ว:

1. ไปที่ **Site administration → Plugins → Local plugins → Locale support (English / Thai)**
2. เลือก **Default locale for formatting**:
   - **Use server default** – ไม่เปลี่ยน locale (ใช้ตามเซิร์ฟเวอร์)
   - **English (en_US.UTF-8)** – ใช้สำหรับจัดรูปแบบภาษาอังกฤษ
   - **Thai (th_TH.UTF-8)** – ใช้สำหรับจัดรูปแบบไทย
3. บันทึกการตั้งค่า

## ข้อควรทราบ

- ปลั๊กอินจะ**ตั้งค่า locale ของ PHP** ให้ตรงกับที่เลือกเท่านั้น จะ**ไม่**ติดตั้ง locale ให้บนเซิร์ฟเวอร์
- ถ้าเซิร์ฟเวอร์ยังไม่มี locale นั้น (เช่น `en_US.UTF-8` หรือ `th_TH.UTF-8`) การตั้งค่าจะไม่มีผล และข้อความแจ้งเตือนของ Moodle อาจยังแสดงอยู่
- สำหรับเซิร์ฟเวอร์ที่คุณจัดการได้ (เช่น Docker, VPS):
  - **Debian/Ubuntu**: ติดตั้งแพ็กเกจ `locales` แล้วแก้ `/etc/locale.gen` ให้มี `en_US.UTF-8` และ `th_TH.UTF-8` จากนั้นรัน `locale-gen`
  - **Docker**: ใน Dockerfile ให้ติดตั้งและ generate locale ตามที่ได้ตั้งค่าไว้ใน moodle/Dockerfile ของโปรเจกต์นี้

การติดตั้งปลั๊กอินนี้ไม่แก้ไข core ของ Moodle จึงไม่กระทบระบบทั้งหมด และสามารถถอนการติดตั้งได้จากหน้า Plugins ตามปกติ

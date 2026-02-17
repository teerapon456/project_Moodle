# Phase 2: หลายหมวดบนหน้าเดียว (แบบ CMU MOOC)

## เป้าหมาย
หน้าแรกแสดง **หลาย section** — แต่ละ section เป็นหนึ่งหมวดหมู่ (category) มีหัวข้อ + ลิงก์ "ดูทั้งหมด" + การ์ดหลักสูตรในหมวดนั้น (จำกัดจำนวนต่อ section เช่น 8 หรือ 12)

## สิ่งที่ทำแล้ว (Implementation)

### 1. Theme settings (Site administration → Appearance → Academi → Course catalog)
- **Frontpage layout:** `Default` | `Multi-category (CMU style)`  
  - Default = แสดงเนื้อหาตาม Moodle หน้าแรกปกติ (รายการหลักสูตร/หมวดตามที่ตั้งค่าไซต์)  
  - Multi-category = แสดงหลาย section ตามรายการหมวดด้านล่าง
- **Multi-category: Category IDs** — รหัสหมวดหมู่ (คั่นด้วย comma) เช่น `1,2,5` ลำดับการแสดงเป็นตามที่พิมพ์
- **Multi-category: Courses per section** — จำนวนหลักสูตรสูงสุดต่อ section (ค่าเริ่มต้น 8)

### 2. ข้อมูลและ logic (lib.php)
- ฟังก์ชัน `theme_academi_get_multicategory_sections()`:
  - อ่านค่าจาก theme settings (category ids, per section)
  - สำหรับแต่ละ category id: ดึง category ที่ visible, ดึงรายการหลักสูตรที่ visible (ไม่รวม site course), จำกัดจำนวนตาม "per section"
  - แต่ละหลักสูตรส่ง: id, name, url, imgurl, summary (ตัดข้อความสั้นๆ)
  - คืนค่าเป็น array ของ section: `[ 'id', 'name', 'url', 'coursecount', 'courses' => [ ... ] ]`

### 3. Template
- **frontpage.mustache:** ถ้า `use_multicategory` เป็น true จะแสดง `{{> theme_academi/frontpage_multicategory}}` แทนที่ `output.main_content` ในพื้นที่แคตาล็อก
- **frontpage_multicategory.mustache:** วนลูป `{{#sections}}` แต่ละ section มี:
  - หัวข้อ (h2) + ลิงก์ "ดูทั้งหมด" ไปยัง `/course/index.php?categoryid=...`
  - กริดการ์ดหลักสูตร (class `frontpage-multicategory-cards`) — แต่ละการ์ดมีรูป, ชื่อ, summary สั้น, ลิงก์

### 4. Layout (layout/frontpage.php)
- อ่าน `frontpage_layout` ถ้าเป็น `multicategory` และมี category ids:
  - เรียก `theme_academi_get_multicategory_sections()` ส่งเข้า templatecontext เป็น `multicategory_sections`
  - ตั้ง `use_multicategory` = true
- ถ้าไม่ใช้ multicategory: `use_multicategory` = false, `multicategory_sections` = []

### 5. สไตล์ (SCSS)
- ไฟล์ `scss/frontpage.scss`: เพิ่มส่วน `.frontpage-multicategory-section`, `.frontpage-multicategory-cards`, `.frontpage-multicategory-card` — ใช้โทนมารูน (border, ลิงก์, ปุ่มดูทั้งหมด) และ grid responsive สอดคล้องกับ coursecardcolumns ที่มีอยู่

## ข้อจำกัด
- โหมด Multi-category **แทนที่** การแสดงรายการหลักสูตรมาตรฐานของ Moodle บนหน้าแรก (ไม่รวมสไลด์โชว์, แถบหมวดหมู่, หลักสูตรแนะนำ, footer)
- การตั้งค่า "Front page" ของ Moodle (เช่น เลือกแสดงรายการหลักสูตรหรือหมวดหมู่) จะไม่มีผลเมื่อใช้โหมด Multi-category — ธีมเป็นคนควบคุมเนื้อหาส่วนนี้
- จำนวน section = จำนวน category IDs ที่ระบุ (และมีอยู่จริง)

## การทดสอบ
1. ตั้ง Frontpage layout = Multi-category (CMU style)
2. ใส่ Category IDs เช่น 1,2 (หมวดที่มีหลักสูตร)
3. ตั้ง Courses per section = 6
4. Purge caches แล้วเปิดหน้าแรก — ควรเห็นหลาย section แต่ละ section มีหัวข้อหมวด + การ์ดหลักสูตร + ลิงก์ "ดูทั้งหมด"

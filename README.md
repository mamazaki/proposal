# ระบบเสนอชื่อคณะทำงาน (Working Group Nomination System)

ระบบสารสนเทศเพื่อจัดการรายชื่อคณะทำงานการจัดทำข้อเสนอและแนวทางการขับเคลื่อนการบริหารงานเชิงพื้นที่แบบบูรณาการด้านการศึกษา สำนักงานศึกษาธิการจังหวัดอุดรธานี

ระบบถูกออกแบบมาให้รองรับการทำงานบน Shared Hosting ทั่วไป เน้นความรวดเร็วในการนำไปใช้งานจริง (Rapid Deployment) ความยืดหยุ่นในการจัดการข้อมูลระดับ Master Data และการตรวจสอบย้อนกลับ (Traceability) ตามมาตรฐานของหน่วยงานราชการ

## 🚀 คุณสมบัติเด่น (Features)
- **Dynamic Multi-row Entry:** ฟอร์มบันทึกข้อมูลแบบเพิ่มได้หลายแถวพร้อมกัน (Vanilla JS Clone Node) พร้อมระบบ Auto-fill ลำดับตำแหน่งอัจฉริยะ
- **Dynamic Dropdown & Autocomplete:** ค้นหาประเด็นย่อยตามหัวข้อหลักอัตโนมัติ และมีระบบ Autocomplete ดึงประวัติรายชื่อเดิมจากฐานข้อมูล
- **Master Data Management:** แอดมินสามารถเพิ่ม/แก้ไข/เปิด-ปิด หัวข้อเรื่องหลัก (Main Topics) และคณะทำงานย่อย (Sub Groups) ได้อิสระโดยไม่ต้องแก้โค้ด
- **Multi-criteria Filter & Export:** ระบบกรองข้อมูลแบบซ้อนเงื่อนไข และส่งออกไฟล์ Excel (`.xlsx`) จัดคอลัมน์อัตโนมัติด้วย SheetJS (Client-side Processing)
- **Role-Based Access Control (RBAC):** แบ่งสิทธิ์ Admin (จัดการได้ทั้งหมด) และ User (จัดการได้เฉพาะข้อมูลตนเอง)
- **Enterprise Audit Trail:** ระบบบันทึก Log การใช้งานทุก Action (LOGIN, INSERT, UPDATE, DELETE) พร้อมเก็บ Data Diff (Before/After) ในรูปแบบ JSON และบันทึก IP Address
- **Soft Delete:** ป้องกันข้อมูลสูญหายจากการลบผิดพลาด ซ่อนข้อมูลจากหน้าจอแต่ยังคงมีหลักฐานอยู่ในฐานข้อมูลเพื่อการ Audit

## 🛠️ Stack & Technologies
- **Backend:** PHP 7.4+ / 8.x (PDO)
- **Database:** MySQL / MariaDB (Normalized Schema)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3
- **External Libraries:** SheetJS (สำหรับ Export `.xlsx`)

## 📦 การติดตั้ง (Installation)

1. **เตรียมไฟล์เข้า Server**
   - โคลน Repository นี้ หรืออัปโหลดไฟล์ทั้งหมดขึ้น Shared Hosting (โฟลเดอร์ `public_html` หรือ Sub-folder ที่ต้องการ)

2. **ตั้งค่าฐานข้อมูล**
   - นำไฟล์ `database.sql` ไป Import ลงใน MySQL ผ่าน phpMyAdmin

3. **ตั้งค่าการเชื่อมต่อ**
   - คัดลอกไฟล์ `config.sample.php` และเปลี่ยนชื่อเป็น `config.php`
   - แก้ไขข้อมูลการเชื่อมต่อฐานข้อมูลใน `config.php`:
     ```php
     $host = 'localhost';
     $dbname = 'YOUR_DB_NAME';
     $user = 'YOUR_DB_USER';
     $pass = 'YOUR_DB_PASSWORD';
     ```

4. **การเข้าสู่ระบบครั้งแรก**
   - รันสคริปต์ `reset_pass.php` (ที่เคยสร้างไว้) 1 ครั้งผ่าน Browser เพื่อตั้งค่ารหัสผ่านใหม่ให้กับ User: `admin`
   - ลบไฟล์ `reset_pass.php` ทิ้งทันทีเมื่อตั้งรหัสผ่านเสร็จสิ้น
   - เข้าสู่ระบบผ่าน `login.php`

## 🛡️ ความปลอดภัยและข้อควรระวัง (Security Notes)
- ห้ามอัปโหลดไฟล์ `config.php` ขึ้น Public Repository เด็ดขาด (กำหนดไว้ใน `.gitignore` แล้ว)
- ระบบใช้ไฟล์ `.htaccess` ในการป้องกัน Directory Listing และสกัดกั้นการเข้าถึงไฟล์ Configuration ขอให้ตรวจสอบว่า Apache บน Server เปิดให้ Override กฎเหล่านี้แล้ว
- การ Query ทั้งหมดใช้ Prepared Statements (PDO) เพื่อป้องกัน SQL Injection

## 👨‍💻 ผู้พัฒนา
**นายสุทธิชัย ชมชื่น** นักวิชาการคอมพิวเตอร์ชำนาญการ  
สำนักงานศึกษาธิการจังหวัดอุดรธานี
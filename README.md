# BeautiGo PHP + MySQL

1. Import `database.sql` ผ่าน phpMyAdmin
2. แก้ username/password ฐานข้อมูลใน `includes/database.php`
3. อัปโหลดโฟลเดอร์ `beautigo` ไปยัง `public_html/beautigo`
4. ให้โฟลเดอร์ `uploads` เขียนไฟล์ได้
5. เปิด `index.php`

หมายเหตุ: การชำระเงินในตัวอย่างเป็น Demo (กดแล้วยืนยัน paid) หากใช้งานจริงต้องต่อ Payment Gateway และตรวจสอบ webhook ฝั่ง server

Google Maps: ตัวอย่างนี้เก็บ latitude/longitude และรองรับปุ่มใช้พิกัดปัจจุบันแล้ว หากต้องการแผนที่แบบปักหมุด ให้เพิ่ม Google Maps JavaScript API key หรือใช้ Leaflet/OpenStreetMap

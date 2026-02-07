<?php
// view-pdf.php
// Proxy เสิร์ฟ PDF แบบ inline เพื่อให้เบราว์เซอร์เปิดในแท็บใหม่ (หากรองรับ)

session_start();
date_default_timezone_set('Asia/Bangkok');

// ไฟล์จริงอยู่ภายใต้ public/assets/user_manual_guide/
$baseDir =  'assets/user_manual_guide';

// รายการไฟล์ที่ "อนุญาต" ให้เปิด (ใช้ alias แทนชื่อไฟล์จริง เพื่อความปลอดภัย)
$allowed = [
    'applicants' => 'คู่มือการใช้งาน_IGA_Integrity Assessment_Applicants.pdf',
    'associates' => 'คู่มือการใช้งาน_IGA_Integrity Assessment_Associates.pdf',
];

// รับคีย์ไฟล์จาก query string
$key = $_GET['f'] ?? '';
if (!isset($allowed[$key])) {
    http_response_code(404);
    echo 'Not found';
    exit();
}

$filename = $allowed[$key];
$path     = $baseDir . '/' . $filename;

// ตรวจสอบไฟล์ว่ามีอยู่จริง
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    echo 'File not found';
    exit();
}

// ตั้ง header ให้ "เปิด" ในเบราว์เซอร์ ถ้ารองรับ (ไม่ใช่ download)
$downloadName = ($key === 'applicants') ? 'IGA_Applicants.pdf' : 'IGA_Associates.pdf';
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
// ปิดการประกาศ range เพื่อลด edge case ที่บาง client trigger download
// (ถ้าต้องการรองรับ range ก็ลบบรรทัดนี้ได้)
header('Accept-Ranges: none');

// ป้องกัน cache เกินควร (เลือกตามนโยบาย)
header('Cache-Control: private, max-age=3600'); // แคช 1 ชม. ในบราวเซอร์ผู้ใช้

// ส่งไฟล์ออกไป
$fp = fopen($path, 'rb');
fpassthru($fp);
fclose($fp);
exit();

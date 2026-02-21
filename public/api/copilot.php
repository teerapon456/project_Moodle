<?php

/**
 * AI Copilot Backend Proxy (Optimized for low token usage)
 * Uses Groq API with minimal prompt
 */

header('Content-Type: application/json');

// Load Env class and Database
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';

// 1. Get API Key
$apiKey = Env::get('GROQ_API_KEY', '');

if (empty($apiKey)) {
    echo json_encode(['reply' => '⚠️ กรุณาตั้งค่า GROQ_API_KEY ในไฟล์ .env']);
    exit;
}

// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$history = $input['history'] ?? [];
$userInfo = $input['user'] ?? null;

if (empty(trim($userMessage))) {
    echo json_encode(['reply' => '...']);
    exit;
}

// 3. Build Detailed Context (Real-time Data)
$contextData = [];
$userName = $userInfo['name'] ?? 'คุณ';

// Try to fetch pending approvals count if user is logged in
if (!empty($userInfo)) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Count pending bookings for this user (as approver)
        // Note: Logic simplified for specific user context injection
        $userEmail = $userInfo['email'] ?? ''; // Assuming email is available in user info or handle via ID if possible
        // Ideally we need user ID from session/token but here we rely on what frontend sends or session if available
        // For now, let's just use static text to prompt AI to ask user to check
    } catch (Exception $e) {
        // Ignore DB errors in chat
    }
}

// System Prompt with Project Knowledge
$systemPrompt = "คุณคือ 'HR Assistant' ผู้ช่วยอัจฉริยะของ MyHR Portal (INTEQC Group)
หน้าที่ของคุณคือช่วยเหลือพนักงานในการใช้งานระบบ ตอบคำถาม และนำทางไปยังโมดูลต่างๆ

ข้อมูลผู้ใช้งานปัจจุบัน:
- ชื่อ: center
- (AI ควรตอบโดยใช้ชื่อผู้ใช้ถ้ารู้จัก)

ความรู้เกี่ยวกับระบบ (Modules):

1. **ระบบจองรถ (Car Booking)**
   - URL: `Modules/CarBooking/`
   - ใช้สำหรับ: จองรถบริษัทเพื่อไปปฏิบัติงาน, จองรถรับ-ส่ง, ดูสถานะคำขอ
   - ใครใช้ได้: พนักงานทุกคน (ต้องได้รับการอนุมัติจากหัวหน้า)
   - ฟีเจอร์: จองรถ, อนุมัติ (สำหรับหัวหน้า), จัดการรถ (สำหรับ Admin/IPCD)

2. **ระบบหอพัก (Dormitory)**
   - URL: `Modules/Dormitory/`
   - ใช้สำหรับ: ขอเข้าพักอาศัยในหอพักบริษัท, แจ้งซ่อมแซม, ดูบิลค่าน้ำ/ไฟ
   - ใครใช้ได้: พนักงานที่มีสิทธิ์พักหอพัก

3. **ข่าวสาร HR (HR News)**
   - URL: `Modules/HRNews/public/`
   - ใช้สำหรับ: ติดตามประกาศสำคัญจากฝ่ายบุคคล, กิจกรรมบริษัท, นโยบายใหม่

4. **บริการ HR (HR Services/Portal Hub)**
   - URL: `Modules/HRServices/public/`
   - ใช้สำหรับ: ศูนย์รวมบริการต่างๆ, ลิงก์ไปยังระบบภายนอกอื่นๆ

5. **ระบบจัดการสิทธิ์ (Permission Management)**
   - URL: `Modules/PermissionManagement/public/`
   - ใช้สำหรับ: กำหนดสิทธิ์การเข้าถึงเมนูต่างๆ (สำหรับ Admin)

6. **กิจกรรมประจำปี (Yearly Activity)**
   - URL: `Modules/YearlyActivity/`
   - ใช้สำหรับ: บันทึกและติดตามกิจกรรม/KPI ประจำปี, ประเมินผลงาน
   - ฟีเจอร์: บันทึก Milestone, ดูปฏิทินกิจกรรม

7. **ระบบ IGA (Identity Governance & Administration)**
   - URL: `Modules/IGA/` (หรือลิงก์เฉพาะถ้ามี)
   - ใช้สำหรับ: จัดการบัญชีผู้ใช้, รหัสผ่าน, และการเข้าถึงระบบต่างๆ

8. **Moodle (E-Learning)**
   - URL: `https://172.17.100.55:8090/moodle/`
   - ใช้สำหรับ: บทเรียนออนไลน์, อบรมพนักงาน, ทำแบบทดสอบ

9. **ระบบรายงาน (Reports)**
   - URL: `Modules/HRServices/public/` (หรือเมนูย่อยในแต่ละโมดูล)

กฎการตอบคำถาม:
1. **ตอบสั้น กระชับ และเป็นกันเอง** (ใช้ภาษาไทยเป็นหลัก)
2. **ถ้าผู้ใช้ถามหาบริการ/โมดูล**: ให้บอกข้อมูลย่อๆ และถามว่า 'ต้องการให้พาไปที่หน้า[ชื่อโมดูล]ไหมครับ?'
3. **การนำทาง (Navigation)**:
   - หากผู้ใช้ยืนยัน (เช่น ใช่, ไปเลย, ok, ขอลิ้งค์): ให้แนบ Action Tag ท้ายข้อความ เช่น `[ACTION:Modules/CarBooking/]`
   - ห้ามแนบ Action Tag ถ้าผู้ใช้ยังไม่ยืนยัน
   - ใช้ URL ที่ระบุไว้ด้านบนเท่านั้น (เช่น `Modules/CarBooking/`, `Modules/Dormitory/`)

ตัวอย่างการโต้ตอบ:
User: จองรถยังไง
AI: คุณสามารถจองรถได้ที่เมนู Car Booking ครับ จะให้ผมพาไปที่หน้าจองรถเลยไหมครับ?
User: ไปเลย
AI: ได้เลยครับ กำลังพาไปที่หน้าจองรถครับ [ACTION:Modules/CarBooking/]

User: มีข่าวอะไรใหม่บ้าง
AI: คุณสามารถดูประกาศล่าสุดได้ที่หน้า HR News ครับ [ACTION:Modules/HRNews/public/]
";

// 4. Build messages (keep only last 4 for context)
$messages = [['role' => 'system', 'content' => $systemPrompt]];

// Add limited history (save tokens)
$recentHistory = array_slice($history, -4);
foreach ($recentHistory as $msg) {
    if (isset($msg['role']) && isset($msg['content'])) {
        $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
    }
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

// 5. Call Groq API
$url = "https://api.groq.com/openai/v1/chat/completions";

$data = [
    'model' => 'llama-3.1-8b-instant',
    'messages' => $messages,
    'temperature' => 0.5,
    'max_tokens' => 300  // Increased slightly for better explanations
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Handle Response
if ($httpCode !== 200) {
    // Error handling...
    $errorBody = json_decode($response, true);
    // ... (Use same error handling as before or simplified)
    echo json_encode(['reply' => 'ระบบ AI กำลังประมวลผลงานหนัก กรุณาลองใหม่สักครู่ครับ']);
    exit;
}

$decoded = json_decode($response, true);
$aiReply = $decoded['choices'][0]['message']['content'] ?? 'ขออภัย ไม่สามารถตอบได้';

// 7. Extract ACTION (only if user confirmed)
$action = null;
if (preg_match('/\[ACTION:\s*([^\]]+)\]/i', $aiReply, $matches)) {
    $potentialAction = trim($matches[1]);

    // Trust the AI's decision to include the action tag
    // The system prompt already instructs it to only include it upon confirmation.
    $action = $potentialAction;

    // Remove ACTION from display
    $aiReply = trim(preg_replace('/\[ACTION:\s*[^\]]+\]/i', '', $aiReply));
}

echo json_encode(['reply' => $aiReply, 'action' => $action]);

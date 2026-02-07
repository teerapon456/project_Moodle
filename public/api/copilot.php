<?php

/**
 * AI Copilot Backend Proxy (Optimized for low token usage)
 * Uses Groq API with minimal prompt
 */

header('Content-Type: application/json');

// Load Env class
require_once __DIR__ . '/../../core/Config/Env.php';

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

// 3. Build minimal system prompt
$userName = $userInfo['name'] ?? 'คุณ';

$systemPrompt = "คุณคือผู้ช่วย AI ของ MyHR Portal ชื่อ 'HR Assistant'
ตอบสั้นๆ กระชับ ใช้ภาษาไทย

โมดูลที่มี:
- จองรถ: Modules/CarBooking/index.php
- หอพัก: Modules/Dormitory/index.php
- ข่าวสาร: Modules/HRNews/public/index.php
- บริการHR: Modules/HRServices/public/index.php
- IGA: Modules/IGA/index.php
- จัดการสิทธิ์: Modules/PermissionManagement/public/index.php
- กิจกรรมประจำปี: Modules/YearlyActivity/index.php

กฎสำคัญ:
1. ถ้า user ถามเรื่องโมดูล ให้ถาม 'ต้องการให้พาไปไหม?'
2. ใส่ [ACTION:URL] ต่อท้าย เฉพาะเมื่อ user ตอบ ใช่/ไป/ok/เอา
3. อย่าใส่ ACTION ถ้า user ยังไม่ยืนยัน";

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
    'max_tokens' => 200  // Keep responses short
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
    $errorBody = json_decode($response, true);
    $errorMsg = $errorBody['error']['message'] ?? 'Unknown Error';

    // Auto-retry suggestion
    if ($httpCode === 429) {
        echo json_encode(['reply' => 'ระบบยุ่งอยู่ กรุณารอสักครู่แล้วลองใหม่ค่ะ...']);
    } else {
        echo json_encode(['reply' => "ขออภัย เกิดข้อผิดพลาด"]);
    }
    exit;
}

$decoded = json_decode($response, true);
$aiReply = $decoded['choices'][0]['message']['content'] ?? 'ขออภัย ไม่สามารถตอบได้';

// 7. Extract ACTION (only if user confirmed)
$action = null;
if (preg_match('/\[ACTION:\s*([^\]]+)\]/i', $aiReply, $matches)) {
    $potentialAction = trim($matches[1]);

    // Only allow action if user confirmed
    $confirmWords = ['ใช่', 'เอา', 'ได้', 'โอเค', 'ok', 'yes', 'ไป', 'พาไป', 'ไปเลย', 'ตกลง'];
    $userMsgLower = mb_strtolower(trim($userMessage));

    foreach ($confirmWords as $word) {
        if (strpos($userMsgLower, mb_strtolower($word)) !== false) {
            $action = $potentialAction;
            break;
        }
    }

    // Remove ACTION from display
    $aiReply = trim(preg_replace('/\[ACTION:\s*[^\]]+\]/i', '', $aiReply));
}

echo json_encode(['reply' => $aiReply, 'action' => $action]);

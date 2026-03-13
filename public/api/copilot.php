<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log request for debugging
@file_put_contents('/tmp/copilot_requests.log', date('Y-m-d H:i:s') . " - Request Received: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . " from " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n", FILE_APPEND);

// Ensure script keeps running even if browser disconnects
ignore_user_abort(true);
set_time_limit(60);

// Load Env class and Database
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';

$db = new Database();
$conn = $db->getConnection();

// 1. Get API Key
$apiKey = Env::get('GROQ_API_KEY', '');

if (empty($apiKey)) {
    echo json_encode(['reply' => '⚠️ กรุณาตั้งค่า GROQ_API_KEY ในไฟล์ .env']);
    exit;
}

/**
 * Helper to get AI settings from DB
 */
function get_ai_setting($conn, $key, $default = '')
{
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM ai_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
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

// 2.1 Check Rate Limit
$userId = $userInfo['id'] ?? null;
if ($userId) {
    $dailyLimit = (int)get_ai_setting($conn, 'daily_limit', '50');
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM copilot_usage_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$userId]);
        $todayCount = (int)$stmt->fetchColumn();

        if ($todayCount >= $dailyLimit) {
            echo json_encode(['reply' => "⚠️ คุณใช้งานเกินขีดจำกัดรายวัน ($dailyLimit ครั้ง) แล้ว สามารถลองใหม่ได้ในวันพรุ่งนี้ครับ"]);
            exit;
        }
    } catch (Exception $e) {
        // Continue if check fails
    }
}

// 3. Build Tools Definition
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_my_user_info',
            'description' => 'ค้นหาข้อมูลส่วนตัวของผู้ใช้งานปัจจุบัน เช่น รหัสพนักงาน, แผนก, อีเมล, เบอร์โทรศัพท์',
            'parameters' => [
                'type' => 'object',
                'properties' => (object)[]
            ]
        ]
    ],
    [
        'type' => 'function',
        'function' => [
            'name' => 'search_hr_knowledge',
            'description' => 'ค้นหาข้อมูลกฎระเบียบ สวัสดิการ หรือประกาศต่างๆ จากฐานข้อมูล HR (ข่าวสารและบริการ)',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'คำค้นหาที่เกี่ยวข้องกับกฎระเบียบหรือสวัสดิการ'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ]
];

/**
 * Tool Execution Functions
 */
function execute_tool($name, $args, $userInfo)
{
    global $conn;
    if (!$conn) return "เชื่อมต่อฐานข้อมูลล้มเหลว";

    switch ($name) {
        case 'get_my_user_info':
            if (empty($userInfo['id'])) return "ไม่พบข้อมูลผู้ใช้งานในระบบ";
            try {
                $stmt = $conn->prepare("SELECT id, username, email, fullname, EmpCode, OrgUnitName, PositionName FROM users WHERE id = ?");
                $stmt->execute([$userInfo['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                return $user ? json_encode($user, JSON_UNESCAPED_UNICODE) : "ไม่พบข้อมูลโปรไฟล์ของคุณ";
            } catch (Exception $e) {
                return "ขออภัย ระบบไม่สามารถดึงข้อมูลส่วนตัวได้ในขณะนี้";
            }

        case 'search_hr_knowledge':
            $queryStr = trim($args['query'] ?? '');
            $isGeneric = in_array(strtolower($queryStr), ['', 'latest', 'news', 'services', 'ข่าว', 'บริการ', 'ประกาศ', 'สรุป']);
            $queryParam = "%" . $queryStr . "%";

            try {
                // 1. Fetch All Active Policies (Semantic Engine handles matching)
                // Llama 3 70B context window is 8k+, returning all simple text rules is well within token budget
                // and avoids SQL LIKE missing synonyms like "เดินทาง" vs "ท่องเที่ยว"
                $stmt = $conn->query("SELECT title, category, content FROM hr_policies WHERE is_active = 1");
                $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Search News
                $news = [];
                if (!$isGeneric) {
                    $stmt = $conn->prepare("SELECT title, content, created_at FROM hr_news WHERE (title LIKE ? OR content LIKE ?) AND status = 'published' ORDER BY created_at DESC LIMIT 3");
                    $stmt->execute([$queryParam, $queryParam]);
                    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if ($isGeneric || empty($news)) {
                    $stmt = $conn->query("SELECT title, content, created_at FROM hr_news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
                    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // 3. Search Services
                $services = [];
                if (!$isGeneric) {
                    $stmt = $conn->prepare("SELECT name, name_translations FROM hr_services WHERE (name LIKE ? OR name_translations LIKE ?) AND status = 'ready' LIMIT 3");
                    $stmt->execute([$queryParam, $queryParam]);
                    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if ($isGeneric || empty($services)) {
                    $stmt = $conn->query("SELECT name, name_translations FROM hr_services WHERE status = 'ready' LIMIT 3");
                    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                return json_encode([
                    'from_policies' => $policies,
                    'from_news' => $news,
                    'from_services' => $services,
                    'note' => $isGeneric ? "Showing latest items (generic query)" : "Filtered results"
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                return "ขออภัย ระบบไม่สามารถค้นหาข้อมูลได้ในขณะนี้";
            }

        default:
            return "ไม่รู้จักคำสั่งนี้";
    }
}

/**
 * Log AI Usage to Database
 */
function log_usage($conn, $userId, $query, $toolCalled, $tokensUsed, $model)
{
    try {
        $stmt = $conn->prepare("INSERT INTO copilot_usage_logs (user_id, `query`, tool_called, tokens_used, model) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $query, $toolCalled, $tokensUsed, $model]);
    } catch (Exception $e) {
        // Silently fail to not disrupt AI service
        error_log("Copilot Logging Error: " . $e->getMessage());
    }
}

// System Prompt with Project Knowledge
$userName = ($userInfo['name'] ?? 'ผู้ใช้งาน');
$userIdLabel = ($userInfo['id'] ?? 'N/A');

$systemPromptTemplate = get_ai_setting($conn, 'system_prompt', "คุณคือ 'HR Assistant' ผู้ช่วยอัจฉริยะของ MyHR Portal (INTEQC Group)
หน้าที่ของคุณคือช่วยเหลือพนักงานในการใช้งานระบบ ตอบคำถาม และนำทางไปยังโมดูลต่างๆ");

$systemPrompt = str_replace(['{{userName}}', '{{userId}}'], [$userName, $userIdLabel], $systemPromptTemplate);

// 4. Build messages
$messages = [['role' => 'system', 'content' => $systemPrompt]];
$recentHistory = array_slice($history, -4);
foreach ($recentHistory as $msg) {
    if (isset($msg['role']) && isset($msg['content'])) {
        $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
    }
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

// 5. Call AI with Recursive Tool Handling
function callGroq($messages, $tools, $apiKey, $model = 'llama-3.3-70b-versatile')
{
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $data = [
        'model' => $model,
        'messages' => $messages,
        'tools' => $tools,
        'tool_choice' => 'auto',
        'temperature' => $model === 'llama-3.1-8b-instant' ? 0.3 : 0.1,
        'max_tokens' => 1000
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Total timeout (seconds)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        @file_put_contents('/tmp/copilot_raw.log', date('Y-m-d H:i:s') . " - Error Code $httpCode: $response\n", FILE_APPEND);
        return ['error' => true, 'response' => $response];
    }
    @file_put_contents('/tmp/copilot_raw.log', date('Y-m-d H:i:s') . " - Response: $response\n", FILE_APPEND);
    return json_decode($response, true);
}

try {
    $primaryModel = get_ai_setting($conn, 'model_name', 'llama-3.3-70b-versatile');
    $fallbackModel = get_ai_setting($conn, 'fallback_model_name', 'llama-3.1-8b-instant');

    $decoded = callGroq($messages, $tools, $apiKey, $primaryModel);

    // Fallback to 8b if primary fails (Rate Limit 429 or Model Error 400)
    if (isset($decoded['error']) || empty($decoded['choices'])) {
        $decoded = callGroq($messages, $tools, $apiKey, $fallbackModel);
    }

    // Call AI with tool results
    if (isset($decoded['choices'][0]['message']['tool_calls'])) {
        $assistantMessage = $decoded['choices'][0]['message'];
        
        // Ensure content is at least null or empty string if not set, required by Groq
        if (!isset($assistantMessage['content'])) {
            $assistantMessage['content'] = "";
        }

        $toolMessages = [$assistantMessage];
        $toolsUsed = [];

        foreach ($assistantMessage['tool_calls'] as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $functionArgs = json_decode($toolCall['function']['arguments'], true) ?: [];
            $toolsUsed[] = $functionName;

            $toolResult = execute_tool($functionName, $functionArgs, $userInfo);

            $toolMessages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'name' => $functionName,
                'content' => $toolResult
            ];
        }

        // Call AI again with tool results
        $finalMessages = array_merge($messages, $toolMessages);
        
        // Remove tools from the second call to force a final text answer
        $decoded = callGroq($finalMessages, [], $apiKey, $primaryModel);

        // Fallback for second call
        if (isset($decoded['error']) || empty($decoded['choices'])) {
            @file_put_contents('/tmp/copilot_raw.log', date('Y-m-d H:i:s') . " - Primary 2nd call failed, falling back.\n", FILE_APPEND);
            $decoded = callGroq($finalMessages, $tools, $apiKey, $fallbackModel);
        }

        // Cumulative logging
        $totalTokens = ($decoded['usage']['total_tokens'] ?? 0);
        log_usage($conn, $userInfo['id'] ?? null, $userMessage, implode(', ', $toolsUsed), $totalTokens, $decoded['model'] ?? 'unknown');
    } else {
        // Direct response logging
        $totalTokens = ($decoded['usage']['total_tokens'] ?? 0);
        log_usage($conn, $userInfo['id'] ?? null, $userMessage, null, $totalTokens, $decoded['model'] ?? 'unknown');
    }
} catch (Exception $e) {
    @file_put_contents('/tmp/copilot_error.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    $decoded = ['choices' => [['message' => ['content' => 'ขออภัย ระบบขัดข้องกรุณาลองใหม่ (Internal Error)']]]];
}

// 6. Final Response
$aiReply = $decoded['choices'][0]['message']['content'] ?? 'ขออภัย ระบบขัดข้องกรุณาลองใหม่';

// 7. Extract ACTION
$action = null;
if (preg_match('/\[ACTION:\s*([^\]]+)\]/i', $aiReply, $matches)) {
    $action = trim($matches[1]);
    $aiReply = trim(preg_replace('/\[ACTION:\s*[^\]]+\]/i', '', $aiReply));
}

echo json_encode(['reply' => $aiReply, 'action' => $action]);

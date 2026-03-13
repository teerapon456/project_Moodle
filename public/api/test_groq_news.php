<?php
// test_groq_news.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../core/Config/Env.php';
$apiKey = Env::get("GROQ_API_KEY");
$model = "llama-3.3-70b-versatile";

$messages = [
    ["role" => "system", "content" => "คุณคือพนักงาน HR"],
    ["role" => "user", "content" => "มีข่าวอะไรใหม่บ้าง"]
];

$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "search_hr_knowledge",
            "description" => "ค้นหาข้อมูลกฎระเบียบ สวัสดิการ หรือข่าวสาร",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "query" => [
                        "type" => "string",
                        "description" => "คำค้นหา"
                    ]
                ],
                "required" => ["query"]
            ]
        ]
    ]
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => $model,
    "messages" => $messages,
    "tools" => $tools,
    "tool_choice" => "auto"
]));
$response = curl_exec($ch);
if(curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo "Response: " . $response . "\n";
}
curl_close($ch);
?>

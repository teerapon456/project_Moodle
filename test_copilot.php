<?php

/**
 * Test script for AI Copilot Tooling
 */

require_once __DIR__ . '/public/api/copilot.php';

$db = new Database();
$conn = $db->getConnection();
echo "DB Connection: SUCCESS\n";

$userInfo = ['id' => 1];
echo "Testing get_my_user_info: " . execute_tool('get_my_user_info', [], $userInfo) . "\n";
echo "Testing search_hr_knowledge: " . execute_tool('search_hr_knowledge', ['query' => 'รถ'], $userInfo) . "\n";

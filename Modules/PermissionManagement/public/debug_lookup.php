<?php
require_once '/var/www/html/core/Config/Env.php';
$conn = new PDO('mysql:host=' . Env::get('DB_HOST') . ';dbname=' . Env::get('DB_NAME'), Env::get('DB_USER'), Env::get('DB_PASS'));
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = '1916506010';

echo "Testing lookup for username: [$username]\n";

// Test 1: Direct Query
$stmt = $conn->query("SELECT * FROM users WHERE username = '$username'");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Direct Query Result: " . ($res ? "FOUND (ID: " . $res['user_id'] . ")" : "NOT FOUND") . "\n";

// Test 2: Prepared Statement (Same as app)
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Prepared Statement Result: " . ($res ? "FOUND (ID: " . $res['user_id'] . ")" : "NOT FOUND") . "\n";

// Test 3: Check Person ID Update SQL syntax
if ($res) {
    $uid = $res['user_id'];
    echo "User ID Type: " . gettype($uid) . "\n";
    echo "SQL Test: UPDATE users SET person_id = 'TEST' WHERE user_id = $uid\n";
    // This will error if UID is string UUID and not quoted
}

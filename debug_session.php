<?php
require_once __DIR__ . '/core/Config/SessionConfig.php';

// Simulate a request
startOptimizedSession();

header('Content-Type: text/plain');

echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "--- INI Settings ---\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "redis extension loaded: " . (extension_loaded('redis') ? 'YES' : 'NO') . "\n";

echo "\n--- Environment ---\n";
echo "REDIS_HOST env: " . getenv('REDIS_HOST') . "\n";

echo "\n--- Write Test ---\n";
$_SESSION['debug_timestamp'] = time();
echo "Wrote 'debug_timestamp' to session.\n";
print_r($_SESSION);

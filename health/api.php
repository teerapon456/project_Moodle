<?php

/**
 * Standalone System Health API
 * Runs on a dedicated 'health' container to ensure true independence.
 */

// Use absolute paths mapped in Docker volumes
require_once __DIR__ . '/core/Database/Database.php';

header('Content-Type: application/json');

/**
 * Control a Docker container via the Docker Engine API (Unix Socket)
 */
function controlContainer($containerName, $action = 'start')
{
    $secret = $_ENV['HEALTH_ADMIN_SECRET'] ?? 'myhr_super_secret_admin_key_2026';
    $providedSecret = $_SERVER['HTTP_X_HEALTH_SECRET'] ?? '';

    if ($providedSecret !== $secret) {
        return ['status' => 'error', 'message' => 'Unauthorized'];
    }

    $socket = '/var/run/docker.sock';
    if (!file_exists($socket)) {
        return ['status' => 'error', 'message' => 'Docker Socket not available'];
    }

    $url = "http://localhost/v1.41/containers/{$containerName}/{$action}";

    if (!is_writable($socket)) {
        return ['status' => 'error', 'message' => "Docker Socket is not writable by web server. Permissions: " . substr(sprintf('%o', fileperms($socket)), -4)];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: 0']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 204 || $httpCode === 304) {
        return ['status' => 'success', 'message' => "Service " . ($httpCode === 304 ? "already " : "") . " {$action}ed"];
    } else {
        if ($response === false) {
            return ['status' => 'error', 'message' => "Docker Connection Error: {$curlError} (Code: {$curlErrno}). Path: {$socket}"];
        }
        $error = json_decode($response, true);
        return ['status' => 'error', 'message' => $error['message'] ?? "Docker API Error ({$httpCode}): " . bin2hex(substr($response, 0, 100))];
    }
}

function checkTcpStatus($host, $port, $timeout = 2)
{
    try {
        $start = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $latency = round((microtime(true) - $start) * 1000); // ms

        if (!$fp) {
            $errorMsg = "Connection Refused";
            if ($errno === 110) $errorMsg = "Connection Timeout";
            if ($errno === 113) $errorMsg = "No Route to Host";
            return ['status' => 'offline', 'latency' => 0, 'error' => $errorMsg];
        }
        fclose($fp);
        return ['status' => 'online', 'latency' => $latency];
    } catch (Exception $e) {
        return ['status' => 'offline', 'latency' => 0, 'error' => $e->getMessage()];
    }
}

function getSystemUptime()
{
    $uptime = @file_get_contents('/proc/uptime');
    if ($uptime !== false) {
        $uptime = explode(' ', $uptime)[0];
        $seconds = floor($uptime);
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $mins = floor(($seconds % 3600) / 60);

        return [
            'uptime' => $days > 0 ? "{$days}d {$hours}h" : "{$hours}h {$mins}m",
            'started_at' => date('Y-m-d H:i', time() - $seconds)
        ];
    }
    return ['uptime' => 'Active', 'started_at' => '--'];
}

function checkDatabase()
{
    try {
        $start_time = microtime(true);
        $db = new Database();
        $conn = $db->getConnection();
        $latency = round((microtime(true) - $start_time) * 1000);

        $uptime = "Unknown";
        $started_at = "Unknown";
        $error = null;

        if ($conn) {
            $status = $conn->query("SHOW GLOBAL STATUS LIKE 'Uptime'")->fetch(PDO::FETCH_ASSOC);
            if ($status) {
                $seconds = $status['Value'];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $uptime = $days > 0 ? "{$days}d {$hours}h" : "{$hours}h " . floor(($seconds % 3600) / 60) . "m";
                $started_at = date('Y-m-d H:i', time() - $seconds);
            }
        } else {
            $error = "DB Connection Failed";
        }

        return [
            'status' => $conn ? 'online' : 'offline',
            'latency' => $latency,
            'uptime' => $uptime,
            'started_at' => $started_at,
            'error' => $error,
            'link' => ':8081/?pma_username=' . ($_ENV['PMA_USER'] ?? '') . '&server=1'
        ];
    } catch (Exception $e) {
        return ['status' => 'offline', 'latency' => 0, 'uptime' => '--', 'started_at' => '--', 'error' => $e->getMessage(), 'link' => '#'];
    }
}

function getContainerUptime()
{
    // In a container, PID 1 is the main process. 
    // /proc/1/stat column 22 is the start time in jiffies after boot.
    $stat = @file_get_contents('/proc/1/stat');
    $uptime_sys = @file_get_contents('/proc/uptime');

    if ($stat && $uptime_sys) {
        $stats = explode(' ', $stat);
        $uptime_sys = explode(' ', $uptime_sys)[0];

        // Column 22 is index 21
        $starttime_jiffies = $stats[21];
        $hertz = 100; // Common value, though could be queried via getconf CLK_TCK

        $starttime_sec = $starttime_jiffies / $hertz;
        $container_uptime_sec = $uptime_sys - $starttime_sec;

        $seconds = floor($container_uptime_sec);
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $mins = floor(($seconds % 3600) / 60);

        return [
            'uptime' => $days > 0 ? "{$days}d {$hours}h" : ($hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m"),
            'started_at' => date('Y-m-d H:i', time() - $seconds)
        ];
    }
    return ['uptime' => 'Active', 'started_at' => 'Running'];
}

$redisHost = $_ENV['REDIS_HOST'] ?? 'myhr-redis';
$redisPort = $_ENV['REDIS_PORT'] ?? 6379;

// Handle Control Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['service']) && isset($input['action'])) {
        // Map service display name to container name
        $nameMap = [
            'Database' => 'myhr-db',
            'Moodle DB' => 'myhr-moodle-db',
            'Redis' => 'myhr-redis',
            'Gateway' => 'myhr-gateway',
            'Portal (Main)' => 'myhr-portal',
            'Moodle Frontend' => 'myhr-frontend',
            'Moodle LMS' => 'myhr-moodle',
            'Car Booking' => 'myhr-carbooking',
            'Yearly Activity' => 'myhr-yearlyactivity',
            'IGA Module' => 'myhr-iga',
            'Dormitory' => 'myhr-dormitory',
            'phpMyAdmin' => 'myhr-phpmyadmin'
        ];

        $containerName = $nameMap[$input['service']] ?? null;
        if ($containerName) {
            $result = controlContainer($containerName, $input['action']);
            echo json_encode($result);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$containerMetrics = getContainerUptime();

// Mocking uptime for containers since we can't easily query docker socket
$servicesDefinition = [
    'Database' => ['host' => 'myhr-db', 'port' => 3306, 'type' => 'db', 'link' => ':8081/?server=1'],
    'Moodle DB' => ['host' => 'myhr-moodle-db', 'port' => 3306, 'type' => 'tcp', 'link' => ':8081/?server=2'],
    'Redis' => ['host' => 'myhr-redis', 'port' => 6379, 'type' => 'tcp', 'link' => '#'],
    'Gateway' => ['host' => 'myhr-gateway', 'port' => 80, 'type' => 'tcp', 'link' => '#'],

    'Portal (Main)' => ['host' => 'myhr-portal', 'port' => 80, 'type' => 'tcp', 'link' => '/'],
    'Moodle Frontend' => ['host' => 'myhr-frontend', 'port' => 3000, 'type' => 'tcp', 'link' => '/moodle/'],
    'Moodle LMS' => ['host' => 'myhr-moodle', 'port' => 80, 'type' => 'tcp', 'link' => '/moodle/'],

    'Car Booking' => ['host' => 'myhr-carbooking', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/CarBooking/'],
    'Dormitory' => ['host' => 'myhr-dormitory', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/Dormitory/'],
    'Yearly Activity' => ['host' => 'myhr-yearlyactivity', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/YearlyActivity/'],
    'IGA Module' => ['host' => 'myhr-iga', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/IGA/'],

    'phpMyAdmin' => ['host' => 'myhr-phpmyadmin', 'port' => 80, 'type' => 'tcp', 'link' => ':8081/'],
    'Health Check' => ['host' => 'localhost', 'port' => 80, 'type' => 'self', 'link' => '/health/']
];

$serviceDetails = [];
$allOnline = true;

foreach ($servicesDefinition as $name => $cfg) {
    if ($cfg['type'] === 'db') {
        $data = checkDatabase();
    } elseif ($cfg['type'] === 'self') {
        $data = ['status' => 'online', 'latency' => 1, 'uptime' => $containerMetrics['uptime'], 'started_at' => $containerMetrics['started_at'], 'link' => $cfg['link']];
    } else {
        $check = checkTcpStatus($cfg['host'], $cfg['port']);
        $data = array_merge($check, [
            'link' => $cfg['link'],
            // Since all containers were started together by Docker Compose,
            // they share the same container uptime metrics.
            'uptime' => $check['status'] === 'online' ? $containerMetrics['uptime'] : 'Offline',
            'started_at' => $check['status'] === 'online' ? $containerMetrics['started_at'] : 'Stopped'
        ]);
    }

    $serviceDetails[$name] = $data;
    if ($data['status'] !== 'online') {
        $allOnline = false;
    }
}

$response = [
    'status' => $allOnline ? 'healthy' : 'degraded',
    'timestamp' => date('Y-m-d H:i:sP'),
    'services' => $serviceDetails
];

echo json_encode($response);

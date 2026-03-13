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

/**
 * Fetch CPU/RAM stats for multiple containers in parallel with persistence cache
 */
function getMultipleContainerStats($containerNames)
{
    $socket = '/var/run/docker.sock';
    if (!file_exists($socket) || !is_writable($socket)) return [];

    $cacheFile = '/tmp/health_stats_cache.json';
    $cache = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    }

    $mh = curl_multi_init();
    $handles = [];

    foreach ($containerNames as $name) {
        $ch = curl_init();
        $url = "http://localhost/v1.41/containers/{$name}/stats?stream=false";
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        // Important: Set Host header for some environments
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: localhost"]);
        curl_multi_add_handle($mh, $ch);
        $handles[$name] = $ch;
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $results = [];
    $updatedCache = false;
    $log = [];

    foreach ($handles as $name => $ch) {
        $response = curl_multi_getcontent($ch);
        $info = curl_getinfo($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($response) {
            $stats = json_decode($response, true);
            if ($stats && !isset($stats['message'])) {
                // CPU Calculation
                $cpu_percent = 0.0;
                if (isset($stats['cpu_stats'], $stats['precpu_stats'])) {
                    $cpu_usage = $stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0;
                    $precpu_usage = $stats['precpu_stats']['cpu_usage']['total_usage'] ?? 0;
                    $system_usage = $stats['cpu_stats']['system_cpu_usage'] ?? 0;
                    $presystem_usage = $stats['precpu_stats']['system_cpu_usage'] ?? 0;
                    
                    $cpu_delta = $cpu_usage - $precpu_usage;
                    $system_delta = $system_usage - $presystem_usage;
                    $online_cpus = $stats['cpu_stats']['online_cpus'] ?? 1;

                    if ($system_delta > 0 && $cpu_delta > 0) {
                        $cpu_percent = ($cpu_delta / $system_delta) * $online_cpus * 100.0;
                    }
                }

                // Memory Calculation
                $mem_usage = $stats['memory_stats']['usage'] ?? 0;
                $mem_limit = $stats['memory_stats']['limit'] ?? 1;
                $mem_stats = $stats['memory_stats']['stats'] ?? [];
                $inactive_file = $mem_stats['inactive_file'] ?? ($mem_stats['total_inactive_file'] ?? 0);
                $used_memory = $mem_usage - $inactive_file;
                $mem_percent = ($used_memory / $mem_limit) * 100.0;

                $data = [
                    'cpu_percent' => round($cpu_percent, 1),
                    'mem_percent' => round($mem_percent, 1),
                    'mem_usage_mb' => round($used_memory / 1024 / 1024, 1),
                    'mem_limit_mb' => round($mem_limit / 1024 / 1024, 1),
                    'timestamp' => time()
                ];
                
                $results[$name] = $data;
                $cache[$name] = $data;
                $updatedCache = true;
                $log[] = "Success: $name";
            } else {
                $log[] = "Empty stats/error message: $name (" . ($stats['message'] ?? 'unknown') . ")";
                if (isset($cache[$name])) {
                    $results[$name] = $cache[$name];
                    $results[$name]['stale'] = true;
                }
            }
        } else {
            $log[] = "No response: $name, HTTP: " . $info['http_code'];
            if (isset($cache[$name])) {
                $results[$name] = $cache[$name];
                $results[$name]['stale'] = true;
            }
        }
    }

    @file_put_contents('/tmp/health_api_log.txt', date('Y-m-d H:i:s') . " - " . implode(', ', $log) . "\n", FILE_APPEND);

    if ($updatedCache) {
        @file_put_contents($cacheFile, json_encode($cache));
    }

    curl_multi_close($mh);
    return $results;
}

/**
 * Fetch all container information in a single call to Docker API
 */
function getAllContainersInfo()
{
    $socket = '/var/run/docker.sock';
    if (!file_exists($socket)) return [];

    $url = "http://localhost/v1.41/containers/json?all=true";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];

    $containers = json_decode($response, true);
    $map = [];
    if (is_array($containers)) {
        foreach ($containers as $c) {
            $state = $c['State'] ?? 'offline';
            $status = $c['Status'] ?? '';
            
            // Clean up uptime from Status string (e.g. "Up 6 hours" -> "6 hours")
            $uptime = "Stopped";
            if ($state === 'running') {
                $uptime = preg_replace('/^Up\s+/', '', $status);
                $uptime = preg_replace('/\s+\(healthy\)$/', '', $uptime);
            }

            $info = [
                'id' => $c['Id'],
                'status' => ($state === 'running') ? 'online' : 'offline',
                'uptime' => $uptime,
                'state' => $state,
                'created' => $c['Created'] ?? 0
            ];

            foreach ($c['Names'] as $rawName) {
                $name = ltrim($rawName, '/');
                $map[$name] = $info;
            }
        }
    }
    return $map;
}

function getContainerInspect($idOrName)
{
    $socket = '/var/run/docker.sock';
    if (!file_exists($socket)) return null;

    $url = "http://localhost/v1.41/containers/{$idOrName}/json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;
    return json_decode($response, true);
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

function getSystemMetrics()
{
    $metrics = [
        'load' => '0.00 0.00 0.00',
        'cpu_percent' => 0,
        'cores' => 1,
        'ram' => ['total' => 0, 'free' => 0, 'available' => 0, 'used_percent' => 0],
        'swap' => ['total' => 0, 'free' => 0, 'used_percent' => 0],
        'disk' => ['total' => 0, 'free' => 0, 'used_percent' => 0]
    ];

    // CPU Cores
    $cores = @shell_exec('nproc');
    $metrics['cores'] = $cores ? (int)trim($cores) : 1;

    // Load Average
    $load = @file_get_contents('/proc/loadavg');
    if ($load) {
        $loadArr = explode(' ', $load);
        $metrics['load'] = "{$loadArr[0]} {$loadArr[1]} {$loadArr[2]}";
        
        // Utilization = (Load / Cores) * 100
        $metrics['cpu_percent'] = round(($loadArr[0] / $metrics['cores']) * 100, 1);
    }

    // Memory Info
    $meminfo = @file_get_contents('/proc/meminfo');
    if ($meminfo) {
        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemFree:\s+(\d+)/', $meminfo, $free);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
        preg_match('/SwapTotal:\s+(\d+)/', $meminfo, $stotal);
        preg_match('/SwapFree:\s+(\d+)/', $meminfo, $sfree);

        $metrics['ram']['total'] = (int)($total[1] ?? 0);
        $metrics['ram']['free'] = (int)($free[1] ?? 0);
        $metrics['ram']['available'] = (int)($avail[1] ?? 0);
        
        if ($metrics['ram']['total'] > 0) {
            $used = $metrics['ram']['total'] - $metrics['ram']['available'];
            $metrics['ram']['used_percent'] = round(($used / $metrics['ram']['total']) * 100, 1);
        }

        $metrics['swap']['total'] = (int)($stotal[1] ?? 0);
        $metrics['swap']['free'] = (int)($sfree[1] ?? 0);
        if ($metrics['swap']['total'] > 0) {
            $sused = $metrics['swap']['total'] - $metrics['swap']['free'];
            $metrics['swap']['used_percent'] = round(($sused / $metrics['swap']['total']) * 100, 1);
        }
    }

    // Disk Info
    $path = '/'; 
    $disk_total = @disk_total_space($path);
    $disk_free = @disk_free_space($path);
    if ($disk_total !== false && $disk_free !== false) {
        $disk_used = $disk_total - $disk_free;
        $metrics['disk']['total'] = round($disk_total / 1024 / 1024 / 1024, 1); // GB
        $metrics['disk']['free'] = round($disk_free / 1024 / 1024 / 1024, 1); // GB
        $metrics['disk']['used_percent'] = round(($disk_used / $disk_total) * 100, 1);
    }

    return $metrics;
}

$redisHost = $_ENV['REDIS_HOST'] ?? 'myhr-redis';
$redisPort = $_ENV['REDIS_PORT'] ?? 6379;

// Service to Container Mapping
$nameMap = [
    'Core Database' => 'myhr-db',
    'Redis' => 'myhr-redis',
    'Gateway' => 'myhr-gateway',
    'Portal (Main)' => 'myhr-portal',
    'Moodle LMS' => 'myhr-moodle',
    'Car Booking' => 'myhr-carbooking',
    'Yearly Activity' => 'myhr-yearlyactivity',
    'IGA Module' => 'myhr-iga',
    'Dormitory' => 'myhr-dormitory',
    'phpMyAdmin' => 'myhr-phpmyadmin',
    'Health Dashboard' => 'myhr-health'
];

// Handle Control Requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['service']) && isset($input['action'])) {
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

// 0. Define Services
$servicesDefinition = [
    'Core Database' => ['host' => 'myhr-db', 'port' => 3306, 'type' => 'db', 'link' => ':8081/?server=1'],
    'Redis' => ['host' => 'myhr-redis', 'port' => 6379, 'type' => 'tcp', 'link' => '#'],
    'Gateway' => ['host' => 'myhr-gateway', 'port' => 80, 'type' => 'tcp', 'link' => '#'],

    'Portal (Main)' => ['host' => 'myhr-portal', 'port' => 80, 'type' => 'tcp', 'link' => '/'],
    'Moodle LMS' => ['host' => 'myhr-moodle', 'port' => 80, 'type' => 'tcp', 'link' => '/moodle/'],

    'Car Booking' => ['host' => 'myhr-carbooking', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/CarBooking/'],
    'Dormitory' => ['host' => 'myhr-dormitory', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/Dormitory/'],
    'Yearly Activity' => ['host' => 'myhr-yearlyactivity', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/YearlyActivity/'],
    'IGA Module' => ['host' => 'myhr-iga', 'port' => 80, 'type' => 'tcp', 'link' => '/Modules/IGA/'],

    'phpMyAdmin' => ['host' => 'myhr-phpmyadmin', 'port' => 80, 'type' => 'tcp', 'link' => ':8081/'],
    'Health Dashboard' => ['host' => 'localhost', 'port' => 80, 'type' => 'self', 'link' => '/health/']
];

// 1. Fetch all basic container info in one bulk call
$allContainers = getAllContainersInfo();

// 2. Identify which containers are online and need resource stats
$onlineContainerNames = [];
foreach ($servicesDefinition as $name => $cfg) {
    $cName = $nameMap[$name] ?? null;
    if ($cName && isset($allContainers[$cName]) && $allContainers[$cName]['status'] === 'online') {
        $onlineContainerNames[] = $cName;
    }
}

// 3. Fetch resource stats (CPU/RAM) for all online containers in parallel
$allStats = getMultipleContainerStats($onlineContainerNames);

$serviceDetails = [];
$allOnline = true;

foreach ($servicesDefinition as $name => $cfg) {
    $cName = $nameMap[$name] ?? null;
    $containerInfo = $cName ? ($allContainers[$cName] ?? null) : null;

    if ($cfg['type'] === 'db') {
        $data = checkDatabase();
    } elseif ($cfg['type'] === 'self') {
        $data = [
            'status' => 'online', 
            'latency' => 1, 
            'uptime' => $containerInfo['uptime'] ?? 'Running', 
            'started_at' => 'Active', 
            'link' => $cfg['link']
        ];
    } else {
        $check = checkTcpStatus($cfg['host'], $cfg['port']);
        $data = array_merge($check, [
            'link' => $cfg['link'],
            'uptime' => $containerInfo['uptime'] ?? ($check['status'] === 'online' ? 'Up' : 'Offline'),
            'started_at' => $check['status'] === 'online' ? 'Running' : 'Stopped'
        ]);
    }

    // Add Container Specific Stats if available (from parallel fetch)
    if ($cName && isset($allStats[$cName])) {
        $data['container_stats'] = $allStats[$cName];
    }

    // Try to get actual startedAt using Inspect if we have containerInfo
    if ($containerInfo && $containerInfo['status'] === 'online') {
        $inspect = getContainerInspect($containerInfo['id']);
        if ($inspect && isset($inspect['State']['StartedAt'])) {
            $sAt = $inspect['State']['StartedAt'];
            // startedAt is like 2024-03-13T07:09:02.123456789Z
            $data['started_at'] = date('Y-m-d H:i', strtotime($sAt));
        }
    }

    $serviceDetails[$name] = $data;
    if ($data['status'] !== 'online') {
        $allOnline = false;
    }
}

$response = [
    'status' => $allOnline ? 'healthy' : 'degraded',
    'timestamp' => date('Y-m-d H:i:sP'),
    'system_metrics' => getSystemMetrics(),
    'services' => $serviceDetails
];

echo json_encode($response);

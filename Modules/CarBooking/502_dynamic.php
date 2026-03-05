<?php
// Modules/CarBooking/502_dynamic.php
// This script is called by Nginx when the main portal is down.

// Check if this is an API or SSE request by looking at the Original URI or Accept header
$originalUri = $_SERVER['HTTP_X_ORIGINAL_URI'] ?? $_SERVER['REQUEST_URI'];
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';

$isApiRequest = (
    strpos($originalUri, '/api/') !== false ||
    strpos($originalUri, 'routes.php') !== false ||
    strpos($originalUri, '/ajax/') !== false ||
    strpos($accept, 'application/json') !== false ||
    strpos($accept, 'text/event-stream') !== false
);

if ($isApiRequest) {
    http_response_code(503);
    if (strpos($accept, 'text/event-stream') !== false) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        echo "event: error\ndata: {\"error\": \"Gateway Timeout\"}\n\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Gateway Timeout',
            'message' => 'The system is currently undergoing maintenance. Please try again later.'
        ]);
    }
    exit;
}

require_once __DIR__ . '/../../core/Database/Database.php';

$db = new Database();
$conn = $db->getConnection();

$services = [];

if ($conn) {
    // Only fetch services that don't depend on the main portal being up,
    // or fetch all 'ready' services that are not the portal itself.
    // For now, let's fetch 'ready' services.
    $sql = "SELECT * FROM hr_services WHERE status = 'ready' ORDER BY category ASC, id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to format URL paths properly
function formatPath($path)
{
    if (empty($path) || $path === '#') return 'javascript:void(0)';
    // Convert relative paths like ../../Dormitory/ to /Modules/Dormitory/
    if (strpos($path, '../../') === 0) {
        return '/Modules/' . substr($path, 6);
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบกำลังปรับปรุง - MyHR Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <!-- Include Remix Icon if icons are in Remix syntax -->
    <link href="/assets/css/remixicon.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-4xl text-center my-8">
        <div class="mb-6">
            <svg class="mx-auto h-20 w-20 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">ระบบกำลังปิดปรับปรุง</h1>
        <p class="text-gray-600 mb-8 text-lg max-w-2xl mx-auto">
            ขณะนี้หน้าเว็บล็อกอินส่วนกลาง (Portal) อยู่ระหว่างการอัปเดตหรือปิดซ่อมบำรุงชั่วคราว <br>
            แต่คุณยังสามารถเข้าใช้งานระบบย่อยอื่นๆ ที่เปิดใช้งานได้ตามปกติด้านล่างนี้ครับ
        </p>

        <?php if (empty($services)): ?>
            <div class="p-6 bg-yellow-50 text-yellow-700 rounded-lg">
                ไม่พบข้อมูลระบบที่เปิดใช้งานได้ในขณะนี้ กรุณาลองใหม่อีกครั้งในภายหลัง
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
                <?php foreach ($services as $service):
                    // Parse translations since it's JSON
                    $nameTh = $service['name'];
                    $nameEn = $service['name'];
                    if (!empty($service['name_translations'])) {
                        $translations = json_decode($service['name_translations'], true);
                        if (is_array($translations)) {
                            $nameTh = $translations['th'] ?? $nameTh;
                            $nameEn = $translations['en'] ?? $nameEn;
                        }
                    }

                    // Exclude the portal itself and HRServices
                    if ($service['path'] === '/' || stripos($service['path'], 'HRServices') !== false) {
                        continue;
                    }

                    $finalPath = formatPath($service['path']);
                    $iconColor = !empty($service['icon_color']) ? htmlspecialchars($service['icon_color']) : '#3B82F6';
                ?>
                    <a href="<?= htmlspecialchars($finalPath) ?>"
                        class="flex items-center p-4 border-2 border-gray-100 rounded-xl transition-all duration-300 group hover:shadow-md hover:border-gray-300 hover:bg-gray-50 w-full text-left"
                        style="--hr-icon-color: <?= $iconColor ?>;">

                        <div class="mr-5 flex items-center justify-center w-12 text-4xl flex-shrink-0" style="color: <?= $iconColor ?>;">
                            <?php if (!empty($service['icon'])): ?>
                                <?php if (strpos($service['icon'], 'ri-') === 0): ?>
                                    <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                                <?php else: ?>
                                    <img src="/<?= ltrim(htmlspecialchars($service['icon']), '/') ?>" class="w-8 h-8 object-contain">
                                <?php endif; ?>
                            <?php else: ?>
                                <i class="ri-apps-line"></i>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-current transition-colors">
                                <?= htmlspecialchars($nameTh) ?>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">
                                <?= htmlspecialchars($nameEn) ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-12 pt-6 border-t border-gray-100 text-sm text-gray-400">
            <p>Error 502 / 504: Gateway Timeout</p>
            <p>กรุณาลองรีเฟรชหน้าเว็บนี้อีกครั้งในภายหลัง</p>
        </div>
    </div>
</body>

</html>
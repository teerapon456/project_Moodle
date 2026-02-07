<?php

/**
 * Car Booking - Reports View
 * Migrated to Tailwind CSS
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);
$allBookings = $controller->listAll();

// Handle Filter Parameters
$selectedMonth = $_GET['month'] ?? date('m'); // Default to current month
$selectedYear = $_GET['year'] ?? date('Y');   // Default to current year
$isFiltered = isset($_GET['filter']);

$totalBookings = 0; // Count only filtered
$approvedCount = 0;
$inUseCount = 0;
$pendingReturnCount = 0;
$rejectedCount = 0;
$pendingCount = 0;
$completedCount = 0;
$cancelledCount = 0;

// Filter and Count for Cards/Table
foreach ($allBookings as $b) {
    if ($isFiltered) {
        $bDate = strtotime($b['created_at']);
        if (date('m', $bDate) != $selectedMonth || date('Y', $bDate) != $selectedYear) {
            continue;
        }
    }

    $totalBookings++; // Increment filtered total

    switch ($b['status']) {
        case 'approved':
            $approvedCount++;
            break;
        case 'in_use':
            $inUseCount++;
            break;
        case 'pending_return':
            $pendingReturnCount++;
            break;
        case 'rejected_supervisor':
        case 'rejected_manager':
            $rejectedCount++;
            break;
        case 'completed':
            $completedCount++;
            break;
        case 'pending_supervisor':
        case 'pending_manager':
            $pendingCount++;
            break;
        case 'cancelled':
        case 'revoked':
            $cancelledCount++;
            break;
    }
}

// Monthly Stats Logic (Show 6 months ending at selected date)
$monthlyStats = [];
$targetDate = new DateTime("$selectedYear-$selectedMonth-01");
for ($i = 5; $i >= 0; $i--) {
    $d = clone $targetDate;
    $d->modify("-{$i} months");
    $monthKey = $d->format('Y-m');
    $monthlyStats[$monthKey] = ['label' => $d->format('M Y'), 'count' => 0];
}

foreach ($allBookings as $b) {
    $bookingMonth = date('Y-m', strtotime($b['created_at']));
    if (isset($monthlyStats[$bookingMonth])) $monthlyStats[$bookingMonth]['count']++;
}

$thaiMonths = ['01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'];
?>

<!-- Export Section -->
<!-- Filter & Export Section -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-filter-3-line text-primary"></i>
            กรองข้อมูลและส่งออก
        </h3>
        <button onclick="exportExcel()" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors text-sm">
            <i class="ri-file-excel-2-line"></i> Export Excel
        </button>
    </div>
    <div class="p-6">
        <form method="GET" class="flex flex-wrap items-end gap-6">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="filter" value="1">

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">เลือกเดือน</label>
                <div class="relative">
                    <i class="ri-calendar-2-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <select name="month" id="exportMonth" onchange="this.form.submit()" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer bg-white">
                        <?php foreach ($thaiMonths as $mKey => $mName): ?>
                            <option value="<?= $mKey ?>" <?= $mKey == $selectedMonth ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">เลือกปี</label>
                <div class="relative">
                    <i class="ri-calendar-event-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <select name="year" id="exportYear" onchange="this.form.submit()" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer bg-white">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y + 543 ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Hidden filter trigger for noscript -->
                <noscript>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">ค้นหา</button>
                </noscript>
        </form>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <div class="bg-white border-l-4 border-primary rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-primary text-2xl mb-3">
            <i class="ri-file-list-3-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900"><?= $totalBookings ?></div>
        <div class="text-gray-500 text-sm">คำขอทั้งหมด</div>
    </div>
    <div class="bg-white border-l-4 border-success rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-success text-2xl mb-3">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900"><?= $approvedCount ?></div>
        <div class="text-gray-500 text-sm">อนุมัติ</div>
    </div>
    <div class="bg-white border-l-4 border-danger rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center text-danger text-2xl mb-3">
            <i class="ri-close-circle-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900"><?= $rejectedCount ?></div>
        <div class="text-gray-500 text-sm">ปฏิเสธ</div>
    </div>
    <div class="bg-white border-l-4 border-info rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-2xl mb-3">
            <i class="ri-flag-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900"><?= $completedCount ?></div>
        <div class="text-gray-500 text-sm">เสร็จสิ้น</div>
    </div>
</div>

<!-- Report Cards -->
<div class="grid md:grid-cols-2 gap-6 mb-6">
    <!-- Monthly Trend -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h4 class="flex items-center gap-2 font-semibold text-gray-900 mb-4">
            <i class="ri-line-chart-line text-primary"></i> คำขอรายเดือน
        </h4>
        <div class="flex items-end gap-2 h-40 pt-4">
            <?php
            $maxCount = max(array_column($monthlyStats, 'count')) ?: 1;
            foreach ($monthlyStats as $stat):
                $height = ($stat['count'] / $maxCount * 100);
            ?>
                <div class="flex-1 flex flex-col items-center">
                    <span class="text-xs font-semibold text-gray-700 mb-1"><?= $stat['count'] ?></span>
                    <div class="w-full bg-gradient-to-t from-primary to-primary-light rounded-t" style="height: <?= max(4, $height) ?>%"></div>
                    <span class="text-[10px] text-gray-500 mt-2 text-center"><?= $stat['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h4 class="flex items-center gap-2 font-semibold text-gray-900 mb-4">
            <i class="ri-pie-chart-line text-primary"></i> สถานะคำขอ
        </h4>
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="flex-1 text-gray-600">อนุมัติแล้ว</span>
                <span class="font-semibold text-gray-900"><?= $approvedCount ?></span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $totalBookings > 0 ? ($approvedCount / $totalBookings * 100) : 0 ?>%"></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex-1 text-gray-600">รออนุมัติ</span>
                <span class="font-semibold text-gray-900"><?= $pendingCount ?></span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full" style="width: <?= $totalBookings > 0 ? ($pendingCount / $totalBookings * 100) : 0 ?>%"></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex-1 text-gray-600">ปฏิเสธ</span>
                <span class="font-semibold text-gray-900"><?= $rejectedCount ?></span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500 rounded-full" style="width: <?= $totalBookings > 0 ? ($rejectedCount / $totalBookings * 100) : 0 ?>%"></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex-1 text-gray-600">เสร็จสิ้น</span>
                <span class="font-semibold text-gray-900"><?= $completedCount ?></span>
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: <?= $totalBookings > 0 ? ($completedCount / $totalBookings * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-bar-chart-box-line text-primary"></i> สรุปข้อมูล
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หัวข้อ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เปอร์เซ็นต์</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">อัตราการอนุมัติ</td>
                    <td class="px-4 py-3 text-gray-900"><?= $approvedCount + $completedCount ?> / <?= $totalBookings ?></td>
                    <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"><?= $totalBookings > 0 ? round(($approvedCount + $completedCount) / $totalBookings * 100, 1) : 0 ?>%</span></td>
                </tr>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">อัตราการปฏิเสธ</td>
                    <td class="px-4 py-3 text-gray-900"><?= $rejectedCount ?> / <?= $totalBookings ?></td>
                    <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><?= $totalBookings > 0 ? round($rejectedCount / $totalBookings * 100, 1) : 0 ?>%</span></td>
                </tr>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600">คำขอรอดำเนินการ</td>
                    <td class="px-4 py-3 text-gray-900"><?= $pendingCount ?></td>
                    <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800"><?= $totalBookings > 0 ? round($pendingCount / $totalBookings * 100, 1) : 0 ?>%</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function exportExcel() {
        const month = document.getElementById('exportMonth').value;
        const year = document.getElementById('exportYear').value;
        window.location.href = `${API_BASE}?controller=reports&action=exportExcel&month=${month}&year=${year}`;
    }
</script>
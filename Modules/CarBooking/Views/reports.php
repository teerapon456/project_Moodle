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
$revokedCount = 0;

// SLA Calculation Tracking
$slaTotalSeconds = 0;
$slaCount = 0;

// Filter and Count for Cards/Table
foreach ($allBookings as $b) {
    if ($isFiltered) {
        $bDate = strtotime($b['start_time']);
        if (date('m', $bDate) != $selectedMonth || date('Y', $bDate) != $selectedYear) {
            continue;
        }
    }

    $totalBookings++; // Increment filtered total

    switch ($b['status']) {
        case 'approved':
        case 'in_use':
        case 'pending_return':
            $approvedCount++;
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

    // SLA Calculation: Time from supervisor_approved_at to manager_approved_at (IPCD processing time)
    if (!empty($b['manager_approved_at']) && !empty($b['supervisor_approved_at'])) {
        $supApproved = strtotime($b['supervisor_approved_at']);
        $mgrApproved = strtotime($b['manager_approved_at']);
        if ($mgrApproved > $supApproved) {
            $slaTotalSeconds += ($mgrApproved - $supApproved);
            $slaCount++;
        }
    }
}

// Calculate Average SLA (in hours)
$avgSlaHours = 0;
if ($slaCount > 0) {
    $avgSlaHours = round($slaTotalSeconds / $slaCount / 3600, 1);
}

// Monthly Stats Logic (Show 6 months ending at selected date)
$monthlyStats = [];
$targetDate = new DateTime("$selectedYear-$selectedMonth-01");
for ($i = 5; $i >= 0; $i--) {
    $d = clone $targetDate;
    $d->modify("-{$i} months");
    $monthKey = $d->format('Y-m');
    $monthlyStats[$monthKey] = [
        'label' => $d->format('M Y'),
        'count' => 0,
        'completed' => 0
    ];
}

foreach ($allBookings as $b) {
    $bookingMonth = date('Y-m', strtotime($b['start_time']));
    if (isset($monthlyStats[$bookingMonth])) {
        $monthlyStats[$bookingMonth]['count']++;
        if ($b['status'] === 'completed') {
            $monthlyStats[$bookingMonth]['completed']++;
        }
    }
}

$thaiMonths = ['01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'];
?>

<!-- Export Section -->
<!-- Filter & Export Section -->
<div class="mb-8">
    <div class="flex items-center justify-between px-2 py-4">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-filter-3-line text-primary"></i>
            กรองข้อมูลและส่งออก
        </h3>
        <button onclick="exportExcel()" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors text-sm">
            <i class="ri-file-excel-2-line"></i> Export Excel
        </button>
    </div>
    <form method="GET" class="flex flex-wrap items-end gap-6 mt-2">
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
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 text-xl group-hover:scale-110 transition-transform">
                <i class="ri-file-list-3-line"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $totalBookings ?></div>
        <div class="text-gray-400 text-xs mt-0.5">คำขอทั้งหมด</div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-500 text-xl group-hover:scale-110 transition-transform">
                <i class="ri-checkbox-circle-line"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400">Approved</span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $approvedCount ?></div>
        <div class="text-gray-400 text-xs mt-0.5">อนุมัติ</div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center text-rose-500 text-xl group-hover:scale-110 transition-transform">
                <i class="ri-close-circle-line"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-rose-400">Rejected</span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $rejectedCount ?></div>
        <div class="text-gray-400 text-xs mt-0.5">ปฏิเสธ</div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center text-sky-500 text-xl group-hover:scale-110 transition-transform">
                <i class="ri-flag-line"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-sky-400">Done</span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $completedCount ?></div>
        <div class="text-gray-400 text-xs mt-0.5">เสร็จสิ้น</div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 hover:shadow-md transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-500 text-xl group-hover:scale-110 transition-transform">
                <i class="ri-timer-line"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-400">SLA</span>
        </div>
        <div class="text-2xl font-bold text-gray-900"><?= $avgSlaHours ?> <span class="text-xs font-normal text-gray-400">ชม.</span></div>
        <div class="text-gray-400 text-xs mt-0.5">เฉลี่ยเวลาอนุมัติ</div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<!-- Report Cards -->
<div class="space-y-6 mb-8">
    <!-- Monthly Trend (Area Chart) — Full Width -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
            <h4 class="flex items-center gap-2 font-semibold text-gray-800 text-sm">
                <i class="ri-line-chart-line text-primary"></i> แนวโน้มการจองรายเดือน
            </h4>
            <div class="flex items-center gap-4 text-[10px] font-medium text-gray-400">
                <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background: rgba(99,102,241,.5)"></span> คำขอทั้งหมด</div>
                <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> เสร็จสิ้น</div>
            </div>
        </div>
        <div class="p-6">
            <canvas id="trendChart" height="180"></canvas>
        </div>
    </div>

    <!-- Status & Summary Combined -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
            <h4 class="flex items-center gap-2 font-semibold text-gray-800 text-sm">
                <i class="ri-bar-chart-box-line text-primary"></i> สถานะและสรุปประสิทธิภาพ
            </h4>
        </div>
        <div class="grid md:grid-cols-2 divide-x divide-gray-100">
            <!-- Left: Status Breakdown -->
            <div class="p-6 space-y-4">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">สถานะคำขอ</h5>
                <?php
                $statusItems = [
                    ['label' => 'อนุมัติแล้ว', 'count' => $approvedCount, 'color' => 'emerald', 'icon' => 'ri-checkbox-circle-fill'],
                    ['label' => 'รออนุมัติ',   'count' => $pendingCount,  'color' => 'amber',   'icon' => 'ri-time-fill'],
                    ['label' => 'ปฏิเสธ',     'count' => $rejectedCount, 'color' => 'rose',    'icon' => 'ri-close-circle-fill'],
                    ['label' => 'เสร็จสิ้น',   'count' => $completedCount, 'color' => 'sky',     'icon' => 'ri-flag-fill'],
                    ['label' => 'ยกเลิก',     'count' => $cancelledCount, 'color' => 'gray',    'icon' => 'ri-forbid-fill'],
                ];
                foreach ($statusItems as $si):
                    $pct = $totalBookings > 0 ? round($si['count'] / $totalBookings * 100) : 0;
                ?>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="<?= $si['icon'] ?> text-<?= $si['color'] ?>-400 text-xs"></i>
                                <?= $si['label'] ?>
                            </div>
                            <span class="text-sm font-bold text-gray-800"><?= $si['count'] ?></span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-<?= $si['color'] ?>-400 rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Right: Summary Table -->
            <div class="p-6">
                <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">ตัวชี้วัด</h5>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">อัตราการอนุมัติ</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800"><?= $approvedCount + $completedCount ?><span class="text-gray-400 font-normal">/<?= $totalBookings ?></span></span>
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                <i class="ri-arrow-up-s-fill"></i><?= $totalBookings > 0 ? round(($approvedCount + $completedCount) / $totalBookings * 100, 1) : 0 ?>%
                            </span>
                        </div>
                    </div>
                    <div class="border-t border-gray-50"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">อัตราการปฏิเสธ</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800"><?= $rejectedCount ?><span class="text-gray-400 font-normal">/<?= $totalBookings ?></span></span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700">
                                <?= $totalBookings > 0 ? round($rejectedCount / $totalBookings * 100, 1) : 0 ?>%
                            </span>
                        </div>
                    </div>
                    <div class="border-t border-gray-50"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">รอดำเนินการ</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800"><?= $pendingCount ?></span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                                <?= $totalBookings > 0 ? round($pendingCount / $totalBookings * 100, 1) : 0 ?>%
                            </span>
                        </div>
                    </div>
                    <div class="border-t border-gray-50"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">SLA เฉลี่ย</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800"><?= $avgSlaHours ?> ชม. <span class="text-gray-400 font-normal text-xs">(<?= $slaCount ?>)</span></span>
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $avgSlaHours <= 24 ? 'bg-emerald-50 text-emerald-700' : ($avgSlaHours <= 48 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') ?>">
                                <i class="ri-timer-line"></i><?= $avgSlaHours <= 24 ? 'ดี' : ($avgSlaHours <= 48 ? 'ปานกลาง' : 'ช้า') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function exportExcel() {
        const month = document.getElementById('exportMonth').value;
        const year = document.getElementById('exportYear').value;
        window.location.href = `${API_BASE}?controller=reports&action=exportExcel&month=${month}&year=${year}`;
    }

    // Area Chart with Chart.js
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;

        const labels = <?= json_encode(array_column($monthlyStats, 'label')) ?>;
        const totalData = <?= json_encode(array_values(array_column($monthlyStats, 'count'))) ?>;
        const completedData = <?= json_encode(array_values(array_column($monthlyStats, 'completed'))) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'คำขอทั้งหมด',
                        data: totalData,
                        borderColor: 'rgba(99, 102, 241, 0.6)',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2,
                        borderDash: [5, 3],
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(99, 102, 241, 0.8)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'เสร็จสิ้น',
                        data: completedData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: {
                            size: 12,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 11
                        },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: '#94a3b8'
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(241, 245, 249, 0.8)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: '#94a3b8',
                            stepSize: 1,
                            padding: 8
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
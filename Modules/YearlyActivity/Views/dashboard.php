<?php
require_once __DIR__ . '/../Controllers/DashboardController.php';

$controller = new DashboardController();
$data = $controller->overview();
$stats = $data['stats'];
$upcoming = $data['upcoming'];
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-xl sm:rounded-2xl p-4 sm:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-bold mb-2">My Overview</h1>
            <p class="text-indigo-100 text-lg">Here's what's happening across all your calendars.</p>
        </div>
        <div class="absolute right-0 top-0 h-full w-1/3 bg-white/10 skew-x-12 translate-x-12"></div>
        <div class="absolute right-20 bottom-[-50px] w-64 h-64 bg-purple-500/30 rounded-full blur-3xl"></div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-4 sm:p-6 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 sm:gap-4">
            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl sm:text-2xl">
                <i class="ri-calendar-check-line"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-medium">Active Calendars</p>
                <h3 class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $stats['calendars'] ?></h3>
            </div>
        </div>
        <div class="bg-white p-4 sm:p-6 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 sm:gap-4">
            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-xl sm:text-2xl">
                <i class="ri-task-line"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Activities</p>
                <h3 class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $stats['activities'] ?></h3>
            </div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Chart -->
        <div class="bg-white p-4 sm:p-6 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4">Activity Status</h3>
            <div class="relative h-48 sm:h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Monthly Chart -->
        <div class="bg-white p-4 sm:p-6 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 mb-4">Monthly Workload</h3>
            <div class="relative h-48 sm:h-64">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data from PHP (Safe Defaults)
            const statusData = <?= !empty($data['analytics']['status_distribution']) ? json_encode($data['analytics']['status_distribution']) : '{}' ?>;
            const workloadData = <?= !empty($data['analytics']['workload_by_calendar']) ? json_encode($data['analytics']['workload_by_calendar']) : '[]' ?>;

            // Status Chart
            const ctxStatus = document.getElementById('statusChart').getContext('2d');
            const statusKeys = Object.keys(statusData);

            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusKeys.length > 0 ? statusKeys.map(s => s.charAt(0).toUpperCase() + s.slice(1)) : ['No Data'],
                    datasets: [{
                        data: statusKeys.length > 0 ? Object.values(statusData) : [1],
                        backgroundColor: statusKeys.length > 0 ? [
                            '#10b981', '#f59e0b', '#3b82f6', '#6366f1', '#8b5cf6', '#ef4444'
                        ] : ['#e5e7eb'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: window.innerWidth < 640 ? 'bottom' : 'right'
                        }
                    }
                }
            });

            // Monthly Workload Chart (Stacked by Calendar)
            let datasets = [];
            const calendarNames = Array.isArray(workloadData) ? [] : Object.keys(workloadData); // Check if object or empty array

            if (calendarNames.length > 0) {
                datasets = calendarNames.map((name, index) => {
                    const colors = ['#6366f1', '#ec4899', '#8b5cf6', '#10b981', '#f59e0b', '#3b82f6', '#ef4444'];
                    const color = colors[index % colors.length];
                    return {
                        label: name,
                        data: workloadData[name], // Already array [0,0,0...]
                        backgroundColor: color,
                        borderRadius: 4
                    };
                });
            } else {
                // Empty state
                datasets = [{
                    label: 'No Data',
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: '#e5e7eb'
                }];
            }

            const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
            new Chart(ctxMonthly, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                footer: (items) => {
                                    const total = items.reduce((a, b) => a + b.parsed.y, 0);
                                    return 'Total: ' + total;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <!-- Upcoming Activities -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Use-Wide Upcoming Activities</h2>
            <a href="?page=my_calendars" class="text-indigo-600 text-sm font-medium hover:underline">View All Calendars</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php
            // Fetch Settings using dynamic path check
            $settingsPath = __DIR__ . '/../Controllers/SettingsController.php';
            if (file_exists($settingsPath)) {
                require_once $settingsPath;
                $settingsCtrl = new SettingsController();
                $mySettings = $settingsCtrl->getSettings();
                $isCompact = ($mySettings['compact_view'] ?? '0') === '1';
                $limit = (int)($mySettings['dashboard_limit'] ?? 5);
            } else {
                $isCompact = false;
                $limit = 5;
            }

            // Define compact styles
            $paddingClass = $isCompact ? 'p-3' : 'p-6';
            $dateBoxSize = $isCompact ? 'w-10 p-1 scale-90 origin-top' : 'w-16 p-2';
            $titleSize = $isCompact ? 'text-base' : 'text-lg';
            ?>
            <?php if (empty($upcoming)): ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="ri-cup-line text-4xl mb-3 block"></i>
                    No upcoming activities found in any of your calendars.
                </div>
            <?php else: ?>
                <?php foreach (array_slice($upcoming, 0, $limit) as $act):
                    $start = strtotime($act['start_date']);
                    $isSoon = ($start - time()) < (60 * 60 * 24 * 7); // Within 7 days
                ?>
                    <div class="<?php echo $paddingClass; ?> hover:bg-gray-50 transition flex flex-col md:flex-row gap-4 items-start md:items-center">
                        <div class="flex-shrink-0 <?php echo $dateBoxSize; ?> text-center bg-gray-50 rounded-lg border border-gray-200">
                            <div class="text-xs text-gray-500 uppercase tracking-wide"><?= date('M', $start) ?></div>
                            <div class="text-xl font-bold text-gray-800"><?= date('j', $start) ?></div>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 <?php echo $titleSize; ?>">
                                <a href="?page=activity_detail&id=<?= $act['id'] ?>" class="hover:text-indigo-600"><?= htmlspecialchars($act['name']) ?></a>
                                <?php if ($isSoon): ?>
                                    <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full font-bold uppercase">Soon</span>
                                <?php endif; ?>
                            </h4>
                            <div class="flex items-center gap-4 mt-1 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i class="ri-calendar-line"></i> <?= htmlspecialchars($act['calendar_name']) ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="ri-map-pin-line"></i> <?= htmlspecialchars($act['location'] ?? 'No Location') ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="?page=activity_detail&id=<?= $act['id'] ?>" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
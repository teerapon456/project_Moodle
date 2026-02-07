<?php
// Modules/YearlyActivity/Views/calendar_view.php
// Variables available: $calendar, $activities

$role = $calendar['user_role'] ?? 'viewer';
$canEdit = in_array($role, ['owner', 'admin', 'editor']);
?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4 sm:mb-8">
    <div>
        <div class="flex items-center gap-3">
            <a href="?page=dashboard" class="text-gray-400 hover:text-indigo-600 transition">
                <i class="ri-arrow-left-line text-xl"></i>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800"><?= htmlspecialchars($calendar['name']) ?></h1>
            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-semibold rounded-full border border-indigo-100">
                <?= $calendar['year'] ?>
            </span>
        </div>
        <p class="text-gray-500 text-sm mt-1 ml-8">
            <span class="capitalize font-medium text-gray-700"><?= $role ?></span> Access
            &bull; <?= count($activities) ?> Activities
        </p>
        <?php if (!empty($calendar['description'])): ?>
            <p class="text-gray-500 text-sm mt-2 ml-8 max-w-2xl text-justify border-l-2 border-gray-200 pl-3">
                <?= nl2br(htmlspecialchars($calendar['description'])) ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="flex gap-2">
        <?php if (in_array($role, ['owner', 'admin'])): ?>
            <a href="?page=calendar_settings&id=<?= $calendar['id'] ?>" class="px-4 py-2 bg-white text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition text-sm font-medium flex items-center gap-2">
                <i class="ri-settings-3-line"></i> Settings
            </a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
            <div class="flex items-center gap-3">
                <button onclick="promptImportExcel()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition shadow-sm flex items-center gap-2">
                    <i class="ri-file-excel-2-line"></i> Import Excel
                </button>
                <a href="?page=activity_wizard&step=1&calendar_id=<?= $calendar['id'] ?>" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition shadow-sm flex items-center gap-2">
                    <i class="ri-add-line"></i> Add Activity
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Tabs -->
<?php
// Fetch Settings for Default Tab
require_once __DIR__ . '/../Controllers/SettingsController.php';
$settingsCtrl = new SettingsController();
$mySettings = $settingsCtrl->getSettings();
$defaultTab = $mySettings['default_tab'] ?? 'list';
?>
<div class="mb-4 sm:mb-6 border-b border-gray-200">
    <nav class="flex gap-2 sm:gap-6 overflow-x-auto scrollbar-hide pb-1">
        <button onclick="switchView('list')" id="btn-list" class="view-tab px-1 py-3 text-sm font-bold text-indigo-600 border-b-2 border-indigo-600">
            <i class="ri-list-check mr-1"></i> List View
        </button>
        <button onclick="switchView('timeline')" id="btn-timeline" class="view-tab px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700">
            <i class="ri-calendar-2-line mr-1"></i> Timeline (Gantt)
        </button>
        <button onclick="switchView('rasci')" id="btn-rasci" class="view-tab px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700">
            <i class="ri-grid-line mr-1"></i> RASCI Matrix
        </button>
        <button onclick="switchView('risks')" id="btn-risks" class="view-tab px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700">
            <i class="ri-alert-line mr-1"></i> Risks
        </button>
    </nav>
</div>

<!-- List View -->
<div id="view-list" class="view-content block">
    <?php if (empty($activities)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-list-check text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">No Activities Yet</h3>
            <p class="text-gray-500 mb-6">Start planning your year by adding your first activity.</p>
            <?php if ($canEdit): ?>
                <a href="?page=activity_wizard&step=1&calendar_id=<?= $calendar['id'] ?>" class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition font-medium">Add Activity</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Filters -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6 flex gap-4 overflow-x-auto">
            <button class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-medium shadow-sm whitespace-nowrap">All Activities</button>
            <button class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap">Milestones</button>
            <button class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap">Upcoming</button>
            <button class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-medium whitespace-nowrap">Completed</button>
        </div>

        <div class="space-y-4">
            <?php foreach ($activities as $act):
                $statusColors = [
                    'proposed' => 'bg-gray-100 text-gray-600 border border-gray-200',
                    'planned' => 'bg-blue-50 text-blue-700 border border-blue-100',
                    'incoming' => 'bg-indigo-50 text-indigo-700 border border-indigo-100 ring-2 ring-indigo-500/20',
                    'in_progress' => 'bg-amber-50 text-amber-700 border border-amber-100',
                    'on_hold' => 'bg-rose-50 text-rose-700 border border-rose-100', // Paused
                    'completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                    'cancelled' => 'bg-slate-100 text-slate-500 border border-slate-200 decoration-line-through'
                ];
                $status = $act['status'] ?? 'planned';
                $statusClass = $statusColors[$status] ?? 'bg-gray-50 text-gray-600';

                // Icon for status
                $statusIcons = [
                    'proposed' => 'ri-lightbulb-line',
                    'planned' => 'ri-calendar-line',
                    'incoming' => 'ri-run-line',
                    'in_progress' => 'ri-loader-4-line animate-spin-slow',
                    'on_hold' => 'ri-pause-circle-line',
                    'completed' => 'ri-checkbox-circle-line',
                    'cancelled' => 'ri-close-circle-line'
                ];
                $statusIcon = $statusIcons[$status] ?? 'ri-checkbox-blank-circle-line';
            ?>
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition group relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-l-xl"></div>
                    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                        <div class="flex-shrink-0 w-16 text-center bg-gray-50 rounded-lg p-2 border border-gray-200">
                            <?php if (!empty($act['start_date'])): ?>
                                <div class="text-xs text-gray-500 uppercase tracking-wide"><?= date('M', strtotime($act['start_date'])) ?></div>
                                <div class="text-xl font-bold text-gray-800"><?= date('j', strtotime($act['start_date'])) ?></div>
                            <?php else: ?>
                                <div class="text-xs text-gray-400">TBD</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-lg font-bold text-gray-800 truncate"><?= htmlspecialchars($act['name']) ?></h3>
                                <div class="px-2 py-0.5 rounded-lg text-xs font-semibold uppercase flex items-center gap-1 <?= $statusClass ?>">
                                    <i class="<?= $statusIcon ?>"></i>
                                    <?= str_replace('_', ' ', $status) ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1"><i class="ri-flag-line"></i> <?= htmlspecialchars($act['type'] ?? 'General') ?></span>
                                <span class="flex items-center gap-1"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($act['location'] ?? 'No Location') ?></span>
                                <span class="flex items-center gap-1"><i class="ri-user-line"></i> <?= htmlspecialchars($act['created_by_name'] ?? 'User') ?></span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500 font-medium">Progress</span>
                                    <span class="text-indigo-600 font-bold"><?= $act['progress'] ?>%</span>
                                </div>
                                <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: <?= $act['progress'] ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mt-4 md:mt-0">
                            <div class="text-right hidden md:block">
                                <div class="text-xs text-gray-400 uppercase font-semibold">Milestones</div>
                                <div class="font-bold text-gray-800 text-lg"><?= $act['milestone_count'] ?? 0 ?></div>
                            </div>
                            <div class="h-8 w-px bg-gray-200 hidden md:block"></div>
                            <div class="flex gap-2">
                                <a href="?page=activity_detail&id=<?= $act['id'] ?>" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"><i class="ri-eye-line text-lg"></i></a>
                                <?php if ($canEdit): ?>
                                    <a href="?page=activity_wizard&step=1&id=<?= $act['id'] ?>&calendar_id=<?= $calendar['id'] ?>" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"><i class="ri-edit-line text-lg"></i></a>
                                    <button onclick="confirmDeleteActivity(<?= $act['id'] ?>)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"><i class="ri-delete-bin-line text-lg"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Timeline View -->
<div id="view-timeline" class="view-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="timeline-scroll-container overflow-x-auto">
            <div class="min-w-[800px] lg:min-w-0 p-4 sm:p-6">
                <!-- Months Header -->
                <div class="flex w-full gap-0 mb-4 border-b border-gray-100 pb-2">
                    <div class="w-[25%] shrink-0 text-xs font-bold text-gray-400 uppercase">Activity</div>
                    <div class="w-[75%] flex w-full">
                        <?php
                        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        foreach ($months as $m):
                        ?>
                            <div class="flex-1 text-[10px] md:text-xs font-bold text-gray-400 uppercase text-center border-l border-gray-100 first:border-0 py-1"><?= $m ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Grid Lines Background (Absolute) -->
                <!-- We'll simulate grid via col-span logic in rows -->

                <!-- Activities Rows -->
                <div class="space-y-3">
                    <?php foreach ($activities as $act):
                        if (empty($act['start_date']) || empty($act['end_date'])) continue;
                        $start = strtotime($act['start_date']);
                        $end = strtotime($act['end_date']);
                        $yearStart = strtotime($calendar['year'] . '-01-01');
                        $yearEnd = strtotime($calendar['year'] . '-12-31');

                        // Calculate position %
                        $totalSeconds = $yearEnd - $yearStart;
                        $startOffset = max(0, $start - $yearStart);
                        $duration = $end - $start;

                        $leftPercent = ($startOffset / $totalSeconds) * 100;
                        $widthPercent = ($duration / $totalSeconds) * 100;
                        // Min width 1%
                        $widthPercent = max(1, $widthPercent);
                    ?>
                        <div class="flex w-full gap-0 items-center hover:bg-gray-50 p-2 rounded-lg transition group border-b border-gray-50 last:border-0 relative">
                            <!-- Activity Name Column -->
                            <div class="w-[25%] shrink-0 min-w-0 pr-4 border-r border-gray-100">
                                <div class="font-medium text-gray-800 truncate" title="<?= htmlspecialchars($act['name']) ?>">
                                    <?= htmlspecialchars($act['name']) ?>
                                </div>
                                <div class="text-xs text-gray-400 truncate">
                                    <?= date('d M', $start) ?> - <?= date('d M', $end) ?>
                                </div>
                            </div>

                            <!-- Timeline Bar Column -->
                            <div class="w-[75%] relative h-10 w-full">
                                <!-- Background Grid Lines -->
                                <div class="absolute inset-0 flex w-full h-full pointer-events-none">
                                    <?php for ($i = 0; $i < 12; $i++): ?>
                                        <div class="flex-1 border-l border-gray-100 h-full first:border-0"></div>
                                    <?php endfor; ?>
                                </div>

                                <!-- Activity Bar -->
                                <div class="absolute top-2 bottom-2 bg-indigo-500 rounded-md shadow-sm border border-indigo-600 opacity-80 group-hover:opacity-100 transition z-10"
                                    style="left: <?= $leftPercent ?>%; width: <?= $widthPercent ?>%;"
                                    title="<?= htmlspecialchars($act['name']) ?> (<?= date('d M', $start) ?> - <?= date('d M', $end) ?>)">
                                </div>
                            </div>

                            <a href="?page=activity_detail&id=<?= $act['id'] ?>" class="absolute inset-0 z-20"></a>

                            <!-- Tooltip -->
                            <div class="hidden group-hover:block absolute left-0 bottom-full mb-2 bg-gray-900 text-white text-xs p-2 rounded z-50 whitespace-nowrap">
                                <?= $act['name'] ?> (<?= date('M j', $start) ?> - <?= date('M j', $end) ?>)
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                        <div class="text-center py-10 text-gray-400">No scheduled activities to display.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RASCI View -->
<div id="view-rasci" class="view-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden text-sm">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">RASCI Responsibility Matrix</h3>
            <p class="text-xs text-gray-500">Row: Activity > Milestone | Column: User Role (R=Responsible, A=Accountable, S=Support, C=Consulted, I=Informed)</p>
        </div>

        <?php
        // Pivot Logic
        $matrix = [];
        $allUsers = [];

        foreach ($allRasci as $row) {
            $actName = $row['activity_name'];
            $msName = $row['milestone_name'];
            $userId = $row['user_id']; // Use ID for mapping, but we need name for column header
            $userName = $row['fullname'];
            $role = $row['role'];

            // Collect Users (Unique)
            if (!isset($allUsers[$userId])) {
                $allUsers[$userId] = [
                    'id' => $userId,
                    'name' => $userName,
                    'initials' => strtoupper(substr($userName, 0, 2))
                ];
            }

            // Build Matrix
            if (!isset($matrix[$actName])) {
                $matrix[$actName] = [];
            }
            if (!isset($matrix[$actName][$msName])) {
                $matrix[$actName][$msName] = [];
            }
            $matrix[$actName][$msName][$userId] = $role;
        }

        // Sort users alphabetically for consistent columns
        usort($allUsers, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $roleColors = [
            'R' => 'bg-red-100 text-red-700',
            'A' => 'bg-blue-100 text-blue-700',
            'S' => 'bg-green-100 text-green-700',
            'C' => 'bg-yellow-100 text-yellow-700',
            'I' => 'bg-purple-100 text-purple-700'
        ];
        ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-max">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500">
                        <th class="px-4 py-3 font-bold border-r border-gray-100 sticky left-0 bg-gray-50 z-10 w-64">Activity</th>
                        <th class="px-4 py-3 font-bold border-r border-gray-100 sticky left-64 bg-gray-50 z-10 w-48">Milestone</th>
                        <?php foreach ($allUsers as $u): ?>
                            <th class="px-2 py-3 font-bold text-center border-r border-gray-100 w-16" title="<?= htmlspecialchars($u['name']) ?>">
                                <div class="flex flex-col items-center">
                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold mb-1">
                                        <?= $u['initials'] ?>
                                    </span>
                                    <span class="truncate w-14 text-[10px]"><?= htmlspecialchars(explode(' ', $u['name'])[0]) ?></span>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($matrix)): ?>
                        <tr>
                            <td colspan="<?= count($allUsers) + 2 ?>" class="px-6 py-12 text-center text-gray-400">
                                <i class="ri-table-line text-4xl mb-2 opacity-50 block"></i>
                                No RASCI data available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matrix as $actName => $milestones): ?>
                            <?php
                            $msCount = count($milestones);
                            $firstMs = true;
                            foreach ($milestones as $msName => $userRoles):
                            ?>
                                <tr class="hover:bg-gray-50 transition border-b border-gray-100 group">
                                    <?php if ($firstMs): ?>
                                        <td class="px-4 py-3 font-bold text-gray-800 border-r border-gray-100 sticky left-0 bg-white group-hover:bg-gray-50 z-10 align-top" rowspan="<?= $msCount ?>">
                                            <div class="line-clamp-2" title="<?= htmlspecialchars($actName) ?>"><?= htmlspecialchars($actName) ?></div>
                                        </td>
                                    <?php endif; ?>

                                    <td class="px-4 py-3 text-gray-600 border-r border-gray-100 sticky left-64 bg-white group-hover:bg-gray-50 z-10">
                                        <div class="line-clamp-2 text-xs" title="<?= htmlspecialchars($msName) ?>"><?= htmlspecialchars($msName) ?></div>
                                    </td>

                                    <?php foreach ($allUsers as $u): ?>
                                        <td class="px-1 py-3 text-center border-r border-gray-100">
                                            <?php if (isset($userRoles[$u['id']])):
                                                $r = $userRoles[$u['id']];
                                            ?>
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded text-xs font-bold <?= $roleColors[$r] ?? 'bg-gray-100' ?>" title="<?= $r ?>">
                                                    <?= $r ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php $firstMs = false; ?>
                            <?php endforeach; ?>
                            <!-- Divider between Activities -->
                            <tr class="bg-gray-100 h-1">
                                <td colspan="<?= count($allUsers) + 2 ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Risks View (Heatmap Matrix) -->
<div id="view-risks" class="view-content hidden">

    <?php
    // Prepare Data for Matrix
    $riskMatrix = [];
    // Initialize 5x5 grid
    for ($p = 5; $p >= 1; $p--) {
        for ($i = 1; $i <= 5; $i++) {
            $riskMatrix[$p][$i] = [];
        }
    }

    // Populate
    if (!empty($allRisks)) {
        foreach ($allRisks as $risk) {
            $p = (int)$risk['probability'];
            $i = (int)$risk['impact'];
            // Clamp 1-5 just in case
            $p = max(1, min(5, $p));
            $i = max(1, min(5, $i));

            $riskMatrix[$p][$i][] = $risk;
        }
    }

    // Helper for Cell Color
    function getRiskClass($prob, $imp, $hasData)
    {
        $score = $prob * $imp;
        if ($score >= 15) return $hasData ? 'bg-red-500 text-white shadow-md scale-105 z-10' : 'bg-red-50';
        if ($score >= 10) return $hasData ? 'bg-orange-500 text-white shadow-md scale-105 z-10' : 'bg-orange-50';
        if ($score >= 5)  return $hasData ? 'bg-yellow-400 text-black shadow-md scale-105 z-10' : 'bg-yellow-50';
        return $hasData ? 'bg-green-500 text-white shadow-md scale-105 z-10' : 'bg-green-50';
    }
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Input: Matrix -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-800 mb-4">Risk Heatmap</h3>

                <div class="relative pl-8">
                    <!-- Y Label -->
                    <div class="absolute left-0 top-1/2 -rotate-90 text-xs font-bold text-gray-400 uppercase tracking-widest origin-center -translate-y-1/2 -translate-x-1/2 w-full text-center">Probability</div>

                    <div class="flex flex-col gap-1">
                        <?php for ($p = 5; $p >= 1; $p--): ?>
                            <div class="flex gap-1">
                                <!-- Y Axis Scale -->
                                <div class="w-6 flex items-center justify-center text-xs text-gray-400 font-bold"><?= $p ?></div>

                                <?php for ($i = 1; $i <= 5; $i++):
                                    $count = count($riskMatrix[$p][$i]);
                                    $hasData = $count > 0;
                                    $bgClass = getRiskClass($p, $i, $hasData);
                                    $textClass = ($p * $i >= 5 && $p * $i < 10 && $hasData) ? 'text-black' : 'text-white';
                                    if (!$hasData) $textClass = ''; // Reset for empty

                                    $cursor = $hasData ? 'cursor-pointer hover:ring-2 hover:ring-indigo-400' : '';
                                ?>
                                    <div onclick="showRiskList(<?= $p ?>, <?= $i ?>)"
                                        class="w-full aspect-square rounded-md flex items-center justify-center transition-all duration-200 <?= $bgClass ?> <?= $cursor ?>">
                                        <?php if ($count > 0): ?>
                                            <span class="font-bold text-lg drop-shadow-sm <?= $textClass ?>"><?= $count ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endfor; ?>

                        <!-- X Axis -->
                        <div class="flex gap-1 mt-1 ml-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="w-full text-center text-xs text-gray-400 font-bold"><?= $i ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- X Label -->
                    <div class="text-center mt-2 text-xs font-bold text-gray-400 uppercase tracking-widest pl-6">Impact</div>
                </div>
            </div>
        </div>

        <!-- Output: List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800" id="risk-list-title">All Risks</h3>
                        <p class="text-sm text-gray-500" id="risk-list-subtitle">Select a cell to filter risks.</p>
                    </div>
                    <button onclick="showAllRisks()" class="text-sm text-indigo-600 font-medium hover:text-indigo-800 hidden" id="reset-risk-btn">
                        Show All
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto max-h-[500px] p-2" id="risk-list-container">
                    <!-- Risks will be injected here -->
                    <div class="text-center py-20 text-gray-400">
                        <i class="ri-dashboard-line text-4xl mb-2"></i>
                        <p>Select a cell in the heatmap to view risks.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Embed PHP data to JS
    const riskData = <?= json_encode($riskMatrix) ?>;
    const allRiskFlat = <?= json_encode($allRisks) ?>;

    function showRiskList(p, i) {
        const list = riskData[p][i];
        const container = document.getElementById('risk-list-container');
        const title = document.getElementById('risk-list-title');
        const subtitle = document.getElementById('risk-list-subtitle');
        const resetBtn = document.getElementById('reset-risk-btn');

        title.textContent = `Risks (Prob: ${p}, Impact: ${i})`;
        subtitle.textContent = `${list.length} risk(s) found at this level.`;
        resetBtn.classList.remove('hidden');

        renderRiskCards(list, container);
    }

    function showAllRisks() {
        const container = document.getElementById('risk-list-container');
        const title = document.getElementById('risk-list-title');
        const subtitle = document.getElementById('risk-list-subtitle');
        const resetBtn = document.getElementById('reset-risk-btn');

        title.textContent = 'All Risks';
        subtitle.textContent = `${allRiskFlat.length} total risks identified.`;
        resetBtn.classList.add('hidden');

        renderRiskCards(allRiskFlat, container);
    }

    function renderRiskCards(list, container) {
        if (!list || list.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12 text-gray-400">
                    <p>No risks in this category.</p>
                </div>`;
            return;
        }

        container.innerHTML = list.map(r => {
            const score = r.probability * r.impact;
            let badgeColor = 'bg-green-100 text-green-700';
            if (score >= 15) badgeColor = 'bg-red-100 text-red-700';
            else if (score >= 10) badgeColor = 'bg-orange-100 text-orange-700';
            else if (score >= 5) badgeColor = 'bg-yellow-100 text-yellow-800';

            return `
            <div class="p-4 mb-3 bg-white border border-gray-100 rounded-lg hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <a href="?page=summary_5w2h&id=${r.activity_id}" class="font-bold text-gray-800 hover:text-indigo-600 flex items-center gap-2 group-hover:underline">
                            ${r.activity_name}
                            <i class="ri-external-link-line text-gray-400 text-xs"></i>
                        </a>
                        <div class="text-xs text-gray-500 mt-1">Milestone: ${r.milestone_name}</div>
                    </div>
                    <span class="px-2 py-1 rounded text-xs font-bold ${badgeColor}">Score: ${score}</span>
                </div>
                <p class="text-sm text-gray-600 mb-3">${r.risk_description}</p>
                <div class="bg-gray-50 p-3 rounded text-xs text-gray-500 italic border-l-2 border-gray-300">
                    Mitigation: ${r.mitigation_plan || 'No plan defined.'}
                </div>
            </div>
            `;
        }).join('');
    }

    // Init with all risks or empty? User asked to "click to see all", but let's default to empty prompting selection or show all? 
    // User said "Press to see details", implied selecting a cell.
    // But let's show all by default for visibility initially or just the prompt. Sticking to prompt as implemented.
</script>

<script>
    function switchView(viewId) {
        document.querySelectorAll('.view-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.view-tab').forEach(el => {
            el.classList.remove('text-indigo-600', 'border-indigo-600');
            el.classList.add('text-gray-500', 'border-transparent');
        });

        document.getElementById('view-' + viewId).classList.remove('hidden');
        const btn = document.getElementById('btn-' + viewId);
        btn.classList.add('text-indigo-600', 'border-indigo-600');
        btn.classList.remove('text-gray-500', 'border-transparent');
    }
</script>

<script>
    function confirmDeleteActivity(id) {
        Swal.fire({
            title: 'Delete Activity?',
            text: "Are you sure you want to delete this activity? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('?action=delete_activity', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Deleted!',
                                'Activity has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                data.message || 'Failed to delete activity.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error!',
                            'An unexpected error occurred.',
                            'error'
                        );
                    });
            }
        });
    }

    function promptImportExcel() {
        Swal.fire({
            title: 'Import Activities from Excel',
            html: `
                <div class="text-left text-sm mb-4">
                    <p class="mb-2">Upload an Excel file (.xlsx) with the standard columns.</p>
                    <p class="mb-4"><a href="?action=download_template" class="text-indigo-600 underline font-medium" target="_blank">Download Excel Template</a></p>
                    <code class="block bg-gray-100 p-2 rounded text-xs text-gray-500">Name | Type | Status | Start Date | End Date | Description</code>
                </div>
                <input type="file" id="excelFile" class="swal2-input" accept=".xlsx, .xls">
            `,
            showCancelButton: true,
            confirmButtonText: 'Upload & Import',
            preConfirm: () => {
                const file = document.getElementById('excelFile').files[0];
                if (!file) {
                    Swal.showValidationMessage('Please select a file');
                }
                return file;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('file', result.value);
                formData.append('calendar_id', <?= $calendar['id'] ?>);

                Swal.fire({
                    title: 'Importing...',
                    didOpen: () => Swal.showLoading()
                });

                fetch('?action=import_activities', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', `Imported ${data.imported} activities!`, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Import failed', 'error');
                            if (data.errors && data.errors.length > 0) {
                                console.error(data.errors);
                            }
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Network error occurred', 'error');
                    });
            }
        });
    }
</script>

<script>
    // Auto-switch to default tab
    document.addEventListener('DOMContentLoaded', () => {
        const defaultTab = '<?= $defaultTab ?>';
        if (defaultTab && defaultTab !== 'list') {
            switchView(defaultTab);
        }
    });
</script>
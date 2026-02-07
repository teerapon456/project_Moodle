<?php
// Modules/YearlyActivity/Views/summary_5w2h.php
$userId = $_SESSION['user']['id'] ?? 0;
?>
<div class="max-w-7xl mx-auto space-y-8 pb-10">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-800">Activity Summary (5W2H)</h1>
                <?php
                $actStatusColors = [
                    'pending' => 'bg-gray-100 text-gray-600',
                    'in_progress' => 'bg-blue-100 text-blue-700',
                    'completed' => 'bg-green-100 text-green-700',
                    'on_hold' => 'bg-orange-100 text-orange-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                    'proposed' => 'bg-purple-100 text-purple-700',
                    'incoming' => 'bg-teal-100 text-teal-700'
                ];
                $asColor = $actStatusColors[$activity['status']] ?? 'bg-gray-100 text-gray-600';
                ?>
                <?php if ($canEdit): ?>
                    <button onclick="updateActivityStatus(<?= $activity['id'] ?>, '<?= $activity['status'] ?>')"
                        class="px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider <?= $asColor ?> hover:opacity-80 transition shadow-sm flex items-center gap-1">
                        <?= str_replace('_', ' ', $activity['status']) ?>
                        <i class="ri-edit-line"></i>
                    </button>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider <?= $asColor ?>">
                        <?= str_replace('_', ' ', $activity['status']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-gray-500 mt-1">Comprehensive overview and RASCI Matrix.</p>
        </div>
        <div class="flex gap-2">
            <?php if ($canEdit): ?>
                <a href="?page=activity_wizard&id=<?= $activity['id'] ?>&step=1" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg font-medium hover:bg-indigo-100 transition">
                    <i class="ri-edit-line mr-1"></i> Edit Plan
                </a>
            <?php endif; ?>
            <a href="?page=calendar&id=<?= $activity['calendar_id'] ?>" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-medium hover:bg-gray-200 transition">
                <i class="ri-arrow-left-line mr-1"></i> Back to Calendar
            </a>
        </div>
    </div>

    <!-- Top 4W1H Grid (Compact) with History Log Sidebar if space permits, or full width below -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: 4W1H Grid -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 4W1H Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- What -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 md:col-span-2">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">W</span>
                        <h3 class="font-bold text-gray-700">What</h3>
                    </div>
                    <h4 class="text-lg font-bold text-indigo-700"><?= htmlspecialchars($summary['What'] ?? '-') ?></h4>
                    <p class="text-gray-600 text-sm mt-1"><?= nl2br(htmlspecialchars($summary['Description'] ?? '-')) ?></p>
                </div>

                <!-- Why -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">W</span>
                        <h3 class="font-bold text-gray-700">Why</h3>
                    </div>
                    <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($summary['Why'] ?? '-')) ?></p>
                </div>

                <!-- How Much -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 bg-green-50/50">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-xs">H</span>
                        <h3 class="font-bold text-gray-700">How Much</h3>
                    </div>
                    <p class="text-2xl font-bold text-green-700"><?= $summary['How Much'] ?></p>
                </div>

                <!-- When -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-xs">W</span>
                        <h3 class="font-bold text-gray-700">When</h3>
                    </div>
                    <p class="font-bold text-gray-800"><?= $summary['When'] ?></p>
                </div>

                <!-- Where -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-xs">W</span>
                        <h3 class="font-bold text-gray-700">Where</h3>
                    </div>
                    <p class="text-gray-800"><?= htmlspecialchars($summary['Where'] ?? '-') ?></p>
                </div>

                <!-- Key Person -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 md:col-span-2">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-xs">KP</span>
                        <h3 class="font-bold text-gray-700">Key Person</h3>
                    </div>
                    <div class="font-bold text-purple-700"><?= htmlspecialchars($activity['key_person_name'] ?? '-') ?></div>
                    <div class="text-xs text-gray-400">Main Point of Contact</div>
                </div>

                <!-- How (Scope) -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 md:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-xs">H</span>
                        <h3 class="font-bold text-gray-700">How (Scope)</h3>
                    </div>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($summary['How'] ?? '-')) ?></p>
                </div>
            </div>
        </div>

        <!-- Right Column: Activity History Timeline -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="ri-history-line"></i> Activity Timeline
            </h3>

            <div class="relative pl-6 border-l-2 border-indigo-100 space-y-6 max-h-[500px] overflow-y-auto pr-2 scrollbar-thin">
                <?php if (empty($summary['Logs'])): ?>
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-gray-200 border-2 border-white"></span>
                        <p class="text-sm text-gray-400 italic">No history logged yet.</p>
                        <div class="text-xs text-gray-300 mt-1">Created on <?= date('d M Y', strtotime($activity['created_at'])) ?></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($summary['Logs'] as $log): ?>
                        <div class="relative">
                            <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-indigo-500 border-2 border-white"></span>
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase"><?= date('d M Y, H:i', strtotime($log['changed_at'])) ?></div>
                                <div class="text-sm font-bold text-gray-800 mt-0.5">
                                    <?= $log['new_status'] == $log['previous_status'] ? 'Update:' : 'Status changed to' ?>
                                    <span class="capitalize text-indigo-600"><?= str_replace('_', ' ', $log['new_status']) ?></span>
                                </div>
                                <?php if ($log['note']): ?>
                                    <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-100 text-gray-600 text-xs italic">
                                        Note: "<?= htmlspecialchars($log['note']) ?>"
                                    </div>
                                <?php endif; ?>
                                <?php if ($log['changed_by_name']): ?>
                                    <div class="text-xs text-gray-400 mt-1">by <?= htmlspecialchars($log['changed_by_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .scrollbar-thin::-webkit-scrollbar {
                width: 4px;
            }

            .scrollbar-thin::-webkit-scrollbar-track {
                background: transparent;
            }

            .scrollbar-thin::-webkit-scrollbar-thumb {
                background-color: rgba(209, 213, 219, 0.5);
                border-radius: 20px;
            }

            .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                background-color: rgba(156, 163, 175, 0.7);
            }
        </style>

    </div>


    <!-- RASCI Matrix & Implementation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-lg font-bold text-gray-800">RASCI Matrix & Implementation</h3>
                <p class="text-xs text-gray-500">Roles: R=Responsible, A=Accountable, S=Support, C=Consult, I=Informed</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                        <th class="px-6 py-4 font-semibold w-1/4">Milestone</th>
                        <th class="px-4 py-4 font-semibold text-center">Status</th>
                        <th class="px-4 py-4 font-semibold text-center text-red-600 bg-red-50/50 border-l border-red-100">R</th>
                        <th class="px-4 py-4 font-semibold text-center text-orange-600 bg-orange-50/50 border-l border-orange-100">A</th>
                        <th class="px-4 py-4 font-semibold text-center text-blue-600 bg-blue-50/50 border-l border-blue-100">S</th>
                        <th class="px-4 py-4 font-semibold text-center text-teal-600 bg-teal-50/50 border-l border-teal-100">C</th>
                        <th class="px-4 py-4 font-semibold text-center text-gray-600 bg-gray-50/50 border-l border-gray-200">I</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($summary['Milestones'])): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">No milestones defined.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary['Milestones'] as $ms):
                            // Check if current user is 'R'
                            $rasciList = $summary['RasciMap'][$ms['id']] ?? [];
                            $isR = false;
                            $rUser = null;

                            $roles = ['R' => [], 'A' => [], 'S' => [], 'C' => [], 'I' => []];
                            foreach ($rasciList as $r) {
                                $roles[$r['role']][] = $r['fullname'];
                                if ($r['role'] === 'R') {
                                    $rUser = $r;
                                    if ($r['user_id'] == $userId) $isR = true;
                                }
                            }

                            // Status Colors
                            $statusColors = [
                                'pending' => 'bg-gray-100 text-gray-600',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'on_hold' => 'bg-orange-100 text-orange-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'proposed' => 'bg-purple-100 text-purple-700'
                            ];
                            $sColor = $statusColors[$ms['status']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                            <tr class="hover:bg-gray-50/50 transition duration-150">
                                <!-- Milestone Info -->
                                <td class="px-6 py-4 align-top">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($ms['name']) ?></div>
                                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($ms['description'] ?? '') ?></div>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600 border border-gray-200">
                                            Due: <?= $ms['due_date'] ?? 'TBD' ?>
                                        </span>
                                        <span class="text-xs bg-indigo-50 px-2 py-0.5 rounded text-indigo-600 border border-indigo-100">
                                            Weight: <?= $ms['weight_percent'] ?>%
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 align-top text-center">
                                    <?php if ($isR): ?>
                                        <button onclick="updateStatus(<?= $ms['id'] ?>, '<?= $ms['status'] ?>')"
                                            class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $sColor ?> hover:opacity-80 transition shadow-sm">
                                            <?= str_replace('_', ' ', $ms['status']) ?>
                                            <i class="ri-arrow-down-s-line ml-1"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $sColor ?>">
                                            <?= str_replace('_', ' ', $ms['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- RASCI Role Columns -->
                                <?php foreach (['R', 'A', 'S', 'C', 'I'] as $role):
                                    $bgClass = match ($role) {
                                        'R' => 'bg-red-50/30 border-red-100',
                                        'A' => 'bg-orange-50/30 border-orange-100',
                                        'S' => 'bg-blue-50/30 border-blue-100',
                                        'C' => 'bg-teal-50/30 border-teal-100',
                                        'I' => 'bg-gray-50/30 border-gray-200',
                                    };
                                ?>
                                    <td class="px-4 py-4 align-top text-center border-l <?= $bgClass ?>">
                                        <?php if (empty($roles[$role])): ?>
                                            <span class="text-gray-300">-</span>
                                        <?php else: ?>
                                            <div class="flex flex-col gap-1 items-center">
                                                <?php foreach ($roles[$role] as $name): ?>
                                                    <div class="text-xs font-medium text-gray-700 bg-white border border-gray-200 px-2 py-1 rounded shadow-sm whitespace-nowrap">
                                                        <?= htmlspecialchars($name) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Plan vs Actual Gantt Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Plan vs Actual Timeline</h3>
            <p class="text-xs text-gray-500">Visual comparison of planned due dates vs actual completion dates.</p>
        </div>
        <div class="p-6 overflow-x-auto">
            <?php
            // 1. Calculate Date Range
            // 1. Calculate Date Range
            $startDate = !empty($activity['start_date']) ? strtotime($activity['start_date']) : time();
            $endDate = !empty($activity['end_date']) ? strtotime($activity['end_date']) : $startDate;

            // Cache logs to avoid duplicate queries and allow bounds checking
            $milestoneLogsMap = $summary['MilestoneLogs'] ?? [];

            // Adjust min/max date if milestones go beyond activity range
            foreach ($summary['Milestones'] as $ms) {
                // Use cached logs from controller
                $logs = $milestoneLogsMap[$ms['id']] ?? [];

                // Check Start Dates (Plan)
                if (!empty($ms['start_date'])) $startDate = min($startDate, strtotime($ms['start_date']));

                // Check End Dates (Plan)
                if (!empty($ms['due_date'])) $endDate = max($endDate, strtotime($ms['due_date']));

                // Check Actual Dates from Logs for Bounds
                foreach ($logs as $l) {
                    if (!empty($l['actual_start_date'])) {
                        $startDate = min($startDate, strtotime($l['actual_start_date']));
                    }
                    if (!empty($l['actual_end_date'])) {
                        $endDate = max($endDate, strtotime($l['actual_end_date']));
                    }
                }
            }
            // Add buffer
            $endDate += 86400 * 7;
            // Add start buffer just in case
            $startDate -= 86400 * 2;

            $totalSeconds = $endDate - $startDate;
            if ($totalSeconds <= 0) $totalSeconds = 86400; // Prevent div by zero

            // Helper to get % position
            $getPos = function ($dateStr) use ($startDate, $totalSeconds) {
                if (!$dateStr) return 0;
                $ts = strtotime($dateStr);
                $rel = $ts - $startDate;
                return max(0, min(100, ($rel / $totalSeconds) * 100));
            };
            ?>

            <!-- Legend (Moved to Top) -->
            <div class="flex flex-wrap gap-4 mb-6 text-xs px-2">
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-400 rounded"></span> Pending</div>
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded"></span> In Progress</div>
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded"></span> Completed</div>
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-orange-500 rounded"></span> On Hold</div>
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-purple-500 rounded"></span> Proposed</div>
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-red-500 rounded"></span> Cancelled</div>
            </div>

            <div class="relative min-w-[800px]">
                <!-- Table Header: Names + Timeline Dates -->
                <div class="flex mb-2">
                    <div class="w-48 shrink-0 font-bold text-gray-700 text-sm flex items-end pb-2 px-2 border-b border-gray-200 border-r border-gray-100">
                        Milestone
                    </div>
                    <!-- Timeline Header (Dynamic) -->
                    <div class="flex-1 relative h-6 border-b border-gray-200 text-xs text-gray-500 font-medium pl-2">
                        <?php
                        $daysCount = $totalSeconds / 86400;

                        if ($daysCount <= 60) {
                            // DAILY HEADER
                            // Show every day: "28 Mon"
                            $curr = $startDate;
                            while ($curr <= $endDate) {
                                $left = (($curr - $startDate) / $totalSeconds) * 100;
                                // Rotate text slightly if too crowded, or just use small font
                                echo '<div class="absolute whitespace-nowrap text-[10px]" style="left: ' . $left . '%; transform: translateX(-50%);">' . date('d', $curr) . '</div>';
                                $curr = strtotime('+1 day', $curr);
                            }
                        } else {
                            // WEEKLY / MONTHLY HEADER
                            // Show Months (Strong)
                            $currM = $startDate;
                            while ($currM <= $endDate) {
                                $left = (($currM - $startDate) / $totalSeconds) * 100;
                                echo '<div class="absolute font-bold text-gray-800" style="left: ' . $left . '%">' . date('M Y', $currM) . '</div>';
                                $currM = strtotime('+1 month', $currM);
                            }

                            // Optional: Show Week numbers if space permits
                        }
                        ?>
                    </div>
                </div>

                <!-- Grid Lines -->
                <div class="absolute inset-0 pointer-events-none">
                    <?php
                    $curr = $startDate;
                    while ($curr <= $endDate) {
                        $left = (($curr - $startDate) / $totalSeconds) * 100;
                        echo '<div class="absolute top-0 bottom-0 border-l border-gray-100 dashed" style="left: ' . $left . '%"></div>';
                        $curr = strtotime('+1 month', $curr);
                    }
                    ?>
                </div>

                <!-- Milestones Rows -->
                <div class="space-y-4">
                    <?php foreach ($summary['Milestones'] as $ms):
                        // Plan Bar
                        $planStartTp = !empty($ms['start_date']) ? strtotime($ms['start_date']) : $startDate;
                        $planEndTp = !empty($ms['due_date']) ? strtotime($ms['due_date']) : $planStartTp;
                        if ($planEndTp < $planStartTp) $planEndTp = $planStartTp;

                        $planLeft = (($planStartTp - $startDate) / $totalSeconds) * 100;
                        $planWidth = (($planEndTp - $planStartTp) / $totalSeconds) * 100;

                        // 2. Build History Segments ... (Existing Logic)

                        $segments = [];
                        $msLogs = $milestoneLogsMap[$ms['id']] ?? [];

                        // Determine Actual Start/End from Logs for Tooltip
                        $derivedStart = null;
                        $derivedEnd = null;
                        foreach ($msLogs as $l) {
                            if (!empty($l['actual_start_date'])) {
                                $t = strtotime($l['actual_start_date']);
                                if (!$derivedStart || $t < $derivedStart) $derivedStart = $t;
                            }
                            if (!empty($l['actual_end_date'])) {
                                $t = strtotime($l['actual_end_date']);
                                if (!$derivedEnd || $t > $derivedEnd) $derivedEnd = $t;
                            }
                        }

                        // A. Convert Logs to Segments with Lookahead Clamping
                        // 1. Sort logs by Start Date first
                        usort($msLogs, function ($a, $b) {
                            $tA = !empty($a['actual_start_date']) ? strtotime($a['actual_start_date']) : 0;
                            $tB = !empty($b['actual_start_date']) ? strtotime($b['actual_start_date']) : 0;
                            return $tA - $tB;
                        });

                        $count = count($msLogs);
                        for ($i = 0; $i < $count; $i++) {
                            $l = $msLogs[$i];
                            if (empty($l['actual_start_date'])) continue;

                            $s = strtotime($l['actual_start_date']);
                            $e = !empty($l['actual_end_date']) ? strtotime($l['actual_end_date']) : null;

                            // If no explicit end date, clamp to next log's start or Now
                            if (!$e) {
                                if (isset($msLogs[$i + 1]) && !empty($msLogs[$i + 1]['actual_start_date'])) {
                                    $nextStart = strtotime($msLogs[$i + 1]['actual_start_date']);
                                    // If next starts after this starts, cap it. Otherwise (same time?), default to same.
                                    $e = ($nextStart > $s) ? $nextStart : $s;
                                } else {
                                    $e = time(); // Last one goes to Now
                                }
                            }

                            if ($e < $s) $e = $s;

                            $segments[] = [
                                'start' => $s,
                                'end' => $e,
                                'status' => $l['new_status'],
                                'note' => $l['note']
                            ];
                        }

                        // B. Add "Pending" Segment (Plan Start -> First Log Start)
                        // Find earliest actual start
                        $firstActualStart = null;
                        foreach ($segments as $seg) {
                            if ($firstActualStart === null || $seg['start'] < $firstActualStart) $firstActualStart = $seg['start'];
                        }

                        // If no logs, assume pending whole time (fallback logic handled earlier? No, let's do it here)
                        if (empty($segments)) {
                            // Fallback for empty logs
                            $segments[] = [
                                'start' => $planStartTp, // defaults logic from bounds
                                'end' => $planEndTp,
                                'status' => $ms['status']
                            ];
                        }

                        // C. Stack Segments (Swimlane Algorithm)
                        // 1. Sort by Start Date
                        usort($segments, function ($a, $b) {
                            if ($a['start'] == $b['start']) return $a['end'] - $b['end'];
                            return $a['start'] - $b['start'];
                        });

                        // 2. Assign Rows
                        $rows = []; // Store end_time of last segment in each row
                        foreach ($segments as &$seg) {
                            $placed = false;
                            foreach ($rows as $rid => $rowEnd) {
                                if ($seg['start'] >= $rowEnd) {
                                    $seg['row'] = $rid;
                                    $rows[$rid] = $seg['end'];
                                    $placed = true;
                                    break;
                                }
                            }
                            if (!$placed) {
                                $seg['row'] = count($rows);
                                $rows[] = $seg['end'];
                            }
                        }
                        unset($seg); // breakup ref

                        $rowCount = count($rows) ?: 1;
                        $rowHeight = 20; // px
                        $planHeight = 22; // Fixed height for plan row
                        $containerHeight = max(40, $planHeight + ($rowCount * $rowHeight) + 6); // min 40px

                        // Map status to colors
                        $getColor = function ($st) {
                            return match ($st) {
                                'pending' => 'bg-gray-400',
                                'in_progress' => 'bg-blue-500',
                                'completed' => 'bg-green-500',
                                'on_hold' => 'bg-orange-500',
                                'proposed' => 'bg-purple-500',
                                'cancelled' => 'bg-red-500',
                                default => 'bg-gray-400'
                            };
                        };
                    ?>
                        <!-- Table Row Layout (Fixed) -->
                        <div class="flex group border-b border-gray-100 last:border-0 hover:bg-gray-50 transition">
                            <!-- Column 1: Milestone Name -->
                            <div class="w-48 shrink-0 py-3 px-2 border-r border-gray-100 flex items-center">
                                <span class="text-xs font-bold text-gray-700 w-full truncate" title="<?= htmlspecialchars($ms['name']) ?>">
                                    <?= htmlspecialchars($ms['name']) ?>
                                </span>
                            </div>

                            <!-- Column 2: Timeline Container -->
                            <div class="flex-1 relative py-2 pl-2">
                                <div class="relative bg-gray-50 rounded w-full border border-gray-100 transition-all duration-300" style="height: <?= $containerHeight ?>px">
                                    <!-- Grid Layer (Inside) -->
                                    <div class="absolute inset-0 pointer-events-none z-0">
                                        <?php
                                        // 1. Month Separation (Solid)
                                        $currG = $startDate;
                                        while ($currG <= $endDate) {
                                            $leftG = (($currG - $startDate) / $totalSeconds) * 100;
                                            echo '<div class="absolute top-0 bottom-0 border-l border-gray-300 pointer-events-none" style="left: ' . $leftG . '%"></div>';
                                            $currG = strtotime('+1 month', $currG);
                                        }

                                        // 2. Day/Week Separation (Dashed)
                                        if ($daysCount <= 60) {
                                            $currD = strtotime('+1 day', $startDate);
                                            while ($currD <= $endDate) {
                                                $leftD = (($currD - $startDate) / $totalSeconds) * 100;
                                                echo '<div class="absolute top-0 bottom-0 border-l border-gray-200 dashed pointer-events-none" style="left: ' . $leftD . '%"></div>';
                                                $currD = strtotime('+1 day', $currD);
                                            }
                                        } else {
                                            $currW = strtotime('next monday', $startDate);
                                            while ($currW <= $endDate) {
                                                $leftW = (($currW - $startDate) / $totalSeconds) * 100;
                                                echo '<div class="absolute top-0 bottom-0 border-l border-gray-200 dashed pointer-events-none" style="left: ' . $leftW . '%"></div>';
                                                $currW = strtotime('+1 week', $currW);
                                            }
                                        }
                                        ?>
                                    </div>

                                    <!-- Plan Bar (Top Row) -->
                                    <div class="absolute top-1 h-4 bg-gray-900 rounded opacity-100 z-10 border border-black shadow-md ring-1 ring-white/50"
                                        style="left: <?= $planLeft ?>%; width: <?= max(0.5, $planWidth) ?>%"
                                        title="Plan (<?= $ms['start_date'] ?> - <?= $ms['due_date'] ?>)"></div>

                                    <!-- Actual Bar (Segments) -->
                                    <?php foreach ($segments as $seg):
                                        $segLeft = (($seg['start'] - $startDate) / $totalSeconds) * 100;
                                        $segWidth = (($seg['end'] - $seg['start']) / $totalSeconds) * 100;
                                        $top = $planHeight + ($seg['row'] * $rowHeight) + 2;
                                    ?>
                                        <div class="absolute h-4 <?= $getColor($seg['status']) ?> shadow-sm opacity-90 border border-white/20 hover:opacity-100 hover:z-50 transition rounded-sm text-[10px] flex items-center justify-center overflow-hidden text-white cursor-help"
                                            style="left: <?= $segLeft ?>%; width: <?= max(0.5, $segWidth) ?>%; top: <?= $top ?>px;"
                                            title="<?= ucfirst($seg['status']) ?>: <?= date('d M H:i', $seg['start']) ?> - <?= date('d M H:i', $seg['end']) ?> (<?= $seg['note'] ?? '' ?>)">
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Comparison Tooltip -->
                                    <div class="absolute opacity-0 group-hover:opacity-100 transition bottom-full left-[<?= $planLeft ?>%] mb-2 bg-black text-white text-xs rounded px-2 py-1 whitespace-nowrap z-50 pointer-events-none">
                                        <strong>Plan:</strong> <?= $ms['start_date'] ?? 'N/A' ?> - <?= $ms['due_date'] ?><br>
                                        <strong>Latest Actual:</strong> <?= $derivedStart ? date('d M H:i', $derivedStart) : '-' ?> - <?= $derivedEnd ? date('d M H:i', $derivedEnd) : '-' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        function updateActivityStatus(id, currentStatus) {
                            Swal.fire({
                                title: 'Update Activity Status',
                                html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                        <select id="swal-act-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="incoming" ${currentStatus === 'incoming' ? 'selected' : ''}>Incoming (Planned)</option>
                            <option value="in_progress" ${currentStatus === 'in_progress' ? 'selected' : ''}>In Progress</option>
                            <!-- Completed is automatic only -->
                            <option value="on_hold" ${currentStatus === 'on_hold' ? 'selected' : ''}>On Hold</option>
                            <option value="proposed" ${currentStatus === 'proposed' ? 'selected' : ''}>Proposed (New Idea)</option>
                            <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Log Note / Reason</label>
                        <textarea id="swal-act-note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Explain why the status is changing..."></textarea>
                    </div>
                </div>
            `,
                                showCancelButton: true,
                                confirmButtonText: 'Update Status',
                                confirmButtonColor: '#4f46e5',
                                preConfirm: () => {
                                    return {
                                        status: document.getElementById('swal-act-status').value,
                                        note: document.getElementById('swal-act-note').value
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const formData = new FormData();
                                    formData.append('id', id);
                                    formData.append('status', result.value.status);
                                    formData.append('note', result.value.note);

                                    fetch('?action=change_activity_status', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(r => r.json())
                                        .then(res => {
                                            if (res.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Updated!',
                                                    timer: 1500,
                                                    showConfirmButton: false
                                                }).then(() => location.reload());
                                            } else {
                                                Swal.fire('Error', res.message || 'Failed to update', 'error');
                                            }
                                        })
                                        .catch(err => {
                                            Swal.fire('Error', 'Network error', 'error');
                                        });
                                }
                            });
                        }

                        function updateStatus(id, currentStatus, currentActualStartDate, currentActualEndDate) {
                            Swal.fire({
                                title: 'Update Milestone Status',
                                html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="swal-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="in_progress" ${currentStatus === 'in_progress' ? 'selected' : ''}>In Progress</option>
                        <option value="completed" ${currentStatus === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="on_hold" ${currentStatus === 'on_hold' ? 'selected' : ''}>On Hold</option>
                        <option value="proposed" ${currentStatus === 'proposed' ? 'selected' : ''}>Proposed</option>
                        <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
                <div class="mb-4 text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Note (Plan vs Actual)</label>
                    <textarea id="swal-note" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" rows="2" placeholder="Why is the status changing?"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual Start</label>
                        <input type="datetime-local" id="swal-start-date" value="${currentActualStartDate ? currentActualStartDate.replace(' ', 'T') : ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual End</label>
                        <input type="datetime-local" id="swal-end-date" value="${currentActualEndDate ? currentActualEndDate.replace(' ', 'T') : ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Start required for In Progress. End required for Completed.</p>
            </div>
        `,
                                showCancelButton: true,
                                confirmButtonText: 'Update',
                                confirmButtonColor: '#4f46e5',
                                preConfirm: () => {
                                    const status = document.getElementById('swal-status').value;
                                    const note = document.getElementById('swal-note').value;
                                    const startDate = document.getElementById('swal-start-date').value;
                                    const endDate = document.getElementById('swal-end-date').value;

                                    if (['in_progress'].includes(status) && !startDate) {
                                        Swal.showValidationMessage('Actual Start Date is required for In Progress');
                                        return false;
                                    }
                                    if (['completed', 'on_hold', 'proposed', 'cancelled'].includes(status) && !endDate) {
                                        Swal.showValidationMessage('Actual End Date is required for this status');
                                        return false;
                                    }
                                    return {
                                        status,
                                        note,
                                        startDate,
                                        endDate
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const formData = new FormData();
                                    formData.append('id', id);
                                    formData.append('status', result.value.status);
                                    formData.append('note', result.value.note);
                                    formData.append('actual_start_date', result.value.startDate);
                                    formData.append('actual_end_date', result.value.endDate);

                                    fetch('?action=update_milestone_status', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(r => r.json())
                                        .then(res => {
                                            if (res.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Updated!',
                                                    timer: 1500,
                                                    showConfirmButton: false
                                                }).then(() => location.reload());
                                            } else {
                                                Swal.fire('Error', res.message || 'Failed to update', 'error');
                                            }
                                        })
                                        .catch(err => {
                                            Swal.fire('Error', 'Network error', 'error');
                                        });
                                }
                            });
                        }
                    </script>
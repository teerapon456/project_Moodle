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
                    'in_progress' => 'bg-blue-100 text-red-700',
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
            <?php
            // Allow rating if authorised and activity is completed
            if ($canEdit && $activity['status'] === 'completed'):
                $btnLabel = !empty($userRating) ? 'Update My Rating' : 'Rate Activity';
                $btnIcon = !empty($userRating) ? 'ri-star-fill' : 'ri-star-line';
                $ratingData = !empty($userRating) ? json_encode($userRating) : 'null';
            ?>
                <button onclick="rateActivity(<?= $activity['id'] ?>, <?= htmlspecialchars($ratingData, ENT_QUOTES) ?>)" class="px-4 py-2 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition shadow-md">
                    <i class="<?= $btnIcon ?> mr-1"></i> <?= $btnLabel ?>
                </button>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <a href="?page=activity_wizard&id=<?= $activity['id'] ?>&step=1" class="px-4 py-2 bg-red-50 text-primary rounded-lg font-medium hover:bg-red-100 transition">
                    <i class="ri-edit-line mr-1"></i> Edit Plan
                </a>
            <?php endif; ?>
            <a href="?page=calendar&id=<?= $activity['calendar_id'] ?>" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-medium hover:bg-gray-200 transition">
                <i class="ri-arrow-left-line mr-1"></i> Back to Calendar
            </a>
        </div>
    </div>

    <!-- Top 4W1H Grid (Compact) with History Log Sidebar if space permits, or full width below -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch lg:h-[580px]">

        <!-- Left Column: 4W1H Grid -->
        <div class="lg:col-span-2 flex flex-col h-full overflow-y-auto scrollbar-thin pr-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
                <!-- What -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 md:col-span-2">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-primary flex items-center justify-center font-bold text-xs">W</span>
                        <h3 class="font-bold text-gray-700">What</h3>
                    </div>
                    <h4 class="text-lg font-bold text-red-700"><?= htmlspecialchars($summary['What'] ?? '-') ?></h4>
                    <p class="text-gray-600 text-sm mt-1"><?= nl2br(htmlspecialchars($summary['Description'] ?? '-')) ?></p>
                </div>

                <!-- Why -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center font-bold text-xs">W</span>
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
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 md:col-span-2 flex-grow flex flex-col">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-xs">H</span>
                        <h3 class="font-bold text-gray-700">How (Scope)</h3>
                    </div>
                    <div class="text-gray-600 flex-grow"><?= nl2br(htmlspecialchars($summary['How'] ?? '-')) ?></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Activity History Timeline -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2 flex-shrink-0">
                <i class="ri-history-line"></i> Activity Timeline
            </h3>

            <div class="relative pl-2 space-y-6 overflow-y-auto pr-3 scrollbar-thin flex-grow h-0">

                <?php if (empty($summary['Logs'])): ?>
                    <div class="relative flex gap-4">
                        <div class="w-4 h-4 rounded-full bg-gray-200 border-2 border-white flex-shrink-0"></div>
                        <p class="text-sm text-gray-400 italic">No history logged yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($summary['Logs'] as $log): ?>
                        <div class="relative flex gap-4 pb-6 last:pb-0">

                            <div class="absolute left-[7px] top-2 bottom-0 w-[2px] bg-red-100 last:hidden"></div>

                            <div class="relative z-10 flex-shrink-0">
                                <div class="w-4 h-4 rounded-full bg-red-500 border-2 border-white mt-1"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-gray-400 uppercase leading-none">
                                    <?= date('d M Y, H:i', strtotime($log['changed_at'])) ?>
                                    <?php if ($log['changed_by_name']): ?>
                                        <span class="ml-1 text-[10px] font-normal text-gray-400 normal-case">by <?= htmlspecialchars($log['changed_by_name']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="text-sm font-bold text-gray-800 mt-1">
                                    <?= $log['new_status'] == $log['previous_status'] ? 'Update:' : 'Status changed to' ?>
                                    <span class="capitalize text-red-600"><?= str_replace('_', ' ', $log['new_status']) ?></span>
                                </div>

                                <?php if ($log['note']): ?>
                                    <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-100 text-gray-600 text-[11px] italic leading-relaxed">
                                        Note: "<?= htmlspecialchars($log['note']) ?>"
                                    </div>
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
                    <tr class="bg-gray-50 border-b border-gray-200 text-[10px] text-gray-500 uppercase">
                        <th class="px-3 py-4 font-semibold w-44">Milestone</th>
                        <th class="px-4 py-4 font-semibold text-center w-px whitespace-nowrap border-l border-gray-200">Status</th>
                        <?php foreach (($summary['InvolvedPeople'] ?? []) as $uid => $name): ?>
                            <th class="px-2 py-4 font-semibold text-center border-l border-gray-200 w-px">
                                <div class="text-[10px] leading-tight text-gray-400 font-normal whitespace-nowrap">TEAM MEMBER</div>
                                <div class="text-gray-700 mt-1 whitespace-nowrap"><?= htmlspecialchars($name) ?></div>
                            </th>
                        <?php endforeach; ?>
                        <th class="px-6 py-4 font-semibold text-center w-px border-l border-gray-200 whitespace-nowrap">Files</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($summary['Milestones'])): ?>
                        <tr>
                            <td colspan="<?= 3 + count($summary['InvolvedPeople'] ?? []) ?>" class="px-6 py-8 text-center text-gray-400">No milestones defined.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary['Milestones'] as $ms):
                            // Check if current user is involved
                            $isActor = false;
                            $userRole = null;
                            if (isset($summary['RoleMatrix'][$ms['id']][$userId])) {
                                $isActor = true;
                                $userRole = $summary['RoleMatrix'][$ms['id']][$userId];
                            }

                            // Status Colors
                            $statusColors = [
                                'pending' => 'bg-gray-100 text-gray-600',
                                'in_progress' => 'bg-blue-100 text-red-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'on_hold' => 'bg-orange-100 text-orange-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'proposed' => 'bg-purple-100 text-purple-700'
                            ];
                            $sColor = $statusColors[$ms['status']] ?? 'bg-gray-100 text-gray-600';
                        ?>
                            <tr class="hover:bg-gray-50/50 transition duration-150">
                                <!-- Milestone Info -->
                                <td class="px-4 py-4 align-top">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($ms['name']) ?></div>
                                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($ms['description'] ?? '') ?></div>
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <span class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded text-gray-500 border border-gray-200">
                                            Due: <?= $ms['due_date'] ?? 'TBD' ?>
                                        </span>
                                        <span class="text-[10px] bg-red-50 px-1.5 py-0.5 rounded text-primary border border-red-100">
                                            Weight: <?= $ms['weight_percent'] ?>%
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 align-top text-center w-px whitespace-nowrap border-l border-gray-100">
                                    <?php if ($canEdit || $userRole === 'R'): ?>
                                        <button onclick="updateStatus(<?= $ms['id'] ?>, '<?= $ms['status'] ?>', '<?= $ms['actual_start_date'] ?? '' ?>', '<?= $ms['actual_end_date'] ?? '' ?>')"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $sColor ?> hover:opacity-80 transition shadow-sm border border-gray-200/50">
                                            <?= str_replace('_', ' ', $ms['status']) ?>
                                            <i class="ri-arrow-down-s-line ml-0.5"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $sColor ?>">
                                            <?= str_replace('_', ' ', $ms['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- People Columns -->
                                <?php foreach (($summary['InvolvedPeople'] ?? []) as $uid => $name):
                                    $role = $summary['RoleMatrix'][$ms['id']][$uid] ?? null;
                                    $roleColor = match ($role) {
                                        'R' => 'bg-red-600 text-white',
                                        'A' => 'bg-orange-500 text-white',
                                        'S' => 'bg-primary text-white',
                                        'C' => 'bg-purple-600 text-white',
                                        'I' => 'bg-gray-500 text-white',
                                        default => ''
                                    };
                                ?>
                                    <td class="px-2 py-4 align-middle text-center border-l border-gray-100 w-px">
                                        <?php if ($role): ?>
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm <?= $roleColor ?> mx-auto shadow-sm ring-2 ring-white">
                                                <?= $role ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-200">.</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <!-- Attachments -->
                                <td class="px-6 py-4 align-top border-l border-gray-100 w-px whitespace-nowrap">
                                    <div class="space-y-2">
                                        <?php
                                        $attachments = $summary['MilestoneAttachments'][$ms['id']] ?? [];
                                        foreach ($attachments as $att): ?>
                                            <div class="flex items-center justify-between group bg-gray-50 hover:bg-white p-1 rounded border border-gray-100 transition-all">
                                                <a href="/<?= ltrim($att['file_path'], '/') ?>" target="_blank" class="flex items-center gap-2 min-w-0">
                                                    <i class="ri-file-fill text-primary"></i>
                                                    <span class="text-xs text-gray-600 truncate max-w-[70px]" title="<?= htmlspecialchars($att['file_name']) ?>">
                                                        <?= htmlspecialchars($att['file_name']) ?>
                                                    </span>
                                                </a>
                                                <?php if ($canEdit || $userRole === 'R'): ?>
                                                    <button onclick="deleteAttachment(<?= $att['id'] ?>, <?= $ms['id'] ?>)" class="text-gray-400 hover:text-red-500 transition opacity-0 group-hover:opacity-100">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($canEdit || $userRole === 'R'): ?>
                                            <button onclick="triggerUpload(<?= $ms['id'] ?>)" class="w-full py-1.5 border-2 border-dashed border-gray-200 rounded-lg text-[10px] text-gray-400 font-bold hover:border-primary hover:text-primary transition-all flex items-center justify-center gap-1">
                                                <i class="ri-upload-2-line"></i> Upload
                                            </button>
                                            <input type="file" id="file-input-<?= $ms['id'] ?>" class="hidden" onchange="handleFileUpload(this, <?= $ms['id'] ?>)">
                                        <?php endif; ?>
                                    </div>
                                </td>
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
                <div class="flex items-center gap-1"><span class="w-3 h-3 bg-red-500 rounded"></span> In Progress</div>
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
                                'in_progress' => 'bg-red-500',
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateActivityStatus(id, currentStatus) {
            Swal.fire({
                title: 'Update Activity Status',
                html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                        <select id="swal-act-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
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
                        <textarea id="swal-act-note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" placeholder="Explain why the status is changing..."></textarea>
                    </div>
                </div>
            `,
                showCancelButton: true,
                confirmButtonText: 'Update Status',
                confirmButtonColor: '#2563eb',
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
                    <select id="swal-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
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
                    <textarea id="swal-note" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none" rows="2" placeholder="Why is the status changing?"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual Start</label>
                        <input type="datetime-local" id="swal-start-date" value="${currentActualStartDate ? currentActualStartDate.replace(' ', 'T') : ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual End</label>
                        <input type="datetime-local" id="swal-end-date" value="${currentActualEndDate ? currentActualEndDate.replace(' ', 'T') : ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Start required for In Progress. End required for Completed.</p>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                confirmButtonColor: '#2563eb',
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

        function rateActivity(id, existingData = null) {
            Swal.fire({
                title: existingData ? 'Update My Evaluation' : 'Evaluation & Scoring',
                width: '500px',
                html: `
            <div class="text-left space-y-6 pt-2">
                <!-- Dimensional Scoring -->
                <div class="space-y-4">
                    ${['quality', 'timeliness', 'impact'].map(category => `
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-bold text-gray-700 capitalize">${category}</label>
                                <span class="text-xs text-primary font-bold" id="star-label-${category}">
                                    ${existingData ? 'Rating Selected' : 'Select Rating'}
                                </span>
                            </div>
                            <div class="flex justify-center gap-3">
                                ${[1, 2, 3, 4, 5].map(num => `
                                    <button type="button" 
                                            onclick="setStarRating('${category}', ${num})" 
                                            id="star-${category}-${num}"
                                            class="star-btn text-3xl text-gray-300 hover:scale-110 transition active:scale-95">
                                        <i class="ri-star-fill"></i>
                                    </button>
                                `).join('')}
                            </div>
                            <input type="hidden" id="score-${category}" value="${existingData ? existingData['score_' + category] : ''}">
                        </div>
                    `).join('')}
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Lessons Learned / Feedback</label>
                    <textarea id="swal-eval-note" rows="4" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-yellow-400 outline-none transition" placeholder="What went well? What can be improved?">${existingData ? existingData.evaluation_note || '' : ''}</textarea>
                </div>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: existingData ? 'Update Evaluation' : 'Submit Evaluation',
                confirmButtonColor: '#eab308',
                didOpen: () => {
                    window.setStarRating = (category, rating) => {
                        document.getElementById(`score-${category}`).value = rating;
                        const labelMap = {
                            1: 'Poor',
                            2: 'Fair',
                            3: 'Standard',
                            4: 'Very Good',
                            5: 'Excellent'
                        };
                        document.getElementById(`star-label-${category}`).innerText = labelMap[rating] || 'Rating Selected';

                        // Reset and fill stars
                        [1, 2, 3, 4, 5].forEach(num => {
                            const star = document.getElementById(`star-${category}-${num}`);
                            star.classList.remove('text-yellow-400', 'text-gray-300');
                            star.classList.add(num <= rating ? 'text-yellow-400' : 'text-gray-300');
                        });
                    };

                    // If existing data, trigger initial star display
                    if (existingData) {
                        ['quality', 'timeliness', 'impact'].forEach(cat => {
                            const rating = parseInt(existingData['score_' + cat]);
                            if (rating) setStarRating(cat, rating);
                        });
                    }
                },
                preConfirm: () => {
                    const quality = document.getElementById('score-quality').value;
                    const timeliness = document.getElementById('score-timeliness').value;
                    const impact = document.getElementById('score-impact').value;

                    if (!quality || !timeliness || !impact) {
                        Swal.showValidationMessage('Please provide ratings for all 3 categories');
                        return false;
                    }

                    return {
                        quality,
                        timeliness,
                        impact,
                        note: document.getElementById('swal-eval-note').value
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('score_quality', result.value.quality);
                    formData.append('score_timeliness', result.value.timeliness);
                    formData.append('score_impact', result.value.impact);
                    formData.append('note', result.value.note);

                    fetch('?action=rate_activity', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Evaluation Saved!',
                                    text: 'The performance scores have been recorded.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire('Error', res.message || 'Failed to save', 'error');
                            }
                        })
                        .catch(err => Swal.fire('Error', 'Network error', 'error'));
                }
            });
        }
    </script>
    <!-- Team Discussions / Activity Notes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6 mb-12">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Team Discussions / Activity Notes</h3>
                <p class="text-xs text-gray-500">Shared notes and collaboration for this activity.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-bold"><?= count($summary['Comments'] ?? []) ?> Comments</span>
            </div>
        </div>

        <div class="p-6">
            <div id="comments-container" class="space-y-6 max-h-[500px] overflow-y-auto scrollbar-thin pr-2 mb-8">
                <?php if (empty($summary['Comments'])): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm">
                            <i class="ri-chat-voice-line text-2xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-400 text-sm">No discussions yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($summary['Comments'] as $comment): ?>
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 rounded-full bg-red-100 text-primary flex items-center justify-center font-bold shrink-0">
                                <?= strtoupper(substr($comment['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($comment['user_name']) ?></span>
                                    <span class="text-[10px] text-gray-400"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <div class="text-gray-600 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <?php
                                    $commentHtml = htmlspecialchars($comment['comment_text']);
                                    // Highlight @PersonName mentions (sort by length desc to avoid partial matches)
                                    $people = $summary['InvolvedPeople'] ?? [];
                                    $people['all'] = 'All Members'; // Add All Members support
                                    uasort($people, function ($a, $b) {
                                        return strlen($b) - strlen($a);
                                    });
                                    foreach ($people as $uid => $pName) {
                                        $escaped = htmlspecialchars($pName);
                                        $commentHtml = str_replace(
                                            '@' . $escaped,
                                            '<span class="text-primary font-bold bg-red-50 px-1.5 py-0.5 rounded-md border border-red-100">@' . $escaped . '</span>',
                                            $commentHtml
                                        );
                                    }
                                    // Highlight #MilestoneName mentions (sort by length desc)
                                    $milestones = $summary['Milestones'] ?? [];
                                    usort($milestones, function ($a, $b) {
                                        return strlen($b['name']) - strlen($a['name']);
                                    });
                                    foreach ($milestones as $ms) {
                                        $escaped = htmlspecialchars($ms['name']);
                                        $commentHtml = str_replace(
                                            '#' . $escaped,
                                            '<span class="text-green-700 font-bold bg-green-50 px-1.5 py-0.5 rounded-md border border-green-100">#' . $escaped . '</span>',
                                            $commentHtml
                                        );
                                    }
                                    echo nl2br($commentHtml);
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Comment Input -->
            <div class="flex gap-4 items-start border-t border-gray-100 pt-6">
                <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold shrink-0">
                    <i class="ri-user-line"></i>
                </div>
                <div class="flex-1 space-y-3">
                    <div class="relative">
                        <div id="comment-backdrop" class="absolute inset-0 px-4 py-3 text-sm pointer-events-none overflow-hidden whitespace-pre-wrap break-words border border-transparent rounded-xl" style="font-family: inherit; line-height: inherit;"></div>
                        <textarea id="comment-text" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary outline-none transition" style="background: transparent; color: transparent; caret-color: #333;" placeholder="Add a comment or note... (use @ to mention people, # for milestones)"></textarea>
                        <!-- Mention Dropdown -->
                        <div id="mention-dropdown" class="hidden absolute bottom-full left-0 mb-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl z-50 max-h-64 overflow-y-auto scrollbar-thin">
                            <div id="mention-list" class="py-1"></div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button onclick="postComment(<?= $activity['id'] ?>)" class="px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary-dark transition flex items-center gap-2">
                            <i class="ri-send-plane-fill"></i> Post Comment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Scorecard (Multi-Evaluator Averages) -->
    <?php
    if (!empty($averageEvaluation)):
        $percentage = round($averageEvaluation['avg_total']);
        $grade = 'C';
        $gradeClass = 'text-red-500 bg-red-50';
        if ($percentage >= 85) {
            $grade = 'S';
            $gradeClass = 'text-yellow-600 bg-yellow-50';
        } elseif ($percentage >= 70) {
            $grade = 'A';
            $gradeClass = 'text-green-600 bg-green-50';
        } elseif ($percentage >= 50) {
            $grade = 'B';
            $gradeClass = 'text-blue-600 bg-blue-50';
        }
    ?>
        <div class="bg-white p-6 rounded-xl shadow-lg border-2 border-yellow-400/30 bg-yellow-50/10 mt-8 mb-12">
            <div class="flex items-center justify-between mb-8 border-b border-yellow-200/50 pb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-yellow-400 text-white flex items-center justify-center font-bold shadow-lg transform -rotate-3">
                        <i class="ri-medal-2-fill text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Performance Summary</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ring-1 ring-yellow-200">
                                <i class="ri-group-line mr-0.5"></i> <?= $averageEvaluation['evaluator_count'] ?> Evaluators
                            </span>
                            <span class="text-xs text-gray-400">| Consensus-based Average Score</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center group">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1 group-hover:text-yellow-500 transition">Overall Grade</div>
                        <div class="px-4 py-2 rounded-xl <?= $gradeClass ?> border-2 border-current shadow-sm font-black text-lg transition-transform hover:scale-105"><?= $grade ?> GRADE</div>
                    </div>
                    <div class="h-14 w-[1px] bg-yellow-200/50"></div>
                    <div class="text-center">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Activity Score</div>
                        <div class="text-5xl font-black text-yellow-600 drop-shadow-sm"><?= $percentage ?><span class="text-xl ml-0.5 font-bold">%</span></div>
                    </div>
                </div>
            </div>

            <!-- 3D Average Score Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php
                $dimensions = [
                    ['label' => 'Quality of Work', 'score' => $averageEvaluation['avg_quality'], 'icon' => 'ri-check-double-line', 'color' => 'blue', 'desc' => 'Depth & Professionalism'],
                    ['label' => 'Timeliness', 'score' => $averageEvaluation['avg_timeliness'], 'icon' => 'ri-time-line', 'color' => 'green', 'desc' => 'Execution Speed'],
                    ['label' => 'Strategic Impact', 'score' => $averageEvaluation['avg_impact'], 'icon' => 'ri-flashlight-line', 'color' => 'orange', 'desc' => 'Added Value & Results'],
                ];
                foreach ($dimensions as $dim):
                ?>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center relative overflow-hidden group hover:border-<?= $dim['color'] ?>-200 transition">
                        <div class="absolute top-0 left-0 w-1 h-full bg-<?= $dim['color'] ?>-500/20"></div>
                        <div class="w-12 h-12 rounded-xl bg-<?= $dim['color'] ?>-50 text-<?= $dim['color'] ?>-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="<?= $dim['icon'] ?> text-2xl"></i>
                        </div>
                        <div class="text-xs font-black text-gray-800 uppercase tracking-wider mb-1"><?= $dim['label'] ?></div>
                        <div class="flex gap-1.5 mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="ri-star-fill text-lg <?= $i <= round($dim['score']) ? 'text-yellow-400' : 'text-gray-100' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="text-[10px] text-gray-400 font-medium italic mb-2"><?= $dim['desc'] ?></div>
                        <div class="bg-<?= $dim['color'] ?>-50 text-<?= $dim['color'] ?>-700 px-3 py-1 rounded-full text-xs font-bold ring-1 ring-<?= $dim['color'] ?>-100">
                            <?= number_format($dim['score'], 1) ?> / 5.0
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Individual Feedbacks & Lessons Learned -->
            <div class="space-y-4">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest pl-2 mb-2 flex items-center gap-2">
                    <i class="ri-discuss-line text-lg"></i> Individual Evaluator Feedback (Lessons Learned)
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($averageEvaluation['feedbacks'] as $fb):
                        if (empty($fb['evaluation_note'])) continue;
                    ?>
                        <div class="bg-white/60 p-5 rounded-2xl border border-yellow-200/30 relative">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs">
                                        <?= strtoupper(substr($fb['evaluator_name'], 0, 1)) ?>
                                    </div>
                                    <div class="text-sm font-bold text-gray-700"><?= htmlspecialchars($fb['evaluator_name']) ?></div>
                                </div>
                                <div class="text-[10px] font-bold text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded ring-1 ring-yellow-100">
                                    Rated: <?= $fb['score_total'] ?>%
                                </div>
                            </div>
                            <div class="text-gray-600 text-sm italic leading-relaxed whitespace-pre-line">
                                "<?= htmlspecialchars($fb['evaluation_note']) ?>"
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // === Mention Data ===
        <?php
        // Prepare mention data in PHP to avoid JS syntax errors in IDE
        $mentionPeople = ['all' => 'All Members'];
        $currentUserId = $userId ?? 0;
        foreach (($summary['InvolvedPeople'] ?? []) as $uid => $name) {
            if ($uid != $currentUserId) {
                $mentionPeople[(string)$uid] = $name;
            }
        }
        $mentionMilestones = [];
        foreach (($summary['Milestones'] ?? []) as $ms) {
            $mentionMilestones[(string)$ms['id']] = $ms['name'];
        }
        ?>
        const mentionData = {
            people: <?= json_encode($mentionPeople) ?>,
            milestones: <?= json_encode($mentionMilestones) ?>
        };

        // === Mention Logic ===
        (function() {
            const textarea = document.getElementById('comment-text');
            const dropdown = document.getElementById('mention-dropdown');
            const listEl = document.getElementById('mention-list');
            const backdrop = document.getElementById('comment-backdrop');
            if (!textarea || !dropdown || !listEl) return;

            let mentionMode = null; // '@' or '#'
            let mentionStart = -1;

            // Build name lists for highlighting (sort by length desc) - Exposed to global for postComment
            window.peopleNames = Object.values(mentionData.people).sort((a, b) => b.length - a.length);
            window.msNames = Object.values(mentionData.milestones).sort((a, b) => b.length - a.length);

            function highlightMentions() {
                if (!backdrop) return;
                let html = textarea.value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                // Highlight @PersonName
                peopleNames.forEach(name => {
                    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    html = html.replace(new RegExp('@' + escaped, 'g'),
                        '<span class="text-primary" style="background:rgba(239,68,68,0.12);border-radius:3px;">@' + name + '</span>');
                });
                // Highlight #MilestoneName
                msNames.forEach(name => {
                    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    html = html.replace(new RegExp('#' + escaped, 'g'),
                        '<span class="text-green-700" style="background:rgba(34,197,94,0.12);border-radius:3px;">#' + name + '</span>');
                });
                // Add trailing space so line breaks match
                backdrop.innerHTML = html + '\n';
            }

            textarea.addEventListener('input', function() {
                highlightMentions();
                const val = this.value;
                const pos = this.selectionStart;
                // Find the last trigger character before cursor
                let triggerPos = -1;
                let triggerChar = null;
                for (let i = pos - 1; i >= 0; i--) {
                    if (val[i] === ' ' || val[i] === '\n') break;
                    if (val[i] === '@' || val[i] === '#') {
                        triggerPos = i;
                        triggerChar = val[i];
                        break;
                    }
                }

                if (triggerPos >= 0) {
                    mentionMode = triggerChar;
                    mentionStart = triggerPos;
                    const query = val.substring(triggerPos + 1, pos).toLowerCase();
                    showMentionDropdown(query);
                } else {
                    hideMentionDropdown();
                }
            });

            textarea.addEventListener('keydown', function(e) {
                // Atomic delete: Backspace removes entire mention at once
                if (e.key === 'Backspace') {
                    const pos = this.selectionStart;
                    const val = this.value;
                    const allMentions = [];
                    peopleNames.forEach(n => allMentions.push('@' + n));
                    msNames.forEach(n => allMentions.push('#' + n));

                    for (const mention of allMentions) {
                        // Check if cursor is right after the mention (or inside it with trailing space)
                        const beforeCursor = val.substring(0, pos);
                        const mentionIdx = beforeCursor.lastIndexOf(mention);
                        if (mentionIdx >= 0 && mentionIdx + mention.length >= pos - 1) {
                            // Delete the entire mention
                            e.preventDefault();
                            const endPos = mentionIdx + mention.length;
                            // Also remove trailing space if present
                            const actualEnd = (val[endPos] === ' ') ? endPos + 1 : endPos;
                            this.value = val.substring(0, mentionIdx) + val.substring(actualEnd);
                            this.setSelectionRange(mentionIdx, mentionIdx);
                            highlightMentions();
                            return;
                        }
                    }
                }

                // Dropdown navigation
                if (dropdown.classList.contains('hidden')) return;
                if (e.key === 'Escape') {
                    hideMentionDropdown();
                    e.preventDefault();
                }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const items = listEl.querySelectorAll('[data-mention-item]');
                    if (!items.length) return;
                    const active = listEl.querySelector('[data-active]');
                    let idx = Array.from(items).indexOf(active);
                    if (active) {
                        active.removeAttribute('data-active');
                        active.classList.remove('bg-red-50');
                    }
                    idx = e.key === 'ArrowDown' ? (idx + 1) % items.length : (idx - 1 + items.length) % items.length;
                    items[idx].setAttribute('data-active', '');
                    items[idx].classList.add('bg-red-50');
                    items[idx].scrollIntoView({
                        block: 'nearest'
                    });
                }
                if (e.key === 'Enter') {
                    const active = listEl.querySelector('[data-active]');
                    if (active) {
                        e.preventDefault();
                        active.click();
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target) && e.target !== textarea) {
                    hideMentionDropdown();
                }
            });

            function showMentionDropdown(query) {
                listEl.innerHTML = '';
                let items = [];

                if (mentionMode === '@') {
                    // Show people ONLY
                    for (const [uid, name] of Object.entries(mentionData.people)) {
                        if (!query || name.toLowerCase().includes(query)) {
                            const isAll = uid === 'all';
                            items.push({
                                type: 'person',
                                id: uid,
                                name: name,
                                icon: isAll ? 'ri-team-line' : 'ri-user-line',
                                color: isAll ? 'text-orange-600' : 'text-primary'
                            });
                        }
                    }
                } else if (mentionMode === '#') {
                    // Show milestones ONLY
                    for (const [msId, name] of Object.entries(mentionData.milestones)) {
                        if (!query || name.toLowerCase().includes(query)) {
                            items.push({
                                type: 'milestone',
                                id: msId,
                                name: name,
                                icon: 'ri-flag-line',
                                color: 'text-green-600'
                            });
                        }
                    }
                }

                if (items.length === 0) {
                    listEl.innerHTML = '<div class="px-4 py-3 text-xs text-gray-400 text-center">No matches found</div>';
                    dropdown.classList.remove('hidden');
                    return;
                }

                // Add group headers
                const people = items.filter(i => i.type === 'person');
                const milestones = items.filter(i => i.type === 'milestone');

                if (people.length > 0) {
                    listEl.innerHTML += '<div class="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase bg-gray-50">👤 People</div>';
                    people.forEach((item, idx) => {
                        listEl.innerHTML += createMentionItem(item, idx === 0);
                    });
                }
                if (milestones.length > 0) {
                    listEl.innerHTML += '<div class="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase bg-gray-50">📌 Milestones</div>';
                    milestones.forEach((item, idx) => {
                        listEl.innerHTML += createMentionItem(item, people.length === 0 && idx === 0);
                    });
                }

                // Attach click handlers
                listEl.querySelectorAll('[data-mention-item]').forEach(el => {
                    el.addEventListener('click', function() {
                        insertMention(this.dataset.type, this.dataset.id, this.dataset.name);
                    });
                    el.addEventListener('mouseenter', function() {
                        listEl.querySelectorAll('[data-active]').forEach(e => {
                            e.removeAttribute('data-active');
                            e.classList.remove('bg-red-50');
                        });
                        this.setAttribute('data-active', '');
                        this.classList.add('bg-red-50');
                    });
                });

                dropdown.classList.remove('hidden');
            }

            function createMentionItem(item, isFirst) {
                return `<div data-mention-item data-type="${item.type}" data-id="${item.id}" data-name="${item.name}"
                                    class="px-3 py-2 cursor-pointer hover:bg-gray-50 flex items-center gap-2 transition ${isFirst ? 'bg-red-50" data-active' : '"'}>
                                    <i class="${item.icon} ${item.color}"></i>
                                    <span class="text-sm text-gray-700">${item.name}</span>
                                    <span class="text-[10px] text-gray-400 ml-auto">${item.type === 'person' ? 'Person' : 'Milestone'}</span>
                                </div>`;
            }

            function insertMention(type, id, name) {
                const val = textarea.value;
                const prefix = type === 'person' ? '@' : '#';
                const syntax = `${prefix}${name} `;
                const before = val.substring(0, mentionStart);
                const after = val.substring(textarea.selectionStart);
                textarea.value = before + syntax + after;
                textarea.focus();
                const newPos = before.length + syntax.length;
                textarea.setSelectionRange(newPos, newPos);
                hideMentionDropdown();
                highlightMentions();
            }

            function hideMentionDropdown() {
                dropdown.classList.add('hidden');
                mentionMode = null;
                mentionStart = -1;
            }
        })();

        // YearlyActivity Enhancements JS logic

        function triggerUpload(msId) {
            const input = document.getElementById('file-input-' + msId);
            if (input) input.click();
        }

        function handleFileUpload(input, msId) {
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            const formData = new FormData();
            formData.append('milestone_id', msId);
            formData.append('file', file);

            Swal.fire({
                title: 'Uploading...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('?action=upload_attachment', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Uploaded!',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message || 'Upload failed', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Network error', 'error');
                });
        }

        function deleteAttachment(id, msId) {
            Swal.fire({
                title: 'ยืนยันการลบไฟล์?',
                text: "ไฟล์จะถูกลบออกอย่างถาวรและไม่สามารถกู้คืนได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    fetch('?action=delete_attachment', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                location.reload();
                            } else {
                                Swal.fire('Error', res.message || 'Failed to delete', 'error');
                            }
                        });
                }
            });
        }

        function postComment(activityId) {
            const textElem = document.getElementById('comment-text');
            if (!textElem) return;
            let text = textElem.value.trim();

            if (!text) return;

            // Format mentions: @Name -> @[Name](uid:ID)
            if (typeof peopleNames !== 'undefined' && typeof mentionData !== 'undefined') {
                peopleNames.forEach(name => {
                    // Find person by name in the mentionData.people object
                    const personEntry = Object.entries(mentionData.people).find(([id, n]) => n === name);
                    if (personEntry) {
                        const [personId, personName] = personEntry;
                        const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const regex = new RegExp('@' + escapedName + '(?![\\w\\s]*\\]\\(uid:)', 'g');
                        text = text.replace(regex, `@[${name}](uid:${personId})`);
                    }
                });
            }

            const formData = new FormData();
            formData.append('activity_id', activityId);
            formData.append('comment_text', text);

            fetch('?action=add_comment', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        textElem.value = '';
                        location.reload();
                    } else {
                        Swal.fire('Error', res.message || 'Failed to post comment', 'error');
                    }
                });
        }

        // Ensure updateStatus uses the correct parameters
        // Redefining just in case to be sure it's the latest version
        const originalUpdateStatus = window.updateStatus;
    </script>
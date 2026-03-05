<?php

/**
 * IGA Module - Test Results View
 * Accessible via ?page=results&attempt_id=123
 */

require_once __DIR__ . '/../Models/TestModel.php';
require_once __DIR__ . '/../Models/AttemptModel.php';

$testModel = new TestModel($pdo);
$attemptModel = new AttemptModel($pdo);

$attemptId = $_GET['attempt_id'] ?? null;
$userId = $user['id'] ?? null;

if (!$attemptId) {
    echo '<div class="p-8 text-center text-gray-500">ไม่พบข้อมูลการทดสอบ</div>';
    return;
}

// Fetch attempt details
$stmt = $pdo->prepare("
    SELECT uta.*, t.test_name, t.description as test_desc, t.min_passing_score, t.show_result_immediately
    FROM iga_user_test_attempts uta
    JOIN iga_tests t ON uta.test_id = t.test_id
    WHERE uta.attempt_id = :aid AND uta.user_id = :uid
    LIMIT 1
");
$stmt->execute([':aid' => $attemptId, ':uid' => $userId]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    echo '<div class="p-12 text-center">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-error-warning-line text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">เข้าถึงไม่ได้</h2>
        <p class="text-gray-500 mb-6">คุณไม่มีสิทธิ์เข้าถึงผลการทดสอบนี้ หรือไม่พบข้อมูล</p>
        <a href="?page=history" class="inline-flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-xl font-bold">ไปที่หน้าประวัติ</a>
    </div>';
    return;
}

// 4. Calculate Stats
$maxScore = 0;
$questions = $attemptModel->getAttemptQuestions($attemptId, $attempt['test_id']);
foreach ($questions as $q) $maxScore += (float)$q['score'];

$score = (float)$attempt['total_score'];
$percent = $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0;
$isPassed = ($attempt['min_passing_score'] !== null) ? ($score >= $attempt['min_passing_score']) : null;
$durationSeconds = $attempt['time_spent_seconds'] ?? 0;
$durationMin = floor($durationSeconds / 60);
$durationSec = $durationSeconds % 60;

// 5. Answer breakdown
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_questions,
        SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
        SUM(CASE WHEN ua.is_correct = 0 AND ua.user_answer_id IS NOT NULL THEN 1 ELSE 0 END) as incorrect_count,
        SUM(CASE WHEN ua.user_answer_id IS NULL THEN 1 ELSE 0 END) as unanswered_count
    FROM iga_user_attempt_questions aq
    LEFT JOIN iga_user_answers ua ON aq.attempt_id = ua.attempt_id AND aq.question_id = ua.question_id
    WHERE aq.attempt_id = :aid
");
$stmt->execute([':aid' => $attemptId]);
$breakdown = $stmt->fetch(PDO::FETCH_ASSOC);

// 6. Peer comparison data (Histogram)
$stmt = $pdo->prepare("
    SELECT total_score 
    FROM iga_user_test_attempts 
    WHERE test_id = :tid AND is_completed = 1
");
$stmt->execute([':tid' => $attempt['test_id']]);
$allScores = $stmt->fetchAll(PDO::FETCH_COLUMN);

$totalPersons = count($allScores);
$meanScore = $totalPersons > 0 ? array_sum($allScores) / $totalPersons : 0;
$medianScore = 0;
if ($totalPersons > 0) {
    sort($allScores);
    $mid = floor(($totalPersons - 1) / 2);
    if ($totalPersons % 2) {
        $medianScore = $allScores[$mid];
    } else {
        $medianScore = ($allScores[$mid] + $allScores[$mid + 1]) / 2;
    }
}
$minScore = $totalPersons > 0 ? min($allScores) : 0;
$maxScoreReached = $totalPersons > 0 ? max($allScores) : 0;
$stdDev = 0;
if ($totalPersons > 1) {
    $variance = 0;
    foreach ($allScores as $s) $variance += pow($s - $meanScore, 2);
    $stdDev = sqrt($variance / ($totalPersons - 1));
}

// Status Config
$statusCfg = [
    'passed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-100', 'icon' => 'ri-checkbox-circle-fill', 'label' => 'Passed'],
    'failed' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'border' => 'border-red-100', 'icon' => 'ri-close-circle-fill', 'label' => 'Failed'],
    'info'   => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-100', 'icon' => 'ri-information-fill', 'label' => 'Completed']
];

$currentStatus = $statusCfg['info'];
if ($isPassed === true) $currentStatus = $statusCfg['passed'];
elseif ($isPassed === false) $currentStatus = $statusCfg['failed'];

?>

<div class="max-w-[1400px] mx-auto px-4 py-8">
    <!-- Report Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <h1 class="text-4xl font-black text-primary uppercase tracking-tight">Individual Assessment Report</h1>
        <div class="flex gap-3 no-print">
            <a href="?page=history<?= $mid ?>" class="inline-flex items-center gap-2 px-6 py-2 bg-white border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition-all shadow-sm">
                <i class="ri-arrow-left-line"></i> Back to Overview Report
            </a>
        </div>
    </div>

    <!-- Info Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="bg-primary px-6 py-3">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="ri-profile-line"></i> Examinee and Assessment Information
            </h3>
        </div>
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
            <div class="space-y-4">
                <div class="flex items-start">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Examinee Name:</span>
                    <span class="text-gray-600"><?= htmlspecialchars($user['fullname'] ?? 'Unknown') ?></span>
                </div>
                <div class="flex items-start">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Email:</span>
                    <span class="text-gray-600"><?= htmlspecialchars($user['email'] ?? '-') ?></span>
                </div>
                <div class="flex items-start">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Assessment Name:</span>
                    <span class="text-gray-600 font-bold text-primary"><?= htmlspecialchars($attempt['test_name']) ?></span>
                </div>
                <div class="flex items-start">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Assessment Description:</span>
                    <span class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars(strip_tags($attempt['test_desc'])) ?></span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="flex items-center">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Start Time:</span>
                    <span class="text-gray-600"><?= date('j M Y H:i', strtotime($attempt['start_time'])) ?> u.</span>
                </div>
                <div class="flex items-center">
                    <span class="w-40 shrink-0 font-bold text-gray-900">End Time:</span>
                    <span class="text-gray-600"><?= date('j M Y H:i', strtotime($attempt['end_time'])) ?> u.</span>
                </div>
                <div class="flex items-center">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Total Time Spent:</span>
                    <span class="text-gray-600"><?= $durationMin ?> min. <?= $durationSec ?> sec.</span>
                </div>
                <div class="flex items-center">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Total Score Earned:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-black text-primary"><?= number_format($score, 2) ?> / <?= number_format($maxScore, 2) ?></span>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase rounded-full flex items-center gap-1">
                            <i class="ri-checkbox-circle-fill"></i> Completed
                        </span>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="w-40 shrink-0 font-bold text-gray-900">Assessment Result:</span>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-2 px-6 py-2 rounded-xl <?= $currentStatus['bg'] ?> <?= $currentStatus['text'] ?> border border-current font-black uppercase tracking-wider">
                            <i class="<?= $currentStatus['icon'] ?> text-lg"></i> <?= $currentStatus['label'] ?>
                        </span>
                        <?php if (($attempt['submission_status'] ?? 'normal') === 'auto_submitted_afk'): ?>
                            <span class="px-4 py-2 bg-red-600 text-white text-xs font-black uppercase rounded-xl flex items-center gap-2 animate-pulse">
                                <i class="ri-alarm-warning-fill"></i> Auto-Submitted (AFK)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Proctoring Integrity Section -->
                <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4 col-span-2">
                    <div class="p-4 rounded-2xl border-2 <?= ($attempt['afk_count'] ?? 0) > 0 ? 'border-amber-100 bg-amber-50/50' : 'border-emerald-100 bg-emerald-50/50' ?>">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-8 h-8 rounded-lg <?= ($attempt['afk_count'] ?? 0) > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' ?> flex items-center justify-center">
                                <i class="ri-window-line"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tab Switch Frequency</div>
                                <div class="text-lg font-black <?= ($attempt['afk_count'] ?? 0) > 0 ? 'text-amber-700' : 'text-emerald-700' ?>">
                                    <?= (int)($attempt['afk_count'] ?? 0) ?> สลับหน้าจอ
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 italic mt-2">
                            * จำนวนครั้งที่ผู้เข้าสอบสลับหน้าต่างหรือออกจากหน้าเบราว์เซอร์
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl border-2 <?= ($attempt['submission_status'] ?? 'normal') === 'normal' ? 'border-blue-100 bg-blue-50/50' : 'border-red-100 bg-red-50/50' ?>">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-8 h-8 rounded-lg <?= ($attempt['submission_status'] ?? 'normal') === 'normal' ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600' ?> flex items-center justify-center">
                                <i class="ri-send-plane-fill"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Submission Mode</div>
                                <div class="text-lg font-black <?= ($attempt['submission_status'] ?? 'normal') === 'normal' ? 'text-blue-700' : 'text-red-700' ?>">
                                    <?= ($attempt['submission_status'] ?? 'normal') === 'normal' ? 'Normal Submission' : 'Auto-Submitted (System Force)' ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 italic mt-2">
                            * วิธีการส่งแบบทดสอบ (ส่งเองโดยผู้เข้าสอบ หรือถูกส่งอัตโนมัติโดยระบบ)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Score Summary (Doughnut) -->
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="bg-primary px-6 py-3">
                <h3 class="text-white font-bold text-lg">Multiple Choice Question Score Summary</h3>
            </div>
            <div class="p-8 flex-grow flex flex-col items-center justify-center">
                <div class="relative w-full aspect-square max-w-[280px]">
                    <canvas id="scoreDoughnut"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Score Earned</div>
                        <div class="text-3xl font-black text-gray-900"><?= number_format($score, 2) ?></div>
                    </div>
                </div>
                <div class="mt-8 space-y-2 w-full">
                    <div class="flex justify-center items-center gap-4 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-sm bg-[#64ccc5]"></span>
                            <span class="text-xs font-bold text-gray-500 uppercase">Earned</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-sm bg-[#ff8aae]"></span>
                            <span class="text-xs font-bold text-gray-500 uppercase">Remaining</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-gray-800">Score Earned: <span class="text-emerald-500"><?= number_format($score, 2) ?> Score</span></div>
                        <div class="text-lg font-black text-gray-800">Possible Score: <?= number_format($maxScore, 2) ?> Score</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance by Category -->
        <?php
        $stmt = $pdo->prepare("
            SELECT COALESCE(c.category_name, 'General') as name, 
                   SUM(q.score) as max_score, 
                   SUM(COALESCE(ua.score_earned, 0)) as earned_score
            FROM iga_user_attempt_questions aq
            JOIN iga_questions q ON aq.question_id = q.question_id
            LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
            LEFT JOIN iga_user_answers ua ON aq.attempt_id = ua.attempt_id AND aq.question_id = ua.question_id
            WHERE aq.attempt_id = :aid
            GROUP BY q.category_id, c.category_name
        ");
        $stmt->execute([':aid' => $attemptId]);
        $catScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $chartLabels = [];
        $chartDataPercent = [];
        $chartColors = ['#ff8aae', '#f8de22', '#9ade7b', '#73e2a7', '#56cbf1', '#6d82f3', '#c689eb', '#f06292'];
        foreach ($catScores as $idx => $cs) {
            $chartLabels[] = $cs['name'];
            $chartDataPercent[] = $cs['max_score'] > 0 ? round(($cs['earned_score'] / $cs['max_score']) * 100) : 0;
        }
        ?>
        <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="bg-primary px-6 py-3 flex justify-between items-center no-print-section">
                <h3 class="text-white font-bold text-lg">Assessment Performance by Category (Percentage)</h3>
                <div class="flex items-center gap-2 no-print">
                    <span class="text-white text-xs font-bold uppercase tracking-widest">Chart type:</span>
                    <select id="chartTypeToggle" class="bg-white text-gray-900 text-xs px-3 py-1.5 rounded-lg border-0 outline-none font-bold">
                        <option value="radar">Radar chart</option>
                        <option value="bar">Bar chart</option>
                    </select>
                </div>
            </div>
            <div class="p-8 flex-grow">
                <div id="categoryChartContainer" class="w-full h-[450px]">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Peer Comparison Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <div class="lg:col-span-12 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-primary px-6 py-3">
                <h3 class="text-white font-bold text-lg">Peer Comparison (Histogram & Distribution)</h3>
            </div>
            <div class="p-8">
                <!-- Stats Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Amount of persons</div>
                        <div class="text-xl font-black text-gray-900"><?= $totalPersons ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Mean Score</div>
                        <div class="text-xl font-black text-gray-900"><?= number_format($meanScore, 2) ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Median Score</div>
                        <div class="text-xl font-black text-gray-900"><?= number_format($medianScore, 2) ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Min Score</div>
                        <div class="text-xl font-black text-gray-900"><?= number_format($minScore, 2) ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Max Score</div>
                        <div class="text-xl font-black text-gray-900"><?= number_format($maxScoreReached, 2) ?></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Std. Dev.</div>
                        <div class="text-xl font-black text-gray-900"><?= number_format($stdDev, 2) ?></div>
                    </div>
                    <div class="bg-primary p-4 rounded-xl text-center text-white">
                        <div class="text-[10px] font-bold opacity-80 uppercase tracking-widest mb-1">Your Score</div>
                        <div class="text-xl font-black text-white"><?= number_format($score, 2) ?></div>
                    </div>
                </div>

                <div class="h-[400px]">
                    <canvas id="histogramChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Answer Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-12">
        <div class="bg-primary px-6 py-3">
            <h3 class="text-white font-bold text-lg">Answer Status Table</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-8 py-4 text-sm font-black text-gray-900 uppercase tracking-tighter border-b border-gray-100">Question Type</th>
                        <th class="px-8 py-4 text-sm font-black text-gray-900 uppercase tracking-tighter border-b border-gray-100">Completion Status</th>
                        <th class="px-8 py-4 text-sm font-black text-gray-900 uppercase tracking-tighter border-b border-gray-100">Amount of questions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr>
                        <td class="px-8 py-6 font-bold text-gray-900" rowspan="3">Multiple Choice</td>
                        <td class="px-8 py-4 text-gray-600">Correct Answers</td>
                        <td class="px-8 py-4 font-bold text-emerald-600"><?= $breakdown['correct_count'] ?></td>
                    </tr>
                    <tr>
                        <td class="px-8 py-4 text-gray-600 border-l border-gray-50">Incorrect Answers</td>
                        <td class="px-8 py-4 font-bold text-red-600"><?= $breakdown['incorrect_count'] ?></td>
                    </tr>
                    <tr>
                        <td class="px-8 py-4 text-gray-600 border-l border-gray-50">Not Answered</td>
                        <td class="px-8 py-4 font-bold text-gray-400"><?= $breakdown['unanswered_count'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const primaryColor = '#b91c1c';
        const chartColors = <?= json_encode($chartColors) ?>;

        // 1. Score Doughnut
        const doughnutCtx = document.getElementById('scoreDoughnut').getContext('2d');
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Earned', 'Remaining'],
                datasets: [{
                    data: [<?= $score ?>, <?= $maxScore - $score ?>],
                    backgroundColor: ['#64ccc5', '#ff8aae'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 2. Category Performance Chart
        const categoryLabels = <?= json_encode($chartLabels) ?>;
        const categoryData = <?= json_encode($chartDataPercent) ?>;
        let categoryChart;

        function createCategoryChart(type) {
            if (categoryChart) categoryChart.destroy();

            const ctx = document.getElementById('categoryChart').getContext('2d');
            const config = {
                type: type,
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Performance (%)',
                        data: categoryData,
                        backgroundColor: type === 'bar' ? chartColors : primaryColor + '22',
                        borderColor: type === 'radar' ? primaryColor : 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: primaryColor,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: type === 'bar' ? {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: value => value + '%'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    } : {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                display: false
                            },
                            grid: {
                                color: '#f1f5f9'
                            },
                            angleLines: {
                                color: '#f1f5f9'
                            },
                            pointLabels: {
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                padding: 10
                            }
                        }
                    }
                }
            };
            categoryChart = new Chart(ctx, config);
        }

        createCategoryChart('radar');
        const toggler = document.getElementById('chartTypeToggle');
        if (toggler) {
            toggler.addEventListener('change', e => createCategoryChart(e.target.value));
        }

        // 3. Histogram Chart
        const allScores = <?= json_encode($allScores) ?>;
        const yourScore = <?= $score ?>;

        // Group scores into bins
        const binsCount = Math.min(20, Math.ceil(<?= $maxScore ?>));
        const binSize = <?= $maxScore ?> / binsCount;
        const bins = Array(binsCount + 1).fill(0);
        allScores.forEach(s => {
            const binIdx = Math.min(binsCount, Math.floor(s / binSize));
            bins[binIdx]++;
        });

        // Compute normal curve
        const n = allScores.length;
        const mean = n > 0 ? allScores.reduce((a, b) => a + parseFloat(b), 0) / n : 0;
        const stddev = n > 1 ? Math.sqrt(allScores.reduce((sum, s) => sum + Math.pow(parseFloat(s) - mean, 2), 0) / (n - 1)) : 1;

        // Normal PDF scaled to histogram frequency
        function normalPDF(x, mu, sigma) {
            return (1 / (sigma * Math.sqrt(2 * Math.PI))) * Math.exp(-0.5 * Math.pow((x - mu) / sigma, 2));
        }
        const normalCurveData = Array.from({
            length: binsCount + 1
        }, (_, i) => {
            const x = i * binSize;
            return n * binSize * normalPDF(x, mean, stddev);
        });

        const histogramCtx = document.getElementById('histogramChart').getContext('2d');
        new Chart(histogramCtx, {
            type: 'bar',
            data: {
                labels: Array.from({
                    length: binsCount + 1
                }, (_, i) => Math.round(i * binSize)),
                datasets: [{
                        label: 'Frequency',
                        data: bins,
                        backgroundColor: 'rgba(185, 28, 28, 0.1)',
                        borderColor: primaryColor,
                        borderWidth: 1,
                        barPercentage: 1,
                        categoryPercentage: 1,
                        order: 2
                    },
                    {
                        label: 'Normal Distribution',
                        type: 'line',
                        data: normalCurveData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2.5,
                        borderDash: [6, 3],
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                        order: 1
                    },
                    {
                        label: 'You are here',
                        type: 'bubble',
                        data: [{
                            x: Math.round(yourScore / binSize),
                            y: (bins[Math.min(binsCount, Math.floor(yourScore / binSize))] || 0) + 0.2,
                            r: 6
                        }],
                        backgroundColor: primaryColor,
                        borderColor: '#fff',
                        borderWidth: 2,
                        order: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: context => {
                                if (context.dataset.label === 'You are here') return `Your score: ${yourScore}`;
                                return `Count: ${context.parsed.y} persons`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Score Range',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Amount of persons',
                            font: {
                                weight: 'bold'
                            }
                        },
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>

<style>
    @media print {

        .sidebar,
        .top-bar,
        nav,
        .no-print,
        #chartTypeToggle,
        .no-print-section {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .max-w-\[1400px\] {
            max-width: 100% !important;
            padding: 0 !important;
        }

        .bg-white {
            border: none !important;
            box-shadow: none !important;
        }

        .rounded-2xl {
            border-radius: 0 !important;
        }

        .border {
            border-color: #eee !important;
        }

        body {
            background: white !important;
        }
    }
</style>
<?php
// User/Admin Result View
// Params: $attempt, $sectionResults
?>
<div class="max-w-5xl mx-auto pb-12 print:max-w-none print:pb-0" id="report-content">

    <!-- Header Actions -->
    <div class="flex justify-between items-center mb-8 print:hidden">
        <a href="javascript:history.back()" class="flex items-center gap-2 text-gray-500 hover:text-gray-700 transition-colors">
            <i class="ri-arrow-left-line"></i> กลับ
        </a>
        <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-colors shadow-lg">
            <i class="ri-printer-line"></i> พิมพ์รายงาน
        </button>
    </div>

    <!-- Report Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 print:shadow-none print:border-none">
        <!-- Header Ribbon -->
        <div class="bg-primary/90 text-white p-8 relative print:bg-white print:text-black print:p-0 print:border-b print:border-gray-300 print:mb-6">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl print:hidden"></div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
                <div>
                    <h1 class="text-3xl font-bold"><?= htmlspecialchars($attempt['test_name']) ?></h1>
                    <div class="mt-2 flex items-center gap-4 text-primary-100 print:text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="ri-user-line"></i>
                            <span class="font-medium"><?= htmlspecialchars($attempt['fullname']) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-calendar-line"></i>
                            <span><?= date('d/m/Y H:i', strtotime($attempt['start_time'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 print:border print:border-gray-800 print:bg-transparent">
                    <div class="text-xs uppercase tracking-widest opacity-80 mb-1">ผลการประเมิน</div>
                    <?php
                    // Recalculate pass status based on new logic if needed, or use controller logic
                    $isPassed = $attempt['total_score'] >= $attempt['pass_score'];
                    ?>
                    <?php if ($isPassed): ?>
                        <div class="text-2xl font-bold text-green-300 flex items-center gap-2 print:text-black">
                            <i class="ri-checkbox-circle-fill"></i> ผ่านเกณฑ์
                        </div>
                    <?php else: ?>
                        <div class="text-2xl font-bold text-red-300 flex items-center gap-2 print:text-black">
                            <i class="ri-close-circle-fill"></i> ไม่ผ่านเกณฑ์
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="p-8 print:p-0">
            <!-- Score Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Total Score Area -->
                <div class="col-span-1 bg-gray-50 rounded-2xl p-6 border border-gray-100 flex flex-col items-center justify-center text-center print:border print:border-gray-200">
                    <div class="text-sm text-gray-500 uppercase tracking-widest font-semibold mb-2">คะแนนรวม</div>
                    <div class="text-6xl font-bold text-primary mb-2"><?= floatval($attempt['total_score']) ?></div>
                    <div class="text-gray-400 text-sm font-medium">จาก <?= floatval($sectionResults ? array_sum(array_column($sectionResults, 'section_max_score')) : 0) ?> คะแนน</div>
                </div>

                <!-- Analysis Chart -->
                <div class="col-span-2 h-64 print:h-80">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="ri-pie-chart-2-line text-primary"></i> วิเคราะห์ผลรายด้าน
                    </h3>
                    <canvas id="scoreChart"></canvas>
                </div>
            </div>

            <hr class="border-gray-100 mb-12 print:border-gray-300">

            <!-- Detailed Breakdown -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="ri-file-list-3-line text-primary"></i> รายละเอียดคะแนนตามหมวดหมู่
                </h3>

                <div class="overflow-hidden rounded-xl border border-gray-100 shadow-sm print:border-gray-300">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100 print:bg-gray-100">
                            <tr>
                                <th class="px-6 py-4">หมวดหมู่ (Section)</th>
                                <th class="px-6 py-4 text-center">คะแนนเต็ม</th>
                                <th class="px-6 py-4 text-center">คะแนนที่ได้</th>
                                <th class="px-6 py-4 text-right">คิดเป็นร้อยละ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 print:divide-gray-300">
                            <?php foreach ($sectionResults as $sec):
                                $percent = $sec['section_max_score'] > 0 ? ($sec['section_score'] / $sec['section_max_score']) * 100 : 0;
                            ?>
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($sec['section_title']) ?></td>
                                    <td class="px-6 py-4 text-center text-gray-500"><?= floatval($sec['section_max_score']) ?></td>
                                    <td class="px-6 py-4 text-center font-bold text-primary"><?= floatval($sec['section_score']) ?></td>
                                    <td class="px-6 py-4 text-right text-gray-600">
                                        <div class="flex items-center justify-end gap-3">
                                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden print:hidden">
                                                <div class="h-full bg-primary" style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <span class="w-12"><?= number_format($percent, 1) ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signature Area for Print -->
            <div class="hidden print:flex mt-16 justify-between items-end pt-8 border-t border-gray-300">
                <div class="text-center w-48">
                    <div class="h-16 border-b border-dotted border-gray-400 mb-2"></div>
                    <div class="text-sm">เจ้าหน้าที่คุมสอบ</div>
                </div>
                <div class="text-center w-48">
                    <div class="h-16 border-b border-dotted border-gray-400 mb-2"></div>
                    <div class="text-sm">ผู้เข้าสอบ</div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('scoreChart').getContext('2d');
    const data = {
        labels: <?= json_encode(array_column($sectionResults, 'section_title')) ?>,
        datasets: [{
            label: 'คะแนนที่ได้',
            data: <?= json_encode(array_column($sectionResults, 'section_score')) ?>,
            backgroundColor: 'rgba(162, 29, 33, 0.2)',
            borderColor: 'rgba(162, 29, 33, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(162, 29, 33, 1)',
            fill: true
        }, {
            label: 'คะแนนเต็ม',
            data: <?= json_encode(array_column($sectionResults, 'section_max_score')) ?>,
            backgroundColor: 'rgba(200, 200, 200, 0.1)',
            borderColor: 'rgba(200, 200, 200, 0.5)',
            borderWidth: 1,
            borderDash: [5, 5],
            fill: false
        }]
    };

    const config = {
        type: 'radar', // Radar chart is great for skills/sections assessment
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    suggestedMin: 0
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    };

    new Chart(ctx, config);
</script>

<style>
    @media print {
        body {
            background: white;
        }

        nav,
        header,
        aside,
        #sidebar,
        #sidebar-overlay {
            display: none !important;
        }

        .print\:hidden {
            display: none !important;
        }

        .print\:max-w-none {
            max-width: none !important;
        }

        .print\:shadow-none {
            box-shadow: none !important;
        }

        .print\:border-none {
            border: none !important;
        }

        #report-content {
            padding: 0;
            margin: 0;
            width: 100%;
        }
    }
</style>
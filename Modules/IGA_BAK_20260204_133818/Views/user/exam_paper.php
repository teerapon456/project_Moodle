<?php
// Exam Paper View
?>
<div class="max-w-4xl mx-auto pb-24">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-16 z-20 shadow-sm mx-[-2rem] px-8 py-4 mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold text-gray-800 line-clamp-1"><?= htmlspecialchars($attempt['test_name']) ?></h1>
            <div class="text-xs text-gray-500 mt-0.5">ส่วนที่ <?= isset($sections[0]) ? '1 จาก ' . count($sections) : '1' ?></div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                <i class="ri-timer-line text-gray-500"></i>
                <span id="timer" class="font-mono font-bold text-gray-700 text-lg">--:--:--</span>
            </div>
        </div>
    </div>

    <form id="examForm" action="index.php?controller=exam&action=submit" method="post" class="space-y-8">
        <input type="hidden" name="attempt_id" value="<?= $attempt['attempt_id'] ?>">

        <?php foreach ($sections as $index => $section): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800 text-lg">ส่วนที่ <?= $index + 1 ?>: <?= htmlspecialchars($section['section_title']) ?></h2>
                    <?php if ($section['instructions']): ?>
                        <p class="text-sm text-gray-500 mt-1"><?= nl2br(htmlspecialchars($section['instructions'])) ?></p>
                    <?php endif; ?>
                </div>

                <div class="p-6 space-y-8 divide-y divide-gray-100">
                    <?php foreach ($section['questions'] as $qIndex => $q): ?>
                        <div class="pt-8 first:pt-0" id="q_<?= $q['question_id'] ?>">
                            <div class="mb-4">
                                <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded mb-2 inline-block">ข้อที่ <?= $q['question_order'] ?></span>
                                <span class="text-gray-400 text-xs ml-2"><?= floatval($q['points']) ?> คะแนน</span>
                                <div class="text-gray-800 font-medium text-lg leading-relaxed mt-1">
                                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                </div>
                            </div>

                            <div class="pl-4 border-l-2 border-gray-100 space-y-3">
                                <?php if ($q['question_type'] === 'single_choice'): ?>
                                    <?php foreach ($q['options'] as $opt): ?>
                                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 hover:border-gray-300 cursor-pointer transition-all group">
                                            <div class="flex items-center h-5 mt-0.5">
                                                <input type="radio"
                                                    name="q[<?= $q['question_id'] ?>]"
                                                    value="<?= $opt['option_id'] ?>"
                                                    class="h-4 w-4 text-primary border-gray-300 focus:ring-primary"
                                                    onchange="saveAnswer(<?= $attempt['attempt_id'] ?>, <?= $q['question_id'] ?>, this.value, 'option')">
                                            </div>
                                            <span class="text-gray-700 group-hover:text-gray-900 select-none"><?= htmlspecialchars($opt['option_text']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php elseif ($q['question_type'] === 'short_answer'): ?>
                                    <textarea
                                        name="q[<?= $q['question_id'] ?>]"
                                        rows="3"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm"
                                        placeholder="พิมพ์คำตอบของคุณที่นี่..."
                                        onblur="saveAnswer(<?= $attempt['attempt_id'] ?>, <?= $q['question_id'] ?>, this.value, 'text')"></textarea>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>

<!-- Sticky Footer -->
<div class="fixed bottom-0 left-0 md:left-64 right-0 bg-white border-t border-gray-200 p-4 shadow-lg z-30 flex items-center justify-between">
    <div class="flex items-center gap-2 text-sm text-gray-500" id="saveStatus">
        <i class="ri-cloud-line"></i>
        <span>พร้อมบันทึก</span>
    </div>
    <button type="button" onclick="if(confirm('ยืนยันการส่งข้อสอบ? เมื่อส่งแล้วจะไม่สามารถแก้ไขคำตอบได้')) document.getElementById('examForm').submit();" class="bg-success hover:bg-green-600 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg shadow-green-500/30 transition-all flex items-center gap-2">
        <i class="ri-send-plane-fill"></i>
        <span>ส่งคำตอบ</span>
    </button>
</div>

<script>
    // Timer Logic (Simple Client-side)
    // TODO: Sync with server start time

    function saveAnswer(attemptId, questionId, value, type) {
        const status = document.getElementById('saveStatus');
        status.className = 'flex items-center gap-2 text-sm text-blue-600';
        status.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> <span>กำลังบันทึก...</span>';

        fetch('index.php?controller=exam&action=saveAnswer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    question_id: questionId,
                    answer: value,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    status.className = 'flex items-center gap-2 text-sm text-green-600';
                    status.innerHTML = '<i class="ri-checkbox-circle-line"></i> <span>บันทึกแล้ว</span>';
                    setTimeout(() => {
                        status.className = 'flex items-center gap-2 text-sm text-gray-500';
                        status.innerHTML = '<i class="ri-cloud-line"></i> <span>บันทึกอัตโนมัติ</span>';
                    }, 2000);
                } else {
                    status.className = 'flex items-center gap-2 text-sm text-red-600';
                    status.innerHTML = '<i class="ri-error-warning-line"></i> <span>บันทึกไม่สำเร็จ</span>';
                }
            })
            .catch(err => {
                console.error(err);
                status.className = 'flex items-center gap-2 text-sm text-red-600';
                status.innerHTML = '<i class="ri-wifi-off-line"></i> <span>การเชื่อมต่อขัดข้อง</span>';
            });
    }
</script>
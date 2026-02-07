<?php
// Exam Intro View
?>
<div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-primary/90 p-8 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>

            <h1 class="text-2xl font-bold relative z-10"><?= htmlspecialchars($test['test_name']) ?></h1>
            <p class="text-primary-100 text-sm mt-2 relative z-10 opacity-90">โปรดอ่านคำชี้แจงอย่างละเอียด</p>
        </div>

        <div class="p-8">
            <div class="prose prose-sm max-w-none text-gray-600 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">คำชี้แจง</h3>
                <p class="leading-relaxed"><?= nl2br(htmlspecialchars($test['description'] ?? 'ไม่มีรายละเอียดเพิ่มเติม')) ?></p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-4 border border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                        <i class="ri-timer-line"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">ระยะเวลา</div>
                        <div class="font-bold text-gray-800"><?= $test['duration_minutes'] == 0 ? 'ไม่จำกัด' : $test['duration_minutes'] . ' นาที' ?></div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-4 border border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl">
                        <i class="ri-award-line"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">เกณฑ์การผ่าน</div>
                        <div class="font-bold text-gray-800"><?= floatval($test['min_passing_score']) ?> คะแนน</div>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <form action="index.php?controller=exam&action=start" method="post" class="space-y-6">
                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">

                <div class="flex items-start gap-3 p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                    <div class="flex items-center h-5 mt-1">
                        <input id="agree" name="agree" type="checkbox" required class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                    </div>
                    <label for="agree" class="text-sm text-yellow-800 select-none cursor-pointer">
                        ข้าพเจ้ายอมรับข้อตกลงและเข้าใจว่าเมื่อเริ่มทำแบบทดสอบแล้ว เวลาจะเริ่มนับทันที (กรณีมีการจับเวลา)
                    </label>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg shadow-primary/30 transition-all transform hover:-translate-y-0.5">
                        เริ่มทำแบบทดสอบ
                    </button>
                    <a href="index.php?controller=exam&action=index" class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">
                        กลับหน้าหลัก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
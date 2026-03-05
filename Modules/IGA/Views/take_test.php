<?php

/**
 * IGA Take Test View
 * Handles initialization, display, and AJAX syncing for the test interface.
 */

require_once __DIR__ . '/../Models/TestModel.php';
require_once __DIR__ . '/../Models/AttemptModel.php';
require_once __DIR__ . '/../Models/QuestionModel.php';

$testModel = new TestModel($pdo);
$attemptModel = new AttemptModel($pdo);
$questionModel = new QuestionModel($pdo);

// 1. Handle AJAX actions (Interception point)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    $actionType = $_GET['type'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);

    header('Content-Type: application/json');
    try {
        if ($actionType === 'save_answer') {
            $success = $attemptModel->saveAnswer(
                $input['attempt_id'],
                $input['question_id'],
                $input['answer'],
                $input['time_spent'] ?? 0
            );
            echo json_encode(['success' => $success]);
            exit;
        } elseif ($actionType === 'update_state') {
            $success = $attemptModel->updateAttemptState(
                $input['attempt_id'],
                $input['current_index'],
                $input['total_time_spent']
            );
            echo json_encode(['success' => $success]);
            exit;
        } elseif ($actionType === 'update_section_time') {
            $success = $attemptModel->updateSectionTime(
                $input['attempt_id'],
                $input['section_id'],
                $input['time_spent_seconds']
            );
            echo json_encode(['success' => $success]);
            exit;
        } elseif ($actionType === 'update_afk_count') {
            $success = $attemptModel->updateAfkCount(
                $input['attempt_id'],
                $input['afk_count']
            );
            echo json_encode(['success' => $success]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// 2. Handle Test Submission (POST redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'submit_test') {
    $attemptId = $_POST['attempt_id'];
    $afkCount = $_POST['afk_count'] ?? 0;
    $submissionStatus = $_POST['submission_status'] ?? 'normal';
    $attemptModel->finishAttempt($attemptId, $afkCount, $submissionStatus);
    header("Location: ?page=results&attempt_id=" . $attemptId . (isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : ''));
    exit;
}

// 3. Initialize Attempt
$testId = $_POST['test_id'] ?? ($_GET['test_id'] ?? null);
$attemptId = $_POST['attempt_id'] ?? null;

if (!$testId) {
    echo '<div class="alert alert-danger">ไม่พบรหัสแบบทดสอบ</div>';
    return;
}

$userId = $isApplicant ? 'APP_' . $_SESSION['applicant_id'] : $user['id'];
$attempt = $attemptModel->getOrCreateAttempt($userId, $testId, $attemptId);
$attemptId = $attempt['attempt_id'];

$testInfo = $testModel->getTestById($testId);
$questions = $attemptModel->getAttemptQuestions($attemptId, $testId);
$userAnswers = $attemptModel->getAttemptAnswers($attemptId);
$sectionTimes = $attemptModel->getSectionTimes($attemptId);

// Map section times for easier access
$mappedSectionTimes = [];
foreach ($sectionTimes as $st) {
    $mappedSectionTimes[$st['section_id']] = $st['time_spent_seconds'];
}

$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';
?>

<div class="max-w-[1400px] mx-auto px-4 lg:px-6" id="testApp">
    <!-- Header: Stats & Timer -->
    <div class="sticky top-[64px] z-30 mb-8 -mx-4 lg:-mx-6 px-4 lg:px-6 py-4 bg-white border-b border-gray-100 shadow-sm transition-shadow duration-300" id="stickyHeader">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-maroon-50 flex items-center justify-center text-primary text-2xl shrink-0 shadow-sm">
                    <i class="ri-graduation-cap-fill"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 leading-tight"><?= htmlspecialchars($testInfo['test_name']) ?></h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-wider">
                            <i class="ri-pushpin-line"></i> <span id="questionProgress">ข้อที่ 1 / <?= count($questions) ?></span>
                        </span>
                        <div class="h-1 w-32 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary transition-all duration-500" id="progressBar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between md:justify-end gap-3 md:gap-8">
                <div class="flex items-center gap-6">
                    <div id="sectionTimerWrapper" class="hidden text-right border-r border-gray-100 pr-6">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">เวลารายส่วน</div>
                        <div class="text-xl font-black text-amber-500 tabular-nums" id="sectionTimer">00:00</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">เวลาคงเหลือ</div>
                        <div class="text-2xl font-black text-primary tabular-nums" id="overallTimer">00:00</div>
                    </div>
                </div>
                <button class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-600 rounded-xl text-sm font-bold transition-all active:scale-95 group" id="btnExit">
                    <i class="ri-logout-box-r-line text-lg group-hover:rotate-180 transition-transform duration-500"></i> ออก
                </button>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Question Area -->
        <div class="flex-grow">
            <!-- AFK Warning Banner -->
            <div id="afkWarning" class="hidden mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-4 animate-bounce">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                    <i class="ri-error-warning-fill text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-800">ตรวจพบการสลับหน้าจอ!</h4>
                    <p class="text-xs text-red-600">กรุณาทำแบบทดสอบอย่างต่อเนื่อง การสลับหน้าจอบ่อยครั้งอาจส่งผลต่อคะแนนหรือระบบจะส่งคำตอบทันที</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden min-vh-50 flex flex-col" id="questionCard">
                <div class="px-8 py-6 bg-gray-50/50 border-b border-gray-100">
                    <div class="flex items-center gap-2 text-primary text-xs font-bold uppercase tracking-widest mb-1">
                        <i class="ri-layout-grid-line"></i> Section
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900" id="sectionName">...</h2>
                    <p class="text-sm text-gray-400 mt-1" id="sectionDesc"></p>
                </div>

                <div class="px-8 py-10 flex-grow">
                    <div id="questionContent" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <!-- Loaded via JS -->
                    </div>
                </div>

                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between gap-4">
                    <button class="inline-flex items-center gap-2 px-8 py-3 bg-white border border-gray-200 text-gray-700 hover:border-primary hover:text-primary rounded-2xl font-bold transition-all disabled:opacity-30 disabled:pointer-events-none active:scale-95" id="btnPrev">
                        <i class="ri-arrow-left-s-line text-xl"></i> ก่อนหน้า
                    </button>
                    <div>
                        <button class="inline-flex items-center gap-2 px-10 py-3 bg-primary hover:bg-maroon-800 text-white rounded-2xl font-bold shadow-lg shadow-red-200 transition-all active:scale-95 group/btn" id="btnNext">
                            ข้อถัดไป <i class="ri-arrow-right-s-line text-xl group-hover/btn:translate-x-1 transition-transform"></i>
                        </button>
                        <button class="hidden inline-flex items-center gap-2 px-10 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all active:scale-95 group/btn" id="btnSubmit">
                            ส่งคำตอบ <i class="ri-send-plane-fill text-xl group-hover/btn:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Navigation Grid -->
        <div class="w-full lg:w-80 shrink-0">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-gray-900">แผงควบคุมข้อสอบ</h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= count($questions) ?> ITEMS</span>
                </div>

                <div class="grid grid-cols-5 gap-2 mb-8" id="navGrid">
                    <?php foreach ($questions as $idx => $q): ?>
                        <button class="w-full aspect-square flex items-center justify-center rounded-xl text-xs font-bold transition-all border q-nav-btn sm:hover:scale-110 active:scale-95"
                            data-index="<?= $idx ?>"
                            id="qnav_<?= $idx ?>">
                            <?= $idx + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-4">
                    <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white shrink-0">
                            <i class="ri-save-3-line"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Auto-save Active</div>
                            <p class="text-[10px] text-emerald-600">คำตอบถูกบันทึกไว้อย่างปลอดภัย</p>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                        <div class="flex items-center gap-2 mb-2 text-blue-800 text-xs font-bold">
                            <i class="ri-information-line"></i> ควรรู้
                        </div>
                        <ul class="text-[10px] text-blue-600 space-y-1.5 list-disc pl-4 leading-relaxed">
                            <li>กรุณาอย่ารีเฟรชหรือสลับหน้าเบราว์เซอร์</li>
                            <li>หากหมดเวลา ระบบจะส่งคำตอบให้อัตโนมัติ</li>
                            <li>ปุ่มจะเป็นสีเขียวเมื่อตอบคำถามแล้ว</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="submitForm" method="POST" action="?page=take_test<?= $mid ?>">
    <input type="hidden" name="form_action" value="submit_test">
    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
    <input type="hidden" id="formAfkCount" name="afk_count" value="0">
    <input type="hidden" id="formSubmissionStatus" name="submission_status" value="normal">
</form>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    (function() {
        // 1. Data initialization
        const questions = <?= json_encode($questions) ?>;
        const initialAnswers = <?= json_encode($userAnswers) ?>;
        const initialSectionTimes = <?= json_encode($mappedSectionTimes) ?>;
        const attemptId = <?= (int)$attemptId ?>;
        const totalDuration = <?= (int)($testInfo['duration_minutes'] ?? 0) ?>;
        const initialTimeSpent = <?= (int)($attempt['time_spent_seconds'] ?? 0) ?>;
        const initialIndex = <?= (int)($attempt['current_question_index'] ?? 0) ?>;
        const ajaxUrl = '?page=take_test&action=fetch_data';

        let currentIndex = initialIndex;
        let answers = {
            ...initialAnswers
        };
        let sectionTimes = {
            ...initialSectionTimes
        };
        let overallSeconds = Math.max(0, totalDuration * 60 - initialTimeSpent);
        let currentSectionSeconds = 0;

        // AFK Tracking
        let afkCount = <?= (int)($attempt['afk_count'] ?? 0) ?>;
        let afkLimit = 4;
        let lastVisibilityState = 'visible';

        // Timer intervals
        let timerInterval = null;
        let sectionInterval = null;

        // UI Elements
        const qContent = document.getElementById('questionContent');
        const qProgress = document.getElementById('questionProgress');
        const progressBar = document.getElementById('progressBar');
        const overallTimerEl = document.getElementById('overallTimer');
        const sectionTimerEl = document.getElementById('sectionTimer');
        const sectionTimerWrapper = document.getElementById('sectionTimerWrapper');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnExit = document.getElementById('btnExit');
        const afkBanner = document.getElementById('afkWarning');

        // Audio Alarm System
        let audioCtx = null;
        let alarmInterval = null;

        function playAlarm() {
            if (alarmInterval) return;

            if (!audioCtx) {
                audioCtx = new(window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const beep = () => {
                if (!audioCtx) return;
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'square';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note

                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);

                osc.connect(gain);
                gain.connect(audioCtx.destination);

                osc.start();
                osc.stop(audioCtx.currentTime + 0.1);
            };

            // Start beeping every 500ms
            beep();
            alarmInterval = setInterval(beep, 500);
        }

        function stopAlarm() {
            if (alarmInterval) {
                clearInterval(alarmInterval);
                alarmInterval = null;
            }
        }

        // 2. Main Logic
        function init() {
            startOverallTimer();
            renderQuestion();
            updateNavigationState();
            setupAFKDetection();

            // Auto-save state every 30 seconds
            setInterval(syncState, 30000);
        }

        // Inactivity Tracking (1 minute)
        let inactivityTimer = null;
        const INACTIVITY_LIMIT = 60000; // 1 minute

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                // Only show warning if not already in another alert
                if (!Swal.isVisible()) {
                    playAlarm();
                    Swal.fire({
                        title: 'ท่านยังอยู่หรือไม่?',
                        text: 'ไม่พบกิจกรรมบนหน้าจอเป็นเวลา 1 นาทีแล้ว กรุณากดปุ่มเพื่อสอบต่อ',
                        icon: 'info',
                        confirmButtonText: 'ทำข้อสอบต่อ',
                        confirmButtonColor: '#10b981',
                        allowOutsideClick: false
                    }).then(() => {
                        stopAlarm();
                    });
                }
            }, INACTIVITY_LIMIT);
        }

        function setupAFKDetection() {
            // Document visibility
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    handleAFKDetected('Tab Switch (Hidden)');
                }
            });

            // Window blur
            window.addEventListener('blur', () => {
                handleAFKDetected('Tab Switch (Blur)');
            });

            // Re-gain focus
            window.addEventListener('focus', () => {
                afkBanner.classList.add('hidden');
            });

            // Inactivity resets
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
                document.addEventListener(evt, resetInactivityTimer, true);
            });
            resetInactivityTimer();
        }

        function handleAFKDetected(reason) {
            afkCount++;

            // Persist AFK count immediately
            fetch(`${ajaxUrl}&type=update_afk_count`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    afk_count: afkCount
                })
            });

            afkBanner.classList.remove('hidden');

            console.warn(`${reason} detected: ${afkCount}`);

            const remaining = afkLimit - afkCount;

            if (afkCount >= afkLimit) {
                // Too many leaves, auto submit
                stopAlarm();
                document.getElementById('formSubmissionStatus').value = 'auto_submitted_afk';
                finishTest('ตรวจพบการสลับหน้าจอเกินกำหนด (Auto-submitted ระบบระงับการสอบ)');
            } else {
                playAlarm();
                Swal.fire({
                    title: 'คำเตือนความเสี่ยง',
                    text: `ตรวจพบการสลับหน้าจอ! ท่านเหลือโอกาสอีก ${remaining} ครั้ง หากทำอีกระบบจะส่งคำตอบทันที`,
                    icon: 'warning',
                    confirmButtonText: 'รับทราบ',
                    confirmButtonColor: '#b91c1c',
                    allowOutsideClick: false
                }).then(() => {
                    stopAlarm();
                });
            }
        }

        function renderQuestion() {
            const q = questions[currentIndex];
            const answer = answers[q.question_id] || '';

            document.getElementById('sectionName').textContent = q.section_name;
            document.getElementById('sectionDesc').textContent = q.section_description || '';

            let html = `<div class="text-xl font-bold text-gray-800 leading-relaxed">${q.question_text}</div>`;

            if (q.question_type === 'multiple_choice' || q.question_type === 'true_false') {
                html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6">`;
                q.question_options.forEach(opt => {
                    const isChecked = String(answer) === String(opt.option_id);
                    html += `
                    <label class="group relative flex items-center p-5 rounded-2xl border-2 cursor-pointer transition-all h-full ${isChecked ? 'border-primary bg-maroon-50' : 'border-gray-50 bg-gray-50 hover:border-gray-200'}">
                        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-all ${isChecked ? 'border-primary bg-primary' : 'border-gray-300 bg-white group-hover:border-primary'}">
                            <div class="w-2 h-2 rounded-full bg-white transition-transform ${isChecked ? 'scale-100' : 'scale-0'}"></div>
                        </div>
                        <input type="radio" name="q_${q.question_id}" value="${opt.option_id}" ${isChecked ? 'checked' : ''} class="absolute opacity-0">
                        <span class="ml-4 font-bold leading-relaxed ${isChecked ? 'text-primary' : 'text-gray-600'} transition-colors">${opt.option_text}</span>
                    </label>
                `;
                });
                html += `</div>`;
            } else if (q.question_type === 'accept') {
                const isChecked = String(answer) === '1';
                html += `
                <div class="pt-6">
                    <label class="flex items-start gap-4 p-6 border-2 border-dashed rounded-[2rem] cursor-pointer transition-all ${isChecked ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-emerald-200'}">
                        <input type="checkbox" id="acceptCheck" ${isChecked ? 'checked' : ''} class="w-6 h-6 mt-1 rounded-lg border-2 border-gray-300 text-emerald-500 focus:ring-emerald-500 transition-all shrink-0">
                        <div>
                            <h4 class="font-bold text-gray-900">บันทึกความเข้าใจและยอมรับ</h4>
                            <p class="text-sm text-gray-500 mt-1">ข้าพเจ้ายอมรับว่าได้รับข้อความและทำความเข้าใจเนื้อหาสาระสำคัญทั้งหมดแล้ว</p>
                        </div>
                    </label>
                </div>
            `;
            } else if (q.question_type === 'short_answer' || q.question_type === 'open_ended' || q.question_type === 'essay') {
                html += `
                <div class="pt-6">
                    <textarea class="w-full p-6 border-2 border-gray-50 bg-gray-50 rounded-3xl focus:bg-white focus:border-primary focus:ring-4 focus:ring-red-50 outline-none transition-all text-gray-800 font-medium leading-relaxed" 
                              rows="6" placeholder="พิมพ์คำตอบของคุณที่นี่...">${answer}</textarea>
                    <div class="mt-2 text-[10px] text-gray-400 font-bold uppercase tracking-widest text-right">บันทึกอัตโนมัติเมื่อท่านหยุดพิมพ์</div>
                </div>
            `;
            }

            qContent.innerHTML = html;

            // Setup change listeners
            const inputs = qContent.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                const eventType = input.tagName === 'TEXTAREA' ? 'input' : 'change';
                let debounceTimer;

                input.addEventListener(eventType, (e) => {
                    const value = e.target.type === 'checkbox' ? (e.target.checked ? '1' : '0') : e.target.value;

                    if (input.tagName === 'TEXTAREA') {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => saveLocalAnswer(q.question_id, value), 500);
                    } else {
                        saveLocalAnswer(q.question_id, value);
                        // Visual feedback for radios
                        if (e.target.type === 'radio') renderQuestion();
                    }
                });
            });

            manageSectionTimer(q.section_id, q.section_duration);
        }

        function saveLocalAnswer(qid, val) {
            answers[qid] = val;
            updateNavigationState();

            // AJAX save
            fetch(`${ajaxUrl}&type=save_answer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    question_id: qid,
                    answer: val
                })
            });
        }

        function updateNavigationState() {
            qProgress.textContent = `ข้อที่ ${currentIndex + 1} / ${questions.length}`;
            progressBar.style.width = `${((currentIndex + 1) / questions.length) * 100}%`;

            btnPrev.disabled = currentIndex === 0;

            if (currentIndex === questions.length - 1) {
                btnNext.classList.add('hidden');
                btnSubmit.classList.remove('hidden');
            } else {
                btnNext.classList.remove('hidden');
                btnSubmit.classList.add('hidden');
            }

            // Update grid colors
            questions.forEach((q, i) => {
                const btn = document.getElementById(`qnav_${i}`);
                if (i === currentIndex) {
                    btn.className = 'w-full aspect-square flex items-center justify-center rounded-xl text-xs font-bold transition-all border-2 border-primary bg-primary text-white shadow-lg shadow-red-200 q-nav-btn';
                } else if (answers[q.question_id]) {
                    btn.className = 'w-full aspect-square flex items-center justify-center rounded-xl text-xs font-bold transition-all border-emerald-500 bg-emerald-500 text-white q-nav-btn';
                } else {
                    btn.className = 'w-full aspect-square flex items-center justify-center rounded-xl text-xs font-bold transition-all border-gray-100 bg-gray-50 text-gray-400 hover:border-gray-200 hover:text-gray-600 q-nav-btn';
                }
            });
        }

        function manageSectionTimer(sectionId, duration) {
            if (sectionInterval) clearInterval(sectionInterval);

            if (!duration || duration <= 0) {
                sectionTimerWrapper.classList.add('hidden');
                return;
            }

            sectionTimerWrapper.classList.remove('hidden');
            const spent = sectionTimes[sectionId] || 0;
            currentSectionSeconds = Math.max(0, duration * 60 - spent);

            updateTimerDisplay(sectionTimerEl, currentSectionSeconds);

            sectionInterval = setInterval(() => {
                currentSectionSeconds--;
                sectionTimes[sectionId] = (sectionTimes[sectionId] || 0) + 1;
                updateTimerDisplay(sectionTimerEl, currentSectionSeconds, true);

                if (currentSectionSeconds <= 0) {
                    clearInterval(sectionInterval);
                    Swal.fire({
                        title: 'หมดเวลาทำส่วนนี้',
                        text: 'ระบบจะนำท่านไปยังข้อถัดไปหรือส่วนใหม่ทันที',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#b91c1c'
                    }).then(() => moveToNextSection());
                }
            }, 1000);
        }

        function startOverallTimer() {
            updateTimerDisplay(overallTimerEl, overallSeconds);
            timerInterval = setInterval(() => {
                overallSeconds--;
                if (overallSeconds <= 0) {
                    clearInterval(timerInterval);
                    finishTest('หมดเวลาทำข้อสอบแล้ว (Auto-submitted)');
                }
                updateTimerDisplay(overallTimerEl, overallSeconds);
            }, 1000);
        }

        function updateTimerDisplay(el, seconds, isSection = false) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            el.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;

            if (seconds < 60) {
                el.classList.replace(isSection ? 'text-amber-500' : 'text-primary', 'text-red-500');
                if (seconds % 2 === 0) el.classList.add('opacity-50');
                else el.classList.remove('opacity-50');
            } else {
                el.classList.remove('text-red-500');
                el.classList.add(isSection ? 'text-amber-500' : 'text-primary');
            }
        }

        function syncState() {
            const totalSpent = (totalDuration * 60) - overallSeconds;
            fetch(`${ajaxUrl}&type=update_state`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    current_index: currentIndex,
                    total_time_spent: totalSpent
                })
            });

            const q = questions[currentIndex];
            if (q.section_duration > 0) {
                fetch(`${ajaxUrl}&type=update_section_time`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        attempt_id: attemptId,
                        section_id: q.section_id,
                        time_spent_seconds: sectionTimes[q.section_id]
                    })
                });
            }
        }

        function moveToNextSection() {
            const currentSecId = questions[currentIndex].section_id;
            for (let i = currentIndex + 1; i < questions.length; i++) {
                if (questions[i].section_id !== currentSecId) {
                    currentIndex = i;
                    renderQuestion();
                    updateNavigationState();
                    syncState();
                    return;
                }
            }
            finishTest('เสร็จสิ้นทุกส่วนแล้ว');
        }

        function finishTest(msg = 'ส่งแบบทดสอบสำเร็จ') {
            document.getElementById('formAfkCount').value = afkCount;

            Swal.fire({
                title: msg,
                text: 'กำลังบันทึกและประมวลผลผลลัพธ์...',
                icon: 'success',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            syncState();
            setTimeout(() => {
                document.getElementById('submitForm').submit();
            }, 1500);
        }

        // 3. Event Listeners
        btnNext.addEventListener('click', () => {
            if (currentIndex < questions.length - 1) {
                currentIndex++;
                renderQuestion();
                updateNavigationState();
                syncState();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });

        btnPrev.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                renderQuestion();
                updateNavigationState();
                syncState();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });

        btnSubmit.addEventListener('click', () => {
            // Count unanswered
            const unanswered = questions.length - Object.keys(answers).length;
            const text = unanswered > 0 ?
                `ท่านยังไม่ได้ตอบอีก ${unanswered} ข้อ ต้องการยืนยันการส่งหรือไม่?` :
                'หลังจากส่งแล้วจะไม่สามารถกลับมาแก้ไขได้อีก';

            Swal.fire({
                title: 'ยืนยันการส่งแบบทดสอบ?',
                text: text,
                icon: unanswered > 0 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันส่งคำตอบ',
                cancelButtonText: 'ย้อนกลับ',
                confirmButtonColor: unanswered > 0 ? '#f59e0b' : '#10b981',
                customClass: {
                    confirmButton: 'px-8 py-3 rounded-2xl font-bold',
                    cancelButton: 'px-8 py-3 rounded-2xl font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) finishTest();
            });
        });

        btnExit.addEventListener('click', () => {
            Swal.fire({
                title: 'ต้องการพักการสอบ?',
                text: 'ระบบได้บันทึกความคืบหน้าไว้ ท่านสามารถกลับมาทำต่อได้ในภายหลัง',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'ออกจาระบบ',
                cancelButtonText: 'ทำข้อสอบต่อ',
                confirmButtonColor: '#b91c1c'
            }).then((result) => {
                if (result.isConfirmed) {
                    syncState();
                    window.location.href = '?page=dashboard<?= $mid ?>';
                }
            });
        });

        document.querySelectorAll('.q-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentIndex = parseInt(btn.getAttribute('data-index'));
                renderQuestion();
                updateNavigationState();
                syncState();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });

        // Sticky header shadowing on scroll (Removed padding toggle to fix vibration)
        window.addEventListener('scroll', () => {
            const header = document.getElementById('stickyHeader');
            if (window.scrollY > 20) {
                header.classList.add('shadow-md');
                header.classList.remove('shadow-sm');
            } else {
                header.classList.remove('shadow-md');
                header.classList.add('shadow-sm');
            }
        });

        init();
    })();
</script>

<style>
    #stickyHeader {
        top: 64px;
        /* Matches index.php top-bar height */
    }

    .min-vh-50 {
        min-height: 50vh;
    }

    /* Smooth transitions for questions */
    #questionContent>div {
        animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
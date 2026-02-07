<?php
require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_take_test');




require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('alert_no_permission_user'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login");
    exit();
}

$user_id = $_SESSION['user_id'];
$test_id = $_POST['test_id'] ?? null;



if (!is_numeric($test_id) || $test_id <= 0) {
    set_alert(get_text('alert_invalid_test_id'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/user");
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

$test_info = [];
$questions_data = []; 
$current_question_index = 0; 
$total_questions = 0;
$attempt_id = null;
$time_spent_at_resume = 0; // เวลาที่ใช้ไปแล้ว (โหลดจาก DB)
$user_section_times = []; // เพิ่มตัวแปรนี้เพื่อเก็บเวลาที่ใช้ไปในแต่ละ Section

try {
    // 1. ดึงข้อมูลแบบทดสอบ
    $stmt = $conn->prepare("SELECT test_id, test_name, description, duration_minutes, show_result_immediately FROM tests WHERE test_id = ? AND is_published = 1");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$test_info) {
        set_alert(get_text('alert_test_not_found_or_unpublished'), "danger");
        header("Location: /INTEQC_GLOBAL_ASSESMENT/user");
        exit();
    }

    // 2. ตรวจสอบหรือสร้าง attempt_id
    $attempt_id = $_POST['attempt_id'] ?? null;
    $existing_attempt = null;

    if ($attempt_id) {
        // ถ้ามีการส่ง attempt_id มา (กรณีกดปุ่ม continue test)
        $stmt_check_attempt = $conn->prepare("SELECT attempt_id, start_time, time_spent_seconds, current_question_index 
            FROM user_test_attempts 
            WHERE attempt_id = ? AND user_id = ? AND test_id = ? AND is_completed = 0");
        $stmt_check_attempt->bind_param("iii", $attempt_id, $user_id, $test_id);
        $stmt_check_attempt->execute();
        $existing_attempt = $stmt_check_attempt->get_result()->fetch_assoc();
        $stmt_check_attempt->close();
    } else {
        // ถ้าไม่มีการส่ง attempt_id มา (เริ่มทำแบบทดสอบใหม่)
        $stmt_check_attempt = $conn->prepare("SELECT attempt_id, start_time, time_spent_seconds, current_question_index 
            FROM user_test_attempts 
            WHERE user_id = ? AND test_id = ? AND is_completed = 0 
            ORDER BY start_time DESC LIMIT 1");
        $stmt_check_attempt->bind_param("ii", $user_id, $test_id);
        $stmt_check_attempt->execute();
        $existing_attempt = $stmt_check_attempt->get_result()->fetch_assoc();
        $stmt_check_attempt->close();
    }

    if ($existing_attempt) {
        // พบการทำแบบทดสอบที่ค้างอยู่: ใช้ attempt_id เดิม
        $attempt_id = $existing_attempt['attempt_id'];
        $time_spent_at_resume = $existing_attempt['time_spent_seconds'] ?? 0;
        $current_question_index = $existing_attempt['current_question_index'] ?? 0;
        // เก็บค่าเหล่านี้ใน Session เพื่อความปลอดภัย (แม้ JS จะมีแล้ว)
        $_SESSION['current_attempt_id'] = $attempt_id;
        $_SESSION['time_spent_at_resume'] = $time_spent_at_resume;
        $_SESSION['current_question_index'] = $current_question_index;
    } else {
        // ไม่พบการทำแบบทดสอบที่ค้างอยู่ หรือทั้งหมดเสร็จสิ้นแล้ว: สร้าง attempt_id ใหม่
        $start_time = date('Y-m-d H:i:s');
        $stmt_new_attempt = $conn->prepare("INSERT INTO user_test_attempts (user_id, test_id, start_time, is_completed, current_question_index, time_spent_seconds) VALUES (?, ?, ?, 0, 0, 0)");
        $stmt_new_attempt->bind_param("iis", $user_id, $test_id, $start_time);
        $stmt_new_attempt->execute();
        $attempt_id = $conn->insert_id;
        $stmt_new_attempt->close();

        if (!$attempt_id) {
            throw new Exception(get_text('error_create_test_attempt')); // ใช้ get_text()
        }
        // กำหนดค่าเริ่มต้นสำหรับ Session ใหม่
        $_SESSION['current_attempt_id'] = $attempt_id;
        $_SESSION['time_spent_at_resume'] = 0;
        $_SESSION['current_question_index'] = 0;
    }

    // *** เพิ่มส่วนนี้: ดึงข้อมูลเวลาที่ใช้ไปในแต่ละ Section สำหรับ attempt นี้ ***
    $stmt_section_times = $conn->prepare("SELECT section_id, time_spent_seconds, start_timestamp FROM user_section_times WHERE attempt_id = ?");
    $stmt_section_times->bind_param("i", $attempt_id);
    $stmt_section_times->execute();
    $result_section_times = $stmt_section_times->get_result();
    while ($row_section_time = $result_section_times->fetch_assoc()) {
        $start_timestamp_unix = null;
        if (!empty($row_section_time['start_timestamp'])) {
            $parsed_time = strtotime($row_section_time['start_timestamp']);
            if ($parsed_time !== false) {
                $start_timestamp_unix = $parsed_time;
            } else {
                error_log("Invalid start_timestamp format for section " . $row_section_time['section_id'] . " (attempt: " . $attempt_id . "): " . $row_section_time['start_timestamp']);
            }
        }

        $user_section_times[$row_section_time['section_id']] = [
            'time_spent' => (int)$row_section_time['time_spent_seconds'],
            'start_timestamp' => $start_timestamp_unix
        ];
    }
    $stmt_section_times->close();

    // ดึงข้อมูลส่วนต่างๆ และคำถามทั้งหมด
    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description AS section_description, s.duration_minutes AS section_duration, s.section_order,
            q.question_id, q.question_text, q.question_type, q.score AS question_max_score, q.question_order
        FROM sections s
        JOIN questions q ON s.section_id = q.section_id
        WHERE s.test_id = ?
        ORDER BY s.section_order ASC, q.question_order ASC
    ");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $question_id = $row['question_id'];

        $question_full_data = [
            'question_id' => $question_id,
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'question_max_score' => $row['question_max_score'],
            'question_order' => $row['question_order'],
            'section_id' => $row['section_id'],
            'section_name' => $row['section_name'],
            'section_duration' => $row['section_duration'],
            'question_options' => []
        ];

        // ดึงตัวเลือกสำหรับคำถามปรนัย/จริง-เท็จ
        if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
            $question_options_stmt = $conn->prepare("SELECT option_id, option_text FROM question_options WHERE question_id = ? ORDER BY option_id ASC");
            $question_options_stmt->bind_param("i", $question_id);
            $question_options_stmt->execute();
            $question_options_result = $question_options_stmt->get_result();
            while ($opt_row = $question_options_result->fetch_assoc()) {
                $question_full_data['question_options'][] = $opt_row;
            }
            $question_options_stmt->close();
        }

        $questions_data[] = $question_full_data;
        $total_questions++;
    }
    $stmt->close();

    if (empty($questions_data)) {
        set_alert(get_text('alert_no_questions_in_test'), "warning"); // ใช้ get_text()
        header("Location: /INTEQC_GLOBAL_ASSESMENT/user");
        exit();
    }

    // โหลดคำตอบที่เคยบันทึกไว้ (สำหรับทำต่อ) และเก็บใน session
    $_SESSION['test_answers'][$attempt_id] = $_SESSION['test_answers'][$attempt_id] ?? [];
    $user_answers_stmt = $conn->prepare("SELECT question_id, user_answer_text FROM user_answers WHERE attempt_id = ?");
    $user_answers_stmt->bind_param("i", $attempt_id);
    $user_answers_stmt->execute();
    $existing_user_answers = $user_answers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $user_answers_stmt->close();

    foreach ($existing_user_answers as $ans) {
        $_SESSION['test_answers'][$attempt_id][$ans['question_id']] = $ans['user_answer_text'];
    }

    // คำนวณเวลาที่เหลือจากเวลาทั้งหมดของแบบทดสอบ ลบด้วยเวลาที่ใช้ไปแล้ว
    $time_remaining_seconds_overall = 0;
    if ($test_info['duration_minutes'] > 0) {
        $total_duration_seconds = $test_info['duration_minutes'] * 60;
        $time_remaining_seconds_overall = max(0, $total_duration_seconds - $time_spent_at_resume);
    }
} catch (Exception $e) {
    set_alert(get_text('alert_load_test_error') . ": " . $e->getMessage(), "danger"); // ใช้ get_text()
    header("Location: /INTEQC_GLOBAL_ASSESMENT/user");
    exit();
}

// ข้อมูลสำหรับ JavaScript
$js_vars = [
    'attemptId' => $attempt_id,
    'testDurationMinutes' => $test_info['duration_minutes'],
    'timeRemainingSecondsOverall' => $time_remaining_seconds_overall,
    'totalQuestions' => $total_questions,
    'currentQuestionIndex' => $current_question_index,
    'questionsData' => $questions_data,
    'initialUserAnswers' => $_SESSION['test_answers'][$attempt_id] ?? [],
    'userSectionTimes' => $user_section_times,
    'lang' => [
        'overall_test_time' => get_text('overall_test_time'),
        'section_time_remaining' => get_text('section_time_remaining'),
        'exit_test' => get_text('exit_test'),
        'loading_questions' => get_text('loading_questions'),
        'question_full_score' => get_text('question_full_score'),
        'question_not_found' => get_text('question_not_found'),
        'unsupported_question_type' => get_text('unsupported_question_type'),
        'type_your_answer_here' => get_text('type_your_answer_here'),
        'previous_question' => get_text('previous_question'),
        'next_question' => get_text('next_question'),
        'go_to_next_section' => get_text('go_to_next_section'),
        'submit_test' => get_text('submit_test'),
        'question_of_total' => get_text('question_of_total'),
        'time_up_overall_alert' => get_text('time_up_overall_alert'),
        'time_up_section_alert' => get_text('time_up_section_alert'),
        'test_completed_alert' => get_text('test_completed_alert'),
        'confirm_submit_test' => get_text('confirm_submit_test'),
        'error_submitting_test' => get_text('error_submitting_test'),
        'network_error_submitting_test' => get_text('network_error_submitting_test'),
        'break_screen_title' => get_text('break_screen_title'),
        'break_screen_message' => get_text('break_screen_message'),
        'break_timer_display' => get_text('break_timer_display'),
        'break_auto_continue_message' => get_text('break_auto_continue_message'),
        'skip_break' => get_text('skip_break'),
        'error_saving_answer' => get_text('error_saving_answer'),
        'network_error_saving_answer' => get_text('network_error_saving_answer'),
        'error_updating_test_state' => get_text('error_updating_test_state'),
        'network_error_updating_test_state' => get_text('network_error_updating_test_state'),
        'error_saving_section_time' => get_text('error_saving_section_time'),
        'network_error_saving_section_time' => get_text('network_error_saving_section_time'),
        'error_updating_section_start_timestamp' => get_text('error_updating_section_start_timestamp'),
        'network_error_updating_section_start_timestamp' => get_text('network_error_updating_section_start_timestamp'),
        'hours_abbr' => get_text('hours_abbr'),
        'minutes_abbr' => get_text('minutes_abbr'),
        'seconds_abbr' => get_text('seconds_abbr'),
        'afk_alert_title' => get_text('afk_alert_title'),
        'afk_alert_message' => get_text('afk_alert_message'),
        'afk_alert_confirm_button' => get_text('afk_alert_confirm_button'),

    ]
];
?>


<?php echo get_alert(); ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSRF token for AJAX/forms -->
<div style="display:none"><?php echo generate_csrf_token(); ?></div>

<style>
    /* Custom CSS for responsiveness */
    /* Adjusts the custom width for different screen sizes */
    .w-80-custom {
        width: 100%;
        /* Full width on small screens */
        padding-left: 15px;
        /* Add some padding on smaller screens */
        padding-right: 15px;
        /* Add some padding on smaller screens */
    }

    @media (min-width: 768px) {

        /* On medium screens and up */
        .w-80-custom {
            width: 80%;
            /* 80% width for larger screens */
            max-width: 960px;
            /* Optional: set a max-width to prevent it from getting too wide on very large screens */
            margin-left: auto;
            /* Center the container */
            margin-right: auto;
            /* Center the container */
            padding-left: var(--bs-gutter-x, 1.5rem);
            /* Reset Bootstrap padding */
            padding-right: var(--bs-gutter-x, 1.5rem);
            /* Reset Bootstrap padding */
        }
    }

    /* Adjust font sizes for timers on smaller screens if necessary */
    @media (max-width: 575.98px) {

        /* Extra small devices (phones) */
        #overallTimer,
        #sectionTimer {
            font-size: 0.85rem !important;
            /* Smaller font for timers */
            margin-right: 0.5rem !important;
            /* Reduce margin */
            margin-bottom: 0.5rem;
            /* Add margin bottom if they stack */
        }

        /* Ensure buttons within flexbox stack on mobile */
        .btn-responsive-stack {
            width: 100%;
            margin-bottom: 0.5rem;
            /* Add spacing between stacked buttons */
        }
    }
</style>

<div class="container-fluid w-80-custom py-4">
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
        <h1 class="mb-2 mb-md-0 text-primary-custom text-center text-md-start w-100 w-md-auto"><?php echo htmlspecialchars($test_info['test_name']); ?></h1>

        <div class="d-flex flex-wrap justify-content-center justify-content-md-end align-items-center mt-3 mt-md-0 w-100 w-md-auto">
            <?php if ($test_info['duration_minutes'] > 0): ?>
                <span class="badge bg-info fs-6 me-md-3 mb-2 mb-md-1" id="overallTimer"><?php echo get_text('overall_test_time'); ?>: Loading...</span>
            <?php endif; ?>
            <span class="badge bg-danger fs-6 me-md-3 mb-2 mb-md-1" id="sectionTimer"><?php echo get_text('section_time_remaining'); ?>: Loading...</span>
        </div>
        <a href="/user" class="btn btn-outline-secondary btn-responsive-stack" id="exitTestBtn">
            <i class="fas fa-times-circle me-2"></i> <?php echo get_text('exit_test'); ?>
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h4 class="mb-0 text-center text-md-start">
                <span id="questionCounter"></span>
                <span id="currentSectionName"></span>
            </h4>
        </div>
        <div class="card-body">
            <div id="questionDisplayArea">
                <p class="text-center text-muted"><?php echo get_text('loading_questions'); ?></p>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                <button class="btn btn-secondary btn-responsive-stack" id="prevQuestionBtn" style="display: none;">
                    <i class="fas fa-chevron-left me-2"></i> <?php echo get_text('previous_question'); ?>
                </button>
                <button class="btn btn-success btn-responsive-stack" id="nextQuestionBtn" style="display: none;">
                    <?php echo get_text('next_question'); ?> <i class="fas fa-chevron-right ms-2"></i>
                </button>
                <button class="btn btn-success btn-responsive-stack" id="submitTestBtn" style="display: none;">
                    <?php echo get_text('submit_test'); ?> <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const jsVars = <?php echo json_encode($js_vars); ?>;

    // Read CSRF token from hidden input rendered by generate_csrf_token()
    const csrfToken = (document.querySelector('input[name="_csrf_token"]') || {}).value || '';

    let attemptId = jsVars.attemptId;
    let testDurationMinutes = jsVars.testDurationMinutes;
    let timeRemainingSecondsOverall = jsVars.timeRemainingSecondsOverall;
    let totalQuestions = jsVars.totalQuestions;
    let currentQuestionIndex = jsVars.currentQuestionIndex;
    let questionsData = jsVars.questionsData;

    let userAnswers = jsVars.initialUserAnswers;

    let userSectionTimes = jsVars.userSectionTimes;
    let currentSectionId = null;
    let currentSectionStartTime = null;
    let sectionTimeSpent = 0;
    let currentSectionTimeRemaining = 0;
    let expiredSections = new Set();
    let overallTestTimerStarted = false;

    const questionDisplayArea = document.getElementById('questionDisplayArea');
    const prevQuestionBtn = document.getElementById('prevQuestionBtn');
    const nextQuestionBtn = document.getElementById('nextQuestionBtn');
    const submitTestBtn = document.getElementById('submitTestBtn');
    const questionCounter = document.getElementById('questionCounter');
    const currentSectionName = document.getElementById('currentSectionName');
    const overallTimerElement = document.getElementById('overallTimer');
    const sectionTimerElement = document.getElementById('sectionTimer');
    const exitTestBtn = document.getElementById('exitTestBtn');

    let overallTestInterval;
    let sectionTimerInterval;
    let breakInterval;

    const BREAK_DURATION_SECONDS = 60;
    let breakTimeRemaining = BREAK_DURATION_SECONDS;
    const AFK_TIMEOUT_SECONDS = 60;
    let afkTimer;
    let isAfkAlertShowing = false;
    let alertSound = new Audio('../assets/sounds/alert_sound.mp3');
    alertSound.volume = 1;
    alertSound.loop = true;

    let wasOverallTimerActiveBeforeAfk = false;
    let wasSectionTimerActiveBeforeAfk = false;
    let wasBreakTimerActiveBeforeAfk = false;

    function resetAfkTimer() {
        clearTimeout(afkTimer);
        afkTimer = setTimeout(triggerAfkAlert, AFK_TIMEOUT_SECONDS * 1000);
    }

    function triggerAfkAlert() {
        if (!isAfkAlertShowing) {
            // ตรวจสอบว่าเวลาหมดหรือไม่ก่อนแสดง AFK popup
            if (timeRemainingSecondsOverall <= 0) {
                
                submitTest();
                return;
            }
            
            // ตรวจสอบว่า section หมดเวลาหรือไม่
            if (currentSectionTimeRemaining <= 0 && questionsData[currentQuestionIndex]) {
                const currentQuestion = questionsData[currentQuestionIndex];
                if (currentQuestion.section_duration > 0) {
                    
                    expiredSections.add(currentQuestion.section_id);
                    
                    let nextSectionFound = false;
                    for (let i = currentQuestionIndex + 1; i < totalQuestions; i++) {
                        if (questionsData[i].section_id !== currentQuestion.section_id) {
                            currentQuestionIndex = i;
                            saveSectionTimeSpent(currentQuestion.section_id);
                            displayBreakScreen();
                            nextSectionFound = true;
                            break;
                        }
                    }
                    
                    if (!nextSectionFound) {
                        saveSectionTimeSpent(currentQuestion.section_id);
                        submitTestTimeExpired();
                    }
                    return;
                }
            }
            
            isAfkAlertShowing = true;
            
            if (overallTestInterval) {
                wasOverallTimerActiveBeforeAfk = true;
                clearInterval(overallTestInterval);
                overallTestInterval = null;
                overallTestTimerStarted = false; 
            }
            if (sectionTimerInterval) {
                wasSectionTimerActiveBeforeAfk = true;
                clearInterval(sectionTimerInterval);
                sectionTimerInterval = null;
            }
            if (breakInterval) {
                wasBreakTimerActiveBeforeAfk = true;
                clearInterval(breakInterval);
                breakInterval = null;
            }

            alertSound.play().catch(e => console.error("Error playing sound:", e));

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: jsVars.lang.afk_alert_title,
                    text: jsVars.lang.afk_alert_message,
                    icon: 'warning',
                    showConfirmButton: true,
                    confirmButtonText: jsVars.lang.afk_alert_confirm_button || 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {},
                    willClose: () => {
                        alertSound.pause();
                        alertSound.currentTime = 0;
                        isAfkAlertShowing = false;

                        if (wasOverallTimerActiveBeforeAfk) {
                            startOverallTestTimer();
                            wasOverallTimerActiveBeforeAfk = false;
                        }
                        if (wasSectionTimerActiveBeforeAfk) {
                            startSectionTimer();
                            wasSectionTimerActiveBeforeAfk = false;
                        }
                        if (wasBreakTimerActiveBeforeAfk) {
                            breakInterval = setInterval(() => {
                                breakTimeRemaining--;
                                updateBreakTimerDisplay();
                                if (breakTimeRemaining <= 0) {
                                    clearInterval(breakInterval);
                                    breakInterval = null;
                                    updateTestState();
                                    displayQuestion();
                                }
                            }, 1000);
                            updateBreakTimerDisplay();
                            wasBreakTimerActiveBeforeAfk = false;
                        }

                        resetAfkTimer();
                    }
                });
            } else {
                alert(jsVars.lang.afk_alert_title + "\n" + jsVars.lang.afk_alert_message);
                isAfkAlertShowing = false;
                alertSound.pause();
                alertSound.currentTime = 0;

                console.log("AFK alert (fallback) dismissed! Resuming timers...");
                if (wasOverallTimerActiveBeforeAfk) {
                    startOverallTestTimer();
                    wasOverallTimerActiveBeforeAfk = false;
                    console.log("Overall timer resumed (fallback).");
                }
                if (wasSectionTimerActiveBeforeAfk) {
                    startSectionTimer();
                    wasSectionTimerActiveBeforeAfk = false;
                    console.log("Section timer resumed (fallback).");
                }
                if (wasBreakTimerActiveBeforeAfk) {
                    breakInterval = setInterval(() => {
                        breakTimeRemaining--;
                        updateBreakTimerDisplay();
                        if (breakTimeRemaining <= 0) {
                            clearInterval(breakInterval);
                            breakInterval = null;
                            updateTestState();
                            displayQuestion();
                        }
                    }, 1000);
                    updateBreakTimerDisplay();
                    wasBreakTimerActiveBeforeAfk = false;
                    console.log("Break timer resumed (fallback).");
                }
                resetAfkTimer();
            }
        } else {
            console.log("AFK alert already active, sound continues to loop.");
        }
    }

    document.addEventListener('mousemove', resetAfkTimer);
    document.addEventListener('keydown', resetAfkTimer);
    document.addEventListener('click', resetAfkTimer);
    document.addEventListener('scroll', resetAfkTimer);
    document.addEventListener('touchstart', resetAfkTimer, false);
    document.addEventListener('touchmove', resetAfkTimer, false);
    document.addEventListener('touchend', resetAfkTimer, false);

    window.onload = function() {
        resetAfkTimer();
    };

    // Fix #2: Centralize timer management in displayQuestion
    function displayQuestion() {
        if (langSelect && langSelect.length > 0) {
            langSelect.prop('disabled', false);
        }
        if (typeof langSelect !== 'undefined' && langSelect.length > 0) {
            langSelect.prop('disabled', true);
        }
        if (currentQuestionIndex < 0 || currentQuestionIndex >= totalQuestions) {
            questionDisplayArea.innerHTML = `<p class='text-center text-danger'>${jsVars.lang.question_not_found}</p>`;
            return;
        }

        const question = questionsData[currentQuestionIndex];
        let htmlContent = `
            <h5 class="mb-3">${question.question_order}. ${question.question_text} <small class="text-muted">(${jsVars.lang.question_full_score}: ${question.question_max_score})</small></h5>
            <p class="text-muted">${question.section_name}</p>
        `;

        if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
            htmlContent += '<div class="form-group">';
            question.question_options.forEach(option => {
                const isChecked = userAnswers[question.question_id] == option.option_id ? 'checked' : '';
                htmlContent += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="question_${question.question_id}" id="option_${option.option_id}" value="${option.option_id}" ${isChecked}>
                        <label class="form-check-label" for="option_${option.option_id}">
                            ${option.option_text}
                        </label>
                    </div>
                `;
            });
            htmlContent += '</div>';
        } else if (question.question_type === 'short_answer') {
            const userAnswer = userAnswers[question.question_id] || '';
            htmlContent += `
                <div class="form-group">
                    <textarea class="form-control" id="answer_${question.question_id}" rows="4" placeholder="${jsVars.lang.type_your_answer_here}">${userAnswer}</textarea>
                </div>
            `;
        } else if (question.question_type === 'accept') {
            htmlContent += `
                <div class="form-group">
                    <textarea class="form-control" readonly placeholder="<?php echo get_text('please_read'); ?>"></textarea>
                </div>
            `;
        } else {
            htmlContent += `<p class='text-danger'>${jsVars.lang.unsupported_question_type}</p>`;
        }

        questionDisplayArea.innerHTML = htmlContent;

        const prevQuestionSectionId = (currentQuestionIndex > 0) ? questionsData[currentQuestionIndex - 1].section_id : null;
        const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;

        if (currentQuestionIndex > 0 &&
            (currentQuestionSectionId === prevQuestionSectionId || !expiredSections.has(prevQuestionSectionId))) {
            prevQuestionBtn.style.display = 'block';
        } else {
            prevQuestionBtn.style.display = 'none';
        }

        const nextQuestionSectionId = (currentQuestionIndex + 1 < totalQuestions) ? questionsData[currentQuestionIndex + 1].section_id : null;

        if (currentQuestionIndex === totalQuestions - 1) {
            nextQuestionBtn.style.display = 'none';
            submitTestBtn.style.display = 'block';
        } else if (nextQuestionSectionId && nextQuestionSectionId !== currentQuestionSectionId) {
            nextQuestionBtn.style.display = 'block';
            nextQuestionBtn.innerHTML = `${jsVars.lang.go_to_next_section} <i class="fas fa-chevron-right ms-2"></i>`;
            submitTestBtn.style.display = 'none';
        } else {
            nextQuestionBtn.style.display = 'block';
            nextQuestionBtn.innerHTML = `${jsVars.lang.next_question} <i class="fas fa-chevron-right ms-2"></i>`;
            submitTestBtn.style.display = 'none';
        }

        questionCounter.innerText = `${jsVars.lang.question_of_total.replace('{current}', currentQuestionIndex + 1).replace('{total}', totalQuestions)}`;
        currentSectionName.innerText = `(${question.section_name})`;

        const newSectionId = question.section_id;
        if (currentSectionId !== newSectionId) {
            if (currentSectionId !== null) {
                saveSectionTimeSpent(currentSectionId);
            }

            currentSectionId = newSectionId;
            sectionTimeSpent = userSectionTimes[currentSectionId] ? userSectionTimes[currentSectionId].time_spent : 0;

            if (userSectionTimes[currentSectionId] && userSectionTimes[currentSectionId].start_timestamp) {
                currentSectionStartTime = userSectionTimes[currentSectionId].start_timestamp;
            } else {
                currentSectionStartTime = Math.floor(Date.now() / 1000);
            }

            updateSectionStartTimestamp(currentSectionId, currentSectionStartTime);
        }

        const currentQuestionSectionDuration = questionsData[currentQuestionIndex].section_duration;
        if (currentQuestionSectionDuration > 0) {
            if (testDurationMinutes > 0 && !overallTestTimerStarted) {
                startOverallTestTimer();
            }
            startSectionTimer();
            sectionTimerElement.style.display = 'inline-block';
        } else {
            clearInterval(sectionTimerInterval);
            sectionTimerInterval = null;
            sectionTimerElement.style.display = 'none';

            if (overallTestInterval) {
                clearInterval(overallTestInterval);
                overallTestInterval = null;
                overallTestTimerStarted = false;
                console.log("Overall timer stopped because section has no time limit.");
            }
            overallTimerElement.style.display = 'none';
        }

        if (question.question_type === 'multiple_choice' || question.question_type === 'true_false') {
            document.querySelectorAll(`input[name="question_${question.question_id}"]`).forEach(radio => {
                radio.removeEventListener('change', saveAnswerHandler);
                radio.addEventListener('change', saveAnswerHandler);
            });
        } else if (question.question_type === 'short_answer') {
            const textarea = document.getElementById(`answer_${question.question_id}`);
            if (textarea) {
                textarea.removeEventListener('input', saveAnswerHandler);
                textarea.addEventListener('input', saveAnswerHandler);
            }
        }
    }


    function saveAnswerHandler(event) {
        const question = questionsData[currentQuestionIndex];
        const answerValue = event.target.value;
        saveAnswer(question.question_id, answerValue);
    }

    function displayBreakScreen() {
        clearInterval(sectionTimerInterval);
        sectionTimerInterval = null;
        overallTestTimerStarted = false;
        clearInterval(overallTestInterval);
        overallTestInterval = null;

        if (typeof langSelect !== 'undefined' && langSelect) {
            langSelect.prop('disabled', true);
        }

        questionDisplayArea.innerHTML = `
            <div class="text-center py-5">
                <h3 class="text-primary-custom mb-4">${jsVars.lang.break_screen_title}</h3>
                <p class="lead">${jsVars.lang.break_screen_message}</p>
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 fs-4" id="breakTimerDisplay">${jsVars.lang.break_timer_display.replace('{time}', formatTime(BREAK_DURATION_SECONDS))}</p>
                <p class="text-muted">${jsVars.lang.break_auto_continue_message}</p>
                <button class="btn btn-primary mt-4" id="skipBreakBtn">
                    ${jsVars.lang.skip_break} <i class="fas fa-forward ms-2"></i>
                </button>
            </div>
        `;

        prevQuestionBtn.style.display = 'none';
        nextQuestionBtn.style.display = 'none';
        submitTestBtn.style.display = 'none';
        sectionTimerElement.style.display = 'none';
        if (testDurationMinutes > 0) overallTimerElement.style.display = 'inline-block';

        breakTimeRemaining = BREAK_DURATION_SECONDS;
        updateBreakTimerDisplay();
        breakInterval = setInterval(() => {
            breakTimeRemaining--;
            updateBreakTimerDisplay();

            if (breakTimeRemaining <= 0) {
                clearInterval(breakInterval);
                breakInterval = null;
                updateTestState();
                displayQuestion();
            }
        }, 1000);

        const skipBreakBtn = document.getElementById('skipBreakBtn');
        if (skipBreakBtn) {
            skipBreakBtn.addEventListener('click', () => {
                clearInterval(breakInterval);
                breakInterval = null;
                updateTestState();
                displayQuestion();
            });
        }
    }

    function updateBreakTimerDisplay() {
        const breakTimerDisplay = document.getElementById('breakTimerDisplay');
        if (breakTimerDisplay) {
            breakTimerDisplay.innerText = `${jsVars.lang.break_timer_display.replace('{time}', formatTime(breakTimeRemaining))}`;
        }
    }

    async function saveAnswer(questionId, answerValue) {
        userAnswers[questionId] = answerValue;

        try {
            const response = await fetch('/INTEQC_GLOBAL_ASSESMENT/views/user/save_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    question_id: questionId,
                    user_answer_text: answerValue,
                    _csrf_token: csrfToken
                })
            });
            const result = await response.json();
            if (!result.success) {
                console.error(`${jsVars.lang.error_saving_answer}:`, result.message);
            } else {
                console.log('Answer saved:', result.message);
            }
        } catch (error) {
            console.error(`${jsVars.lang.network_error_saving_answer}:`, error);
        }
    }

    prevQuestionBtn.addEventListener('click', () => {
        const prevQuestionSectionId = (currentQuestionIndex > 0) ? questionsData[currentQuestionIndex - 1].section_id : null;
        const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;

        if (currentQuestionIndex > 0 &&
            (currentQuestionSectionId === prevQuestionSectionId || !expiredSections.has(prevQuestionSectionId))) {
            currentQuestionIndex--;
            updateTestState();
            displayQuestion();
        }
    });

    nextQuestionBtn.addEventListener('click', () => {
        if (currentQuestionIndex < totalQuestions - 1) {
            const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;
            const nextQuestionSectionId = questionsData[currentQuestionIndex + 1].section_id;

            if (nextQuestionSectionId !== currentQuestionSectionId) {
                currentQuestionIndex++;
                displayBreakScreen();
            } else {
                currentQuestionIndex++;
                updateTestState();
                displayQuestion();
            }
        }
    });

    async function updateTestState() {
        let totalTimeSpent = (testDurationMinutes * 60) - timeRemainingSecondsOverall;
        if (totalTimeSpent < 0) totalTimeSpent = 0;

        try {
            const response = await fetch('/INTEQC_GLOBAL_ASSESMENT/views/user/update_test_state.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    current_question_index: currentQuestionIndex,
                    time_spent_seconds: totalTimeSpent
                })
            });
            const result = await response.json();
            if (!result.success) {
                console.error(`${jsVars.lang.error_updating_test_state}:`, result.message);
            } else {
                console.log('Test state updated:', result.message);
            }
        } catch (error) {
            console.error(`${jsVars.lang.network_error_updating_test_state}:`, error);
        }
    }

    function startOverallTestTimer() {
        if (overallTestTimerStarted) {
            return;
        }
        overallTestTimerStarted = true;

        if (testDurationMinutes <= 0) {
            overallTimerElement.style.display = 'none';
            return;
        }

        overallTimerElement.style.display = 'inline-block';
        updateOverallTimerDisplay();

        overallTestInterval = setInterval(() => {
            timeRemainingSecondsOverall--;
            updateOverallTimerDisplay();

            if (timeRemainingSecondsOverall % 10 === 0 || timeRemainingSecondsOverall <= 0) {
                updateTestState();
            }

            if (timeRemainingSecondsOverall <= 0) {
                clearInterval(overallTestInterval);
                overallTestInterval = null;
                overallTestTimerStarted = false;
                clearInterval(sectionTimerInterval);
                sectionTimerInterval = null;
                submitTest();
            }
        }, 1000);
    }

    function updateOverallTimerDisplay() {
        overallTimerElement.innerText = `${jsVars.lang.overall_test_time}: ${formatTime(timeRemainingSecondsOverall)}`;
    }

    function startSectionTimer() {
        clearInterval(sectionTimerInterval);
        sectionTimerInterval = null;

        const currentQuestion = questionsData[currentQuestionIndex];
        const sectionDuration = currentQuestion.section_duration;

        if (sectionDuration > 0) {
            sectionTimerElement.style.display = 'inline-block';
            let totalSectionDuration = sectionDuration * 60;
            currentSectionTimeRemaining = Math.max(0, totalSectionDuration - sectionTimeSpent);
            updateSectionTimerDisplay();
        } else {
            sectionTimerElement.style.display = 'none';
            currentSectionTimeRemaining = 0;
        }

        if (currentQuestion.section_duration > 0) {
            sectionTimerInterval = setInterval(() => {
                currentSectionTimeRemaining--;
                sectionTimeSpent++;
                updateSectionTimerDisplay();

                if (sectionTimeSpent % 10 === 0 || currentSectionTimeRemaining <= 0) {
                    saveSectionTimeSpent(currentQuestion.section_id);
                }

                if (currentSectionTimeRemaining <= 0) {
                    clearInterval(sectionTimerInterval);
                    sectionTimerInterval = null;
                    expiredSections.add(currentQuestion.section_id);

                    let nextSectionFound = false;
                    for (let i = currentQuestionIndex + 1; i < totalQuestions; i++) {
                        if (questionsData[i].section_id !== currentQuestion.section_id) {
                            currentQuestionIndex = i;
                            saveSectionTimeSpent(currentQuestion.section_id);
                            displayBreakScreen();
                            nextSectionFound = true;
                            break;
                        }
                    }

                    if (!nextSectionFound) {
                        // เรียกใช้ฟังก์ชันพิเศษสำหรับการหมดเวลา
                        saveSectionTimeSpent(currentQuestion.section_id);
                        submitTestTimeExpired();
                    }
                }
            }, 1000);
        }
    }

    function updateSectionTimerDisplay() {
        sectionTimerElement.innerText = `${jsVars.lang.section_time_remaining}: ${formatTime(currentSectionTimeRemaining)}`;
    }

    function formatTime(totalSeconds) {
        if (totalSeconds < 0) totalSeconds = 0;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    async function saveSectionTimeSpent(sectionId) {
        userSectionTimes[sectionId] = userSectionTimes[sectionId] || {};
        userSectionTimes[sectionId].time_spent = sectionTimeSpent;

        try {
            const response = await fetch('/INTEQC_GLOBAL_ASSESMENT/views/user/update_section_time.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    section_id: sectionId,
                    time_spent_seconds: sectionTimeSpent,
                    _csrf_token: csrfToken
                })
            });
            const result = await response.json();
            if (!result.success) {
                console.error(`${jsVars.lang.error_saving_section_time}:`, result.message);
            } else {
                console.log('Section time saved:', result.message);
            }
        } catch (error) {
            console.error(`${jsVars.lang.network_error_saving_section_time}:`, error);
        }
    }

    async function updateSectionStartTimestamp(sectionId, timestamp) {
        try {
            const response = await fetch('/INTEQC_GLOBAL_ASSESMENT/views/user/update_section_time_start.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    section_id: sectionId,
                    start_timestamp: timestamp,
                    _csrf_token: csrfToken
                })
            });
            const result = await response.json();
            if (!result.success) {
                console.error(`${jsVars.lang.error_updating_section_start_timestamp}:`, result.message);
            } else {
                console.log('Section start timestamp updated:', result.message);
            }
        } catch (error) {
            console.error(`${jsVars.lang.network_error_updating_section_start_timestamp}:`, error);
        }
    }

    // โค้ดที่แก้ไขแล้วสำหรับ submitTest
    function submitTest() {
        // โค้ดส่วนจัดการ timer และการบันทึกเวลาส่วน section ยังคงเดิม
        stopAllTimers();

        if (currentSectionId !== null) {
            saveSectionTimeSpent(currentSectionId);
        }

        // แสดง loading popup
        Swal.fire({
            title: '<?php echo get_text("submitting_test_title"); ?>',
            text: '<?php echo get_text("submitting_test_message"); ?>',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // รอสักครู่เพื่อให้ popup แสดงก่อนส่งฟอร์ม
        setTimeout(() => {
            // สร้างฟอร์มแบบไดนามิกและส่งข้อมูล
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user/submit_test.php';

            const hiddenAttemptId = document.createElement('input');
            hiddenAttemptId.type = 'hidden';
            hiddenAttemptId.name = 'attempt_id';
            hiddenAttemptId.value = attemptId;

            form.appendChild(hiddenAttemptId);
            // Add CSRF token to submit form
            const hiddenCsrf = document.createElement('input');
            hiddenCsrf.type = 'hidden';
            hiddenCsrf.name = '_csrf_token';
            hiddenCsrf.value = csrfToken;
            form.appendChild(hiddenCsrf);
            document.body.appendChild(form);

            form.submit(); // สั่งให้ฟอร์มส่งข้อมูล
        }, 3000);
    }

    // ฟังก์ชันพิเศษสำหรับการหมดเวลา
    function submitTestTimeExpired() {
        // หยุด timer ทั้งหมด
        stopAllTimers();

        // แสดง popup หมดเวลาก่อน
        Swal.fire({
            title: '<?php echo get_text("time_expired_title"); ?>',
            text: '<?php echo get_text("test_submitted_message"); ?>',
            icon: 'warning',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: 10000, // แสดง 10 วินาที
            timerProgressBar: true,
            didOpen: () => {
                // เริ่มนับถอยหลัง
                let countdown = 10;
                const countdownInterval = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
            }
        }).then((result) => {
            // ส่งแบบทดสอบแล้วไปหน้า view_result
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user/submit_test.php';

            const hiddenAttemptId = document.createElement('input');
            hiddenAttemptId.type = 'hidden';
            hiddenAttemptId.name = 'attempt_id';
            hiddenAttemptId.value = attemptId;

            // เพิ่ม flag เพื่อบอกว่าหมดเวลา
            const hiddenTimeExpired = document.createElement('input');
            hiddenTimeExpired.type = 'hidden';
            hiddenTimeExpired.name = 'time_expired';
            hiddenTimeExpired.value = '1';

            form.appendChild(hiddenAttemptId);
            form.appendChild(hiddenTimeExpired);
            document.body.appendChild(form);

            form.submit(); // ส่งแบบทดสอบ
        });
    }

    submitTestBtn.addEventListener('click', () => {
        // เรียกใช้ฟังก์ชันใหม่
        submitTest();
    });

    function stopAllTimers() {
        console.log("Stopping all timers...");
        if (overallTestInterval) {
            clearInterval(overallTestInterval);
            overallTestInterval = null;
            overallTestTimerStarted = false;
            console.log("Overall timer stopped.");
        }
        if (sectionTimerInterval) {
            clearInterval(sectionTimerInterval);
            sectionTimerInterval = null;
            console.log("Section timer stopped.");
        }
        if (breakInterval) {
            clearInterval(breakInterval);
            breakInterval = null;
            console.log("Break timer stopped.");
        }
        clearTimeout(afkTimer);
        console.log("AFK timer cleared.");

        if (alertSound) {
            alertSound.pause();
            alertSound.currentTime = 0;
            console.log("AFK alert sound stopped and reset.");
        }
    }

    if (exitTestBtn) {
        exitTestBtn.addEventListener('click', async (event) => {
            event.preventDefault();

            stopAllTimers();

            if (currentSectionId !== null) {
                await saveSectionTimeSpent(currentSectionId);
            }
            await updateTestState();

            if (typeof langSelect !== 'undefined' && langSelect) {
                langSelect.prop('disabled', false);
            }

            window.location.href = '/INTEQC_GLOBAL_ASSESMENT/user/';
        });
    }

    window.addEventListener('beforeunload', (event) => {
        let totalTimeSpent = (testDurationMinutes * 60) - timeRemainingSecondsOverall;
        if (totalTimeSpent < 0) totalTimeSpent = 0;

        if (currentSectionId !== null) {
            const sectionData = {
                attempt_id: attemptId,
                section_id: currentSectionId,
                time_spent_seconds: sectionTimeSpent
            };
            if (navigator.sendBeacon) {
                sectionData._csrf_token = csrfToken;
                navigator.sendBeacon('/user/update_section_time.php', JSON.stringify(sectionData));
            } else {
                fetch('/user/update_section_time.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(Object.assign({}, sectionData, { _csrf_token: csrfToken })),
                    keepalive: true
                }).catch(error => console.error('Error sending section beacon fallback:', error));
            }
        }

        const data = {
            attempt_id: attemptId,
            current_question_index: currentQuestionIndex,
            time_spent_seconds: totalTimeSpent,
            _csrf_token: csrfToken
        };

        try {
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/INTEQC_GLOBAL_ASSESMENT/views/user/update_test_state.php', JSON.stringify(data));
            } else {
                fetch('/INTEQC_GLOBAL_ASSESMENT/views/user/update_test_state.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(data),
                    keepalive: true
                }).catch(error => console.error('Error with sendBeacon fallback:', error));
            }
        } catch (e) {
            console.error("Error with sendBeacon or fetch keepalive:", e);
        }
    });

    let langSelect;

    $(document).ready(() => {
        langSelect = $('select[name="lang"]');
        console.log("langSelect element:", langSelect);

        console.log("Total Questions from jsVars:", totalQuestions);
        console.log("Questions Data from jsVars:", questionsData);

        if (totalQuestions > 0) {
            console.log("Starting test: totalQuestions > 0 is true");
            
            // ปิดการเปลี่ยนภาษาเมื่อเริ่มทำแบบทดสอบ
            if (langSelect && langSelect.length > 0) {
                langSelect.prop('disabled', true);
                console.log("Language selector disabled during test");
            }
            
            currentSectionId = questionsData[currentQuestionIndex].section_id;
            sectionTimeSpent = userSectionTimes[currentSectionId] ? userSectionTimes[currentSectionId].time_spent : 0;

            if (userSectionTimes[currentSectionId] && userSectionTimes[currentSectionId].start_timestamp) {
                currentSectionStartTime = userSectionTimes[currentSectionId].start_timestamp;
                console.log(`[DOMContentLoaded] Resuming Section ${currentSectionId}. Loaded time_spent=${userSectionTimes[currentSectionId].time_spent}, final sectionTimeSpent=${sectionTimeSpent}`);
            } else {
                currentSectionStartTime = Math.floor(Date.now() / 1000);
                console.log(`[DOMContentLoaded] Starting new Section ${currentSectionId}. Initial sectionTimeSpent=${sectionTimeSpent}`);
            }
            displayQuestion();
        } else {
            console.log("No questions found: totalQuestions is 0 or less.");
            questionDisplayArea.innerHTML = `<p class='text-center text-muted'>${jsVars.lang.no_questions_in_test}</p>`;
            prevQuestionBtn.style.display = 'none';
            nextQuestionBtn.style.display = 'none';
            submitTestBtn.style.display = 'none';
            overallTimerElement.style.display = 'none';
            sectionTimerElement.style.display = 'none';
            if (langSelect) {
                langSelect.prop('disabled', false);
            }
        }
    });
</script>
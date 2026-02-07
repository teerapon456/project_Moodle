<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/header.php';

/** ---------- Error handling (log only, no HTML) ---------- */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE ?? (__DIR__ . '/../../logs/php-error.log'));

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log(sprintf(
        "[%s] PHP Error: [%d] %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    ), 3, LOG_FILE ?? (__DIR__ . '/../../logs/php-error.log'));
    return true;
});
set_exception_handler(function (Throwable $exception) {
    error_log(sprintf(
        "[%s] Uncaught Exception: %s (Code: %s, File: %s, Line: %d)\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getCode(),
        $exception->getFile(),
        $exception->getLine()
    ), 3, LOG_FILE ?? (__DIR__ . '/../../logs/php-error.log'));
});

/** ---------- Page meta ---------- */
$page_title = get_text('user_dashboard_title');

/** ---------- DB timezone ---------- */
if (isset($conn) && $conn) {
    @$conn->query("SET time_zone = '+07:00'");
}

/** ---------- Auth ---------- */
redirect_if_not_logged_in('associate', 'applicant');
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    require_login();
    exit();
}

/** ---------- Helper: คำนวณจำนวนข้อสุ่มรวม (ไม่นับ always_include) ---------- */
function compute_random_total_from_json(?string $always_json, ?string $section_counts_json): int
{
    $total = 0;
    if (!empty($section_counts_json)) {
        $sc = json_decode($section_counts_json, true);
        if (is_array($sc)) {
            $is_assoc = array_keys($sc) !== range(0, count($sc) - 1);
            if ($is_assoc) {
                foreach ($sc as $cnt) $total += max(0, (int)$cnt);
            } else {
                foreach ($sc as $row) {
                    if (is_array($row) && isset($row['count'])) {
                        $total += max(0, (int)$row['count']);
                    }
                }
            }
        }
    }
    return $total;
}

/** ---------- ใช้ข้อมูลผู้ใช้จาก header.php (แหล่งเดียว) ---------- */
/* header.php กำหนด $user_profile_data ไว้แล้ว (JOIN roles, emplevelcode) */
$user_emplevel_id = isset($user_profile_data['emplevel_id']) ? (int)$user_profile_data['emplevel_id'] : null;
$user_role_id     = isset($user_profile_data['role_id'])     ? (int)$user_profile_data['role_id']     : null;
/* รองรับได้ทั้ง OrgUnitName (ตัวใหญ่) และ orgunitname (ตัวเล็ก) */
$user_orgunitname =
    $user_profile_data['OrgUnitName']
    ?? $user_profile_data['orgunitname']
    ?? null;

/** ---------- Build dashboard tests ---------- */
$available_tests = [];

// Handle auto-unpublish logic
if (isset($conn) && $conn) {
    $conn->query("UPDATE iga_tests SET is_published = 0 WHERE is_published = 1 AND unpublished_at IS NOT NULL AND unpublished_at <= NOW()");
}

try {
    /** 1) ดึง attempts ของ user */
    $user_attempts = [];
    $stmt_attempts = $conn->prepare("
        SELECT uta.test_id, uta.is_completed, t.test_no, uta.attempt_id
        FROM iga_user_test_attempts uta
        JOIN iga_tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = ?
    ");
    if (!$stmt_attempts) {
        throw new Exception("Failed to prepare user attempts statement: " . $conn->error);
    }
    $stmt_attempts->bind_param("s", $user_id);
    $stmt_attempts->execute();
    $result_attempts = $stmt_attempts->get_result();
    while ($attempt_row = $result_attempts->fetch_assoc()) {
        $user_attempts[] = $attempt_row;
    }
    $stmt_attempts->close();

    // group ตาม test_no หรือ test_id
    $in_progress_data = [];
    $completed_data   = [];
    foreach ($user_attempts as $attempt) {
        $lookup_key = ($attempt['test_no'] !== null) ? $attempt['test_no'] : $attempt['test_id'];
        if (!empty($attempt['is_completed'])) {
            $completed_data[$lookup_key] = true;
        } else {
            if (!isset($in_progress_data[$lookup_key])) {
                $in_progress_data[$lookup_key] = $attempt;
            }
        }
    }

    /** 2) ดึงรายการ test ที่ publish และเข้าเงื่อนไขของ user (อิง test_emplevels/test_orgunits/role_id) */
    $all_published_tests_raw = [];
    $sql_published = "
        SELECT DISTINCT
            t.test_id,
            t.test_name,
            t.description,
            t.test_no,
            t.language,
            t.duration_minutes,
            IFNULL(trc.is_random_mode, 0) AS is_random_mode,
            trc.always_include_questions,
            trc.section_random_counts
        FROM iga_tests t
        LEFT JOIN iga_test_random_question_settings trc ON t.test_id = trc.test_id
        LEFT JOIN roles r ON t.role_id = r.role_id
        WHERE t.is_published = 1
        AND (t.published_at IS NULL OR t.published_at <= NOW())
        AND (t.unpublished_at IS NULL OR t.unpublished_at > NOW())
        AND (
                /* ผู้ใช้ทั่วไป (หรือ role เป็น NULL) → ต้องตรง emplevel (หรือไม่กำหนดเลย) และ iga_orgunit (ถ้ากำหนด) */
                ( (r.role_name != 'applicant' OR r.role_name IS NULL)
                AND (
                        /* ไม่กำหนด level → เปิดให้ทุก level */
                        NOT EXISTS (SELECT 1 FROM iga_test_emplevels te0 WHERE te0.test_id = t.test_id)
                        /* หรือ ระบุ level และตรงกับของผู้ใช้ */
                        OR EXISTS (
                            SELECT 1
                            FROM iga_test_emplevels te
                            WHERE te.test_id = t.test_id
                            AND te.level_id = ?
                        )
                    )
                AND (
                        /* ไม่กำหนด iga_orgunit → เปิดทุกหน่วย */
                        NOT EXISTS (SELECT 1 FROM iga_test_orgunits TO2 WHERE TO2.test_id = t.test_id)
                        /* หรือ ระบุ iga_orgunit และตรงกับของผู้ใช้ */
                        OR EXISTS (
                            SELECT 1
                            FROM iga_test_orgunits TO3
                            WHERE TO3.test_id = t.test_id
                            AND TO3.orgunitname = ?
                        )
                    )
                )
                OR
                /* ผู้สมัคร (applicant) → เช็กเฉพาะ emplevel (หรือไม่กำหนดเลย) ไม่ต้องเช็ก iga_orgunit */
                ( r.role_name = 'applicant'
                AND (
                        NOT EXISTS (SELECT 1 FROM iga_test_emplevels teA0 WHERE teA0.test_id = t.test_id)
                        OR EXISTS (
                            SELECT 1
                            FROM iga_test_emplevels teA
                            WHERE teA.test_id = t.test_id
                            AND teA.level_id = ?
                        )
                    )
                )
            )
        AND (t.role_id IS NULL OR t.role_id = ?)
        ORDER BY t.test_name ASC
    ";

    $orgunit_safe   = (string)($user_orgunitname ?? '');
    $emplevel_param = (int)($user_emplevel_id ?? 0);

    /* ถ้า $user_role_id อาจเป็น NULL ให้แทนด้วย -1 เพื่อไม่ไปแมตช์ role อื่น แต่ยังผ่าน t.role_id IS NULL ได้ */
    $role_param = isset($user_role_id) ? (int)$user_role_id : -1;

    $stmt_published = $conn->prepare($sql_published);
    if (!$stmt_published) {
        throw new Exception("Failed to prepare published tests statement: " . $conn->error);
    }

    /* พารามิเตอร์: level(int), orgunit(string), level(int), role(int) */
    $stmt_published->bind_param('isii', $emplevel_param, $orgunit_safe, $emplevel_param, $role_param);

    $stmt_published->execute();
    $result_published = $stmt_published->get_result();

    while ($test_row = $result_published->fetch_assoc()) {
        $random_total_count = 0;
        if (!empty($test_row['is_random_mode'])) {
            $random_total_count = compute_random_total_from_json(
                $test_row['always_include_questions'] ?? null,
                $test_row['section_random_counts'] ?? null
            );
        }
        $test_row['random_total_count'] = $random_total_count;
        $all_published_tests_raw[] = $test_row;
    }
    $stmt_published->close();

    // group ภาษาโดย test_no
    $grouped_tests_by_test_no = [];
    foreach ($all_published_tests_raw as $test) {
        if ($test['test_no'] !== null) {
            $grouped_tests_by_test_no[$test['test_no']][] = $test;
        }
    }

    /** 3) post-process เป็นรายการแสดง */
    $processed_lookup_keys = [];
    foreach ($all_published_tests_raw as $test) {
        $test_id    = $test['test_id'];
        $test_no    = $test['test_no'];
        $lookup_key = ($test_no !== null) ? $test_no : $test_id;

        if (isset($processed_lookup_keys[$lookup_key]) || isset($completed_data[$lookup_key])) {
            continue;
        }

        $status     = 'not_started';
        $attempt_id = null;
        if (isset($in_progress_data[$lookup_key])) {
            $status     = 'in_progress';
            $attempt_id = $in_progress_data[$lookup_key]['attempt_id'];
        }

        $all_available_for_popup = [];
        if ($test_no !== null && isset($grouped_tests_by_test_no[$test_no])) {
            foreach ($grouped_tests_by_test_no[$test_no] as $lang_option) {
                $ol_lookup_key    = ($lang_option['test_no'] !== null) ? $lang_option['test_no'] : $lang_option['test_id'];
                $is_ol_completed  = isset($completed_data[$ol_lookup_key]);
                $is_ol_inprogress = isset($in_progress_data[$ol_lookup_key]);
                if (!$is_ol_completed && !$is_ol_inprogress) {
                    $all_available_for_popup[] = $lang_option;
                }
            }
        } else {
            $all_available_for_popup[] = $test;
        }

        $test['status']                  = $status;
        $test['attempt_id']              = $attempt_id;
        $test['all_available_for_popup'] = $all_available_for_popup;

        $available_tests[] = $test;
        $processed_lookup_keys[$lookup_key] = true;
    }

    // เรียง: in_progress ก่อน แล้วตามชื่อ
    usort($available_tests, function ($a, $b) {
        if ($a['status'] === 'in_progress' && $b['status'] !== 'in_progress') return -1;
        if ($a['status'] !== 'in_progress' && $b['status'] === 'in_progress') return 1;
        return strcmp($a['test_name'], $b['test_name']);
    });
} catch (Exception $e) {
    set_alert(get_text('alert_fetch_test_error', [$e->getMessage()]), "danger");
    error_log(sprintf(
        "[%s] Dashboard Exception for user %d (emplevel: %s / orgunit: %s / role_id: %s): %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        var_export($user_emplevel_id, true),
        var_export($user_orgunitname, true),
        var_export($user_role_id, true),
        $e->getMessage()
    ));
    $available_tests = [];
}
?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('dashboard_welcome_heading'); ?></h1>
<p class="lead"><?php echo get_text('dashboard_available_tests_intro'); ?></p>

<?php if (count($available_tests) > 0) { ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($available_tests as $test) { ?>
            <div class="col">
                <div class="card h-100 shadow">
                    <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                        <?php if (!empty($test['is_random_mode'])) { ?>
                            <span class="badge bg-warning text-dark"
                                data-bs-toggle="tooltip"
                                title="<?php echo get_text('random_questions_enabled'); ?>">
                                <i class="fas fa-random me-1"></i>
                                <?php echo get_text('random'); ?>
                                (<?php echo (int)($test['random_total_count'] ?? 0); ?>)
                            </span>
                        <?php } ?>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($test['description'])); ?></p>
                        <p class="card-text mb-0">
                            <?php
                            if (!empty($test['test_no'])) {
                                echo '<div class="mt-2">' . get_text('test_no_label') . ': ' . htmlspecialchars($test['test_no']) . '</div>';
                            }

                            // ภาษา
                            $all_languages = [];
                            $current_language = !empty($test['language']) ? strtoupper($test['language']) : '';
                            if ($current_language) $all_languages[] = '<span class="fw-bold">' . htmlspecialchars($current_language) . '</span>';

                            $popup_languages = array_column($test['all_available_for_popup'], 'language');
                            foreach ($popup_languages as $lang) {
                                $lang_upper = strtoupper($lang);
                                if ($lang_upper !== $current_language) $all_languages[] = htmlspecialchars($lang_upper);
                            }
                            if (!empty($all_languages)) {
                                echo '<div class="mt-2"><small class="text-muted">';
                                echo get_text('languages') . ': ' . implode(' | ', $all_languages);
                                echo '</small></div>';
                            }

                            // เวลา
                            if (isset($test['duration_minutes']) && $test['duration_minutes'] !== null) {
                                echo '<div class="mt-2">' . get_text('time_of_test_label') . ': ' .
                                    htmlspecialchars($test['duration_minutes']) . ' ' . get_text('minutes_abbr') . '</div>';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="card-footer bg-secondary">
                        <?php if (isset($test['status']) && $test['status'] === 'in_progress') { ?>
                            <form action="/user/test/" method="POST" style="display:inline;">
                                <input type="hidden" name="test_id" value="<?php echo (int)$test['test_id']; ?>">
                                <input type="hidden" name="attempt_id" value="<?php echo (int)$test['attempt_id']; ?>">
                                <button type="submit" class="btn btn-info-custom text-white">
                                    <i class="fas fa-play-circle me-2"></i> <?php echo get_text('continue_test_button'); ?>
                                </button>
                            </form>
                            <?php } else {
                            $can_show_language_popup = count($test['all_available_for_popup']) > 1;
                            if ($can_show_language_popup) { ?>
                                <button type="button" class="btn btn-primary-custom"
                                    data-bs-toggle="modal"
                                    data-bs-target="#languageSelectionModal"
                                    data-test-name="<?php echo htmlspecialchars($test['test_name']); ?>"
                                    data-test-no="<?php echo htmlspecialchars($test['test_no']); ?>"
                                    data-available-languages='<?php echo json_encode($test['all_available_for_popup']); ?>'>
                                    <?php echo get_text('start_test_button'); ?> <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            <?php } else { ?>
                                <form action="/user/test/" method="POST" style="display:inline;">
                                    <input type="hidden" name="test_id" value="<?php echo (int)$test['test_id']; ?>">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <?php echo get_text('start_test_button'); ?> <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </form>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } else { ?>
    <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_tests_available_message'); ?>
    </div>
<?php } ?>

<!-- Language selection modal -->
<div class="modal fade" id="languageSelectionModal" tabindex="-1" aria-labelledby="languageSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="languageSelectionModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead text-center" id="modalIntroText"></p>
                <form id="languageSelectionForm" action="/user/test/" method="POST">
                    <div id="languageRadios" class="d-grid gap-2"></div>
                    <div class="d-flex justify-content-center mt-4">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                        <button type="submit" class="btn btn-primary-custom" id="confirmLanguageBtn" disabled><?php echo get_text('confirm_button'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<br><br>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var languageSelectionModal = document.getElementById('languageSelectionModal');
        var confirmLanguageBtn = document.getElementById('confirmLanguageBtn');
        var languageSelectionForm = document.getElementById('languageSelectionForm');

        languageSelectionModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var testNo = button.getAttribute('data-test-no') || '';
            var testName = button.getAttribute('data-test-name') || '';
            var availableLanguages = JSON.parse(button.getAttribute('data-available-languages') || '[]');

            var modalTitle = languageSelectionModal.querySelector('.modal-title');
            var modalIntroText = languageSelectionModal.querySelector('#modalIntroText');
            var languageRadiosContainer = languageSelectionModal.querySelector('#languageRadios');

            modalTitle.textContent = '<?php echo get_text('select_language_for_test'); ?>: ' + testName + (testNo ? ' (<?php echo get_text('test_no_label'); ?>: ' + testNo + ')' : '');
            modalIntroText.textContent = '<?php echo get_text('please_choose_language_to_start'); ?>';
            languageRadiosContainer.innerHTML = '';
            confirmLanguageBtn.disabled = true;

            availableLanguages.sort(function(a, b) {
                return String(a.language || '').localeCompare(String(b.language || ''));
            });

            availableLanguages.forEach(function(langOption, index) {
                var radioDiv = document.createElement('div');
                radioDiv.className = 'form-check form-check-inline p-0 d-block';

                var input = document.createElement('input');
                input.type = 'radio';
                input.className = 'form-check-input d-none';
                input.id = 'langRadio' + index;
                input.name = 'test_id';
                input.value = langOption.test_id;
                input.required = true;

                var label = document.createElement('label');
                label.className = 'btn btn-lg btn-outline-primary w-100 py-3';
                label.htmlFor = 'langRadio' + index;
                label.textContent = String(langOption.language || '').toUpperCase();

                radioDiv.appendChild(input);
                radioDiv.appendChild(label);
                languageRadiosContainer.appendChild(radioDiv);

                input.addEventListener('change', function() {
                    confirmLanguageBtn.disabled = false;
                });
            });

            languageRadiosContainer.querySelectorAll('input[type="radio"]').forEach(function(radio) {
                radio.checked = false;
            });
        });

        languageSelectionModal.addEventListener('hidden.bs.modal', function() {
            confirmLanguageBtn.disabled = true;
            languageSelectionForm.reset();
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
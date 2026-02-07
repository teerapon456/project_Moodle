<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/header.php'; 
// Error handling configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    return true; // Suppress error display but log it
});

set_exception_handler(function (Throwable $exception) {
    error_log($exception->getMessage());
});


$page_title = get_text('user_dashboard_title');

$conn->query("SET time_zone = '+07:00'");
redirect_if_not_logged_in('associate', 'applicant');

$user_id = $_SESSION['user_id'];
$available_tests = [];
$current_site_language = $_SESSION['lang'] ?? 'en';

try {
    // 1. ดึงข้อมูลแบบทดสอบที่ผู้ใช้ทำ 'เสร็จสิ้นแล้ว' (is_completed = 1)
    $completed_test_ids = [];
    $completed_test_nos = [];

    $stmt_completed = $conn->prepare("
        SELECT DISTINCT t.test_id, t.test_no
        FROM user_test_attempts uta
        JOIN tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = ? AND uta.is_completed = 1
    ");
    if (!$stmt_completed) {
        throw new Exception("Failed to prepare completed tests statement");
    }
    $stmt_completed->bind_param("i", $user_id);
    $stmt_completed->execute();
    $result_completed = $stmt_completed->get_result();
    while ($row_completed = $result_completed->fetch_assoc()) {
        if ($row_completed['test_no'] !== null) {
            $completed_test_nos[$row_completed['test_no']] = true;
        } else {
            $completed_test_ids[$row_completed['test_id']] = true;
        }
    }
    $stmt_completed->close();
    error_log(sprintf(
        "[%s] Dashboard: Fetched completed tests for user %d. Completed test IDs: %s, Completed test Nos: %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        json_encode(array_keys($completed_test_ids)),
        json_encode(array_keys($completed_test_nos))
    ), 3, LOG_FILE);


    // 2. ดึงข้อมูล test_id และ test_no ของแบบทดสอบที่ผู้ใช้กำลังทำอยู่ (is_completed = 0)
    $active_in_progress_test_id = null;
    $active_in_progress_test_no = null;
    $active_in_progress_test_language = null;

    // Get all in-progress tests for this user
    $in_progress_tests = [];
    $stmt_in_progress = $conn->prepare("
        SELECT uta.attempt_id, t.test_id, t.test_no, t.language, t.duration_minutes, t.test_name
        FROM user_test_attempts uta
        JOIN tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = ? AND uta.is_completed = 0
        ORDER BY uta.attempt_id DESC
    ");
    if (!$stmt_in_progress) {
        $error_message = "Failed to prepare in-progress tests statement: " . $conn->error;
        error_log(sprintf("[%s] Dashboard DB Error: %s\n", date('Y-m-d H:i:s'), $error_message), 3, LOG_FILE);
        throw new Exception($error_message);
    }
    $stmt_in_progress->bind_param("i", $user_id);
    $stmt_in_progress->execute();
    $result_in_progress = $stmt_in_progress->get_result();
    
    while ($row = $result_in_progress->fetch_assoc()) {
        $in_progress_tests[$row['test_id']] = [
            'attempt_id' => $row['attempt_id'],
            'test_id' => $row['test_id'],
            'test_no' => $row['test_no'],
            'language' => $row['language'],
            'test_name' => $row['test_name']
        ];
        
        // For backward compatibility, set the first test as active
        if ($active_in_progress_test_id === null) {
            $active_in_progress_test_id = $row['test_id'];
            $active_in_progress_test_no = $row['test_no'];
            $active_in_progress_test_language = $row['language'];
        }
    }
    
    error_log(sprintf(
        "[%s] Dashboard: User %d has %d tests in progress. Active test: ID %d, No: %s, Lang: %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        count($in_progress_tests),
        $active_in_progress_test_id ?? 'N/A',
        $active_in_progress_test_no ?? 'N/A',
        $active_in_progress_test_language ?? 'N/A'
    ), 3, LOG_FILE);
    
    if (empty($in_progress_tests)) {
        error_log(sprintf("[%s] Dashboard: User %d has no tests in progress.\n", date('Y-m-d H:i:s'), $user_id), 3, LOG_FILE);
    }
    $stmt_in_progress->close();

    // 3. ดึงแบบทดสอบที่เผยแพร่ทั้งหมด พร้อมดึงข้อมูลภาษาอื่น ๆ ด้วย
    $all_published_tests_raw = [];
    $stmt_published = $conn->prepare("
        SELECT test_id, test_name, description, test_no, language, duration_minutes
        FROM tests
        WHERE is_published = 1 
        AND (published_at IS NULL OR published_at <= NOW())
        AND (unpublished_at IS NULL OR unpublished_at > NOW())
        ORDER BY test_name ASC
    ");
    if (!$stmt_published) {
        $error_message = "Failed to prepare published tests statement: " . $conn->error;
        error_log(sprintf("[%s] Dashboard DB Error: %s\n", date('Y-m-d H:i:s'), $error_message), 3, LOG_FILE);
        throw new Exception($error_message);
    }
    $stmt_published->execute();
    $result_published = $stmt_published->get_result();
    while ($test_row = $result_published->fetch_assoc()) {
        $all_published_tests_raw[] = $test_row;
    }
    $stmt_published->close();

    // Group tests by test_no to easily find available languages
    $grouped_tests_by_test_no = [];
    foreach ($all_published_tests_raw as $test) {
        if ($test['test_no'] !== null) {
            if (!isset($grouped_tests_by_test_no[$test['test_no']])) {
                $grouped_tests_by_test_no[$test['test_no']] = [];
            }
            $grouped_tests_by_test_no[$test['test_no']][] = $test;
        }
    }

    // 4. Process tests to show one per test_no with all available languages
    $processed_test_nos = [];
    
    // First, process tests that are in progress
    foreach ($in_progress_tests as $in_progress) {
        $test_no = $in_progress['test_no'];
        if ($test_no === null) continue; // Skip tests without test_no
        
        if (!in_array($test_no, $processed_test_nos)) {
            // Find the full test data from all_published_tests_raw
            $full_test_data = null;
            foreach ($all_published_tests_raw as $published_test) {
                if ($published_test['test_id'] === $in_progress['test_id']) {
                    $full_test_data = $published_test;
                    break;
                }
            }
            
            $test = [
                'test_id' => $in_progress['test_id'],
                'test_no' => $in_progress['test_no'],
                'test_name' => $in_progress['test_name'],
                'language' => $in_progress['language'],
                'description' => $full_test_data['description'] ?? $in_progress['description'] ?? '',
                'status' => 'in_progress',
                'duration_minutes' => $full_test_data['duration_minutes'] ?? $in_progress['duration_minutes'] ?? null,
                'other_available_languages' => []
            ];
            
            // Add all available languages for this test_no
            if (isset($grouped_tests_by_test_no[$test_no])) {
                foreach ($grouped_tests_by_test_no[$test_no] as $lang_option) {
                    if ($lang_option['test_id'] !== $in_progress['test_id']) {
                        $test['other_available_languages'][] = [
                            'test_id' => $lang_option['test_id'],
                            'language' => $lang_option['language'],
                            'test_name' => $lang_option['test_name'],
                            'test_no' => $lang_option['test_no']
                        ];
                    }
                }
            }
            
            $available_tests[] = $test;
            $processed_test_nos[] = $test_no;
        }
    }
    
    // Then process tests that are not in progress
    foreach ($all_published_tests_raw as $test) {
        $current_test_id = $test['test_id'];
        $current_test_no = $test['test_no'];
        
        // Skip if already processed or completed
        if ($current_test_no === null || in_array($current_test_no, $processed_test_nos) || 
            isset($completed_test_nos[$current_test_no])) {
            continue;
        }
        
        // Create a test entry with all available languages
        $test_entry = [
            'test_id' => $current_test_id,
            'test_no' => $current_test_no,
            'test_name' => $test['test_name'],
            'language' => $test['language'],
            'description' => $test['description'],
            'status' => 'not_started',
            'duration_minutes' => $test['duration_minutes'] ?? null,
            'other_available_languages' => []
        ];
        
        // Add all available languages for this test_no
        if (isset($grouped_tests_by_test_no[$current_test_no])) {
            foreach ($grouped_tests_by_test_no[$current_test_no] as $lang_option) {
                if ($lang_option['test_id'] !== $current_test_id && 
                    !isset($completed_test_ids[$lang_option['test_id']])) {
                    $test_entry['other_available_languages'][] = [
                        'test_id' => $lang_option['test_id'],
                        'language' => $lang_option['language'],
                        'test_name' => $lang_option['test_name'],
                        'test_no' => $lang_option['test_no']
                    ];
                }
            }
        }
        
        $available_tests[] = $test_entry;
        $processed_test_nos[] = $current_test_no;
    }

    // จัดเรียง
    usort($available_tests, function ($a, $b) {
        // ให้แบบทดสอบที่กำลังทำอยู่แสดงขึ้นมาก่อน
        $statusA = isset($a['status']) ? $a['status'] : '';
        $statusB = isset($b['status']) ? $b['status'] : '';

        if ($statusA === 'in_progress' && $statusB !== 'in_progress') return -1;
        if ($statusA !== 'in_progress' && $statusB === 'in_progress') return 1;

        // ถ้าสถานะเหมือนกัน ให้เรียงตาม test_no และ test_name
        if ($a['test_no'] === null && $b['test_no'] !== null) return 1;
        if ($a['test_no'] !== null && $b['test_no'] === null) return -1;
        if ($a['test_no'] !== null && $b['test_no'] !== null) {
            $cmp_test_no = strcmp($a['test_no'], $b['test_no']);
            if ($cmp_test_no !== 0) {
                return $cmp_test_no;
            }
        }
        return strcmp($a['test_name'], $b['test_name']);
    });
} catch (Exception $e) {
    set_alert(get_text('alert_fetch_test_error', [$e->getMessage()]), "danger");
    error_log(sprintf(
        "[%s] Dashboard Exception: %s (Code: %s, File: %s, Line: %d)\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getCode(),
        $e->getFile(),
        $e->getLine()
    ), 3, LOG_FILE);
}
?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('dashboard_welcome_heading'); ?></h1>
<p class="lead"><?php echo get_text('dashboard_available_tests_intro'); ?></p>

<?php if (!empty($available_tests)): ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($available_tests as $test): ?>
            <div class="col">
                <div class="card h-100 shadow">
                    <div class="card-header bg-primary-custom text-white">
                        <h5 class="card-title"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($test['description'])); ?></p>
                        <p class="card-text">
                            <?php
                            // Show test number if available
                            $line1_parts = [];
                            if (!empty($test['test_no'])) {
                                $line1_parts[] = get_text('test_no_label') . ': ' . htmlspecialchars($test['test_no']);
                            }
                            
                            // Show all available languages with current language highlighted
                            $all_languages = [];
                            $current_language = !empty($test['language']) ? strtoupper($test['language']) : '';
                            
                            // Add current test's language
                            if ($current_language) {
                                $all_languages[] = '<span class="fw-bold">' . htmlspecialchars($current_language) . '</span>';
                            }
                            
                            // Add other available languages
                            if (!empty($test['other_available_languages'])) {
                                foreach ($test['other_available_languages'] as $lang) {
                                    $lang_upper = strtoupper($lang['language']);
                                    if ($lang_upper !== $current_language) {
                                        $all_languages[] = htmlspecialchars($lang_upper);
                                    }
                                }
                            }
                            
                            if (!empty($all_languages)) {
                                echo '<div class="mt-2"><small class="text-muted">';
                                echo get_text('languages') . ': ' . implode(' | ', $all_languages);
                                echo '</small></div>';
                            }
                            
                            // Show test duration
                            if (isset($test['duration_minutes']) && $test['duration_minutes'] !== null) {
                                echo '<div class="mt-2">' . get_text('time_of_test_label') . ': ' . 
                                     htmlspecialchars($test['duration_minutes']) . ' ' . get_text('minutes_abbr') . '</div>';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="card-footer bg-secondary">
                        <?php if (isset($test['status']) && $test['status'] === 'in_progress'): ?>
                            <form action="/INTEQC_GLOBAL_ASSESMENT/user/test" method="POST" style="display:inline;">
                                <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                <input type="hidden" name="attempt_id" value="<?php echo $in_progress_tests[$test['test_id']]['attempt_id']; ?>">
                                <button type="submit" class="btn btn-info-custom text-white">
                                    <i class="fas fa-play-circle me-2"></i> <?php echo get_text('continue_test_button'); ?>
                                </button>
                            </form>
                        <?php else: // status is 'not_started' 
                        ?>
                            <?php
                            $can_show_language_popup = false;
                            // ใช้ associative array เพื่อป้องกันภาษาซ้ำกัน
                            $all_available_for_popup_temp = [];

                            // เพิ่มภาษาของการ์ดปัจจุบันเข้าไปก่อน ถ้ายังไม่เสร็จสิ้น
                            $is_current_test_completed_for_popup =
                                ($test['test_no'] !== null && isset($completed_test_nos[$test['test_no']])) ||
                                ($test['test_no'] === null && isset($completed_test_ids[$test['test_id']]));

                            if (!$is_current_test_completed_for_popup) {
                                $all_available_for_popup_temp[$test['language']] = [ // ใช้ language เป็น key
                                    'test_id' => $test['test_id'],
                                    'language' => $test['language'],
                                    'test_name' => $test['test_name'],
                                    'test_no' => $test['test_no']
                                ];
                            }

                            // เพิ่มภาษาอื่นๆ ที่ยังไม่เสร็จสิ้นในกลุ่มเดียวกัน
                            if ($test['test_no'] !== null && isset($test['other_available_languages'])) {
                                foreach ($test['other_available_languages'] as $ol) {
                                    $is_ol_completed =
                                        ($ol['test_no'] !== null && isset($completed_test_nos[$ol['test_no']])) ||
                                        (isset($completed_test_ids[$ol['test_id']]) && $ol['test_no'] === null);
                                    if (!$is_ol_completed) {
                                        $all_available_for_popup_temp[$ol['language']] = [ // ใช้ language เป็น key
                                            'test_id' => $ol['test_id'],
                                            'language' => $ol['language'],
                                            'test_name' => $ol['test_name'],
                                            'test_no' => $ol['test_no']
                                        ];
                                    }
                                }
                            }

                            // ตรวจสอบว่ามีภาษาที่แตกต่างกันมากกว่า 1 ภาษาในกลุ่มนี้หรือไม่
                            if (count($all_available_for_popup_temp) > 1) {
                                $can_show_language_popup = true;
                            }

                            // แปลง associative array เป็น list array สำหรับ JSON
                            $all_available_for_popup = array_values($all_available_for_popup_temp);

                            ?>
                            <?php if ($can_show_language_popup): ?>
                                <button type="button" class="btn btn-primary-custom"
                                    data-bs-toggle="modal"
                                    data-bs-target="#languageSelectionModal"
                                    data-test-no="<?php echo htmlspecialchars($test['test_no']); ?>"
                                    data-test-name="<?php echo htmlspecialchars($test['test_name']); ?>"
                                    data-available-languages='<?php echo json_encode($all_available_for_popup); ?>'>
                                    <?php echo get_text('start_test_button'); ?> <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            <?php else: ?>
                                <form action="/INTEQC_GLOBAL_ASSESMENT/user/test/" method="POST" style="display:inline;">
                                    <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <?php echo get_text('start_test_button'); ?> <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_tests_available_message'); ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="languageSelectionModal" tabindex="-1" aria-labelledby="languageSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="languageSelectionModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead text-center" id="modalIntroText"></p>
                <form id="languageSelectionForm" action="/INTEQC_GLOBAL_ASSESMENT/user/test/" method="POST">
                    <div id="languageRadios" class="d-grid gap-2">
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                        <button type="submit" class="btn btn-primary-custom" id="confirmLanguageBtn" disabled><?php echo get_text('confirm_button'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var languageSelectionModal = document.getElementById('languageSelectionModal');
        var confirmLanguageBtn = document.getElementById('confirmLanguageBtn');
        var languageSelectionForm = document.getElementById('languageSelectionForm');

        languageSelectionModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var testNo = button.getAttribute('data-test-no');
            var testName = button.getAttribute('data-test-name');
            var availableLanguages = JSON.parse(button.getAttribute('data-available-languages'));

            var modalTitle = languageSelectionModal.querySelector('.modal-title');
            var modalIntroText = languageSelectionModal.querySelector('#modalIntroText');
            var languageRadiosContainer = languageSelectionModal.querySelector('#languageRadios');

            modalTitle.textContent = '<?php echo get_text('select_language_for_test'); ?>: ' + testName + (testNo ? ' (<?php echo get_text('test_no_label'); ?>: ' + testNo + ')' : '');
            modalIntroText.textContent = '<?php echo get_text('please_choose_language_to_start'); ?>';
            languageRadiosContainer.innerHTML = ''; // Clear previous radio buttons
            confirmLanguageBtn.disabled = true; // Disable confirm button initially

            // Sort languages alphabetically for consistent display
            availableLanguages.sort(function(a, b) {
                return a.language.localeCompare(b.language);
            });

            availableLanguages.forEach(function(langOption, index) {
                var radioDiv = document.createElement('div');
                radioDiv.className = 'form-check form-check-inline p-0 d-block'; // d-block makes it full width

                var input = document.createElement('input');
                input.type = 'radio';
                input.className = 'form-check-input d-none'; // Hide default radio button
                input.id = 'langRadio' + index;
                input.name = 'test_id'; // Name for radio group
                input.value = langOption.test_id;
                input.required = true; // Make selection required

                var label = document.createElement('label');
                label.className = 'btn btn-lg btn-outline-primary w-100 py-3'; // Full width, large, outline button look
                label.htmlFor = 'langRadio' + index;
                label.textContent = langOption.language.toUpperCase();

                radioDiv.appendChild(input);
                radioDiv.appendChild(label);
                languageRadiosContainer.appendChild(radioDiv);

                // Add event listener to enable confirm button when a radio is selected
                input.addEventListener('change', function() {
                    confirmLanguageBtn.disabled = false;
                });
            });

            // Clear any previously selected radio on modal show
            languageRadiosContainer.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.checked = false;
            });

        });

        // Ensure confirm button is disabled if modal is opened again without selection
        languageSelectionModal.addEventListener('hidden.bs.modal', function() {
            confirmLanguageBtn.disabled = true;
            languageSelectionForm.reset(); // Reset form to clear radio selection
        });
    });
</script>
<br><br>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
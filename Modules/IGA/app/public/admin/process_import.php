<?php
date_default_timezone_set('Asia/Bangkok'); // Set your timezone

require_once __DIR__ . '/../../includes/functions.php'; // ยังคง include เพราะอาจมีฟังก์ชันอื่นที่ใช้ เช่น require_login(), has_role(), set_alert()

// Load PhpSpreadsheet library (ensure you have run 'composer install' in the project root)
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ตรวจสอบ session และการเชื่อมต่อฐานข้อมูล
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = $_SESSION['user_id'];
    // ตั้งค่า User-defined variable @user_id ในฐานข้อมูล
    $conn->query("SET @user_id = " . $current_user_id);
    // --- Security & Permissions ---
    require_login(); // Ensure user is logged in
    if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
        set_alert(get_text('alert_no_admin_permission'), "danger");
        header("Location: /login");
        exit();
    }

    $current_user_id = $_SESSION['user_id'] ?? null;
    if (empty($current_user_id)) {
        // This should ideally not happen if require_login() works correctly
        set_alert(get_text('error_user_not_logged_in_for_import'), "danger");
        header("Location: /login");
        exit();
    }
    // --- End Security & Permissions ---

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file'];

        // --- File Upload Validation ---
        if ($file['error'] !== UPLOAD_ERR_OK) {
            set_alert(get_text('upload_error_generic', $file['error']), "danger");
            header("Location: import_test_form.php");
            exit();
        }

        $file_mimes = [
            'application/vnd.ms-excel',
            'application/octet-stream',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        if (!in_array($file['type'], $file_mimes)) {
            set_alert(get_text('upload_error_invalid_file_type'), "danger");
            header("Location: import_test_form.php");
            exit();
        }
        // --- End File Upload Validation ---

        $filePath = $file['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);

            // Start a database transaction for atomicity
            $conn->begin_transaction();

            // === Sheet 1: Test Information ===
            $testSheet = $spreadsheet->getSheet(0);
            $testData = $testSheet->toArray();

            // Validate if test sheet has at least one row (header)
            if (empty($testData) || !isset($testData[0]) || !is_array($testData[0])) {
                throw new Exception(get_text('error_test_sheet_empty_or_no_headers'));
            }

            $headerRowTest = array_map('trim', $testData[0]); // Get and trim headers
            $expectedTestHeaders = [
                'test_name',
                'description',
                'is_published',
                'duration_minutes',
                'show_result_immediately',
                'min_passing_score',
                'creation_year',
                'test_no',
                'language'
                // 'created_by_user_id' is handled by current_user_id, not from Excel
            ];

            // Validate if all expected headers are present in the Test sheet
            foreach ($expectedTestHeaders as $expectedHeader) {
                if (!in_array($expectedHeader, $headerRowTest)) {
                    throw new Exception(get_text('error_test_sheet_header_mismatch') . ": " . get_text('missing_header') . " '" . $expectedHeader . "'");
                }
            }

            // Map header names to their column indices
            $testNameCol = array_search('test_name', $headerRowTest);
            $testDescCol = array_search('description', $headerRowTest);
            $isPublishedCol = array_search('is_published', $headerRowTest);
            $durationMinutesCol = array_search('duration_minutes', $headerRowTest);
            $showResultImmediatelyCol = array_search('show_result_immediately', $headerRowTest);
            $minPassingScoreCol = array_search('min_passing_score', $headerRowTest);
            $creationYearCol = array_search('creation_year', $headerRowTest);
            $testNoCol = array_search('test_no', $headerRowTest);
            $languageCol = array_search('language', $headerRowTest);

            // Read Test data from the second row (index 1 in array)
            if (isset($testData[1])) {
                $testRow = $testData[1];

                $test_name = trim($testRow[$testNameCol] ?? '');
                $description = trim($testRow[$testDescCol] ?? '');
                $is_published = (isset($testRow[$isPublishedCol]) && strtolower(trim($testRow[$isPublishedCol])) === 'yes') ? 1 : 0;
                $duration_minutes = (int)($testRow[$durationMinutesCol] ?? 0);
                $show_result_immediately = (isset($testRow[$showResultImmediatelyCol]) && strtolower(trim($testRow[$showResultImmediatelyCol])) === 'yes') ? 1 : 0;
                $min_passing_score = (int)($testRow[$minPassingScoreCol] ?? 0);
                $creation_year = (int)($testRow[$creationYearCol] ?? date('Y'));
                $test_no = trim($testRow[$testNoCol] ?? '');
                $language = trim($testRow[$languageCol] ?? 'en'); 

                // Set created_by_user_id from the current logged-in user
                $created_by_user_id = $current_user_id;

                if (empty($test_name)) {
                    throw new Exception(get_text('error_test_name_missing'));
                }
                // *** START: Added validation for test_no and language ***
                if (empty($test_no)) {
                    throw new Exception(get_text('error_test_number_missing'));
                }
                if (empty($language)) {
                    throw new Exception(get_text('error_test_language_missing'));
                }
                // *** END: Added validation for test_no and language ***

                // Insert Test data into 'tests' table
                $stmt_test = $conn->prepare("INSERT INTO iga_tests (test_name, description, is_published, created_by_user_id, duration_minutes, show_result_immediately, min_passing_score, creation_year, test_no, language, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt_test->bind_param(
                    "ssisiiiiss", // s: test_name, s: description, i: is_published, i: created_by_user_id, i: duration_minutes, i: show_result_immediately, i: min_passing_score, i: creation_year, s: test_no, s: language
                    $test_name,
                    $description,
                    $is_published,
                    $created_by_user_id,
                    $duration_minutes,
                    $show_result_immediately,
                    $min_passing_score,
                    $creation_year,
                    $test_no,
                    $language
                );
                $stmt_test->execute();
                $test_id = $stmt_test->insert_id; 
                $stmt_test->close();

                // The database trigger is responsible for logging audit trails.

            } else {
                throw new Exception(get_text('error_no_test_data_found'));
            }

            // === Sheet 2: Sections and Questions ===
            $questionSheet = $spreadsheet->getSheet(1); 
            $rowData = $questionSheet->toArray(null, true, false, true);

            // --- VALIDATION FOR QUESTION SHEET HEADERS ---
            // Check if $rowData is empty or doesn't contain at least a header row
            if (empty($rowData) || !isset($rowData[1]) || !is_array($rowData[1])) {
                throw new Exception(get_text('error_question_sheet_empty_or_no_headers'));
            }

            $headerRowQuestion = array_map('trim', $rowData[1]); 

            // บรรทัดนี้ unset($rowData[1]); ปล่อยไว้เหมือนเดิมได้เลยค่ะ
            // เพราะมันจะลบ header row ออกจาก $rowData ทำให้ลูปข้อมูลด้านล่างทำงานถูกต้อง
            unset($rowData[1]);

            // Define expected headers for Sections and Questions, including new fields
            // --- UPDATED HEADERS FOR VARIABLE MULTIPLE CHOICE OPTIONS ---
            $expectedQuestionHeaders = [
                'Section Name',
                'Section Description',
                'Section Duration',
                'Section Order',
                'Question Type',
                'Question Text',
                'Question Order',
                'Is Critical',
                'Option1',
                'Option2',
                'Option3',
                'Option4',
                'Option5',
                'Option6',
                'Option7',
                'Correct Answer Index',
                'Score'
            ];

            foreach ($expectedQuestionHeaders as $expectedHeader) {
                if (!in_array($expectedHeader, $headerRowQuestion)) {
                    throw new Exception(get_text('error_question_sheet_header_mismatch') . ": " . get_text('missing_header') . " '" . $expectedHeader . "'");
                }
            }

            $sectionNameCol = array_search('Section Name', $headerRowQuestion);
            $sectionDescriptionCol = array_search('Section Description', $headerRowQuestion);
            $sectionDurationCol = array_search('Section Duration', $headerRowQuestion);
            $sectionOrderCol = array_search('Section Order', $headerRowQuestion);
            $questionTypeCol = array_search('Question Type', $headerRowQuestion);
            $questionTextCol = array_search('Question Text', $headerRowQuestion);
            $questionOrderCol = array_search('Question Order', $headerRowQuestion);
            $isCriticalCol = array_search('Is Critical', $headerRowQuestion);

            // --- MAPPING FOR NEW OPTION COLUMNS ---
            $optionCols = [];
            for ($i = 1; $i <= 7; $i++) { // Loop for Option1 to Option7
                $optionCols[$i] = array_search('Option' . $i, $headerRowQuestion);
            }
            // --- END MAPPING ---

            $correctAnswerIndexCol = array_search('Correct Answer Index', $headerRowQuestion); 
            $scoreCol = array_search('Score', $headerRowQuestion);

            $current_section_id = null;
            $current_section_name = '';
            $current_section_order = 0; 

            // *** ส่วนที่เพิ่ม/แก้ไข: โหลดข้อมูลภาษาสำหรับใช้ใน process_import.php เท่านั้น ***
            // นี่คือการโหลดข้อมูลภาษาจากไฟล์ PHP Array โดยตรง ไม่ยุ่งกับ global $lang_data
            $test_lang_data = [];
            $language_file_path = __DIR__ . "/../../languages/{$language}.php";

            if (file_exists($language_file_path)) {
                $loaded_data = include $language_file_path;
                if (is_array($loaded_data)) {
                    $test_lang_data = $loaded_data;
                } else {
                    error_log("Language file '{$language_file_path}' did not return an array. Using empty data for local translations.");
                }
            } else {
                error_log("Test language file not found for: '{$language}' at '{$language_file_path}'. Trying default 'en'.");
                // หากไม่พบไฟล์ภาษาที่เฉพาะเจาะจง ให้ลองโหลดภาษาเริ่มต้น (เช่น 'th')
                $default_lang_file_path = __DIR__ . "/../../languages/th.php"; // Changed default to 'th'
                if (file_exists($default_lang_file_path)) {
                    $loaded_data = include $default_lang_file_path;
                    if (is_array($loaded_data)) {
                        $test_lang_data = $loaded_data;
                    } else {
                        error_log("Default language file '{$default_lang_file_path}' did not return an array. Using empty data for local translations.");
                    }
                } else {
                    // Fallback to English if default Thai is also not found
                    $default_en_lang_file_path = __DIR__ . "/../../languages/en.php";
                    if (file_exists($default_en_lang_file_path)) {
                        $loaded_data = include $default_en_lang_file_path;
                        if (is_array($loaded_data)) {
                            $test_lang_data = $loaded_data;
                        }
                    }
                }
            }
            // *** สิ้นสุดส่วนเพิ่ม/แก้ไข: โหลดข้อมูลภาษา ***

            // --- Loop through rows starting from the actual data row (index 2) ---
            $maxRowIndex = max(array_keys($rowData)); // Get the highest index after unset
            for ($row_idx = 2; $row_idx <= $maxRowIndex; $row_idx++) { // Use <= $maxRowIndex
                if (!isset($rowData[$row_idx])) {
                    continue; // Skip if row doesn't exist (e.g., if array keys are not sequential)
                }
                $data = $rowData[$row_idx];

                // Handle potential entirely empty rows (where all cells are empty)
                if (empty(array_filter($data))) {
                    continue; 
                }

                $excel_row_num = $row_idx + 1; 

                $section_name_from_excel = trim($data[$sectionNameCol] ?? '');
                $section_description_from_excel = trim($data[$sectionDescriptionCol] ?? '');
                $section_duration_from_excel = (int)($data[$sectionDurationCol] ?? 0);
                $section_order_from_excel = (int)($data[$sectionOrderCol] ?? 0);

                $question_type = strtolower(trim($data[$questionTypeCol] ?? ''));
                $question_text = trim($data[$questionTextCol] ?? '');
                $score = (float)($data[$scoreCol] ?? 0);
                $question_order = (int)($data[$questionOrderCol] ?? 0);
                $is_critical = (isset($data[$isCriticalCol]) && strtolower(trim($data[$isCriticalCol])) === 'yes') ? 1 : 0;

                // --- Section Handling ---
                // If a new section name is provided or it's the very first section
                if (!empty($section_name_from_excel) && ($section_name_from_excel !== $current_section_name || ($section_order_from_excel > 0 && $section_order_from_excel !== $current_section_order) || $current_section_id === null)) {
                    // Explicitly check if the section name is provided if a new section is being defined
                    if (empty($section_name_from_excel)) {
                        throw new Exception(get_text('error_section_name_missing_in_row', $excel_row_num));
                    }

                    $stmt_section = $conn->prepare("INSERT INTO iga_sections (test_id, section_name, description, duration_minutes, section_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt_section->bind_param("issii", $test_id, $section_name_from_excel, $section_description_from_excel, $section_duration_from_excel, $section_order_from_excel);
                    $stmt_section->execute();
                    $current_section_id = $stmt_section->insert_id;
                    $stmt_section->close();
                    $current_section_name = $section_name_from_excel;
                    $current_section_order = $section_order_from_excel;
                } elseif (empty($section_name_from_excel) && $current_section_id === null) {
                    // First question cannot be without a section name defined in that row
                    throw new Exception(get_text('error_first_question_missing_section') . " (Row: " . $excel_row_num . ")");
                }

                // Ensure we have a valid section ID for the current question
                if ($current_section_id === null) {
                    throw new Exception(get_text('error_section_not_defined_for_question', $excel_row_num));
                }

                // --- Question Text Validation ---
                // If the current row is not defining a new section (i.e., section_name_from_excel is empty or same as current)
                // AND the question_text is empty, then this is an error for a question row.
                // If it defines a new section but has no question text, that's considered a section-only row and is skipped for question insertion.
                if (empty($question_text)) {
                    if (empty($section_name_from_excel) || $section_name_from_excel === $current_section_name) {
                        // This row is expected to be a question for the current section, but question text is empty.
                        throw new Exception(get_text('error_question_text_missing', $excel_row_num));
                    } else {
                        // This row defines a *new* section but has no question. This is allowed, so skip question insertion.
                        continue;
                    }
                }

                // --- Question Insertion ---
                $stmt_question = $conn->prepare("INSERT INTO iga_questions (section_id, question_text, question_type, score, question_order, is_critical, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt_question->bind_param("issdii", $current_section_id, $question_text, $question_type, $score, $question_order, $is_critical);
                $stmt_question->execute();
                $question_id = $stmt_question->insert_id;
                $stmt_question->close();

                // --- Option Handling based on Question Type ---
                if ($question_type === 'multiple_choice') {
                    $options_to_insert = [];
                    $correct_answer_index = (int)($data[$correctAnswerIndexCol] ?? 0); // Correct Answer Index (1-7)

                    // Loop through Option1 to Option7 columns
                    for ($i = 1; $i <= 7; $i++) {
                        $option_text = trim($data[$optionCols[$i]] ?? '');

                        // Only consider non-empty options for insertion
                        if (!empty($option_text)) {
                            $is_correct_option = ($i === $correct_answer_index) ? 1 : 0;
                            $options_to_insert[] = [
                                'text' => $option_text,
                                'is_correct' => $is_correct_option
                            ];
                        }
                    }

                    // Validate if any option is provided for multiple choice
                    if (empty($options_to_insert)) {
                        throw new Exception(get_text('error_mc_options_missing', $excel_row_num));
                    }
                    // Validate correct answer index points to an existing option
                    $found_correct_option = false;
                    foreach ($options_to_insert as $opt) {
                        if ($opt['is_correct'] === 1) {
                            $found_correct_option = true;
                            break;
                        }
                    }
                    if (!$found_correct_option) {
                        // *** START: Modified error message to include section name ***
                        throw new Exception(
                            get_text('error_mc_correct_answer_invalid', $excel_row_num) .
                                get_text('in_section') . " '" . ($current_section_name ?? get_text('unknown_section')) . "'." .
                                " " . get_text('expected_index_from_1_to_7') . " " . get_text('found') . " '" . ($data[$correctAnswerIndexCol] ?? '') . "'"
                        );
                        // *** END: Modified error message ***
                    }

                    foreach ($options_to_insert as $option_data) {
                        $stmt_option = $conn->prepare("INSERT INTO iga_question_options (question_id, option_text, is_correct, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                        $stmt_option->bind_param("isi", $question_id, $option_data['text'], $option_data['is_correct']);
                        $stmt_option->execute();
                        $stmt_option->close();
                    }
                } elseif ($question_type === 'short_answer') {
                    
                } elseif ($question_type === 'true_false') {
                    // Get the raw value from Excel. It could be a string 'TRUE', 'FALSE', or a boolean true/false depending on Excel's cell formatting.
                    $raw_excel_correct_answer = $data[$correctAnswerIndexCol] ?? null; // For True/False, Correct Answer Index column holds 'TRUE' or 'FALSE'

                    $correct_answer_processed = '';
                    if (is_bool($raw_excel_correct_answer)) {
                        // If PhpSpreadsheet parsed it as a boolean, convert to 'TRUE' or 'FALSE' string
                        $correct_answer_processed = $raw_excel_correct_answer ? 'TRUE' : 'FALSE';
                    } else {
                        // Otherwise, treat it as a string and process normally
                        $correct_answer_processed = strtoupper(trim((string)$raw_excel_correct_answer));
                    }

                    // Use the processed value
                    $correct_answer_excel = $correct_answer_processed;

                    // Define standard True/False options
                    $tf_options = [
                        // *** ตรงนี้คือการใช้ $test_lang_data ที่โหลดมาใน LOCAL VARIABLE ***
                        // ถ้าไม่พบ key ใน $test_lang_data จะใช้ 'True'/'False' เป็นค่า fallback
                        'TRUE' => $test_lang_data['label_true_option'] ?? 'True',
                        'FALSE' => $test_lang_data['label_false_option'] ?? 'False'
                    ];

                    // Validate correct answer from Excel
                    if (!array_key_exists($correct_answer_excel, $tf_options)) {
                        // *** START: Modified error message to include section name ***
                        throw new Exception(get_text('error_tf_correct_answer_invalid', $excel_row_num) . get_text('in_section') . " '" . ($current_section_name ?? get_text('unknown_section')) . "'");
                        // *** END: Modified error message ***
                    }

                    foreach ($tf_options as $option_key => $option_text) {
                        $is_correct_option = ($option_key === $correct_answer_excel) ? 1 : 0;
                        $stmt_tf_option = $conn->prepare("INSERT INTO iga_question_options (question_id, option_text, is_correct, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                        if ($stmt_tf_option === false) {
                            throw new Exception("Failed to prepare option insertion statement.");
                        }
                        $stmt_tf_option->bind_param("isi", $question_id, $option_text, $is_correct_option);
                        $execute_success = $stmt_tf_option->execute();
                        if (!$execute_success) {
                            throw new Exception("Failed to insert True/False option: " . $stmt_tf_option->error);
                        }
                        $stmt_tf_option->close();
                    }
                } elseif ($question_type === 'accept') {
        
                } else {
                    // หาก question_type ไม่ตรงกับที่กำหนด ให้ถือเป็นข้อผิดพลาด
                    // *** ตรงนี้ใช้ $test_lang_data เช่นกันหากต้องการแปลด้วยภาษาของ Test นั้น ***
                    $error_message_template = $test_lang_data['error_unsupported_question_type'] ?? 'Unsupported question type at row %d: %s';
                    throw new Exception(sprintf($error_message_template . get_text('in_section') . " '%s'", $excel_row_num, $question_type, ($current_section_name ?? get_text('unknown_section'))));
                }
            }

            // --- Commit Transaction ---
            $conn->commit();
            set_alert(get_text('import_success', $test_name), "success");
            header("Location: /admin/tests"); // Redirect to a success page
            exit();
        } catch (Exception $e) {
            // --- Rollback Transaction on Error ---
            $conn->rollback();
            error_log("Error during Excel import: " . $e->getMessage()); // Log error to PHP error log
            set_alert(get_text('import_error', $e->getMessage()), "danger");
            header("Location: /admin/import_test_form.php");
            exit();
        }
    } else {
        // If no file was uploaded
        set_alert(get_text('import_error_no_file_uploaded'), "danger");
        header("Location: /admin/import_test_form.php");
        exit();
    }
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    set_alert(get_text('error_user_not_logged_in_for_import'), "danger");
    header("Location: /login");
    exit();
}

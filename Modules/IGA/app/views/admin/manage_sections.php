<?php
require_once __DIR__ . '/../../includes/header.php';
// กำหนดชื่อหน้าโดยใช้ get_text() ก่อน require header.php
$page_title = get_text('page_title_manage_sections');

// ตรวจสอบการล็อกอินและบทบาท
require_login();
if (!has_role('admin') && !has_role('editor')) { // ตรวจสอบบทบาท
    // ใช้ get_text() สำหรับข้อความแจ้งเตือน
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: ../../public/login.php");
    exit();
}

$test_id = $_POST['test_id'] ?? null;
$test_name = '';
$sections = [];
$total_test_max_score = 0; // 💡 เพิ่มตัวแปรสำหรับคะแนนรวมสูงสุดของแบบทดสอบ
// ในไฟล์ PHP ของคุณ เช่น header.php หรือก่อนทำการ query ใดๆ ที่แก้ไขข้อมูล
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} else {
    // หากไม่มี user_id ใน session (เช่น Guest) หรือไม่มีการเชื่อมต่อ db
    $conn->query("SET @user_id = NULL");
}
// ตรวจสอบ test_id
if (!is_numeric($test_id) || $test_id <= 0) {
    set_alert(get_text('alert_invalid_test_id_general'), "danger");
    header("Location: manage_tests.php");
    exit();
}

try {
    // ดึงชื่อแบบทดสอบ
    $stmt = $conn->prepare("SELECT test_name FROM tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $test_name = $result->fetch_assoc()['test_name'];
    } else {
        set_alert(get_text('alert_test_not_found'), "danger");
        header("Location: manage_tests.php");
        exit();
    }
    $stmt->close();

    // ดึงส่วนต่างๆ ของแบบทดสอบ พร้อมกับคำถามและตัวเลือก (ถ้ามี)
    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description, s.section_order, s.duration_minutes,
            q.question_id, q.question_text, q.question_type, q.score, q.question_order, q.is_critical,
            o.option_id, o.option_text, o.is_correct
        FROM sections s
        LEFT JOIN questions q ON s.section_id = q.section_id
        LEFT JOIN question_options o ON q.question_id = o.question_id
        WHERE s.test_id = ?
        ORDER BY s.section_order ASC, q.question_order ASC, o.option_id ASC
    ");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $section_id = $row['section_id'];
        $question_id = $row['question_id'];
        $option_id = $row['option_id'];

        // จัดกลุ่มข้อมูล
        if (!isset($sections[$section_id])) {
            $sections[$section_id] = [
                'section_id' => $row['section_id'],
                'section_name' => $row['section_name'],
                'description' => $row['description'],
                'section_order' => $row['section_order'],
                'duration_minutes' => $row['duration_minutes'],
                'max_score' => 0,
                'questions' => []
            ];
        }

        if ($question_id && !isset($sections[$section_id]['questions'][$question_id])) {
            $sections[$section_id]['questions'][$question_id] = [
                'question_id' => $row['question_id'],
                'question_text' => $row['question_text'],
                'question_type' => $row['question_type'],
                'score' => $row['score'],
                'question_order' => $row['question_order'],
                'is_critical' => $row['is_critical'],
                'options' => []
            ];
            $sections[$section_id]['max_score'] += $row['score'];
        }

        if ($question_id && $option_id) {
            $sections[$section_id]['questions'][$question_id]['options'][$option_id] = [
                'option_id' => $row['option_id'],
                'option_text' => $row['option_text'],
                'is_correct' => $row['is_correct']
            ];
        }
    }
    $stmt->close();

    // คำนวณคะแนนรวมสูงสุดของแบบทดสอบทั้งหมด
    foreach ($sections as $section) {
        $total_test_max_score += $section['max_score'];
    }
} catch (Exception $e) {
    set_alert(get_text('error_fetch_sections_questions', [$e->getMessage()]), "danger");
}
?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('manage_sections_questions_title'); ?>: <br>"<?php echo htmlspecialchars($test_name); ?>"</h1>

<div class="d-flex justify-content-between align-items-center mb-4">
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSectionModal">
        <i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_button'); ?>
    </button>
    <a href="/INTEQC_GLOBAL_ASSESMENT/admin/tests" class="btn btn-secondary">
        <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_to_manage_tests_button'); ?>
    </a>
</div>

<?php echo get_alert(); ?>

<?php if (!empty($sections)): ?>
    <div class="alert alert-info text-center mb-4 shadow-sm">
        <h4 class="alert-heading mb-0"><?php echo get_text('total_test_max_score'); ?>: <?php echo $total_test_max_score; ?> <?php echo get_text('points_unit'); ?></h4>
    </div>
    <div id="accordionSections">
        <?php foreach ($sections as $section): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center" id="heading<?php echo $section['section_id']; ?>">
                    <h5 class="mb-0">
                        <button class="btn btn-link text-white text-decoration-none d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $section['section_id']; ?>" aria-expanded="true" aria-controls="collapse<?php echo $section['section_id']; ?>">
                            <i class="fas fa-chevron-down me-2"></i> <?php echo htmlspecialchars($section['section_order'] . ". " . $section['section_name']); ?>
                            <?php if ($section['duration_minutes'] > 0): ?>
                                <span class="badge bg-light text-dark ms-3">
                                    <i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($section['duration_minutes']); ?> <?php echo get_text('minutes_unit'); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark ms-3"><?php echo get_text('unlimited_time'); ?></span>
                            <?php endif; ?>
                            <span class="badge bg-success text-white ms-3">
                                <i class="fas fa-star me-1"></i> <?php echo get_text('section_max_score'); ?>: <?php echo $section['max_score']; ?> <?php echo get_text('points_unit'); ?>
                            </span>
                        </button>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-info text-white edit-section-btn mb-2"
                            data-id="<?php echo $section['section_id']; ?>"
                            data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                            data-description="<?php echo htmlspecialchars($section['description']); ?>"
                            data-order="<?php echo htmlspecialchars($section['section_order']); ?>"
                            data-duration="<?php echo htmlspecialchars($section['duration_minutes']); ?>" title="<?php echo get_text('edit_section_tooltip'); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-section-btn mb-2" data-id="<?php echo $section['section_id']; ?>" title="<?php echo get_text('delete_section_tooltip'); ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>

                <div id="collapse<?php echo $section['section_id']; ?>" class="collapse show" aria-labelledby="heading<?php echo $section['section_id']; ?>" data-bs-parent="#accordionSections">
                    <div class="card-body">
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($section['description'])); ?></p>
                        <hr>
                        <h6><?php echo get_text('questions_in_this_section'); ?>:</h6>
                        <div class="list-group mb-3">
                            <?php if (!empty($section['questions'])): ?>
                                <?php foreach ($section['questions'] as $question): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start mb-2 shadow-sm rounded-3">
                                        <div class="flex-grow-1">
                                            <p class="mb-1">
                                                <strong><?php echo htmlspecialchars($question['question_order'] . ". " . $question['question_text']); ?></strong>
                                                <?php if ($question['is_critical']): ?>
                                                    <span class="badge bg-warning text-dark ms-2" title="<?php echo get_text('critical_question_tooltip'); ?>">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> <?php echo get_text('critical_question_label'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo get_text('label_type'); ?>: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', get_text('question_type_' . $question['question_type'])))); ?> |
                                                <?php echo get_text('label_score'); ?>: <?php echo htmlspecialchars($question['score']); ?>
                                            </small>
                                            <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false'): ?>
                                                <ul class="list-unstyled mt-2 mb-0 ms-3">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <li>
                                                            <?php if ($option['is_correct']): ?>
                                                                <i class="fas fa-check text-success me-1"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-circle text-muted me-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-info text-white edit-question-btn mb-2"
                                                data-id="<?php echo $question['question_id']; ?>"
                                                data-section-id="<?php echo $section['section_id']; ?>"
                                                data-question-text="<?php echo htmlspecialchars($question['question_text']); ?>"
                                                data-question-type="<?php echo htmlspecialchars($question['question_type']); ?>"
                                                data-score="<?php echo htmlspecialchars($question['score']); ?>"
                                                data-question-order="<?php echo htmlspecialchars($question['question_order']); ?>"
                                                data-is-critical="<?php echo htmlspecialchars($question['is_critical']); ?>"
                                                data-options='<?php echo json_encode(array_values($question['options'])); ?>'
                                                title="<?php echo get_text('edit_question_tooltip'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-question-btn mb-2"
                                                data-id="<?php echo $question['question_id']; ?>"
                                                data-section-id="<?php echo $section['section_id']; ?>"
                                                title="<?php echo get_text('delete_question_tooltip'); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted"><?php echo get_text('no_questions_in_section'); ?></p>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-outline-primary-custom add-question-btn" data-section-id="<?php echo $section['section_id']; ?>">
                            <i class="fas fa-plus me-2"></i> <?php echo get_text('add_new_question_button'); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_sections_for_test'); ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addSectionModalLabel"><i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_modal_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sectionForm" action="/INTEQC_GLOBAL_ASSESMENT/process/section-actions" method="POST">
                <div class="modal-body">
                    <?php echo generate_csrf_token(); ?>
                    <input type="hidden" name="action" id="sectionAction" value="add">
                    <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test_id); ?>">
                    <input type="hidden" name="section_id" id="sectionId">
                    <div class="mb-3">
                        <label for="sectionName" class="form-label"><?php echo get_text('label_section_name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sectionName" name="section_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="sectionDescription" class="form-label"><?php echo get_text('label_section_description'); ?></label>
                        <textarea class="form-control" id="sectionDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="sectionDuration" class="form-label"><?php echo get_text('label_section_duration'); ?> <span class="text-muted">(<?php echo get_text('duration_unlimited_hint'); ?>)</span></label>
                        <input type="number" class="form-control" id="sectionDuration" name="duration_minutes" value="0" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="sectionOrder" class="form-label"><?php echo get_text('label_order'); ?></label>
                        <input type="number" class="form-control" id="sectionOrder" name="section_order" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                    <button type="submit" class="btn btn-primary-custom"><?php echo get_text('save_button'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addQuestionModalLabel"><i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_edit_question_modal_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="questionForm" action="/INTEQC_GLOBAL_ASSESMENT/process/question-actions" method="POST">
                <div class="modal-body">
                    <?php echo generate_csrf_token(); ?>
                    <input type="hidden" name="action" id="questionAction" value="add">
                    <input type="hidden" name="section_id" id="questionSectionId">
                    <input type="hidden" name="question_id" id="questionId">
                    <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test_id); ?>">
                    <div class="mb-3">
                        <label for="questionText" class="form-label"><?php echo get_text('label_question_text'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="questionText" name="question_text" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="questionType" class="form-label"><?php echo get_text('label_question_type'); ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="questionType" name="question_type" required>
                            <option value=""><?php echo get_text('select_type_placeholder'); ?></option>
                            <option value="multiple_choice"><?php echo get_text('question_type_multiple_choice'); ?></option>
                            <option value="true_false"><?php echo get_text('question_type_true_false'); ?></option>
                            <option value="short_answer"><?php echo get_text('question_type_short_answer'); ?></option>
                            <option value="accept"><?php echo get_text('question_type_accept'); ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="questionScore" class="form-label"><?php echo get_text('label_score'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="questionScore" name="score" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="questionOrder" class="form-label"><?php echo get_text('label_question_order'); ?></label>
                        <input type="number" class="form-control" id="questionOrder" name="question_order" value="1" min="1" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="isCritical" name="is_critical">
                        <label class="form-check-label" for="isCritical">
                            <?php echo get_text('label_is_critical_question'); ?>
                            <i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo get_text('is_critical_question_tooltip'); ?>"></i>
                        </label>
                    </div>

                    <div id="optionsContainer" style="display: none;">
                        <h6><?php echo get_text('answer_options'); ?>: <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn"><i class="fas fa-plus-circle"></i></button></h6>
                        <div id="optionsList">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                    <button type="submit" class="btn btn-primary-custom"><?php echo get_text('save_button'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel"><?php echo get_text('confirm_deletion_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo get_text('confirm_deletion_message'); ?> <strong id="deleteTargetName"></strong> <?php echo get_text('confirm_deletion_message_part2'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><?php echo get_text('delete_button'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Section Modals & Actions ---
        const addSectionModal = new bootstrap.Modal(document.getElementById('addSectionModal'));
        const sectionForm = document.getElementById('sectionForm');
        const sectionAction = document.getElementById('sectionAction');
        const sectionId = document.getElementById('sectionId');
        const sectionNameInput = document.getElementById('sectionName');
        const sectionDescriptionInput = document.getElementById('sectionDescription');
        const sectionDurationInput = document.getElementById('sectionDuration');
        const sectionOrderInput = document.getElementById('sectionOrder');

        // Reset form when modal is hidden
        addSectionModal._element.addEventListener('hidden.bs.modal', function() {
            sectionForm.reset();
            sectionAction.value = 'add';
            sectionId.value = ''; // Clear for add mode
            sectionId.removeAttribute('name'); // Don't send section_id for add
            document.getElementById('addSectionModalLabel').innerHTML = '<i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_modal_title'); ?>';
            sectionOrderInput.value = <?php echo count($sections) + 1; ?>; // Reset order to next available
            sectionDurationInput.value = 0;
        });

        // Edit Section Button Click
        document.querySelectorAll('.edit-section-btn').forEach(button => {
            button.addEventListener('click', function() {
                sectionAction.value = 'edit';
                sectionId.value = this.dataset.id;
                sectionNameInput.value = this.dataset.name;
                sectionDescriptionInput.value = this.dataset.description;
                sectionOrderInput.value = this.dataset.order;
                sectionDurationInput.value = this.dataset.duration;
                document.getElementById('addSectionModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i> <?php echo get_text('edit_section_modal_title'); ?>';
                addSectionModal.show();
            });
        });

        // --- Section Form Submission (ADD/EDIT Section) ---
        sectionForm.addEventListener('submit', function(event) {
            event.preventDefault(); // ป้องกันการส่งฟอร์มแบบปกติ (full page reload)

            const formData = new FormData(this);

            fetch('/INTEQC_GLOBAL_ASSESMENT/process/section-actions', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('<?php echo get_text('error_prefix'); ?> ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php echo get_text('error_section_operation'); ?>');
                });
        });

        // Delete Section Button Click
        let deleteTargetId = null;
        let deleteTargetType = null;
        let deleteTargetSectionId = null; // เพิ่มตัวแปรสำหรับเก็บ section_id
        const deleteTargetNameSpan = document.getElementById('deleteTargetName');
        const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

        document.querySelectorAll('.delete-section-btn').forEach(button => {
            button.addEventListener('click', function() {
                deleteTargetId = this.dataset.id;
                deleteTargetType = 'section';
                const sectionCardHeader = this.closest('.card-header');
                const sectionTitle = sectionCardHeader ? sectionCardHeader.querySelector('h5 button').textContent.trim().split('. ')[1] : '<?php echo get_text('this_item'); ?>';
                deleteTargetNameSpan.textContent = "<?php echo get_text('section_prefix'); ?> '" + sectionTitle + "'";
                deleteConfirmModal.show();
            });
        });

        // --- Question Modals & Actions ---
        const addQuestionModal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
        const questionForm = document.getElementById('questionForm');
        const questionAction = document.getElementById('questionAction');
        const questionSectionId = document.getElementById('questionSectionId');
        const questionId = document.getElementById('questionId');
        const questionTextInput = document.getElementById('questionText');
        const questionTypeSelect = document.getElementById('questionType');
        const questionScoreInput = document.getElementById('questionScore');
        const questionOrderInput = document.getElementById('questionOrder');
        const isCriticalCheckbox = document.getElementById('isCritical');
        const optionsContainer = document.getElementById('optionsContainer');
        const optionsList = document.getElementById('optionsList');
        const addOptionBtn = document.getElementById('addOptionBtn');

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Reset form when question modal is hidden
        addQuestionModal._element.addEventListener('hidden.bs.modal', function() {
            questionForm.reset();
            questionAction.value = 'add';
            questionId.value = '';
            questionSectionId.value = '';
            questionScoreInput.value = 1;
            questionScoreInput.disabled = false;
            questionOrderInput.value = 1;
            isCriticalCheckbox.checked = false;
            optionsContainer.style.display = 'none';
            optionsList.innerHTML = ''; // Clear options
            document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_edit_question_modal_title'); ?>';
        });

        // Handle Question Type Change
        questionTypeSelect.addEventListener('change', function() {
            optionsList.innerHTML = ''; // Clear existing options
            optionsContainer.style.display = 'none';

            if (this.value === 'accept') {
                questionScoreInput.value = 0;
                questionScoreInput.disabled = true;
            } else {
                questionScoreInput.value = 1;
                questionScoreInput.disabled = false;
            }

            if (this.value === 'multiple_choice') {
                optionsContainer.style.display = 'block';
                addOptionField(false, '', null);
                addOptionField(false, '', null);
            } else if (this.value === 'true_false') {
                optionsContainer.style.display = 'block';
                addOptionField(true, '<?php echo get_text('true_option'); ?>', null);
                addOptionField(false, '<?php echo get_text('false_option'); ?>', null);
            }
        });

        // Add Option Button
        addOptionBtn.addEventListener('click', function() {
            addOptionField(false, '', null);
        });

        // Function to add an option field
        function addOptionField(isCorrect = false, optionText = '', optionId = null) {
            const div = document.createElement('div');
            div.classList.add('input-group', 'mb-2', 'option-item');
            div.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0 is-correct-option" type="radio" name="is_correct_option" value="${optionsList.children.length}" ${isCorrect ? 'checked' : ''} aria-label="Radio button for following text input">
            </div>
            <input type="text" class="form-control option-text" name="options[${optionsList.children.length}][text]" placeholder="<?php echo get_text('option_placeholder'); ?>" value="${htmlspecialchars(optionText)}" required>
            <input type="hidden" name="options[${optionsList.children.length}][option_id]" value="${optionId !== null ? optionId : ''}">
            <button class="btn btn-outline-danger remove-option-btn" type="button"><i class="fas fa-times"></i></button>
        `;
            optionsList.appendChild(div);

            // Add event listener for removing option
            div.querySelector('.remove-option-btn').addEventListener('click', function() {
                div.remove();
                // Re-index radio buttons and input names
                optionsList.querySelectorAll('.option-item').forEach((item, index) => {
                    item.querySelector('.is-correct-option').value = index;
                    item.querySelector('.option-text').name = `options[${index}][text]`;
                    item.querySelector('input[type="hidden"][name^="options["][name$="[option_id]"]').name = `options[${index}][option_id]`;
                });
            });
            // Set focus to the new option text input
            div.querySelector('.option-text').focus();
        }

        // Add Question Button Click
        document.querySelectorAll('.add-question-btn').forEach(button => {
            button.addEventListener('click', function() {
                questionSectionId.value = this.dataset.sectionId;
                questionAction.value = 'add';
                document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_new_question_modal_title'); ?>';

                const currentSectionId = this.dataset.sectionId;
                let maxQuestionOrder = 0;

                const currentSectionCard = document.getElementById(`collapse${currentSectionId}`);
                if (currentSectionCard) {
                    const questionItems = currentSectionCard.querySelectorAll('.list-group-item');
                    questionItems.forEach(item => {
                        const editButton = item.querySelector('.edit-question-btn');
                        if (editButton && editButton.dataset.questionOrder) {
                            const order = parseInt(editButton.dataset.questionOrder);
                            if (!isNaN(order) && order > maxQuestionOrder) {
                                maxQuestionOrder = order;
                            }
                        }
                    });
                }
                questionOrderInput.value = maxQuestionOrder + 1;
                questionTypeSelect.value = 'multiple_choice'; // Reset to a default value
                questionScoreInput.value = 1; // Reset score to default
                questionScoreInput.disabled = false; // Ensure it's enabled for default type
                optionsContainer.style.display = 'block'; // Show options for default type
                optionsList.innerHTML = '';
                addOptionField(false, '', null);
                addOptionField(false, '', null);
                addQuestionModal.show();
            });
        });

        // Edit Question Button Click
        document.querySelectorAll('.edit-question-btn').forEach(button => {
            button.addEventListener('click', function() {
                questionAction.value = 'edit';
                questionId.value = this.dataset.id;
                questionSectionId.value = this.dataset.sectionId;
                questionTextInput.value = this.dataset.questionText;
                const questionType = this.dataset.questionType;
                questionTypeSelect.value = questionType;

                if (questionType === 'accept') {
                    questionScoreInput.value = 0;
                    questionScoreInput.disabled = true;
                } else {
                    questionScoreInput.value = this.dataset.score;
                    questionScoreInput.disabled = false;
                }

                questionOrderInput.value = this.dataset.questionOrder;
                isCriticalCheckbox.checked = (this.dataset.isCritical == '1');

                optionsList.innerHTML = ''; // Clear existing options
                optionsContainer.style.display = 'none';

                if (questionType === 'multiple_choice' || questionType === 'true_false') {
                    optionsContainer.style.display = 'block';
                    let correctOptionIndex = -1;
                    const optionsData = JSON.parse(this.dataset.options);
                    optionsData.forEach((option, index) => {
                        addOptionField(option.is_correct == 1, option.option_text, option.option_id);
                        if (option.is_correct == 1) {
                            correctOptionIndex = index;
                        }
                    });

                    if (correctOptionIndex !== -1) {
                        const correctRadio = optionsList.querySelector(`.is-correct-option[value="${correctOptionIndex}"]`);
                        if (correctRadio) {
                            correctRadio.checked = true;
                        }
                    }
                }

                document.getElementById('addQuestionModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i> <?php echo get_text('edit_question_modal_title'); ?>';
                addQuestionModal.show();
            });
        });

        // --- Question Form Submission (ADD/EDIT Question) ---
        questionForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('is_critical', isCriticalCheckbox.checked ? 1 : 0);

            const options = [];
            optionsList.querySelectorAll('.option-item').forEach((item, index) => {
                const optionId = item.querySelector('input[type="hidden"][name^="options["][name$="[option_id]"]').value;
                const optionText = item.querySelector('.option-text').value;
                const isCorrect = item.querySelector('.is-correct-option').checked ? 1 : 0;
                options.push({
                    option_id: optionId,
                    text: optionText,
                    is_correct: isCorrect
                });
            });

            formData.delete('option_text[]');

            options.forEach((option, index) => {
                formData.append(`options[${index}][option_id]`, option.option_id);
                formData.append(`options[${index}][text]`, option.text);
            });

            fetch('/INTEQC_GLOBAL_ASSESMENT/process/question-actions', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addQuestionModal.hide();
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('<?php echo get_text('error_prefix'); ?> ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php echo get_text('error_question_operation'); ?>');
                });
        });

        // Delete Question Button Click
        document.querySelectorAll('.delete-question-btn').forEach(button => {
            button.addEventListener('click', function() {
                deleteTargetId = this.dataset.id;
                deleteTargetType = 'question';
                deleteTargetSectionId = this.dataset.sectionId; // ดึงค่า section_id จากปุ่มที่ถูกคลิก
                const questionText = this.closest('.list-group-item').querySelector('p strong').textContent.trim();
                deleteTargetNameSpan.textContent = "<?php echo get_text('question_prefix'); ?> '" + questionText + "'";
                deleteConfirmModal.show();
            });
        });

        // Confirm Delete Button in Modal
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!deleteTargetId || !deleteTargetType) return;

            let actionUrl = '';
            let postBody = '';

            // Get CSRF token from the page
            const csrfToken = document.querySelector('input[name="_csrf_token"]').value;
            
            if (deleteTargetType === 'section') {
                actionUrl = '/INTEQC_GLOBAL_ASSESMENT/process/section-actions';
                postBody = '_csrf_token=' + encodeURIComponent(csrfToken) + '&action=delete&section_id=' + deleteTargetId + '&test_id=<?php echo $test_id; ?>';
            } else if (deleteTargetType === 'question') {
                actionUrl = '/INTEQC_GLOBAL_ASSESMENT/process/question-actions';
                postBody = '_csrf_token=' + encodeURIComponent(csrfToken) + '&action=delete&question_id=' + deleteTargetId + '&section_id=' + deleteTargetSectionId + '&test_id=<?php echo $test_id; ?>';
            }

            fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: postBody
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('<?php echo get_text('error_prefix'); ?> ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php echo get_text('error_deletion_operation'); ?>');
                })
                .finally(() => {
                    deleteConfirmModal.hide();
                    deleteTargetId = null;
                    deleteTargetType = null;
                });
        });

        // Helper for HTML escaping in JS
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }
    });
</script>
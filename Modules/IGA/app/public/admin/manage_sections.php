<?php
require_once __DIR__ . '/../../includes/header.php';
// กำหนดชื่อหน้าโดยใช้ get_text() ก่อน require header.php
$page_title = get_text('page_title_manage_sections');

// ตรวจสอบการล็อกอินและบทบาท
require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) { // ตรวจสอบบทบาท
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: /login");
    exit();
}

// Store test_id in session when first accessing the page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_id'])) {
    $_SESSION['current_test_id'] = (int)$_POST['test_id'];
}

// Get test_id from POST or session
$test_id = $_POST['test_id'] ?? $_SESSION['current_test_id'] ?? null;
$test_name = '';
$sections = [];
$total_test_max_score = 0;

if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (string)($_SESSION['user_id'] ?? '');
    $uid = $conn->real_escape_string($current_user_id);
    $conn->query("SET @user_id = '{$uid}'");
} else {
    $conn->query("SET @user_id = NULL");
}

// ตรวจสอบ test_id
if (!is_numeric($test_id) || $test_id <= 0) {
    set_alert(get_text('alert_invalid_test_id_general'), "danger");
    header("Location: manage_iga_tests.php");
    exit();
}

try {
    // ดึงข้อมูลแบบทดสอบ
    $stmt = $conn->prepare("SELECT test_name, category_type_id FROM iga_tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $test_data = $result->fetch_assoc();
        $test_name = $test_data['test_name'];
        $has_category_type = !empty($test_data['category_type_id']);
    } else {
        set_alert(get_text('alert_test_not_found'), "danger");
        header("Location: manage_iga_tests.php");
        exit();
    }
    $stmt->close();

    // ดึงส่วน/คำถาม/ตัวเลือก
    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description, s.section_order, s.duration_minutes,
            q.question_id, q.question_text, q.question_type, q.score, q.question_order, q.is_critical,
            q.category_id, c.category_name,
            o.option_id, o.option_text, o.is_correct
        FROM iga_sections s
        LEFT JOIN iga_questions q ON s.section_id = q.section_id
        LEFT JOIN iga_question_categories c ON q.category_id = c.category_id
        LEFT JOIN iga_question_options o ON q.question_id = o.question_id
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
                'score' => (int)$row['score'],
                'question_order' => $row['question_order'],
                'is_critical' => $row['is_critical'],
                'category_id' => $row['category_id'] ?? null,
                'category_name' => $row['category_name'] ?? null,
                'options' => []
            ];
            $sections[$section_id]['max_score'] += (int)$row['score'];
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

    foreach ($sections as $section) {
        $total_test_max_score += (int)$section['max_score'];
    }
} catch (Exception $e) {
    set_alert(get_text('error_fetch_sections_questions', [$e->getMessage()]), "danger");
}
?>

<h1 class="mb-4 text-primary-custom">
    <?php echo get_text('manage_sections_questions_title'); ?>: <br>"<?php echo htmlspecialchars($test_name); ?>"
</h1>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_button'); ?>
        </button>
        <?php if ($has_category_type): ?>
            <a href="/admin/manage_categories.php" class="btn btn-outline-primary-custom" target="_blank">
                <i class="fas fa-cog me-2"></i> <?php echo get_text('manage_categories'); ?>
            </a>
        <?php endif; ?>
    </div>
    <a href="/admin/tests" class="btn btn-secondary">
        <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_to_manage_tests_button'); ?>
    </a>
</div>

<?php echo get_alert(); ?>

<?php if (!empty($sections)): ?>
    <div class="alert alert-info text-center mb-4 shadow-sm">
        <h4 class="alert-heading mb-0">
            <?php echo get_text('total_test_max_score'); ?>:
            <?php echo (int)$total_test_max_score; ?> <?php echo get_text('points_unit'); ?>
        </h4>
    </div>
    <div id="accordionSections">
        <?php foreach ($sections as $section): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center" id="heading<?php echo $section['section_id']; ?>">
                    <h5 class="mb-0">
                        <button class="btn btn-link text-white text-decoration-none d-flex align-items-center"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse<?php echo $section['section_id']; ?>"
                            aria-expanded="true"
                            aria-controls="collapse<?php echo $section['section_id']; ?>">
                            <i class="fas fa-chevron-down me-2"></i>
                            <?php echo htmlspecialchars($section['section_order'] . ". " . $section['section_name']); ?>
                            <?php if ($section['duration_minutes'] > 0): ?>
                                <span class="badge bg-light text-dark ms-3">
                                    <i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($section['duration_minutes']); ?> <?php echo get_text('minutes_unit'); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark ms-3"><?php echo get_text('unlimited_time'); ?></span>
                            <?php endif; ?>
                            <span class="badge bg-success text-white ms-3">
                                <i class="fas fa-star me-1"></i> <?php echo get_text('section_max_score'); ?>:
                                <?php echo (int)$section['max_score']; ?> <?php echo get_text('points_unit'); ?>
                            </span>
                        </button>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-info text-white edit-section-btn mb-2"
                            data-id="<?php echo $section['section_id']; ?>"
                            data-name="<?php echo htmlspecialchars($section['section_name']); ?>"
                            data-description="<?php echo htmlspecialchars($section['description']); ?>"
                            data-order="<?php echo htmlspecialchars($section['section_order']); ?>"
                            data-duration="<?php echo htmlspecialchars($section['duration_minutes']); ?>"
                            title="<?php echo get_text('edit_section_tooltip'); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-section-btn mb-2"
                            data-id="<?php echo $section['section_id']; ?>"
                            title="<?php echo get_text('delete_section_tooltip'); ?>">
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
                                                <?php echo get_text('label_type'); ?>:
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', get_text('question_type_' . $question['question_type'])))); ?> |
                                                <?php echo get_text('label_score'); ?>: <?php echo htmlspecialchars($question['score']); ?>
                                                <?php if (!empty($question['category_name'])): ?>
                                                    | <?php echo get_text('question_category'); ?>: <?php echo htmlspecialchars($question['category_name']); ?>
                                                <?php endif; ?>
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
                                                data-category-id="<?php echo !empty($question['category_id']) ? htmlspecialchars($question['category_id']) : ''; ?>"
                                                data-options='<?php echo htmlspecialchars(json_encode(array_values($question['options'])), ENT_QUOTES, 'UTF-8'); ?>'
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

<!-- Modal: Add/Edit Section -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addSectionModalLabel"><i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_modal_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- ใช้ absolute path -->
            <form id="sectionForm" action="/process/section-actions" method="POST">
                <div class="modal-body">
                    <?php echo generate_csrf_token(); ?>
                    <input type="hidden" name="action" id="sectionAction" value="add">
                    <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test_id); ?>">
                    <!-- ❗ ไม่มี name โดยตั้งใจ: add mode จะไม่ส่ง section_id -->
                    <input type="hidden" id="sectionId">
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

<!-- Modal: Add/Edit Question (เหมือนเดิม) -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="addQuestionModalLabel"><i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_edit_question_modal_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="questionForm" action="/process/question-actions" method="POST">
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
                    <?php if ($has_category_type): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="questionCategory" class="form-label mb-0"><?php echo get_text('question_category'); ?></label>
                            </div>
                            <select class="form-select" id="questionCategory" name="category_id">
                                <option value=""><?php echo get_text('not_selected'); ?></option>
                                <?php
                                $type_query = "SELECT category_type_id FROM iga_tests WHERE test_id = ?";
                                $type_stmt = $conn->prepare($type_query);
                                $type_stmt->bind_param("i", $test_id);
                                $type_stmt->execute();
                                $type_result = $type_stmt->get_result();
                                $type_data = $type_result->fetch_assoc();
                                $category_type_id = $type_data ? $type_data['category_type_id'] : null;
                                $type_stmt->close();

                                $cat_query = "SELECT c.category_id, c.category_name, c.category_type_id 
                                          FROM iga_question_categories c
                                          WHERE c.category_type_id = ? OR c.category_type_id IS NULL
                                          ORDER BY c.category_name";
                                $cat_stmt = $conn->prepare($cat_query);
                                if ($cat_stmt) {
                                    $cat_stmt->bind_param("i", $category_type_id);
                                    $cat_stmt->execute();
                                    $cat_result = $cat_stmt->get_result();
                                    $has_categories = false;
                                    while ($cat = $cat_result->fetch_assoc()) {
                                        $has_categories = true;
                                        echo '<option value="' . htmlspecialchars($cat['category_id']) . '">' .
                                            htmlspecialchars($cat['category_name']) . '</option>';
                                    }
                                    if (!$has_categories) {
                                        echo '<option value="" disabled>' . get_text('no_categories_found') . '</option>';
                                    }
                                    $cat_stmt->close();
                                } else {
                                    echo '<option value="" disabled>Error loading categories</option>';
                                }
                                ?>
                            </select>
                            <div class="form-text"><?php echo get_text('select_category_hint'); ?></div>
                        </div>
                    <?php endif; ?>
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
                        <input type="number" class="form-control" id="questionScore" name="score" value="1" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="questionOrder" class="form-label"><?php echo get_text('label_question_order'); ?></label>
                        <input type="number" class="form-control" id="questionOrder" name="question_order" value="1" min="1" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="isCritical" name="is_critical">
                        <label class="form-check-label" for="isCritical">
                            <?php echo get_text('label_is_critical_question'); ?>
                            <i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo get_text('is_critical_question_tooltip'); ?>_"></i>
                        </label>
                    </div>

                    <div id="optionsContainer" style="display: none;">
                        <h6><?php echo get_text('answer_options'); ?>:
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn"><i class="fas fa-plus-circle"></i></button>
                        </h6>
                        <div id="optionsList"></div>
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

<!-- Modal ลบ (เหมือนเดิม) -->
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

        // Reset form when modal is hidden (กลับโหมด add)
        addSectionModal._element.addEventListener('hidden.bs.modal', function() {
            sectionForm.reset();
            sectionAction.value = 'add';
            sectionId.value = '';
            sectionId.removeAttribute('name'); // โหมด add: ไม่ส่ง section_id
            document.getElementById('addSectionModalLabel').innerHTML =
                '<i class="fas fa-folder-plus me-2"></i> <?php echo get_text('add_new_section_modal_title'); ?>';
            sectionOrderInput.value = <?php echo count($sections) + 1; ?>;
            sectionDurationInput.value = 0;
        });

        // Edit Section Button Click (เข้าโหมด edit)
        document.querySelectorAll('.edit-section-btn').forEach(button => {
            button.addEventListener('click', function() {
                sectionAction.value = 'edit';
                sectionId.value = this.dataset.id;
                sectionId.setAttribute('name', 'section_id'); // ★ สำคัญ: ส่ง section_id ตอนแก้ไข

                sectionNameInput.value = this.dataset.name;
                sectionDescriptionInput.value = this.dataset.description;
                sectionOrderInput.value = this.dataset.order;
                sectionDurationInput.value = this.dataset.duration;

                document.getElementById('addSectionModalLabel').innerHTML =
                    '<i class="fas fa-edit me-2"></i> <?php echo get_text('edit_section_modal_title'); ?>';
                addSectionModal.show();
            });
        });

        // --- Section Form Submission (ADD/EDIT Section) ---
        sectionForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('/process/section-actions', {
                    method: 'POST',
                    body: formData
                })
                .then(async (res) => {
                    const text = await res.text();
                    if (!res.ok) throw new Error(text || (res.status + ' ' + res.statusText));
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // กันกรณีผิดพลาดเป็น HTML (เช่น 404 กลับหน้า HTML)
                        throw new Error('Invalid JSON: ' + text.slice(0, 300));
                    }
                })
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

        // ====== ด้านล่าง: ส่วน Question เหมือนเดิมของคุณ (ตัดมาจากไฟล์เดิม) ======

        const addQuestionModalEl = document.getElementById('addQuestionModal');
        const addQuestionModal = new bootstrap.Modal(addQuestionModalEl, {
            focus: true,
            keyboard: true,
            backdrop: 'static'
        });

        // Use MutationObserver to handle Bootstrap's aria-hidden changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'aria-hidden') {
                    if (mutation.target.getAttribute('aria-hidden') === 'true') {
                        mutation.target.inert = true;
                        mutation.target.removeAttribute('aria-hidden');
                    } else {
                        mutation.target.inert = false;
                    }
                }
            });
        });

        // Start observing the modal element
        observer.observe(addQuestionModalEl, {
            attributes: true,
            attributeFilter: ['aria-hidden', 'class']
        });

        // Handle modal show/hide for body scroll and backdrop
        addQuestionModalEl.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '15px'; // Prevent content shift when scrollbar disappears
        });

        // Handle modal close button
        const closeButtons = addQuestionModalEl.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                addQuestionModal.hide();
            });
        });
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

        // tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el);
        });

        addQuestionModalEl.addEventListener('show.bs.modal', function() {
            this.inert = false;
            // Focus the first focusable element when modal is shown
            const focusable = this.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusable) focusable.focus();
        });

        addQuestionModalEl.addEventListener('hidden.bs.modal', function() {
            // Remove inert attribute when modal is hidden
            this.inert = false;
            // Hide the modal backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
            // Re-enable body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            // Return focus to the button that triggered the modal
            const triggerButton = document.activeElement.closest('[data-bs-toggle="modal"]');
            if (triggerButton) triggerButton.focus();
            questionForm.reset();
            questionAction.value = 'add';
            questionId.value = '';
            questionSectionId.value = '';
            questionScoreInput.value = 1;
            questionScoreInput.disabled = false;
            questionOrderInput.value = 1;
            isCriticalCheckbox.checked = false;
            optionsContainer.style.display = 'none';
            optionsList.innerHTML = '';
            document.getElementById('addQuestionModalLabel').innerHTML =
                '<i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_edit_question_modal_title'); ?>';
        });

        questionTypeSelect.addEventListener('change', function() {
            optionsList.innerHTML = '';
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

        addOptionBtn.addEventListener('click', function() {
            addOptionField(false, '', null);
        });

        function addOptionField(isCorrect = false, optionText = '', optionId = null) {
            const div = document.createElement('div');
            div.classList.add('input-group', 'mb-2', 'option-item');
            div.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0 is-correct-option" type="radio" name="is_correct_option" value="${optionsList.children.length}" ${isCorrect ? 'checked' : ''} aria-label="Radio button">
            </div>
            <input type="text" class="form-control option-text" name="options[${optionsList.children.length}][text]" placeholder="<?php echo get_text('option_placeholder'); ?>" value="${htmlspecialchars(optionText)}" required>
            <input type="hidden" name="options[${optionsList.children.length}][option_id]" value="${optionId !== null ? optionId : ''}">
            <button class="btn btn-outline-danger remove-option-btn" type="button"><i class="fas fa-times"></i></button>
        `;
            optionsList.appendChild(div);

            div.querySelector('.remove-option-btn').addEventListener('click', function() {
                div.remove();
                optionsList.querySelectorAll('.option-item').forEach((item, index) => {
                    item.querySelector('.is-correct-option').value = index;
                    item.querySelector('.option-text').name = `options[${index}][text]`;
                    item.querySelector('input[type="hidden"][name^="options["][name$="[option_id]"]').name = `options[${index}][option_id]`;
                });
            });
            div.querySelector('.option-text').focus();
        }

        function addDefaultOptions(questionType) {
            if (questionType === 'true_false') {
                addOptionField(true, '<?php echo get_text('true_option'); ?>', null);
                addOptionField(false, '<?php echo get_text('false_option'); ?>', null);
            } else {
                addOptionField(false, '', null);
                addOptionField(false, '', null);
            }
        }

        function resetQuestionForm() {
            document.getElementById('questionForm').reset();
            questionId.value = '';
            questionTextInput.value = '';
            questionTypeSelect.value = 'multiple_choice';
            questionScoreInput.value = '1';
            questionScoreInput.disabled = false;
            questionOrderInput.value = '';
            isCriticalCheckbox.checked = false;
            optionsList.innerHTML = '';
            optionsContainer.style.display = 'block';
            addOptionField(false, '', null);
            addOptionField(false, '', null);
        }

        document.querySelectorAll('.add-question-btn').forEach(button => {
            button.addEventListener('click', function() {
                resetQuestionForm();
                questionAction.value = 'add';
                questionSectionId.value = this.dataset.sectionId;
                // Ensure the modal is interactive
                const modal = document.getElementById('addQuestionModal');
                modal.inert = false;
                document.getElementById('addQuestionModalLabel').innerHTML =
                    '<i class="fas fa-plus-circle me-2"></i> <?php echo get_text('add_new_question_modal_title'); ?>';

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
                addQuestionModal.show();
                // Focus the first focusable element after showing
                const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) firstFocusable.focus();
            });
        });

        document.addEventListener('click', function(event) {
            const editButton = event.target.closest('.edit-question-btn');
            if (!editButton) return;

            resetQuestionForm();

            questionAction.value = 'edit';
            const qId = editButton.getAttribute('data-id');
            const sectionId = editButton.getAttribute('data-section-id');
            const qText = editButton.getAttribute('data-question-text');
            const qType = editButton.getAttribute('data-question-type');
            const qScore = editButton.getAttribute('data-score');
            const qOrder = editButton.getAttribute('data-question-order');
            const isCritical = editButton.getAttribute('data-is-critical') === '1';
            const categoryId = editButton.getAttribute('data-category-id');

            questionId.value = qId;
            questionSectionId.value = sectionId;
            questionTextInput.value = qText;
            questionTypeSelect.value = qType;
            questionScoreInput.value = qType === 'accept' ? '0' : (qScore || '1');
            questionScoreInput.disabled = (qType === 'accept');
            questionOrderInput.value = qOrder;
            isCriticalCheckbox.checked = isCritical;

            if (categoryId) {
                const categorySelect = document.getElementById('questionCategory');
                if (categorySelect) categorySelect.value = categoryId;
            }

            optionsList.innerHTML = '';

            if (qType === 'multiple_choice' || qType === 'true_false') {
                optionsContainer.style.display = 'block';
                let correctOptionIndex = -1;

                const optionsJson = editButton.getAttribute('data-options');
                if (optionsJson && optionsJson !== '[]') {
                    try {
                        const optionsData = JSON.parse(optionsJson);
                        if (Array.isArray(optionsData) && optionsData.length > 0) {
                            optionsData.forEach((option, index) => {
                                if (option) {
                                    const optionText = option.option_text || option.text || '';
                                    const optionId = option.option_id || null;
                                    const isCorrect = option.is_correct == 1 || option.is_correct === true;
                                    addOptionField(isCorrect, optionText, optionId);
                                    if (isCorrect) correctOptionIndex = index;
                                }
                            });
                            setTimeout(() => {
                                if (correctOptionIndex !== -1) {
                                    const correctRadio = optionsList.querySelector(`.is-correct-option[value="${correctOptionIndex}"]`);
                                    if (correctRadio) correctRadio.checked = true;
                                }
                            }, 50);
                        } else {
                            addDefaultOptions(qType);
                        }
                    } catch (e) {
                        console.error('Error parsing question options:', e);
                        addDefaultOptions(qType);
                    }
                } else {
                    addDefaultOptions(qType);
                }
            }

            document.getElementById('addQuestionModalLabel').innerHTML =
                '<i class="fas fa-edit me-2"></i> <?php echo get_text('edit_question_modal_title'); ?>';
            addQuestionModal.show();
        });

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

            fetch('/process/question-actions', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
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

        // Delete Section / Question
        let deleteTargetId = null;
        let deleteTargetType = null;
        let deleteTargetSectionId = null;
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

        document.querySelectorAll('.delete-question-btn').forEach(button => {
            button.addEventListener('click', function() {
                deleteTargetId = this.dataset.id;
                deleteTargetType = 'question';
                deleteTargetSectionId = this.dataset.sectionId;
                const questionText = this.closest('.list-group-item').querySelector('p strong').textContent.trim();
                deleteTargetNameSpan.textContent = "<?php echo get_text('question_prefix'); ?> '" + questionText + "'";
                deleteConfirmModal.show();
            });
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!deleteTargetId || !deleteTargetType) return;

            let actionUrl = '';
            let postBody = '';
            const csrfToken = document.querySelector('input[name="_csrf_token"]').value;

            if (deleteTargetType === 'section') {
                actionUrl = '/process/section-actions';
                postBody = '_csrf_token=' + encodeURIComponent(csrfToken) + '&action=delete&section_id=' + deleteTargetId + '&test_id=<?php echo $test_id; ?>';
            } else if (deleteTargetType === 'question') {
                actionUrl = '/process/question-actions';
                postBody = '_csrf_token=' + encodeURIComponent(csrfToken) + '&action=delete&question_id=' + deleteTargetId + '&section_id=' + deleteTargetSectionId + '&test_id=<?php echo $test_id; ?>';
            }

            fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: postBody
                })
                .then(res => res.json())
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

        // Helper
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
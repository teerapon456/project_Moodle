<?php
// manage_iga_email_templates.php  (Completed)
// - mysqli
// - CSRF
// - Clean HTML (quick sanitize)
// - Better language tabs spacing & visibility
// - Single Preview button (multi-use)
// - Exists check before update
// - Unsaved-changes warning

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php'; // provides $conn (mysqli)
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();
if (!has_role('admin')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    header("Location: /login");
    exit();
}

// ---- Language & translations ----
$lang = $_SESSION['lang'] ?? 'en';
$langFile = __DIR__ . "/../../languages/{$lang}.php";
if (!file_exists($langFile)) {
    $lang = 'en';
    $langFile = __DIR__ . "/../../languages/{$lang}.php";
}
$translations = [];
if (file_exists($langFile)) {
    $translations = require $langFile;
    if (!is_array($translations)) $translations = [];
}
function __t($key, $replacements = []) {
    global $translations;
    $text = $translations[$key] ?? $key;
    foreach ($replacements as $k => $v) $text = str_replace('{'.$k.'}', $v, $text);
    return $text;
}

// ---- DB sanity check ----
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection is not properly initialized.');
}
if (!$conn->query('SELECT 1')) {
    die('Database test query failed: '.$conn->error);
}

// ---- CSRF ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- Quick sanitize for email HTML (แนะนำใช้ HTMLPurifier ในโปรดักชัน) ----
function sanitize_email_html($html) {
    // remove script, iframe
    $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<iframe\b[^>]*>(.*?)</iframe>#is', '', $html);
    // remove on* handlers
    $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/si', '', $html);
    // remove javascript: in href/src
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:.*?\2/si', '', $html);
    return $html;
}

// ---- Load templates list ----
$templates = [];
$res = $conn->query("SELECT * FROM iga_email_templates ORDER BY template_name");
if ($res) {
    while ($row = $res->fetch_assoc()) $templates[] = $row;
    $res->free();
}

// ---- Determine selected template ----
$selected_template = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $tid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM iga_email_templates WHERE template_id = ?");
    $stmt->bind_param("i", $tid);
    if ($stmt->execute()) {
        $r = $stmt->get_result();
        $selected_template = $r->fetch_assoc();
        $r->free();
    }
    $stmt->close();
}
if (!$selected_template && !empty($templates)) {
    $selected_template = $templates[0];
}

// ---- Save handler ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        set_alert('Invalid CSRF token', 'danger');
        header('Location: '.$_SERVER['PHP_SELF'].(isset($_GET['edit'])?'?edit='.(int)$_GET['edit']:''));
        exit;
    }

    $template_id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
    $subject_en  = trim($_POST['subject_en'] ?? '');
    $subject_th  = trim($_POST['subject_th'] ?? '');
    $subject_my  = trim($_POST['subject_my'] ?? '');
    $body_en     = sanitize_email_html(trim($_POST['body_en'] ?? ''));
    $body_th     = sanitize_email_html(trim($_POST['body_th'] ?? ''));
    $body_my     = sanitize_email_html(trim($_POST['body_my'] ?? ''));

    if ($template_id) {

        // Ensure template exists
        $chk = $conn->prepare("SELECT 1 FROM iga_email_templates WHERE template_id=?");
        $chk->bind_param("i", $template_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $chk->close();
            set_alert(__t('invalid_template') ?: 'Invalid template', 'danger');
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        }
        $chk->close();

        $sql = "UPDATE iga_email_templates SET 
                    subject_en = ?, subject_th = ?, subject_my = ?,
                    body_en = ?, body_th = ?, body_my = ?,
                    updated_at = NOW()
                WHERE template_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssssi", $subject_en, $subject_th, $subject_my, $body_en, $body_th, $body_my, $template_id);
            if ($stmt->execute()) {
                set_alert(__t('template_updated_successfully') ?: 'Template updated successfully', 'success');
                header('Location: '.$_SERVER['PHP_SELF'].'?edit='.$template_id);
                exit;
            } else {
                set_alert((__t('error_updating_template') ?: 'Error updating template').': '.$conn->error, 'danger');
            }
            $stmt->close();
        } else {
            set_alert((__t('error_updating_template') ?: 'Error updating template').': '.$conn->error, 'danger');
        }
    } else {
        set_alert(__t('invalid_template') ?: 'Invalid template', 'danger');
    }
}

// ---- Header (ต้องมี bootstrap.bundle.js ใน header/footer) ----
include __DIR__ . '/../../includes/header.php';
?>
<div class="container-fluid py-3">
    <?php echo get_alert(); ?>

    <div class="row g-3">
        <!-- Sidebar list -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-at me-2"></i><?php echo __t('email_templates') ?: 'Email Templates'; ?>
                    </h5>
                </div>
                <div class="list-group list-group-flush" style="max-height: 72vh; overflow:auto;">
                    <?php if (empty($templates)): ?>
                        <div class="p-3 text-muted small"><?php echo __t('no_templates_found') ?: 'No templates found'; ?></div>
                    <?php else: ?>
                        <?php foreach ($templates as $row): ?>
                            <a href="?edit=<?php echo (int)$row['template_id']; ?>"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php
                                    echo ($selected_template && $selected_template['template_id'] == $row['template_id']) ? 'active' : ''; ?>">
                                <div class="me-2">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['template_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['template_key']); ?></small>
                                </div>
                                <i class="fas fa-chevron-right opacity-50"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Editor -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="mb-0">
                            <i class="fas fa-pen-nib me-2"></i>
                            <?php echo htmlspecialchars($selected_template['template_name'] ?? (__t('template') ?: 'Template')); ?>
                        </h5>
                        <?php if (!empty($selected_template['template_key'])): ?>
                            <span class="badge text-bg-light border">
                                <i class="fas fa-key me-1"></i><?php echo htmlspecialchars($selected_template['template_key']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="previewBtn" class="btn btn-outline-success">
                            <i class="fas fa-eye me-1"></i><?php echo __t('preview_template') ?: 'Preview'; ?>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($selected_template): ?>
                    <form method="post" id="emailTemplateForm" action="">
                        <input type="hidden" name="template_id" value="<?php echo (int)$selected_template['template_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <!-- Language tabs (spacing/visibility improved) -->
                        <ul class="nav nav-pills mb-4 gap-2 flex-wrap align-items-center" id="langTabs" role="tablist" aria-label="Template languages">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-4 py-2 rounded-pill" id="tab-en"
                                        data-bs-toggle="tab" data-bs-target="#pane-en" type="button" role="tab"
                                        aria-controls="pane-en" aria-selected="true">
                                    <i class="fas fa-language me-2"></i> English
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-4 py-2 rounded-pill" id="tab-th"
                                        data-bs-toggle="tab" data-bs-target="#pane-th" type="button" role="tab"
                                        aria-controls="pane-th" aria-selected="false">
                                    <i class="fas fa-language me-2"></i> ไทย
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-4 py-2 rounded-pill" id="tab-my"
                                        data-bs-toggle="tab" data-bs-target="#pane-my" type="button" role="tab"
                                        aria-controls="pane-my" aria-selected="false">
                                    <i class="fas fa-language me-2"></i> မြန်မာ
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- EN -->
                            <div class="tab-pane fade show active" id="pane-en" role="tabpanel" aria-labelledby="tab-en">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="subject_en" class="form-label fw-semibold mb-0">
                                            <?php echo __t('subject') ?: 'Subject'; ?> <span class="badge text-bg-primary ms-1">EN</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_en" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_en" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_en" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_en" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_en" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-lg" id="subject_en" name="subject_en"
                                           value="<?php echo htmlspecialchars($selected_template['subject_en'] ?? ''); ?>">
                                    <div class="form-text mt-1"><?php echo __t('subject_note') ?: 'Use variables to personalize subject'; ?></div>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">
                                            <?php echo __t('body') ?: 'Body'; ?> <span class="badge text-bg-primary ms-1">EN</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('special_variables') ?: 'Special Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{verification_link}"><?php echo __t('verification_link'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_en" data-var="{reset_link}"><?php echo __t('reset_link'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <textarea class="form-control ckeditor" id="body_en" name="body_en" rows="14"><?php
                                        echo htmlspecialchars($selected_template['body_en'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>

                            <!-- TH -->
                            <div class="tab-pane fade" id="pane-th" role="tabpanel" aria-labelledby="tab-th">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="subject_th" class="form-label fw-semibold mb-0">
                                            <?php echo __t('subject') ?: 'Subject'; ?> <span class="badge text-bg-primary ms-1">TH</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_th" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_th" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_th" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_th" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_th" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-lg" id="subject_th" name="subject_th"
                                           value="<?php echo htmlspecialchars($selected_template['subject_th'] ?? ''); ?>">
                                    <div class="form-text mt-1"><?php echo __t('subject_note') ?: 'Use variables to personalize subject'; ?></div>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">
                                            <?php echo __t('body') ?: 'Body'; ?> <span class="badge text-bg-primary ms-1">TH</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('special_variables') ?: 'Special Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{verification_link}"><?php echo __t('verification_link'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_th" data-var="{reset_link}"><?php echo __t('reset_link'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <textarea class="form-control ckeditor" id="body_th" name="body_th" rows="14"><?php
                                        echo htmlspecialchars($selected_template['body_th'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>

                            <!-- MY -->
                            <div class="tab-pane fade" id="pane-my" role="tabpanel" aria-labelledby="tab-my">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="subject_my" class="form-label fw-semibold mb-0">
                                            <?php echo __t('subject') ?: 'Subject'; ?> <span class="badge text-bg-primary ms-1">MY</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_my" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_my" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_my" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_my" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-input" href="#" data-target="subject_my" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-lg" id="subject_my" name="subject_my"
                                           value="<?php echo htmlspecialchars($selected_template['subject_my'] ?? ''); ?>">
                                    <div class="form-text mt-1"><?php echo __t('subject_note') ?: 'Use variables to personalize subject'; ?></div>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">
                                            <?php echo __t('body') ?: 'Body'; ?> <span class="badge text-bg-primary ms-1">MY</span>
                                        </label>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-plus me-1"></i><?php echo __t('insert_variable') ?: 'Insert variable'; ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li class="dropdown-header"><?php echo __t('user_variables') ?: 'User Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{full_name}"><?php echo __t('full_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{username}"><?php echo __t('username'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{email}"><?php echo __t('email'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('system_variables') ?: 'System Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{site_name}"><?php echo __t('site_name'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{current_date}"><?php echo __t('current_date'); ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li class="dropdown-header"><?php echo __t('special_variables') ?: 'Special Variables'; ?></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{verification_link}"><?php echo __t('verification_link'); ?></a></li>
                                                <li><a class="dropdown-item insert-var-editor" href="#" data-target="body_my" data-var="{reset_link}"><?php echo __t('reset_link'); ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <textarea class="form-control ckeditor" id="body_my" name="body_my" rows="14"><?php
                                        echo htmlspecialchars($selected_template['body_my'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom action bar -->
                        <div class="border-top pt-3 d-flex justify-content-end">
                            <button type="submit" name="save_template" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i><?php echo __t('save_changes') ?: 'Save changes'; ?>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <?php echo __t('no_templates_found') ?: 'No templates found'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="previewModalLabel"><i class="fas fa-envelope me-2"></i><?php echo __t('preview') ?: 'Preview'; ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="previewContent">
        <!-- injected -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __t('close') ?: 'Close'; ?></button>
      </div>
    </div>
  </div>
</div>

<!-- CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>

<style>
/* Tabs spacing & look */
#langTabs .nav-link {
    border: 1px solid #e9ecef;
    background-color: #fafafa;
    color: #495057;
    margin-right: .25rem;
}
#langTabs .nav-link.active {
    background-color: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
}
#langTabs .nav-link:not(.active):hover {
    background-color: #f1f5ff;
}

/* Editor surface */
.ck-editor__editable {
    min-height: 360px !important;
}

/* Card polish */
.card-header h5 { font-weight: 600; }

/* Preview body */
.preview-wrapper .header {
    padding: 16px 20px; border-bottom:1px solid #e5e7eb; background:#f8fafc;
}
.preview-wrapper .content {
    padding: 20px; line-height: 1.7;
}
.preview-wrapper .footer {
    padding: 14px 20px; border-top:1px solid #e5e7eb; text-align:center; color:#6b7280; font-size:0.92rem;
}
</style>

<script>
// Keep CKEditor instances
window.CKED = {};

// Init editors
document.querySelectorAll('.ckeditor').forEach((ta) => {
  ClassicEditor.create(ta, {
    toolbar: {
      items: [
        'heading', '|',
        'bold','italic','underline','link','bulletedList','numberedList','|',
        'alignment','outdent','indent','blockQuote','insertTable','horizontalLine','|',
        'undo','redo','|','sourceEditing'
      ],
      shouldNotGroupWhenFull: true
    }
  }).then(ed => { window.CKED[ta.id] = ed; })
    .catch(err => console.error('CKEditor init error:', err));
});

// Insert variable into input
document.querySelectorAll('.insert-var-input').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    const id = btn.dataset.target;
    const variable = btn.dataset.var || '';
    const el = document.getElementById(id);
    if (!el) return;
    const s = el.selectionStart ?? el.value.length;
    const epos = el.selectionEnd ?? el.value.length;
    el.value = el.value.slice(0, s) + variable + el.value.slice(epos);
    el.focus();
    el.setSelectionRange(s + variable.length, s + variable.length);
  });
});

// Insert variable into CKEditor
document.querySelectorAll('.insert-var-editor').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    const id = btn.dataset.target;
    const variable = btn.dataset.var || '';
    const ed = window.CKED[id];
    if (!ed) return;
    ed.model.change(writer => {
      const pos = ed.model.document.selection.getFirstPosition();
      writer.insertText(variable, pos);
    });
    ed.editing.view.focus();
  });
});

// Ensure first tab visible on load
document.addEventListener('DOMContentLoaded', () => {
  const firstTabBtn = document.getElementById('tab-en');
  if (firstTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
    const tab = new bootstrap.Tab(firstTabBtn);
    tab.show();
  }
  // ถ้ามี alert ให้เลื่อนขึ้นไปให้เห็น
  if (document.querySelector('.alert')) {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
});

// Unsaved changes warning
let formDirty = false;
document.getElementById('emailTemplateForm')?.addEventListener('input', () => { formDirty = true; });
window.addEventListener('beforeunload', (e) => {
  if (formDirty) { e.preventDefault(); e.returnValue = ''; }
});
document.getElementById('emailTemplateForm')?.addEventListener('submit', () => { formDirty = false; });

// Preview logic (single button, reusable)
function doPreview() {
  const activePane = document.querySelector('.tab-pane.active');
  const lang = activePane?.id?.replace('pane-', '') || 'en';

  // subject & body
  const subjectRaw = document.getElementById('subject_' + lang)?.value || '';
  const ed = window.CKED['body_' + lang];
  let bodyHtml = ed ? ed.getData() : (document.getElementById('body_' + lang)?.value || '');

  // mock replacements
  const rep = {
    '{full_name}': 'John Doe',
    '{username}': 'johnd',
    '{email}': 'john.doe@example.com',
    '{site_name}': 'INTEQC GLOBAL ASSESSMENT',
    '{current_date}': new Date().toLocaleDateString(),
    '{current_year}': new Date().getFullYear(),
    '{verification_link}': location.origin + '/verify/abc123',
    '{reset_link}': location.origin + '/reset/xyz987'
  };

  let subject = subjectRaw;
  Object.entries(rep).forEach(([k,v]) => {
    subject = subject.split(k).join(v);
    bodyHtml = bodyHtml.split(k).join(v);
  });

  const html = `
    <div class="preview-wrapper border rounded-4 overflow-hidden">
      <div class="header d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">${subject || '(no subject)'}</div>
          <div class="small text-muted">To: ${rep['{email}']}</div>
        </div>
        <span class="badge text-bg-primary">Preview · ${lang.toUpperCase()}</span>
      </div>
      <div class="content">${bodyHtml || '<em class="text-muted">(empty body)</em>'}</div>
      <div class="footer">© ${rep['{current_year}']} ${rep['{site_name}']}. All rights reserved.</div>
    </div>
  `;

  document.getElementById('previewContent').innerHTML = html;
  const modal = new bootstrap.Modal(document.getElementById('previewModal'));
  modal.show();
}

document.getElementById('previewBtn')?.addEventListener('click', (e) => {
  e.preventDefault();
  doPreview();
});

// Before submit: sync editors back to textarea
document.getElementById('emailTemplateForm')?.addEventListener('submit', () => {
  ['body_en','body_th','body_my'].forEach(id => {
    if (window.CKED[id]) document.getElementById(id).value = window.CKED[id].getData();
  });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

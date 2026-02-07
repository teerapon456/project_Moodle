<?php
// admin/tests/manage_iga_tests.php

require_once __DIR__ . '/../../includes/header.php';

$page_title = get_text('page_title_manage_tests');

// Set MySQL user-defined variable for current user id (optional)
if (isset($_SESSION['user_id']) && isset($conn) && $conn) {
  $current_user_id = (string) $_SESSION['user_id'];
  $uid = $conn->real_escape_string($current_user_id);
  $conn->query("SET @user_id = '{$uid}'");
} else {
  if (isset($conn) && $conn) $conn->query("SET @user_id = NULL");
}

require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
  set_alert(get_text('alert_no_admin_permission'), "danger");
  header("Location: /login");
  exit();
}

/** ---------------- Filters (POST/GET) ---------------- */
// MODIFIED: ดึงค่าตัวกรองจากทั้ง POST (เมื่อ submit ฟอร์ม) และ GET (เมื่อเปลี่ยนหน้า pagination) เพื่อให้ค่าไม่หาย
$search_query      = $_REQUEST['search']      ?? '';
$filter_published  = $_REQUEST['published']   ?? '';
$filter_created_by = $_REQUEST['created_by']  ?? '';
$filter_role_id    = $_REQUEST['role_id']     ?? ''; // 6 หรือ 7 เท่านั้น

// ถ้าเป็น Super_user_Recruitment → ล็อคให้เป็น 5 เสมอ
if ($is_Super_user_Recruitment) {
  $filter_role_id = '7';
}

// validate role_id (ยอมเฉพาะ 4,5)
if ($filter_role_id !== '' && !in_array((int)$filter_role_id, [6, 7], true)) {
  $filter_role_id = '';
}

/** ---------------- Users for "Created by" filter ---------------- */
$users_for_filter = [];
try {
  // ดึงรายชื่อผู้สร้างตาม role filter (ถ้ามี) และกรณี Super_user_Recruitment บังคับ role_id = 5
  $sql_users = "
        SELECT DISTINCT u.user_id, u.username
        FROM users u
        INNER JOIN iga_tests t ON u.user_id = t.created_by_user_id
        WHERE 1=1
    ";
  $u_params = [];
  $u_types = '';

  if ($is_Super_user_Recruitment) {
    $sql_users .= " AND t.role_id = 7";
  } elseif ($filter_role_id !== '') {
    $sql_users .= " AND t.role_id = ?";
    $u_params[] = (int)$filter_role_id;
    $u_types   .= 'i';
  }

  $sql_users .= " ORDER BY u.username ASC";

  $stmt_users = $conn->prepare($sql_users);
  if (!empty($u_params)) $stmt_users->bind_param($u_types, ...$u_params);
  $stmt_users->execute();
  $result_users = $stmt_users->get_result();
  while ($row = $result_users->fetch_assoc()) {
    $users_for_filter[] = $row;
  }
  $stmt_users->close();
} catch (Exception $e) {
  error_log("Error fetching users for filter: " . $e->getMessage());
  set_alert(get_text('error_data_fetch', ['users', $e->getMessage()]), "danger");
}

/** ---------------- Tests list + pagination ---------------- */
$tests = [];

// Handle auto-unpublish logic
if (isset($conn) && $conn) {
  $conn->query("UPDATE iga_tests SET is_published = 0 WHERE is_published = 1 AND unpublished_at IS NOT NULL AND unpublished_at <= NOW()");
}

$items_per_page = 5;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max(1, $page);
$offset = ($page - 1) * $items_per_page;

try {
  $sql = "SELECT t.test_id, t.test_name, t.description, t.is_published, t.published_at, t.unpublished_at, u.username AS created_by
            FROM iga_tests t
            LEFT JOIN users u ON t.created_by_user_id = u.user_id
            WHERE 1=1";
  $params = [];
  $types  = '';

  // บังคับเฉพาะ applicant เมื่อเป็น Super_user_Recruitment
  if ($is_Super_user_Recruitment) {
    $sql .= " AND t.role_id = 5";
  } else {
    // ถ้าเลือก role_id (4/5) จาก dropdown ก็กรอง
    if ($filter_role_id !== '') {
      $sql .= " AND t.role_id = ?";
      $params[] = (int)$filter_role_id;
      $types   .= 'i';
    }
  }

  if (!empty($search_query)) {
    $sql .= " AND (t.test_name LIKE ? OR t.description LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= 'ss';
  }
  if ($filter_published !== '') {
    $sql .= " AND t.is_published = ?";
    $params[] = (int)$filter_published;
    $types   .= 'i';
  }
  if (!empty($filter_created_by)) {
    $sql .= " AND t.created_by_user_id = ?";
    $params[] = (string)$filter_created_by;
    $types   .= 's';
  }

  // Count
  $count_sql = str_replace(
    "SELECT t.test_id, t.test_name, t.description, t.is_published, t.published_at, t.unpublished_at, u.username AS created_by",
    "SELECT COUNT(*) as total",
    $sql
  );
  $count_stmt = $conn->prepare($count_sql);
  if (!empty($params)) $count_stmt->bind_param($types, ...$params);
  $count_stmt->execute();
  $total_items = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
  $total_pages = (int)ceil($total_items / $items_per_page);
  $count_stmt->close();

  // Main with pagination
  $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
  $types2  = $types . 'ii';
  $params2 = array_merge($params, [$items_per_page, $offset]);
  $stmt = $conn->prepare($sql);
  if (!empty($params2)) $stmt->bind_param($types2, ...$params2);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
  }
  $stmt->close();
} catch (Exception $e) {
  set_alert(get_text('error_data_fetch', ['tests', $e->getMessage()]), "danger");
}

// --- NEW: สร้าง URL Query String สำหรับ Filters ใน Pagination ---
$filter_params = [
  'search'      => $search_query,
  'published'   => $filter_published,
  'created_by'  => $filter_created_by,
  'role_id'     => $filter_role_id,
];

// กรองค่าที่เป็นค่าว่างหรือ null ออกไป ยกเว้น 'published' ที่เป็น '0' (Unpublished)
$valid_filters = array_filter($filter_params, function ($value, $key) {
  if ($key === 'published' && (string)$value === '0') return true;
  return $value !== '' && $value !== null;
}, ARRAY_FILTER_USE_BOTH);

$query_string = http_build_query($valid_filters);
// เตรียม string สำหรับต่อท้าย URL โดยมี & นำหน้าถ้ามี query string
$query_string_separator = $query_string ? '&' . $query_string : '';

?>

<div class="container-fluid py-1">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h1 class="mb-0 text-primary-custom"><?php echo get_text('manage_tests_title'); ?></h1>
    <div class="d-flex justify-content-end align-items-center flex-wrap">
      <a href="/admin/create-test" class="btn btn-primary-custom me-2">
        <i class="fas fa-plus-circle me-2"></i> <?php echo get_text('create_new_test_btn'); ?>
      </a>
      <a href="/admin/import-test" class="btn btn-success">
        <i class="fas fa-file-excel me-2"></i> <?php echo get_text('import_excel_btn'); ?>
      </a>
    </div>
  </div>

  <?php echo get_alert(); ?>

  <div style="display:none" id="cloneCsrfWrapper"><?php echo generate_csrf_token(); ?></div>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
      <div class="flex-grow-1 ms-2">
        <form class="row g-3 align-items-end" method="POST" action="/admin/tests">
          <div class="col-md-3">
            <label for="search" class="form-label"><?php echo get_text('search_button'); ?></label>
            <input class="form-control shadow-sm" type="search" placeholder="<?php echo get_text('search_test_placeholder'); ?>" aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
          </div>

          <div class="col-md-2">
            <label for="role_id" class="form-label"><? echo get_text('test_for') ?></label>
            <select class="form-select shadow-sm" name="role_id" <?php echo $is_Super_user_Recruitment ? 'disabled' : ''; ?>>
              <option value=""><?php echo get_text('filter_all_status') ?: 'ทั้งหมด'; ?></option>
              <option value="6" <?php echo ($filter_role_id === '6' ? 'selected' : ''); ?>><? echo get_text('role_associate') ?></option>
              <option value="7" <?php echo ($filter_role_id === '7' ? 'selected' : ''); ?>><? echo get_text('role_applicant') ?></option>
            </select>
            <?php if ($is_Super_user_Recruitment): ?>
              <input type="hidden" name="role_id" value="7">
            <?php endif; ?>
          </div>

          <div class="col-md-2">
            <label for="published" class="form-label"><?php echo get_text('filter_published_label'); ?></label>
            <select class="form-select shadow-sm" name="published">
              <option value=""><?php echo get_text('filter_all_status'); ?></option>
              <option value="1" <?php echo ($filter_published === '1' ? 'selected' : ''); ?>><?php echo get_text('status_published'); ?></option>
              <option value="0" <?php echo ($filter_published === '0' ? 'selected' : ''); ?>><?php echo get_text('status_unpublished'); ?></option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="created_by" class="form-label"><?php echo get_text('table_header_created_by'); ?></label>
            <select class="form-select shadow-sm" name="created_by" <?php echo $is_Super_user_Recruitment ? '' : ''; ?>>
              <option value=""><?php echo get_text('filter_all_users'); ?></option>
              <?php foreach ($users_for_filter as $user): ?>
                <option value="<?php echo htmlspecialchars($user['user_id']); ?>"
                  <?php echo ($filter_created_by == $user['user_id'] ? 'selected' : ''); ?>>
                  <?php echo htmlspecialchars($user['username']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 d-grid gap-2">
            <button type="submit" class="btn btn-success btn-sm"><?php echo get_text("apply_filter"); ?></button>
            <button type="button" class="btn btn-primary-custom btn-sm" id="resetFilterBtn">
              <?php echo get_text("reset_filter"); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
    <div class="card-body">
      <?php if (!empty($tests)): ?>
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="bg-primary-custom text-white">
              <tr>
                <th><?php echo get_text('table_header_num'); ?></th>
                <th><?php echo get_text('table_header_test_name'); ?></th>
                <th><?php echo get_text('table_header_description'); ?></th>
                <th><?php echo get_text('table_header_published'); ?></th>
                <th><?php echo get_text('table_header_created_by'); ?></th>
                <th><?php echo get_text('table_header_actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; ?>
              <?php foreach ($tests as $test): ?>
                <tr>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                  <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($test['description'], 0, 50, '...', 'UTF-8'))); ?></td>
                  <td>
                  <td>
                    <?php
                    $now = date('Y-m-d H:i:s');
                    $is_scheduled = !empty($test['published_at']) && $test['published_at'] > $now;
                    $is_expired = !empty($test['unpublished_at']) && $test['unpublished_at'] <= $now;

                    if ($test['is_published']):
                      if ($is_scheduled): ?>
                        <span class="badge bg-info text-dark"><i class="fas fa-clock"></i> <?php echo get_text('status_scheduled', 'Scheduled'); ?></span>
                        <small class="d-block text-muted"><?php echo date('d/m/Y H:i', strtotime($test['published_at'])); ?></small>
                      <?php elseif ($is_expired): ?>
                        <span class="badge bg-danger"><i class="fas fa-history"></i> <?php echo get_text('status_expired', 'Expired'); ?></span>
                      <?php else: ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php echo get_text('status_published'); ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark"><i class="fas fa-times-circle"></i> <?php echo get_text('status_unpublished'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($test['created_by'] ?? get_text('not_available_abbr')); ?></td>
                  <td class="text-nowrap">
                    <form action="/admin/edit-test" method="POST" style="display:inline;">
                      <?php echo generate_csrf_token(); ?>
                      <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                      <button type="submit" class="btn btn-sm btn-info text-white mb-2 me-1" title="<?php echo get_text('action_edit'); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                    </form>
                    <form action="/admin/sections" method="POST" style="display:inline;">
                      <?php echo generate_csrf_token(); ?>
                      <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                      <button type="submit" class="btn btn-sm btn-secondary mb-2 me-1" title="<?php echo get_text('action_manage_sections'); ?>">
                        <i class="fas fa-list-ol"></i>
                      </button>
                    </form>

                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary mb-2 me-2 clone-test-btn"
                      title="<?php echo get_text('action_clone_test') ?: 'Clone Test'; ?>"
                      data-id="<?php echo (int)$test['test_id']; ?>">
                      <i class="fas fa-clone"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-danger mb-2 me-1 random-question-btn"
                      title="<?php echo get_text('action_random_question_mode'); ?>"
                      data-test-id="<?php echo $test['test_id']; ?>"
                      data-test-name="<?php echo htmlspecialchars($test['test_name'], ENT_QUOTES); ?>">
                      <i class="fas fa-random"></i>
                    </button>

                    <?php if ($test['is_published']): ?>
                      <button type="button" class="btn btn-sm btn-warning unpublish-test-btn mb-2 me-1" data-id="<?php echo $test['test_id']; ?>" title="<?php echo get_text('action_unpublish'); ?>">
                        <i class="fas fa-eye-slash"></i>
                      </button>
                    <?php else: ?>
                      <button type="button" class="btn btn-sm btn-success publish-test-btn mb-2 me-1" data-id="<?php echo $test['test_id']; ?>" title="<?php echo get_text('action_publish'); ?>">
                        <i class="fas fa-eye"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php
          // คำนวณแถวที่กำลังแสดงอยู่ เช่น 1–5 of 23
          $start_item = $total_items > 0 ? $offset + 1 : 0;
          $end_item   = $total_items > 0 ? min($offset + count($tests), $total_items) : 0;
          ?>

          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
            <div class="text-muted small mb-2 mb-md-0">
              <?php if ($total_items > 0): ?>
                <?php echo get_text("show"); ?>
                <?php echo $start_item; ?>–<?php echo $end_item; ?>
                <?php echo get_text("of"); ?>
                <?php echo $total_items; ?>
                <?php echo get_text("list"); ?>
              <?php else: ?>
                <?php echo get_text("no_data_found"); ?>
              <?php endif; ?>
            </div>

            <?php if (isset($total_pages) && $total_pages > 1): ?>
              <nav aria-label="Page navigation" class="mt-0">
                <ul class="pagination justify-content-center mb-0">
                  <!-- ปุ่มก่อนหน้า -->
                  <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                      href="<?php echo ($page <= 1)
                              ? '#'
                              : '?page=' . ($page - 1) . (!empty($query_string) ? '&' . $query_string : ''); ?>">
                      <i class="fas fa-chevron-left"></i>
                    </a>
                  </li>

                  <?php
                  // กำหนดช่วงหน้าที่แสดงรอบ ๆ หน้าปัจจุบัน
                  $visible_pages = 5;
                  $start_page = max(1, $page - floor($visible_pages / 2));
                  $end_page   = min($total_pages, $start_page + $visible_pages - 1);

                  if ($end_page - $start_page + 1 < $visible_pages) {
                    $start_page = max(1, $end_page - $visible_pages + 1);
                  }

                  // ถ้าเริ่มจากหน้ามากกว่า 1 ให้โชว์หน้า 1 ก่อน + ...
                  if ($start_page > 1): ?>
                    <li class="page-item <?php echo ($page == 1) ? 'active' : ''; ?>">
                      <a class="page-link"
                        href="?page=1<?php echo !empty($query_string) ? '&' . $query_string : ''; ?>">
                        1
                      </a>
                    </li>
                    <?php if ($start_page > 2): ?>
                      <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                  <?php endif; ?>

                  <!-- หน้าตรงกลางรอบ ๆ ปัจจุบัน -->
                  <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo ((int)$i === (int)$page) ? 'active' : ''; ?>">
                      <a class="page-link"
                        href="?page=<?php echo $i . (!empty($query_string) ? '&' . $query_string : ''); ?>">
                        <?php echo $i; ?>
                      </a>
                    </li>
                  <?php endfor; ?>

                  <?php
                  // ถ้ายังไม่ถึงหน้าสุดท้าย ให้ใส่ ... + หน้าสุดท้าย
                  if ($end_page < $total_pages):
                    if ($end_page < $total_pages - 1): ?>
                      <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                      <a class="page-link"
                        href="?page=<?php echo $total_pages . (!empty($query_string) ? '&' . $query_string : ''); ?>">
                        <?php echo $total_pages; ?>
                      </a>
                    </li>
                  <?php endif; ?>

                  <!-- ปุ่มถัดไป -->
                  <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                      href="<?php echo ($page >= $total_pages)
                              ? '#'
                              : '?page=' . ($page + 1) . (!empty($query_string) ? '&' . $query_string : ''); ?>">
                      <i class="fas fa-chevron-right"></i>
                    </a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
          <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_tests_in_system'); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="publishUnpublishConfirmModal" tabindex="-1" aria-labelledby="publishUnpublishConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary-custom text-white">
        <h5 class="modal-title" id="publishUnpublishConfirmModalLabel"><?php echo get_text('confirm_operation_modal_title'); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="publishUnpublishModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
        <button type="button" class="btn btn-primary-custom" id="confirmPublishUnpublishBtn"><?php echo get_text('confirm_button'); ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="cloneTestConfirmModal" tabindex="-1" aria-labelledby="cloneTestConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="cloneTestConfirmModalLabel"><?php echo get_text('action_clone_test') ?: 'Clone Test'; ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="cloneModalBody">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
        <button type="button" class="btn btn-info text-white" id="confirmCloneBtn">
          <i class="fas fa-copy me-1"></i> <?php echo get_text('confirm_button'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="randomQuestionModal" tabindex="-1" aria-labelledby="randomQuestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="randomQuestionModalLabel"><?php echo get_text('random_question_configuration'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="randomQuestionForm" method="POST" action="/admin/save-random-settings.php">
        <?php echo generate_csrf_token(); ?>
        <input type="hidden" name="test_id" id="randomTestId">
        <input type="hidden" name="section_random_counts" id="sectionRandomCounts">
        <div class="modal-body">
          <div class="mb-3">
            <h6 id="testNameDisplay" class="mb-3"></h6>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="enableRandomMode" name="enable_random_mode">
              <label class="form-check-label" for="enableRandomMode">
                <?php echo get_text('enable_random_question_mode'); ?>
              </label>
            </div>

            <div id="randomSettings" style="display: none;">
              <div class="mb-3">
                <label class="form-label">
                  <?php echo get_text('always_include_questions'); ?>
                  <small class="text-muted">(<?php echo get_text('select_questions_always_included'); ?>)</small>
                </label>
                <div id="questionList" class="border p-2" style="max-height: 420px; overflow-y: auto;">
                  <div class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin me-2"></i> <?php echo get_text('loading_random_questions'); ?>
                  </div>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo get_text('random_question_mode_help'); ?>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo get_text('save_changes_button'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<style>
  .accordion-button {
    font-weight: 600;
  }

  .accordion-item {
    border-radius: .5rem;
    overflow: hidden;
  }

  .accordion-item .accordion-button:focus {
    box-shadow: none;
  }

  .list-group-item {
    border: 1px solid rgba(0, 0, 0, .05);
  }
</style>

<script>
  // ===== i18n bridge =====
  function get_text(key, defaultText = '') {
    try {
      if (typeof jsLang !== 'undefined' && jsLang[key] !== undefined) return jsLang[key];
      return defaultText || key;
    } catch (e) {
      console.error('Error in get_text:', e);
      return defaultText || key;
    }
  }

  const jsLang = {
    confirm_publish_modal_body: "<?php echo addslashes(get_text('confirm_publish_modal_body')); ?>",
    confirm_unpublish_modal_body: "<?php echo addslashes(get_text('confirm_unpublish_modal_body')); ?>",
    alert_success_operation: "<?php echo addslashes(get_text('alert_success_operation')); ?>",
    alert_error_prefix: "<?php echo addslashes(get_text('alert_error_prefix')); ?>",
    alert_error_operation: "<?php echo addslashes(get_text('alert_error_operation')); ?>",
    saving: "<?php echo addslashes(get_text('saving')); ?>",
    loading_questions_list: "<?php echo addslashes(get_text('loading_random_questions')); ?>",
    error_loading_questions: "<?php echo addslashes(get_text('error_loading_random_questions')); ?>",
    error_saving_settings: "<?php echo addslashes(get_text('error_saving_settings')); ?>",
    error_occurred: "<?php echo addslashes(get_text('error_occurred')); ?>",
    settings_saved_successfully: "<?php echo addslashes(get_text('settings_saved_successfully')); ?>",
    no_random_questions_found: "<?php echo addslashes(get_text('no_random_questions_found')); ?>",
    number_of_random_questions: "<?php echo addslashes(get_text('number_of_random_questions')); ?>",
    clone_confirm_body_prefix: "<?php echo addslashes(get_text('clone_confirm_body_prefix') ?: 'ต้องการโคลนแบบทดสอบนี้หรือไม่:'); ?>",
    clone_success: "<?php echo addslashes(get_text('clone_success') ?: 'โคลนแบบทดสอบสำเร็จ'); ?>",
    clone_failed: "<?php echo addslashes(get_text('clone_failed') ?: 'โคลนแบบทดสอบไม่สำเร็จ'); ?>",
    confirm_clone_test: "<?php echo addslashes(get_text('confirm_clone_test') ?: 'Clone this test (including sections, questions, options and random settings)?'); ?>",
    alert_clone_success: "<?php echo addslashes(get_text('alert_clone_success') ?: 'Clone completed.'); ?>"
  };

  document.addEventListener('DOMContentLoaded', function() {
    // ---------- helpers ----------
    function showAlert(message, type = 'info') {
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
      alertDiv.role = 'alert';
      alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
      const container = document.querySelector('.container-fluid.py-4') || document.body;
      container.insertBefore(alertDiv, container.firstChild);
      setTimeout(() => {
        const alertInstance = bootstrap.Alert.getOrCreateInstance(alertDiv);
        if (alertInstance) alertInstance.close();
      }, 5000);
    }

    function firstCsrfToken() {
      const node = document.querySelector('input[name="_csrf_token"]');
      return node ? node.value : '';
    }

    // ===== Random Question Modal Logic =====
    function toggleRandomSettings() {
      const randomSettings = document.getElementById('randomSettings');
      const enableRandomMode = document.getElementById('enableRandomMode');
      if (randomSettings && enableRandomMode) {
        randomSettings.style.display = enableRandomMode.checked ? 'block' : 'none';
      }
    }
    const enableRandomModeCheckbox = document.getElementById('enableRandomMode');
    if (enableRandomModeCheckbox) enableRandomModeCheckbox.addEventListener('change', toggleRandomSettings);

    // Open random modal
    document.addEventListener('click', function(e) {
      const randomBtn = e.target.closest('.random-question-btn');
      if (!randomBtn) return;
      e.preventDefault();
      const testId = randomBtn.getAttribute('data-test-id');
      const testName = randomBtn.getAttribute('data-test-name');
      if (testId && testName) openRandomQuestionModal(testId, testName);
    });

    function openRandomQuestionModal(testId, testName) {
      const testIdElement = document.getElementById('randomTestId');
      const testNameElement = document.getElementById('testNameDisplay');
      const questionList = document.getElementById('questionList');
      if (!testIdElement || !testNameElement || !questionList) {
        console.error('Required modal elements not found');
        showAlert('Error: Could not initialize question selection. Please refresh and try again.', 'danger');
        return;
      }
      testIdElement.value = testId;
      testNameElement.textContent = testName;
      questionList.innerHTML =
        `<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> ${get_text('loading_questions_list')}</div>`;

      new bootstrap.Modal(document.getElementById('randomQuestionModal')).show();

      fetch(`/admin/get-test-iga_questions.php?test_id=${encodeURIComponent(testId)}`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          cache: 'no-cache'
        })
        .then(async response => {
          const responseText = await response.text();
          let responseData;
          try {
            responseData = responseText ? JSON.parse(responseText) : {};
          } catch (e) {
            throw new Error('Invalid JSON response from server');
          }
          if (!response.ok) throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
          return responseData;
        })
        .then(data => {
          if (data && data.success) {
            const enableRandomMode = document.getElementById('enableRandomMode');
            const isRandomMode = !!(data.settings && parseInt(data.settings.is_random_mode));
            enableRandomMode.checked = isRandomMode;
            toggleRandomSettings();
            renderSectionAccordion(data);
          } else {
            const msg = (data && data.message) ? data.message : "<?php echo addslashes(get_text('error_loading_random_questions')); ?>";
            questionList.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
          }
        })
        .catch(err => {
          console.error(err);
          questionList.innerHTML = `<div class="alert alert-danger"><?php echo addslashes(get_text('error_loading_random_questions')); ?></div>`;
        });
    }

    function renderSectionAccordion(data) {
      const questionList = document.getElementById('questionList');
      if (!data.sections || !data.iga_sections.length) {
        questionList.innerHTML = '<div class="text-muted"><?php echo addslashes(get_text('no_random_questions_found')); ?></div>';
        return;
      }

      const bySection = {};
      data.iga_sections.forEach(s => {
        bySection[String(s.section_id)] = [];
      });
      (data.questions || []).forEach(item => {
        if (!item.is_section_header) {
          const sid = String(item.section_id);
          (bySection[sid] ||= []).push(item);
        }
      });

      const sectionCounts = (data.settings && data.settings.section_random_counts) ? data.settings.section_random_counts : {};
      const alwaysInclude = (data.settings && data.settings.always_include_questions) ? data.settings.always_include_iga_questions.map(String) : [];

      let html = `<div id="sectionAccordion" class="accordion">`;
      data.iga_sections.forEach((sec, idx) => {
        const sid = String(sec.section_id);
        const questions = bySection[sid] || [];
        const existVal = sectionCounts[sid] ? parseInt(sectionCounts[sid]) : 0;
        const collapsedClass = idx === 0 ? 'show' : '';
        html += `
        <div class="accordion-item mb-2">
          <h2 class="accordion-header" id="h-${sid}">
            <button class="accordion-button ${collapsedClass ? '' : 'collapsed'}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c-${sid}" aria-expanded="${collapsedClass ? 'true' : 'false'}" aria-controls="c-${sid}">
              ${sec.section_name}
            </button>
          </h2>
          <div id="c-${sid}" class="accordion-collapse collapse ${collapsedClass}" aria-labelledby="h-${sid}" data-bs-parent="#sectionAccordion">
            <div class="accordion-body">
              <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                <label class="mb-0 small">${get_text('number_of_random_questions')}:</label>
                <input type="number" min="0" class="form-control form-control-sm section-count-input"
                       style="width:120px" data-section-id="${sid}" value="${existVal}">
              </div>
              ${iga_questions.length === 0 ? `
                <div class="text-muted small"><?php echo addslashes(get_text('no_random_questions_found')); ?></div>
              ` : `
                <div class="list-group">
                  ${iga_questions.map((q, i) => {
                    const qid = String(q.question_id);
                    const isChecked = alwaysInclude.includes(qid) ? 'checked' : '';
                    const qnum = q.question_number || (i + 1);
                    return `
                      <label class="list-group-item d-flex align-items-start">
                        <input class="form-check-input me-2 mt-1" type="checkbox" name="always_include[]" value="${qid}" ${isChecked}>
                        <div>
                          <div class="fw-semibold">ข้อ ${qnum}</div>
                          <div>${q.question_text}</div>
                        </div>
                      </label>
                    `;
                  }).join('')}
                </div>
              `}
            </div>
          </div>
        </div>`;
      });
      html += `</div>`;
      questionList.innerHTML = html;
    }

    // Save random settings
    const randomForm = document.getElementById('randomQuestionForm');
    if (randomForm) {
      randomForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const counts = {};
        document.querySelectorAll('.section-count-input[data-section-id]').forEach(inp => {
          const sid = inp.getAttribute('data-section-id');
          const val = parseInt(inp.value || '0', 10);
          counts[sid] = isNaN(val) ? 0 : Math.max(0, val);
        });
        const hiddenCounts = document.getElementById('sectionRandomCounts');
        if (hiddenCounts) hiddenCounts.value = JSON.stringify(counts);

        const submitBtn = randomForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + (jsLang.saving || 'Saving...');
        }

        fetch('/admin/save-random-settings.php', {
            method: 'POST',
            body: new FormData(randomForm),
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(async res => {
            const text = await res.text();
            let data;
            try {
              data = JSON.parse(text);
            } catch {
              throw new Error('<?php echo addslashes(get_text("error_saving_settings")); ?>');
            }
            if (!res.ok || !data.success) throw new Error(data.message || '<?php echo addslashes(get_text("error_saving_settings")); ?>');
            showAlert('<?php echo addslashes(get_text("settings_saved_successfully")); ?>', 'success');
            setTimeout(() => {
              const modal = bootstrap.Modal.getInstance(document.getElementById('randomQuestionModal'));
              if (modal) modal.hide();
            }, 800);
          })
          .catch(err => {
            console.error(err);
            showAlert(err.message || '<?php echo addslashes(get_text("error_occurred")); ?>', 'danger');
          })
          .finally(() => {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            }
          });
      });
    }

    // ===== Publish / Unpublish =====
    let testIdToTogglePublish = null;
    let publishAction = null;
    document.querySelectorAll('.publish-test-btn, .unpublish-test-btn').forEach(button => {
      button.addEventListener('click', function() {
        testIdToTogglePublish = this.dataset.id;
        publishAction = this.classList.contains('publish-test-btn');
        const modalBody = document.getElementById('publishUnpublishModalBody');
        const confirmBtn = document.getElementById('confirmPublishUnpublishBtn');
        modalBody.innerHTML = publishAction ? jsLang.confirm_publish_modal_body : jsLang.confirm_unpublish_modal_body;
        confirmBtn.classList.toggle('btn-primary-custom', publishAction);
        confirmBtn.classList.toggle('btn-danger', !publishAction);
        new bootstrap.Modal(document.getElementById('publishUnpublishConfirmModal')).show();
      });
    });

    const confirmPublishUnpublishBtn = document.getElementById('confirmPublishUnpublishBtn');
    if (confirmPublishUnpublishBtn) {
      confirmPublishUnpublishBtn.addEventListener('click', function() {
        if (!testIdToTogglePublish) return;
        const action = publishAction ? 'publish' : 'unpublish';
        fetch('/process/test_actions.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=' + encodeURIComponent(action) + '&test_id=' + encodeURIComponent(testIdToTogglePublish)
          })
          .then(res => res.json())
          .then(data => {
            alert(data.success ? `${jsLang.alert_success_operation}: ${data.message}` : `${jsLang.alert_error_prefix}: ${data.message}`);
            if (data.success) location.reload();
          })
          .catch(() => alert(jsLang.alert_error_operation))
          .finally(() => {
            const m = bootstrap.Modal.getInstance(document.getElementById('publishUnpublishConfirmModal'));
            if (m) m.hide();
            testIdToTogglePublish = null;
            publishAction = null;
          });
      });
    }

    // ===== Clone Test (MODAL POP-UP) =====
    let _cloneCtx = {
      id: null,
      name: '',
      btn: null
    };

    // open modal from button
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.clone-test-btn');
      if (!btn) return;

      const testId = btn.getAttribute('data-id');
      // ชื่อแบบทดสอบ: ถ้าไม่ได้ใส่ data-test-name มากับปุ่ม จะดึงจากคอลัมน์ชื่อ (คอลัมน์ที่ 2)
      let testName = btn.getAttribute('data-test-name');
      if (!testName) {
        const tr = btn.closest('tr');
        if (tr && tr.children[1]) testName = tr.children[1].textContent.trim();
      }
      _cloneCtx = {
        id: testId,
        name: testName || '',
        btn
      };

      // ใส่ข้อความในโมดอล
      const cloneBody = document.getElementById('cloneModalBody');
      cloneBody.innerHTML = `
      <div class="d-flex align-items-start">
        <i class="fas fa-clone me-3 mt-1"></i>
        <div>
          <p class="mb-2">${jsLang.clone_confirm_body_prefix}</p>
          <p class="mb-1"><strong>${_cloneCtx.name || ('#' + _cloneCtx.id)}</strong></p>
          <p class="text-muted mb-0">ชื่อใหม่ที่จะสร้าง: <em>${(_cloneCtx.name || ('#' + _cloneCtx.id))} - COPY</em></p>
          <hr class="my-3">
          <small class="text-muted">การโคลนจะรวม Sections, Questions, Options และ Random settings ทั้งหมด</small>
        </div>
      </div>
    `;

      new bootstrap.Modal(document.getElementById('cloneTestConfirmModal')).show();
    });

    // confirm in modal
    const confirmCloneBtn = document.getElementById('confirmCloneBtn');
    if (confirmCloneBtn) {
      confirmCloneBtn.addEventListener('click', function() {
        if (!_cloneCtx.id) return;

        const csrfToken = firstCsrfToken();
        if (!csrfToken) {
          showAlert('Missing CSRF token, please reload the page.', 'danger');
          return;
        }

        // UI in modal
        confirmCloneBtn.disabled = true;
        const original = confirmCloneBtn.innerHTML;
        confirmCloneBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (jsLang.saving || 'Saving...');

        const formData = new FormData();
        formData.append('test_id', _cloneCtx.id);
        formData.append('_csrf_token', csrfToken);

        fetch('/process/clone_test.php', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: 'no-cache'
          })
          .then(async res => {
            const text = await res.text();
            let data;
            try {
              data = JSON.parse(text);
            } catch {
              throw new Error('Server returned non-JSON: ' + text.slice(0, 200));
            }
            if (!res.ok || !data.success) throw new Error(data.message || jsLang.clone_failed);
            return data;
          })
          .then(data => {
            showAlert(jsLang.clone_success, 'success');
            // ปิดโมดอลก่อน redirect
            const m = bootstrap.Modal.getInstance(document.getElementById('cloneTestConfirmModal'));
            if (m) m.hide();

            // ไปหน้าแก้ไขอัตโนมัติ
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/edit-test';
            const in1 = document.createElement('input');
            in1.type = 'hidden';
            in1.name = '_csrf_token';
            in1.value = csrfToken;
            const in2 = document.createElement('input');
            in2.type = 'hidden';
            in2.name = 'test_id';
            in2.value = data.new_test_id;
            form.appendChild(in1);
            form.appendChild(in2);
            document.body.appendChild(form);
            form.submit();
          })
          .catch(err => {
            console.error('Clone error:', err);
            showAlert((jsLang.clone_failed + ': ' + err.message), 'danger');
          })
          .finally(() => {
            confirmCloneBtn.disabled = false;
            confirmCloneBtn.innerHTML = original;
            _cloneCtx = {
              id: null,
              name: '',
              btn: null
            };
          });
      });
    }

    // ===== General UI =====
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    if (resetFilterBtn) {
      resetFilterBtn.addEventListener('click', function() {
        window.location.href = '/admin/tests';
      });
    }
  });
</script>
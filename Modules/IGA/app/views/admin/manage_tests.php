<?php
// This file serves as the Admin page for managing tests.

require_once __DIR__ . '/../../includes/header.php'; // Include header and common functions

// --- Authorization and Initialization ---
// Set page title using get_text() for localization
$page_title = get_text('page_title_manage_tests');

// Set MySQL user-defined variable for the current user's ID
// This is typically used for auditing or session-related queries
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} else {
    // If no user_id in session (e.g., Guest) or no DB connection, set to NULL
    $conn->query("SET @user_id = NULL");
}

require_login(); // Ensure user is logged in
// Check user roles: Must be 'admin' or 'editor'
if (!has_role('admin') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login");
    exit();
}

// --- Data Fetching for Filters ---
$users_for_filter = [];
try {
    $sql_users = "SELECT DISTINCT u.user_id, u.username 
                  FROM users u
                  INNER JOIN tests t ON u.user_id = t.created_by_user_id
                  ORDER BY u.username ASC";
    $stmt_users = $conn->prepare($sql_users);
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


// --- Data Fetching and Filtering for Tests ---
$tests = [];
$search_query = $_POST['search'] ?? '';
$filter_published = $_POST['published'] ?? ''; // '1' for published, '0' for unpublished, '' for all
$filter_created_by = $_POST['created_by'] ?? ''; // New: filter by created_by_user_id

try {
    $sql = "SELECT t.test_id, t.test_name, t.description, t.is_published, t.published_at, t.unpublished_at, u.username AS created_by
            FROM tests t
            LEFT JOIN users u ON t.created_by_user_id = u.user_id
            WHERE 1=1"; // Start with a true condition for easy WHERE clause additions

    $params = [];
    $types = '';

    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $sql .= " AND (t.test_name LIKE ? OR t.description LIKE ?)";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
        $types .= 'ss';
    }

    // Add published filter condition if provided
    if ($filter_published !== '') {
        $sql .= " AND t.is_published = ?";
        $params[] = (int)$filter_published;
        $types .= 'i';
    }

    // New: Add created_by filter condition if provided
    if (!empty($filter_created_by)) {
        $sql .= " AND t.created_by_user_id = ?";
        $params[] = (int)$filter_created_by;
        $types .= 'i';
    }

    $sql .= " ORDER BY t.created_at DESC"; // Order by creation date descending

    $stmt = $conn->prepare($sql);

    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all test data
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    // Set an alert message if data fetching fails
    set_alert(get_text('error_data_fetch', ['tests', $e->getMessage()]), "danger");
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="mb-0 text-primary-custom"><?php echo get_text('manage_tests_title'); ?></h1>
        <div class="d-flex justify-content-end align-items-center flex-wrap">
            <a href="/INTEQC_GLOBAL_ASSESMENT/admin/create-test" class="btn btn-primary-custom me-2 mb-2">
                <i class="fas fa-plus-circle me-2"></i> <?php echo get_text('create_new_test_btn'); ?>
            </a>
            <a href="/INTEQC_GLOBAL_ASSESMENT/admin/import-test" class="btn btn-success me-2 mb-2">
                <i class="fas fa-file-excel me-2"></i> <?php echo get_text('import_excel_btn'); ?>
            </a>
        </div>
    </div>
    <?php echo get_alert(); // Display alert messages (e.g., success, error) 
    ?>


    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><?php echo get_text('list_of_tests'); ?></h5>
        </div>
        <div class="card-body">
            <form class="row g-3 mb-4" method="POST" action="/INTEQC_GLOBAL_ASSESMENT/admin/tests">
                <div class="col-md-4">
                    <label for="search" class="form-label"><?php echo get_text('search_button'); ?></label>
                    <input class="form-control shadow-sm" type="search" placeholder="<?php echo get_text('search_test_placeholder'); ?>" aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-3">
                    <label for="published" class="form-label"><?php echo get_text('filter_published_label'); ?></label>
                    <select class="form-select shadow-sm" name="published">
                        <option value=""><?php echo get_text('filter_all_status'); ?></option>
                        <option value="1" <?php echo ($filter_published === '1' ? 'selected' : ''); ?>><?php echo get_text('status_published'); ?></option>
                        <option value="0" <?php echo ($filter_published === '0' ? 'selected' : ''); ?>><?php echo get_text('status_unpublished'); ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="created_by" class="form-label"><?php echo get_text('table_header_created_by'); ?></label>
                    <select class="form-select shadow-sm" name="created_by">
                        <option value=""><?php echo get_text('filter_all_users'); ?></option>
                        <?php foreach ($users_for_filter as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['user_id']); ?>"
                                <?php echo ($filter_created_by == $user['user_id'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid align-self-end"> <button type="submit" class="btn btn-success mb-2"><?php echo get_text("apply_filter"); ?></button>
                    <button type="button" class="btn btn-primary-custom" id="resetFilterBtn">
                        <?php echo get_text("reset_filter"); ?>
                    </button>
                </div>
            </form>

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
                                    <td>
                                        <form action="/INTEQC_GLOBAL_ASSESMENT/admin/edit-test" method="POST" style="display:inline;">
                                            <?php echo generate_csrf_token(); ?>
                                            <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-info text-white mb-2 me-2" title="<?php echo get_text('action_edit'); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </form>
                                        <form action="/INTEQC_GLOBAL_ASSESMENT/admin/sections" method="POST" style="display:inline;">
                                            <?php echo generate_csrf_token(); ?>
                                            <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary mb-2 me-2" title="<?php echo get_text('action_manage_sections'); ?>">
                                                <i class="fas fa-list-ol"></i>
                                            </button>
                                        </form>
                                        <?php if ($test['is_published']): ?>
                                            <button type="button" class="btn btn-sm btn-warning unpublish-test-btn mb-2 me-2" data-id="<?php echo $test['test_id']; ?>" title="<?php echo get_text('action_unpublish'); ?>">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success publish-test-btn mb-2 me-2" data-id="<?php echo $test['test_id']; ?>" title="<?php echo get_text('action_publish'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            <div class="modal-body" id="publishUnpublishModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                <button type="button" class="btn btn-primary-custom" id="confirmPublishUnpublishBtn"><?php echo get_text('confirm_button'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    // Pass language strings to JavaScript for dynamic content and alerts
    const jsLang = {
        confirm_publish_modal_body: "<?php echo addslashes(get_text('confirm_publish_modal_body')); ?>",
        confirm_unpublish_modal_body: "<?php echo addslashes(get_text('confirm_unpublish_modal_body')); ?>",
        alert_success_operation: "<?php echo addslashes(get_text('alert_success_operation')); ?>",
        alert_error_prefix: "<?php echo addslashes(get_text('alert_error_prefix')); ?>",
        alert_error_operation: "<?php echo addslashes(get_text('alert_error_operation')); ?>"
    };

    document.addEventListener('DOMContentLoaded', function() {
        let testIdToTogglePublish = null;
        let publishAction = null; // true for publish, false for unpublish

        // Event listener for Publish/Unpublish Buttons
        document.querySelectorAll('.publish-test-btn, .unpublish-test-btn').forEach(button => {
            button.addEventListener('click', function() {
                testIdToTogglePublish = this.dataset.id;
                publishAction = this.classList.contains('publish-test-btn'); // Determine action (publish or unpublish)

                const modalBody = document.getElementById('publishUnpublishModalBody');
                const confirmBtn = document.getElementById('confirmPublishUnpublishBtn');

                if (publishAction) {
                    modalBody.innerHTML = jsLang.confirm_publish_modal_body;
                    confirmBtn.classList.remove('btn-danger'); // Ensure correct button styling
                    confirmBtn.classList.add('btn-primary-custom');
                } else {
                    modalBody.innerHTML = jsLang.confirm_unpublish_modal_body;
                    confirmBtn.classList.remove('btn-primary-custom'); // Ensure correct button styling
                    confirmBtn.classList.add('btn-danger');
                }

                // Show the confirmation modal
                const publishUnpublishModal = new bootstrap.Modal(document.getElementById('publishUnpublishConfirmModal'));
                publishUnpublishModal.show();
            });
        });

        // Event listener for Confirm Publish/Unpublish Button inside the modal
        document.getElementById('confirmPublishUnpublishBtn').addEventListener('click', function() {
            if (testIdToTogglePublish) {
                const action = publishAction ? 'publish' : 'unpublish'; // Define action string for PHP
                fetch('/INTEQC_GLOBAL_ASSESMENT/process/test_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=' + action + '&test_id=' + testIdToTogglePublish
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(jsLang.alert_success_operation + ": " + data.message);
                            location.reload(); // Reload page to reflect changes
                        } else {
                            alert(jsLang.alert_error_prefix + ': ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error); // Log detailed error
                        alert(jsLang.alert_error_operation);
                    })
                    .finally(() => {
                        // Hide modal and reset variables regardless of success/failure
                        const publishUnpublishModal = bootstrap.Modal.getInstance(document.getElementById('publishUnpublishConfirmModal'));
                        publishUnpublishModal.hide();
                        testIdToTogglePublish = null;
                        publishAction = null;
                    });
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        // The filter logic is now handled by the form submission with GET requests.
        // The previous JavaScript for testFilter change event is no longer needed
        // as the form submission will handle the redirect.

        // NEW: Add event listener for the Reset Filter button
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                window.location.href = '/INTEQC_GLOBAL_ASSESMENT/admin/tests'; // Redirect to the page without any GET parameters
            });
        }
    });
</script>
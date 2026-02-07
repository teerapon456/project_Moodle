<?php
// Question Categories Report
require_once __DIR__ . '/../../includes/header.php';

// Set page title
$page_title = get_text('page_title_question_categories_report');

// Check login and permissions
require_login();

if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('new_role')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// Handle search and filter
$search_query = $_GET['search'] ?? '';

// Get categories with question counts
try {
    // Use the existing database connection from header.php
    $where_clause = "";
    $params = [];
    $types = "";
    
    if (!empty($search_query)) {
        $where_clause = "WHERE (c.category_name LIKE ? OR c.category_description LIKE ? OR c.category_type LIKE ?)";
        $search_param = "%$search_query%";
        $params = [$search_param, $search_param, $search_param];
        $types = "sss";
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM iga_question_categories c $where_clause";
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total_count = $stmt->get_result()->fetch_assoc()['total'];
    
    // Pagination settings
    $items_per_page = 20;
    $total_pages = ceil($total_count / $items_per_page);
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $items_per_page;
    
    // Get paginated categories with question counts
    $sql = "SELECT 
                c.category_id,
                c.category_name,
                c.category_description,
                c.category_type,
                c.created_at,
                COUNT(q.question_id) as question_count
            FROM 
                iga_question_categories c
            LEFT JOIN 
                questions q ON c.category_id = q.category_id
            $where_clause
            GROUP BY c.category_id
            ORDER BY c.category_type, c.category_name
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        // Merge the parameters with pagination parameters
        $all_params = array_merge($params, [$items_per_page, $offset]);
        $param_types = $types . 'ii';
        
        // Create references for bind_param
        $bind_params = array($param_types);
        foreach ($all_params as $key => $value) {
            $bind_params[] = &$all_params[$key];
        }
        
        // Use call_user_func_array to bind parameters by reference
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    } else {
        $stmt->bind_param('ii', $items_per_page, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in question_categories_report.php: " . $e->getMessage());
    set_alert(get_text("error_loading_data") . ": " . $e->getMessage(), "danger");
    $categories = [];
}
?>

<main class="flex-grow-1 container-wide mt-4">
    <?php echo get_alert(); ?>
    <div class="container-wide py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 text-primary-custom"><?php echo get_text("page_heading_question_categories_report"); ?></h1>
            <div class="d-flex">
                <a href="javascript:window.print()" class="btn btn-outline-primary me-2">
                    <i class="fas fa-print me-1"></i> <?php echo get_text('button_print'); ?>
                </a>
                <a href="/admin" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> <?php echo get_text('button_back'); ?>
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4 print-hide">
            <div class="card-header bg-light">
                <form action="" method="GET" class="row g-2">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="<?php echo get_text('placeholder_search_categories'); ?>" 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search me-1"></i> <?php echo get_text('button_search'); ?>
                            </button>
                            <?php if (!empty($search_query)): ?>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> <?php echo get_text('button_clear'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;"><?php echo get_text('table_header_number'); ?></th>
                                <th style="width: 15%;"><?php echo get_text('table_header_category_type'); ?></th>
                                <th style="width: 20%;"><?php echo get_text('table_header_category_name'); ?></th>
                                <th style="width: 30%;"><?php echo get_text('table_header_description'); ?></th>
                                <th style="width: 15%;"><?php echo get_text('table_header_question_count'); ?></th>
                                <th style="width: 20%;"><?php echo get_text('table_header_created_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php $i = 1 + (($page - 1) * $items_per_page); ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($category['category_type']); ?></td>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($category['category_description'] ?? '')); ?></td>
                                        <td class="text-center"><?php echo $category['question_count']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p class="mb-0"><?php echo get_text('no_categories_found'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav class="p-3 border-top" aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
    @media print {
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 16pt;
            line-height: 1.5;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'TH Sarabun New', sans-serif;
            font-weight: bold;
        }
        h1 { font-size: 18pt; }
        h2 { font-size: 18pt; }
        h3 { font-size: 16pt; }
        .print-hide { display: none !important; }
        .card { border: none; box-shadow: none; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dee2e6; padding: 8px; }
        .table thead th { background-color: #f8f9fa !important; }
        .text-primary-custom { color: #0d6efd !important; }
        .text-muted { color: #6c757d !important; }
        .bg-light { background-color: #f8f9fa !important; }
        .table-light th { background-color: #f8f9fa !important; }
        .table-hover tbody tr:hover { background-color: rgba(0, 0, 0, 0.05); }
    }
    
    @page {
        size: A4;
        margin: 1cm;
    }
    
    @media screen {
        body {
            font-family: 'TH Sarabun New', Arial, sans-serif;
            font-size: 16pt;
        }
        .text-primary-custom { color: #0d6efd; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

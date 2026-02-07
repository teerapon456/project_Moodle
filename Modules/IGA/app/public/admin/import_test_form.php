<?php
require_once __DIR__ . '/../../includes/header.php';

// At this point, $lang_data (used by get_text()) should already be correctly set by header.php.
// We get the current language from session for button highlighting in the language selector.
$current_lang = $_SESSION['lang'] ?? 'en';

// Set page title using the localized text
$page_title = get_text('page_title_import_test');

// Ensure user is logged in and has admin/super_user role
require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: /admin");
    exit();
}
?>

<div class="container mt-4">
    <h1 class="mb-4"><?php echo $page_title; ?></h1>

    <?php
    // Display session alerts using your existing get_alert() function
    echo get_alert();
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo get_text("upload_here"); ?></h4>
        </div>
        <div class="card-body">
            <form action="/admin/process-import" method="post" enctype="multipart/form-data">
                <ul>
                    <li>
                        <a href="/assets/excel_templates/template.xlsx" download="template.xlsx">
                            <?php echo get_text("excel_format_sheet"); ?>
                        </a>
                    </li>
                </ul>
                <div class="mb-3">
                    <label for="excel_file" class="form-label"><?php echo get_text("select_excel_file"); ?><span class="text-danger">*</span></label>
                    <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                </div>
                <button type="submit" class="btn btn-primary"> <?php echo get_text("upload_btn"); ?></button>
            </form>
        </div>
    </div>

    <hr>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo get_text('import_instructions_title'); ?></h4>
        </div>
        <div class="card-body">
            <p class="text-danger"><strong><?php echo get_text('important_note_template'); ?></strong></p>

            <h5><?php echo get_text('test_sheet_header_info'); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th><?php echo get_text('column_name'); ?></th>
                            <th><?php echo get_text('description'); ?></th>
                            <th><?php echo get_text('usage_notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>test_name</code></td>
                            <td><?php echo get_text('test_name_desc'); ?></td>
                            <td><?php echo get_text('test_name_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>description</code></td>
                            <td><?php echo get_text('description_desc'); ?></td>
                            <td><?php echo get_text('description_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>is_published</code></td>
                            <td><?php echo get_text('is_published_desc'); ?></td>
                            <td><?php echo get_text('is_published_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>duration_minutes</code></td>
                            <td><?php echo get_text('duration_minutes_desc'); ?></td>
                            <td><?php echo get_text('duration_minutes_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>show_result_immediately</code></td>
                            <td><?php echo get_text('show_result_immediately_desc'); ?></td>
                            <td><?php echo get_text('show_result_immediately_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>min_passing_score</code></td>
                            <td><?php echo get_text('min_passing_score_desc'); ?></td>
                            <td><?php echo get_text('min_passing_score_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>creation_year</code></td>
                            <td><?php echo get_text('creation_year_desc'); ?></td>
                            <td><?php echo get_text('creation_year_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>test_no</code></td>
                            <td><?php echo get_text('test_no_desc'); ?></td>
                            <td><?php echo get_text('test_no_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>language</code></td>
                            <td><?php echo get_text('language_desc'); ?></td>
                            <td><?php echo get_text('language_usage'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h5 class="mt-4"><?php echo get_text('question_sheet_header_info'); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th><?php echo get_text('column_name'); ?></th>
                            <th><?php echo get_text('description'); ?></th>
                            <th><?php echo get_text('usage_notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>Section Name</code></td>
                            <td><?php echo get_text('section_name_desc'); ?></td>
                            <td><?php echo get_text('section_name_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Section Description</code></td>
                            <td><?php echo get_text('section_description_desc'); ?></td>
                            <td><?php echo get_text('section_description_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Section Duration</code></td>
                            <td><?php echo get_text('section_duration_desc'); ?></td>
                            <td><?php echo get_text('section_duration_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Section Order</code></td>
                            <td><?php echo get_text('section_order_desc'); ?></td>
                            <td><?php echo get_text('section_order_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Question Type</code></td>
                            <td><?php echo get_text('question_type_desc'); ?></td>
                            <td><?php echo get_text('question_type_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Question Text</code></td>
                            <td><?php echo get_text('question_text_desc'); ?></td>
                            <td><?php echo get_text('question_text_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Question Order</code></td>
                            <td><?php echo get_text('question_order_desc'); ?></td>
                            <td><?php echo get_text('question_order_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Is Critical</code></td>
                            <td><?php echo get_text('is_critical_desc'); ?></td>
                            <td><?php echo get_text('is_critical_usage'); ?></td>
                        </tr>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <tr>
                                <td><code>Option<?php echo $i; ?></code></td>
                                <td><?php echo get_text('option_desc_prefix') . " " . $i; ?></td>
                                <td>
                                    <?php
                                    if ($i <= 4) {
                                        echo get_text('option_usage_standard');
                                    } else {
                                        echo get_text('option_usage_optional'); 
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                        <tr>
                            <td><code>Correct Answer Index</code></td>
                            <td><?php echo get_text('correct_answer_index_desc'); ?></td>
                            <td><?php echo get_text('correct_answer_index_usage'); ?></td>
                        </tr>
                        <tr>
                            <td><code>Score</code></td>
                            <td><?php echo get_text('score_desc'); ?></td>
                            <td><?php echo get_text('score_usage'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
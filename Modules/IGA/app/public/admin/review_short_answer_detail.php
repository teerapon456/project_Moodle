<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อตรวจและให้คะแนนคำถามอัตนัยของแบบทดสอบหนึ่งๆ
require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_review_short_answers_detail');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
if (!has_role('admin') && !has_role('editor') ) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

$attempt_id = $_POST['attempt_id'] ?? $_GET['attempt_id'] ?? null;

if (!is_numeric($attempt_id) || $attempt_id <= 0) {
  set_alert(get_text('error_loading_attempt_id'), "danger");
  header("Location: /admin/review-answers/");
  exit();
}

$attempt_info = [];
$test_info = [];
$short_answer_questions = [];
$total_scored_questions = 0;
$total_short_answer_questions = 0;

try {
  $stmt_attempt = $conn->prepare("
    SELECT
      uta.attempt_id,
      uta.test_id,
      uta.user_id,
      uta.start_time,
      uta.end_time,
      uta.is_completed,
      u.full_name AS user_full_name,
      u.username AS user_username
    FROM
      iga_user_test_attempts uta
    JOIN
      users u ON uta.user_id = u.user_id
    WHERE
      uta.attempt_id = ?
  ");
  $stmt_attempt->bind_param("i", $attempt_id);
  $stmt_attempt->execute();
  $attempt_info = $stmt_attempt->get_result()->fetch_assoc();
  $stmt_attempt->close();

  if (!$attempt_info) {
    set_alert(get_text('no_attempt_data_alert'), "danger");
    header("Location: /admin/review-answers/");
    exit();
  }

  $stmt_test = $conn->prepare("SELECT test_name FROM iga_tests WHERE test_id = ?");
  $stmt_test->bind_param("i", $attempt_info['test_id']);
  $stmt_test->execute();
  $test_info = $stmt_test->get_result()->fetch_assoc();
  $stmt_test->close();

  if (!$test_info) {
    set_alert(get_text('no_test_data_alert'), "danger");
    header("Location: /admin/review-answers/");
    exit();
  }

  // Check if test uses random questions
  $stmt_settings = $conn->prepare("
    SELECT 
      trqs.is_random_mode, 
      trqs.always_include_questions
    FROM iga_test_random_question_settings trqs
    WHERE trqs.test_id = ?
  ");
  $stmt_settings->bind_param("i", $attempt_info['test_id']);
  $stmt_settings->execute();
  $test_settings = $stmt_settings->get_result()->fetch_assoc();
  $stmt_settings->close();

  $is_random_mode = !empty($test_settings) && $test_settings['is_random_mode'] == 1;
  $always_include = [];
  
  if ($is_random_mode && !empty($test_settings['always_include_questions'])) {
    $always_include = json_decode($test_settings['always_include_questions'], true);
    if (!is_array($always_include)) {
      $always_include = [];
    }
    // Ensure always_include contains only integers
    $always_include = array_filter($always_include, 'is_numeric');
    $always_include = array_map('intval', $always_include);
  }

  // Build the base query for open-ended questions
  $sql = "
    SELECT
      q.question_id,
      q.question_text,
      q.score AS question_max_score,
      ua.user_answer_id,
      ua.user_answer_text,
      ua.score_earned,
      ua.is_correct
    FROM
      questions q
    JOIN
      iga_user_answers ua ON q.question_id = ua.question_id
    LEFT JOIN iga_user_test_attempts uta ON ua.attempt_id = uta.attempt_id
    LEFT JOIN iga_test_random_question_settings trqs ON uta.test_id = trqs.test_id
    WHERE
      ua.attempt_id = ? 
      AND q.question_type = 'short_answer'
      AND (
        trqs.is_random_mode IS NULL 
        OR trqs.is_random_mode = 0
        OR EXISTS (
          SELECT 1 
          FROM iga_user_answers ua2 
          WHERE ua2.attempt_id = uta.attempt_id 
          AND ua2.question_id = q.question_id
        )" . (!empty($always_include) ? "
        OR q.question_id IN (" . implode(',', array_fill(0, count($always_include), '?')) . ")" : "") . "
      )
    ORDER BY q.question_order ASC
  ";

  // Prepare and execute the query
  $stmt_qa = $conn->prepare($sql);
  
  // Build parameters array
  $params = [$attempt_id]; // Only need the attempt_id once now
  $types = 'i';
  
  // Add always_include parameters if any
  if (!empty($always_include)) {
    $params = array_merge($params, $always_include);
    $types .= str_repeat('i', count($always_include));
  }
  
  // Bind all parameters
  if (!empty($params)) {
    $stmt_qa->bind_param($types, ...$params);
  }
  $stmt_qa->execute();
  $result_qa = $stmt_qa->get_result();

  while ($row = $result_qa->fetch_assoc()) {
    $short_answer_questions[] = $row;
    $total_short_answer_questions++;
    if ($row['score_earned'] !== NULL) {
      $total_scored_questions++;
    }
  }
  $stmt_qa->close();
} catch (Exception $e) {
  set_alert(get_text('error_loading_questions', $e->getMessage()), "danger");
  header("Location: /admin/review-answers/");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_scores'])) {
  $scores_updated = 0;
  try {
    $conn->begin_transaction();

    // สร้าง map สำหรับคะแนนสูงสุดของแต่ละคำถามเพื่อการตรวจสอบที่เร็วขึ้น
    $max_scores_map = [];
    foreach ($short_answer_questions as $qa) {
      $max_scores_map[$qa['user_answer_id']] = $qa['question_max_score'];
    }

    foreach ($_POST['question_scores'] as $user_answer_id => $score) {
      $score = trim($score);

      if ($score === '' || !is_numeric($score)) {
        $score_to_save = NULL;
        $is_correct_to_save = NULL;
      } else {
        $score_to_save = (float)$score;
        $max_score = $max_scores_map[$user_answer_id] ?? 0;

        // *** ส่วนนี้คือการตรวจสอบและแจ้งเตือนตามที่คุณต้องการ ***
        if ($score_to_save > $max_score) {
          $conn->rollback();
          set_alert('ไม่สามารถให้คะแนนเกินคะแนนเต็มได้', "danger");
          header("Location: /admin/review-answer/?attempt_id=" . $attempt_id);
          exit();
        }

        $score_to_save = max(0, $score_to_save); // ตรวจสอบไม่ให้ติดลบ

        if ($score_to_save == $max_score && $max_score > 0) {
          $is_correct_to_save = 1;
        } else {
          $is_correct_to_save = 0;
        }
      }

      $update_stmt = $conn->prepare("UPDATE iga_user_answers SET score_earned = ?, is_correct = ? WHERE user_answer_id = ? AND attempt_id = ?");
      $update_stmt->bind_param("diii", $score_to_save, $is_correct_to_save, $user_answer_id, $attempt_id);
      $update_stmt->execute();
      if ($update_stmt->affected_rows > 0) {
        $scores_updated++;
      }
      $update_stmt->close();
    }

    $total_score_query = $conn->prepare("
      SELECT SUM(ua.score_earned) AS calculated_total_score
      FROM iga_user_answers ua
      WHERE ua.attempt_id = ? AND ua.score_earned IS NOT NULL
    ");
    $total_score_query->bind_param("i", $attempt_id);
    $total_score_query->execute();
    $calculated_total_score_result = $total_score_query->get_result()->fetch_assoc();
    $total_score_query->close();

    $new_total_score = $calculated_total_score_result['calculated_total_score'] ?? 0;

    $update_attempt_score_stmt = $conn->prepare("UPDATE iga_user_test_attempts SET total_score = ? WHERE attempt_id = ?");
    $update_attempt_score_stmt->bind_param("di", $new_total_score, $attempt_id);
    $update_attempt_score_stmt->execute();
    $update_attempt_score_stmt->close();

    $recheck_stmt = $conn->prepare("
      SELECT COUNT(ua.user_answer_id) AS pending_count
      FROM iga_user_answers ua
      JOIN iga_questions q ON ua.question_id = q.question_id
      WHERE ua.attempt_id = ? AND q.question_type = 'short_answer' AND ua.score_earned IS NULL
    ");
    $recheck_stmt->bind_param("i", $attempt_id);
    $recheck_stmt->execute();
    $recheck_result = $recheck_stmt->get_result()->fetch_assoc();
    $recheck_stmt->close();

    if ($recheck_result['pending_count'] == 0) {
      // Logic หากตรวจครบหมดแล้ว
    }

    $conn->commit();
    set_alert(get_text('success_save_scores', $scores_updated), "success");
    header("Location: /admin/review-answer/?attempt_id=" . $attempt_id);
    exit();
  } catch (Exception $e) {
    $conn->rollback();
    set_alert(get_text('error_save_scores', $e->getMessage()), "danger");
    header("Location: /admin/review-answer/?attempt_id=" . $attempt_id);
    exit();
  }
}
?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('page_heading_review_short_answer_detail'); ?></h1>
<p class="lead">
  <?php echo get_text('reviewing_attempt_of'); ?><strong><?php echo htmlspecialchars($attempt_info['user_full_name'] ?: $attempt_info['user_username']); ?></strong>
  <br><?php echo get_text('test_name_label'); ?> <strong><?php echo htmlspecialchars($test_info['test_name']); ?></strong>
  <br><?php echo get_text('start_time_label'); ?> <strong><?php echo htmlspecialchars(thai_datetime_format($attempt_info['start_time'])); ?></strong>
</p>

<?php echo get_alert(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="review-answers" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-2"></i> <?php echo get_text('back_to_list_button'); ?>
  </a>
  <?php if ($total_scored_questions < $total_short_answer_questions): ?>
    <span class="badge bg-warning fs-6">
      <?php echo get_text('remaining_pending_questions', $total_short_answer_questions - $total_scored_questions); ?>
    </span>
  <?php else: ?>
    <span class="badge bg-success fs-6">
      <?php echo get_text('all_short_answers_reviewed'); ?>
    </span>
  <?php endif; ?>
</div>

<?php if (empty($short_answer_questions)): ?>
  <div class="alert alert-info text-center" role="alert">
    <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_short_answers_found'); ?>
  </div>
<?php else: ?>
  <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?attempt_id=<?php echo htmlspecialchars($attempt_id); ?>" method="POST">
    <?php echo generate_csrf_token(); ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary-custom text-white">
        <h4 class="mb-0"><?php echo get_text('question_details_heading'); ?></h4>
      </div>
      <div class="card-body">
        <?php foreach ($short_answer_questions as $index => $qa): ?>
          <div class="mb-4 p-3 border rounded">
            <p class="mb-2"><strong><?php echo get_text('question_number_label', $index + 1); ?></strong> <?php echo nl2br(htmlspecialchars($qa['question_text'])); ?></p>
            <p class="mb-2"><strong><?php echo get_text('max_score_label'); ?></strong> <?php echo htmlspecialchars($qa['question_max_score']); ?> <?php echo get_text('score_label_suffix'); ?></p>
            <p class="mb-1"><strong><?php echo get_text('user_answer_label'); ?></strong></p>
            <div class="bg-light p-2 rounded mb-3">
              <?php echo nl2br(htmlspecialchars($qa['user_answer_text'] ?? get_text('no_answer_provided'))); ?>
            </div>

            <div class="mb-3">
              <label for="score_<?php echo htmlspecialchars($qa['user_answer_id']); ?>" class="form-label">
                **<?php echo get_text('score_input_label', htmlspecialchars($qa['question_max_score'])); ?>
              </label>
              <input
                type="number"
                inputmode="numeric"
                pattern="[0-9]*"
                min="0"
                max="<?php echo htmlspecialchars((int)$qa['question_max_score']); ?>"
                step="1"
                class="form-control"
                id="score_<?php echo htmlspecialchars($qa['user_answer_id']); ?>"
                name="question_scores[<?php echo htmlspecialchars($qa['user_answer_id']); ?>]"
                value="<?php echo htmlspecialchars($qa['score_earned'] !== null ? (int)$qa['score_earned'] : ''); ?>"
                placeholder="<?php echo get_text('score_placeholder'); ?>">
              <div id="score-feedback-<?php echo htmlspecialchars($qa['user_answer_id']); ?>" class="invalid-feedback d-block"></div>
            </div>
            <p class="text-muted mt-2">
              <i class="fas fa-history me-1"></i>
              <?php if ($qa['score_earned'] !== NULL): ?>
                <span class="badge bg-success me-2">ตรวจแล้ว</span>
                <span><?php echo get_text('scored_points_label', number_format($qa['score_earned'], 2), number_format($qa['question_max_score'], 2)); ?></span>
              <?php else: ?>
                <span class="badge bg-secondary me-2">ยังไม่ตรวจ</span>
              <?php endif; ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary-custom">
          <i class="fas fa-save me-2"></i> <?php echo get_text('save_scores_button'); ?>
        </button>
      </div>
    </div>
  </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('input[type="number"][name^="question_scores"]');

    scoreInputs.forEach(input => {
      input.addEventListener('input', function() {
        const maxScore = parseFloat(this.getAttribute('max'));
        const currentScore = parseFloat(this.value);
        const feedbackElement = document.getElementById('score-feedback-' + this.id.split('_')[1]);

        if (currentScore > maxScore) {
          // แสดงข้อความแจ้งเตือนเมื่อคะแนนเกิน
          feedbackElement.textContent = 'ไม่สามารถให้คะแนนเกินคะแนนเต็มได้';
          this.classList.add('is-invalid');
        } else {
          // ลบข้อความแจ้งเตือนถ้าคะแนนถูกต้อง
          feedbackElement.textContent = '';
          this.classList.remove('is-invalid');
        }
      });
    });
  });
</script>
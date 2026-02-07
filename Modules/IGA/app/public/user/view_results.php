<?php
// user/results/index.php — สรุปผลการทำแบบทดสอบของผู้ใช้ + กราฟ (Pie / Radar / Bar / Histogram + Normal)
// ใช้ CDN ทั้งหมด และคงคอมเมนต์ get_test_result_alert() ไว้ตามที่คุณระบุ

date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../../includes/header.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

$conn->query("SET time_zone = '+07:00'");
$page_title = get_text('page_title_view_result');

require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('alert_no_permission_user'), "danger");
    header("Location: /login");
    exit();
}

$user_id    = $_SESSION['user_id'] ?? null;
$attempt_id = $_POST['attempt_id'] ?? $_GET['attempt_id'] ?? null;

if (!$user_id || !is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('alert_invalid_attempt_id'), "danger");
    header("Location: /user");
    exit();
}

$attempt_info = [];
$test_info    = [];
$total_score_earned = 0.0;
$total_max_score    = 0.0;
$user_percentage_score = 0.0;
$pass_fail_status = 'completed';
$has_unchecked_short_answer = false;

// สำหรับ radar/bar
$sections_agg   = [];
$categories_agg = [];
$has_categories = false;

// สำหรับ histogram/normal
$all_attempt_scores = [];
$current_attempt_score = 0.0;
$test_id_for_distribution = null;

try {
    // 1) attempt
    $stmt = $conn->prepare("
        SELECT attempt_id, test_id, user_id, start_time, end_time, time_spent_seconds, is_completed
        FROM iga_user_test_attempts
        WHERE attempt_id = ? AND user_id = ?
    ");
    $stmt->bind_param("is", $attempt_id, $user_id);
    $stmt->execute();
    $attempt_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt_info) {
        set_alert(get_text('alert_attempt_not_found_or_no_permission'), "danger");
        header("Location: /user");
        exit();
    }
    if ((int)$attempt_info['is_completed'] === 0) {
        set_alert(get_text('alert_test_not_completed_yet'), "warning");
        header("Location: /user/test?test_id=" . (int)$attempt_info['test_id']);
        exit();
    }
    $test_id_for_distribution = (int)$attempt_info['test_id'];

    // 2) test info
    $stmt = $conn->prepare("
        SELECT test_id, test_name, description, duration_minutes, show_result_immediately, min_passing_score
        FROM tests
        WHERE test_id = ?
    ");
    $stmt->bind_param("i", $attempt_info['test_id']);
    $stmt->execute();
    $test_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$test_info) {
        set_alert(get_text('alert_test_info_not_found'), "danger");
        header("Location: /user");
        exit();
    }

    // 3) random mode?
    $random_mode = false;
    if ($cfgStmt = $conn->prepare("SELECT is_random_mode FROM iga_test_random_question_settings WHERE test_id = ? LIMIT 1")) {
        $cfgStmt->bind_param("i", $attempt_info['test_id']);
        $cfgStmt->execute();
        $cfg = $cfgStmt->get_result()->fetch_assoc();
        $cfgStmt->close();
        if ($cfg) $random_mode = (bool)$cfg['is_random_mode'];
    }

    // 4) ดึงข้อที่แสดงจริง + รวม Section/Category
    if ($random_mode) {
        $stmt = $conn->prepare("
            SELECT 
                q.question_id, q.question_text, q.question_type, COALESCE(q.score,0) AS question_max_score,
                ua.user_answer_text, ua.is_correct, ua.score_earned,
                s.section_id, s.section_name, s.section_order,
                q.category_id
            FROM iga_user_attempt_questions uaq
            JOIN iga_questions q  ON q.question_id = uaq.question_id
            JOIN iga_sections  s  ON s.section_id  = q.section_id
            LEFT JOIN iga_user_answers ua 
                   ON ua.attempt_id = uaq.attempt_id
                  AND ua.question_id = uaq.question_id
            WHERE uaq.attempt_id = ?
            ORDER BY uaq.shown_order ASC
        ");
        $stmt->bind_param("i", $attempt_id);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                q.question_id, q.question_text, q.question_type, COALESCE(q.score,0) AS question_max_score,
                ua.user_answer_text, ua.is_correct, ua.score_earned,
                s.section_id, s.section_name, s.section_order,
                q.category_id
            FROM iga_sections s
            JOIN iga_questions q ON s.section_id = q.section_id
            LEFT JOIN iga_user_answers ua
              ON ua.question_id = q.question_id
             AND ua.attempt_id = ?
            WHERE s.test_id = ?
            ORDER BY s.section_order ASC, q.question_order ASC
        ");
        $stmt->bind_param("ii", $attempt_id, $attempt_info['test_id']);
    }
    $stmt->execute();
    $rs = $stmt->get_result();

    while ($row = $rs->fetch_assoc()) {
        // อัตนัยค้างตรวจ?
        if ($row['question_type'] === 'short_answer' && $row['is_correct'] === null) {
            $has_unchecked_short_answer = true;
        }

        // รวมต่อ section
        $sid = (int)$row['section_id'];
        if (!isset($sections_agg[$sid])) {
            $sections_agg[$sid] = [
                'section_id'   => $sid,
                'section_name' => $row['section_name'],
                'earned'       => 0.0,
                'max'          => 0.0
            ];
        }
        $sections_agg[$sid]['max']    += (float)$row['question_max_score'];
        $sections_agg[$sid]['earned'] += (float)($row['score_earned'] ?? 0);

        // รวมต่อ category (ถ้าใช้)
        if (!is_null($row['category_id'])) {
            $has_categories = true;
            $cid = (int)$row['category_id'];
            if (!isset($categories_agg[$cid])) {
                // ชื่อ category
                $cn = null;
                $cs = $conn->prepare("SELECT category_name FROM iga_question_categories WHERE category_id = ? LIMIT 1");
                $cs->bind_param("i", $cid);
                $cs->execute();
                $c = $cs->get_result()->fetch_assoc();
                $cs->close();
                $cn = $c['category_name'] ?? ('Cat#'.$cid);

                $categories_agg[$cid] = [
                    'category_id'   => $cid,
                    'category_name' => $cn,
                    'earned'        => 0.0,
                    'max'           => 0.0
                ];
            }
            $categories_agg[$cid]['max']    += (float)$row['question_max_score'];
            $categories_agg[$cid]['earned'] += (float)($row['score_earned'] ?? 0);
        }
    }
    $stmt->close();

    // 5) max score ของ attempt
    if ($random_mode) {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(COALESCE(q.score,0)),0) AS max_sum
            FROM iga_user_attempt_questions uaq
            JOIN iga_questions q ON q.question_id = uaq.question_id
            WHERE uaq.attempt_id = ?
        ");
        $stmt->bind_param("i", $attempt_id);
    } else {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(COALESCE(q.score,0)),0) AS max_sum
            FROM iga_questions q
            JOIN iga_sections  s ON q.section_id = s.section_id
            WHERE s.test_id = ?
        ");
        $stmt->bind_param("i", $attempt_info['test_id']);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $total_max_score = (float)($row['max_sum'] ?? 0);

    // 6) earned score ของ attempt
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(COALESCE(ua.score_earned,0)),0) AS earned_sum
        FROM iga_user_answers ua
        WHERE ua.attempt_id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $total_score_earned = (float)($row['earned_sum'] ?? 0);
    $current_attempt_score = $total_score_earned;

    // 7) distribution: คะแนนรวมของทุก attempt ใน test นี้ที่เสร็จแล้ว
    if ($test_id_for_distribution) {
        $stmt = $conn->prepare("
            WITH shown AS (
                SELECT uaq.attempt_id, uaq.question_id, MIN(uaq.shown_order) AS min_shown
                FROM iga_user_attempt_questions uaq
                JOIN iga_user_test_attempts uta ON uta.attempt_id = uaq.attempt_id
                WHERE uta.test_id = ? AND uta.is_completed = 1
                GROUP BY uaq.attempt_id, uaq.question_id
            )
            SELECT s.attempt_id, SUM(COALESCE(ua.score_earned,0)) AS earned_total
            FROM shown s
            LEFT JOIN iga_user_answers ua
              ON ua.attempt_id = s.attempt_id
             AND ua.question_id = s.question_id
            GROUP BY s.attempt_id
        ");
        $stmt->bind_param("i", $test_id_for_distribution);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $sc = (float)($r['earned_total'] ?? 0);
            if (is_finite($sc)) $all_attempt_scores[] = $sc;
        }
        $stmt->close();
    }
    if (empty($all_attempt_scores) && is_finite($current_attempt_score)) {
        $all_attempt_scores[] = $current_attempt_score;
    }

} catch (Throwable $e) {
    set_alert(get_text('alert_load_result_error') . ": " . $e->getMessage(), "danger");
    header("Location: /user");
    exit();
}

// 8) ผ่าน/ไม่ผ่าน (รวม critical)
$user_percentage_score = ($total_max_score > 0) ? ($total_score_earned / $total_max_score) * 100.0 : 0.0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS critical_count
    FROM iga_user_answers ua
    JOIN iga_questions q ON ua.question_id = q.question_id
    WHERE ua.attempt_id = ? AND q.is_critical = 1 AND ua.is_correct = 0
");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$crit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($crit && (int)$crit['critical_count'] > 0) {
    $pass_fail_status = 'failed_critical';
} else {
    $effective_passing = isset($test_info['min_passing_score']) ? (float)$test_info['min_passing_score'] : 0.0;
    if ($effective_passing > 0) {
        $pass_fail_status = ($user_percentage_score + 1e-9 >= $effective_passing) ? 'passed' : 'failed';
    } else {
        $pass_fail_status = 'passed';
    }
}

echo get_alert();
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="container-fluid w-80-custom py-4">
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
        <h2 class="mb-0 text-primary-custom fs-4 fs-md-2">
            <?php echo get_text('test_result'); ?>: <?php echo htmlspecialchars($test_info['test_name']); ?>
        </h2>
        <div class="header-buttons-container d-flex justify-content-center justify-content-md-end">
            <a href="/user/history" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo get_text('back_to_test_history'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h4 class="mb-0"><?php echo get_text('test_summary'); ?></h4>
        </div>
        <div class="card-body">
            <p><strong><?php echo get_text('test_name_label'); ?>:</strong> <?php echo htmlspecialchars($test_info['test_name']); ?></p>
            <p><strong><?php echo get_text('description_label'); ?>:</strong> <?php echo nl2br(htmlspecialchars($test_info['description'])); ?></p>
            <p><strong><?php echo get_text('start_time_label'); ?>:</strong> <?php echo htmlspecialchars($attempt_info['start_time']); ?></p>
            <p><strong><?php echo get_text('end_time_label'); ?>:</strong> <?php echo htmlspecialchars($attempt_info['end_time'] ?? get_text('not_applicable_abbr')); ?></p>
            <p><strong><?php echo get_text('time_spent_label'); ?>:</strong> <?php echo formatTimeSpent($attempt_info['time_spent_seconds']); ?></p>

            <p class="fs-5">
                <strong><?php echo get_text('total_score_label'); ?>:</strong>
                <?php
                if (!empty($test_info['show_result_immediately'])) {
                    echo "<span class='badge bg-success'>"
                       . number_format($total_score_earned, 2)
                       . " / "
                       . number_format($total_max_score, 2)
                       . "</span>";
                } elseif ($has_unchecked_short_answer) {
                    echo "<span class='badge bg-warning'>"
                       . get_text('status_pending_review_short_answer')
                       . "</span>";
                } else {
                    echo "<span class='badge bg-info'>"
                       . get_text('results_not_immediate')
                       . "</span>";
                }
                ?>
            </p>

            <p class="fs-5">
                <strong><?php echo get_text('test_status_label'); ?>:</strong>
                <?php
                if (!empty($test_info['show_result_immediately'])) {
                    if ($pass_fail_status === 'passed') {
                        echo "<span class='badge bg-success'><i class='fas fa-check-circle me-2'></i>"
                           . get_text('test_status_passed')
                           . "</span>";
                    } elseif ($pass_fail_status === 'failed' || $pass_fail_status === 'failed_critical') {
                        echo "<span class='badge bg-danger'><i class='fas fa-times-circle me-2'></i>"
                           . get_text('test_status_failed')
                           . "</span>";
                    } else {
                        echo "<span class='badge bg-secondary'>"
                           . get_text('status_not_available')
                           . "</span>";
                    }
                } elseif ($has_unchecked_short_answer) {
                    echo "<span class='badge bg-warning'>"
                       . get_text('status_pending_review_short_answer')
                       . "</span>";
                } else {
                    echo "<span class='badge bg-info'>"
                       . get_text('results_not_immediate')
                       . "</span>";
                }
                ?>
            </p>
        </div>
    </div>

    <?php
    // *** คงคอมเมนต์ไว้ตามที่คุณบอก เพื่อกันปัญหา JS ไม่ถูกโหลด ***
    // echo get_test_result_alert(
    //     $has_unchecked_short_answer ?? false,
    //     $pass_fail_status ?? '',
    //     $test_info ?? [],
    // );
    ?>

    <!-- ===== กราฟของ User (เหมือนหน้าแอดมิน) ===== -->
    <hr class="my-4">

    <div class="row mb-4">
        <!-- Pie -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white">
                    <h5 class="mb-0"><?php echo get_text('pie_chart_title') ?: 'Score Breakdown'; ?></h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <canvas id="pieChart" style="max-height:300px;height:max-content;max-width:300px;"></canvas>
                    <div class="mt-3 text-center">
                        <p class="mb-0 fs-6"><strong><?php echo get_text('score_earned_label') ?: 'Earned'; ?>:</strong>
                            <span class="text-success"><?php echo htmlspecialchars(number_format($total_score_earned, 2)); ?> <strong><?php echo get_text('label_score') ?: 'Score'; ?></strong></span>
                        </p>
                        <p class="mb-0 fs-6"><strong><?php echo get_text('score_possible_label') ?: 'Possible'; ?>:</strong>
                            <span class="text-muted"><?php echo htmlspecialchars(number_format($total_max_score, 2)); ?> <strong><?php echo get_text('label_score') ?: 'Score'; ?></strong></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Switchable: Radar / Bar / Histogram(+Normal) -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php echo $has_categories ? (get_text('radar_chart_title_category') ?: 'Category performance') : (get_text('radar_chart_title') ?: 'Section performance'); ?>
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="chartTypeSelect" class="me-2 mb-0 fw-semibold"><?php echo get_text('chart_type_label') ?: 'Chart type'; ?>:</label>
                        <select id="chartTypeSelect" class="form-select form-select-sm" style="width:auto">
                            <option value="radar"><?php echo get_text('radar_chart_option') ?: 'Radar chart'; ?></option>
                            <option value="bar"><?php echo get_text('bar_chart_option') ?: 'Bar chart'; ?></option>
                            <option value="hist"><?php echo get_text('histogram_normal_curve_option') ?: 'Histogram'; ?></option>
                        </select>
                    </div>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div id="radarLegend" class="mb-2"></div>
                    <canvas id="switchableChart" style="max-height:300px;max-width:max-content;"></canvas>
                    <div class="mt-3 w-100">
                        <div id="statsBox" class="alert alert-light border d-flex flex-wrap gap-3 mb-0 d-none" style="font-size:.95rem"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
      #radarLegend { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; margin-bottom:8px; font-size:12px; }
      #radarLegend .item { display:inline-flex; align-items:center; gap:8px; border:1px solid #e0e0e0; border-radius:8px; padding:6px 8px; cursor:pointer; user-select:none; transition:opacity .15s ease; background:#fff; }
      #radarLegend .swatch { width:10px; height:10px; border:2px solid rgba(0,0,0,.2); border-radius:3px; }
      #radarLegend .item.off { opacity:.5; }
      #radarLegend .item.off .name { text-decoration:line-through; }

      #statsBox .stat-item{ display:inline-flex; gap:6px; align-items:center; padding:4px 8px; border:1px dashed #e0e0e0; border-radius:8px; background:#fff; }
      #statsBox .label{opacity:.75; font-weight:600}
      #statsBox .value{font-variant-numeric: tabular-nums}
    </style>

    <!-- Chart.js + Datalabels (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <script>
    Chart.register(ChartDataLabels);

    // ===== PIE =====
    const pieEarned = <?php echo json_encode((float)$total_score_earned); ?>;
    const pieMax    = <?php echo json_encode((float)$total_max_score); ?>;
    const pieRemain = Math.max(0, pieMax - pieEarned);

    new Chart(document.getElementById('pieChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: ['<?php echo get_text('chart_score_earned') ?: 'Earned'; ?>', '<?php echo get_text('chart_score_fail') ?: 'Remaining'; ?>'],
        datasets: [{ data: [pieEarned, pieRemain], backgroundColor: ['rgba(75, 192, 192, 0.8)', 'rgba(255, 99, 132, 0.8)'], hoverOffset: 10 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: {
            label: (ctx) => {
              let label = ctx.label ? ctx.label + ': ' : '';
              const v = (ctx.parsed !== null) ? parseFloat(ctx.parsed) : 0;
              return label + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) + ' <?php echo get_text('score_label_suffix') ?: 'pts'; ?>';
            }
          }},
          datalabels: {
            color: '#fff', borderRadius: 4, font: { weight: 'bold', size: 14 },
            formatter: (_, context) => context.dataIndex === 0
              ? `<?php echo get_text('chart_score_earned') ?: 'Earned'; ?>\n${pieEarned.toFixed(2)}`
              : `<?php echo get_text('chart_score_fail') ?: 'Remaining'; ?>\n${pieRemain.toFixed(2)}`,
            anchor: 'center', align: 'center', offset: 0
          }
        }
      }
    });

    // ===== DATA สำหรับ Radar/Bar (% ต่อ section หรือ category) =====
    const radarLabels = [];
    const radarValues = [];
    <?php if ($has_categories): ?>
        <?php foreach ($categories_agg as $cat): 
            $lbl = $cat['category_name'] ?? ('Cat#'.$cat['category_id']);
            $max = (float)$cat['max']; $earn = (float)$cat['earned'];
            $pct = $max > 0 ? ($earn / $max) * 100.0 : 0.0;
        ?>
        radarLabels.push(<?php echo json_encode($lbl); ?>);
        radarValues.push(<?php echo json_encode(round($pct,2)); ?>);
        <?php endforeach; ?>
    <?php else: ?>
        <?php foreach ($sections_agg as $sec): 
            $lbl = $sec['section_name'] ?? ('Sec#'.$sec['section_id']);
            $max = (float)$sec['max']; $earn = (float)$sec['earned'];
            $pct = $max > 0 ? ($earn / $max) * 100.0 : 0.0;
        ?>
        radarLabels.push(<?php echo json_encode($lbl); ?>);
        radarValues.push(<?php echo json_encode(round($pct,2)); ?>);
        <?php endforeach; ?>
    <?php endif; ?>

    // ===== ข้อมูลสำหรับ Histogram/Normal =====
    const allAttemptScores    = <?php echo json_encode(array_values(array_map(fn($v)=> round((float)$v, 2), $all_attempt_scores))); ?>;
    const currentAttemptScore = <?php echo json_encode(round((float)$current_attempt_score, 2)); ?>;
    const fullScoreCurrent    = <?php echo json_encode((float)$total_max_score); ?>;
    const FULL_INT            = Math.max(1, Math.ceil(fullScoreCurrent)); // 0..FULL_INT

    // Util
    function makeColor(i, a=0.8){ const h=(i*47)%360; return `hsla(${h},70%,55%,${a})`; }
    const shown = new Set(radarLabels.map((_, i)=>i));
    function subset(arr){ return arr.filter((_,i)=>shown.has(i)); }

    // Stats box
    function computeStats(values){
      const n = values.length || 0;
      if (!n) return { n:0, mean:0, median:0, sd:0, min:0, max:0 };
      const sorted = [...values].sort((a,b)=>a-b);
      const sum = values.reduce((s,v)=>s+v,0);
      const mean = sum / n;
      const median = (n % 2) ? sorted[(n-1)/2] : (sorted[n/2-1] + sorted[n/2]) / 2;
      const variance = values.reduce((s,v)=> s + Math.pow(v-mean,2), 0) / n;
      const sd = Math.sqrt(variance);
      return { n, mean, median, sd, min:sorted[0], max:sorted[n-1] };
    }
    function setStatsVisible(visible) {
      const box = document.getElementById('statsBox');
      if (!box) return;
      if (visible) box.classList.remove('d-none');
      else { box.classList.add('d-none'); box.innerHTML = ''; }
    }
    function renderStatsBox(values){
      const box = document.getElementById('statsBox');
      if (!box) return;
      const { n, mean, median, sd, min, max } = computeStats(values);
      const T = <?php echo json_encode([
        'count_label'  => get_text('count_label') ?: 'Count',
        'mean_label'   => get_text('mean_label') ?: 'Mean',
        'median_label' => get_text('median_label') ?: 'Median',
        'stddev_label' => get_text('stddev_label') ?: 'Std. Dev.',
        'min_label'    => get_text('min_label') ?: 'Min',
        'max_label'    => get_text('max_label') ?: 'Max',
        'label_score'  => get_text('label_score') ?: 'Score',
      ]); ?>;
      const fmt2 = (x)=> (isFinite(x) ? x.toFixed(2) : '-');
      box.innerHTML = `
        <span class="stat-item"><span class="label">${T.count_label}:</span><span class="value">${n}</span></span>
        <span class="stat-item"><span class="label">${T.mean_label}:</span><span class="value">${fmt2(mean)} ${T.label_score}</span></span>
        <span class="stat-item"><span class="label">${T.median_label}:</span><span class="value">${fmt2(median)} ${T.label_score}</span></span>
        <span class="stat-item"><span class="label">${T.min_label}:</span><span class="value">${fmt2(min)} ${T.label_score}</span></span>
        <span class="stat-item"><span class="label">${T.max_label}:</span><span class="value">${fmt2(max)} ${T.label_score}</span></span>
        <span class="stat-item"><span class="label">${T.stddev_label}:</span><span class="value">${fmt2(sd)} ${T.label_score}</span></span>
      `;
    }

    // Chart switcher
    let curChart = null;
    const chartCanvas = document.getElementById('switchableChart').getContext('2d');
    function destroyChart(){ if (curChart){ curChart.destroy(); curChart = null; } }

    // Radar
    function renderRadar(labels, values){
      destroyChart();
      setStatsVisible(false);
      curChart = new Chart(chartCanvas, {
        type: 'radar',
        data: { labels, datasets: [{
          label: '<?php echo get_text('radar_chart_dataset_label') ?: "Performance"; ?>',
          data: values,
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          pointBackgroundColor: 'rgba(54, 162, 235, 1)',
          pointBorderColor: '#fff'
        }]},
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display:false },
            tooltip: { callbacks:{ label:(c)=> (c.parsed.r !== null ? `${Math.round(c.parsed.r)}%` : '') } },
            datalabels: {
              color:'rgba(0,0,0,.85)', borderWidth:1, borderRadius:4,
              padding:{top:4,bottom:4,left:6,right:6},
              font:{weight:'bold',size:10},
              formatter:(v)=> (parseFloat(v).toFixed(0) + '%'),
              anchor:'center', align:'center', offset:0, clamp:true
            }
          },
          scales: {
            r: { angleLines:{ display:false }, suggestedMin:0, suggestedMax:100,
                 pointLabels:{ font:{size:10}, maxWidth:90, padding:40, color:'black' },
                 ticks:{ display:false } }
          }
        }
      });
    }

    // Bar (ปรับ % แสดง “ในแท่ง” ตรงกลาง)
    function renderBar(labels, values){
      destroyChart();
      setStatsVisible(false);
      curChart = new Chart(chartCanvas, {
        type: 'bar',
        data: { labels, datasets: [{
          label: '<?php echo get_text('bar_chart_dataset_label') ?: "Score %"; ?>',
          data: values,
          backgroundColor: values.map((_,i)=> makeColor(i, .75)),
          borderColor: values.map((_,i)=> makeColor(i, 1)),
          borderWidth: 1,
          datalabels: {
            display: true,
            anchor: 'center',
            align: 'center',
            color: '#fff',
            clamp: true,
            font: { weight: 'bold', size: 11 },
            formatter:(v)=> (parseFloat(v).toFixed(0) + '%')
          }
        }]},
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins:{
            legend:{ display:false },
            tooltip:{ callbacks:{ label:(ctx)=> `${(ctx.parsed?.y ?? 0).toFixed(0)}%` } }
          },
          scales:{
            x:{ ticks:{ autoSkip:false, maxRotation:45, minRotation:0 } },
            y:{ suggestedMin:0, suggestedMax:100, ticks:{ callback:(v)=> v + '%' } }
          }
        }
      });
    }

    // Histogram + โค้ง Normal (ทับ)
    function renderHistogramWithNormal(allScores, curScore, fullInt){
      destroyChart();
      setStatsVisible(true);
      renderStatsBox(allScores);

      const N = Math.max(1, parseInt(fullInt,10));   // x = 0..N
      const binWidth = 1;
      const labels = Array.from({length: N+1}, (_,i)=> String(i));
      const counts = new Array(N+1).fill(0);

      (allScores||[]).forEach(s => {
        if (!Number.isFinite(s)) return;
        let k = Math.floor(s / binWidth);
        k = Math.min(N, Math.max(0, k));
        counts[k]++;
      });

      // จุด you-are-here (วางเป็น dot บนแท่ง)
      const youData = new Array(N+1).fill(null);
      if (Number.isFinite(curScore)) {
        let idx = Math.min(N, Math.max(0, Math.floor(curScore/binWidth)));
        youData[idx] = counts[idx];
      }

      // โค้งระฆังทฤษฎีวางกลางช่วง
      const n = (allScores || []).length;
      const meanTheo = N / 2;
      const sdTheo   = Math.max(N / 6, 1e-6); // ±3σ ≈ ครอบ 0..N

      function pdfNorm(x, mu, s){
        const z = (x - mu) / s;
        return Math.exp(-0.5*z*z) / (s * Math.sqrt(2*Math.PI));
      }

      const curveAtBins = new Array(N+1).fill(0);
      if (n > 0) {
        for (let k=0; k<=N; k++){
          const xMid = (k + 0.5) * binWidth;
          curveAtBins[k] = pdfNorm(xMid, meanTheo, sdTheo) * n * binWidth; // สเกลเป็น "จำนวนคนต่อ bin"
        }
      }

      const peakBars  = Math.max(...counts, 1);
      const peakCurve = Math.max(...curveAtBins, 0);
      const yMax = Math.max(1, Math.ceil(Math.max(peakBars, peakCurve) * 1.1));

      const dsFreq = {
        type: 'bar',
        label: '<?php echo get_text('histogram_label') ?: 'Frequency'; ?>',
        data: counts,
        backgroundColor: 'rgba(121, 224, 255, 0.35)',
        borderColor: 'rgba(0, 20, 110, 0.8)',
        borderWidth: 1,
        barPercentage: 1.0,
        categoryPercentage: 1.0,
        grouped: false,
        yAxisID: 'y',
        order: 1
      };
      const dsCurve = {
        type: 'line',
        label: '<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>',
        data: curveAtBins,
        borderColor: 'rgba(245, 158, 11, 1)',
        backgroundColor: 'rgba(245, 158, 11, 0.10)',
        fill: true,
        pointRadius: 0,
        borderWidth: 2,
        yAxisID: 'y',
        order: 2
      };
      const dsYou = {
        type: 'line',
        label: '<?php echo get_text('you_are_here') ?: 'Your score'; ?>',
        data: youData,
        showLine: false,
        pointRadius: Number.isFinite(curScore) ? 7 : 0,
        pointHoverRadius: Number.isFinite(curScore) ? 9 : 0,
        pointBackgroundColor: 'rgba(255, 99, 132, 1)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        borderWidth: 0,
        yAxisID: 'y',
        order: 3
      };

      curChart = new Chart(chartCanvas, {
        data: { labels, datasets: [dsFreq, dsCurve, dsYou] },
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins:{
            legend:{ display:true, position:'top' },
            tooltip:{
              mode:'nearest', intersect:false,
              callbacks:{
                title:(items)=> (items?.[0]?.label ? '<?php echo get_text('label_score') ?: 'Score'; ?>: ' + items[0].label : ''),
                label:(ctx)=>{
                  const freqLabel  = '<?php echo get_text('histogram_label') ?: 'Frequency'; ?>';
                  const curveLabel = '<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>';
                  if (ctx.dataset.label === freqLabel)  return '<?php echo get_text('count_label') ?: 'Count'; ?>: ' + (ctx.parsed?.y ?? 0);
                  if (ctx.dataset.label === curveLabel) return curveLabel;
                  return ctx.dataset.label;
                }
              }
            },
            datalabels:{ display:false },
            title:{
              display:true,
              text:`μ=${meanTheo.toFixed(2)}, σ≈${sdTheo.toFixed(2)}  |  <?php echo get_text('max_score_label') ?: 'Full score'; ?>=${N}`
            }
          },
          scales:{
            x:{ title:{ display:true, text:'<?php echo get_text('label_score') ?: 'Score'; ?> (0–' + N + ')' },
                ticks:{ autoSkip:false, maxRotation:0, minRotation:0 } },
            y:{ beginAtZero:true, max:yMax,
                title:{ display:true, text:'<?php echo get_text('count_label') ?: 'Count'; ?>' },
                ticks:{ stepSize:1, callback:(v)=> Number.isInteger(v) ? v : '' } }
          }
        }
      });
    }

    // Legend toggle สำหรับ Radar/Bar
    function buildToggleLegend() {
      const box = document.getElementById('radarLegend');
      box.innerHTML = '';
      radarLabels.forEach((name, i) => {
        const item = document.createElement('div');
        item.className = 'item'; item.dataset.index = i;
        const sw = document.createElement('span'); sw.className = 'swatch';
        sw.style.background = makeColor(i, .6); sw.style.borderColor = makeColor(i, 1);
        const nm = document.createElement('span'); nm.className = 'name'; nm.textContent = name;
        item.appendChild(sw); item.appendChild(nm);
        item.addEventListener('click', () => {
          if (shown.has(i)) { shown.delete(i); item.classList.add('off'); }
          else { shown.add(i); item.classList.remove('off'); }
          if (shown.size === 0) { shown.add(i); item.classList.remove('off'); }
          rerenderCurrentChart();
        });
        box.appendChild(item);
      });
    }
    function setLegendVisible(show) {
      const el = document.getElementById('radarLegend');
      if (!el) return;
      if (show) { el.classList.remove('d-none'); el.style.display = 'flex'; }
      else { el.style.display = 'none'; el.classList.add('d-none'); }
    }

    function rerenderCurrentChart(){
      const type = document.getElementById('chartTypeSelect').value;
      if (type === 'hist')   { setLegendVisible(false); renderHistogramWithNormal(allAttemptScores, currentAttemptScore, FULL_INT); return; }
      // เหลือแค่ radar / bar / hist (ไม่มี normal-only)
      setLegendVisible(true);
      const labels = subset(radarLabels);
      const values = subset(radarValues);
      if (type === 'radar') renderRadar(labels, values);
      else renderBar(labels, values);
    }

    // init
    buildToggleLegend();
    document.getElementById('chartTypeSelect').addEventListener('change', rerenderCurrentChart);
    rerenderCurrentChart();
    </script>
    <!-- ===== จบส่วนกราฟ ===== -->

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

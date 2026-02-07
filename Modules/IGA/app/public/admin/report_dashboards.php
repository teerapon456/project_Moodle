<?php
// /admin/reports/dashboard.php
// Dashboard ภาพรวม (Associate / Applicant)
// - KPI: kpi_total_users (นับเฉพาะ u.is_active = 1) + Completed/Passed/Failed
// - กราฟ: Bar (Pass/Fail by role), Line (Trend), Pie (Role dist.),
//         Histogram + Normal curve (รวมทุก attempt ล่าสุด), Radar (เฉลี่ย % ต่อหมวด Category)
// - ฟิลเตอร์: Role / Status / Test / Date range (start_time) — ทั้งหมดผ่าน GET

require_once __DIR__ . '/../../includes/header.php';

$page_title = get_text('page_title_dashboard_report') ?: 'Dashboard Report';

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    header("Location: /login");
    exit();
}

// ---------------------------------------------------
// ฟิลเตอร์ (GET)
// ---------------------------------------------------
$input = $_GET;

$role_filter    = $input['role']        ?? '-1'; // -1 ทั้งหมด, 1 associate, 2 applicant
$status_filter  = $input['status']      ?? '-1'; // -1 ทั้งหมด, 1 Completed, 0 In progress
$test_filter_id = isset($input['test_id']) && is_numeric($input['test_id']) ? (int)$input['test_id'] : null;

$date_from = trim($input['date_from'] ?? '');
$date_to   = trim($input['date_to']   ?? '');
$df_valid  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from . ' 00:00:00' : null;
$dt_valid  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)   ? $date_to   . ' 23:59:59' : null;

// ถ้าเป็น Super_user_Recruitment ให้ล็อก role = applicant
$is_Super_user_Recruitment = has_role('Super_user_Recruitment') || (!empty($is_Super_user_Recruitment) && $is_Super_user_Recruitment);
if ($is_Super_user_Recruitment) $role_filter = '2';

// ---------------------------------------------------
// Dropdown tests
// ---------------------------------------------------
$tests = [];
try {
    $rs = $conn->query("SELECT test_id, test_name FROM iga_tests ORDER BY test_name ASC");
    if ($rs) { while ($row = $rs->fetch_assoc()) $tests[] = $row; $rs->close(); }
} catch (Throwable $e) { /* ignore */ }

// ---------------------------------------------------
// KPI: Active users (เฉพาะ is_active=1) ตาม role filter เดียวกัน
// ---------------------------------------------------
$active_total = 0;
try {
    $sqlA = "SELECT COUNT(*) AS c
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             WHERE u.is_active = 1";

    // role condition (exactly one)
    if ($is_Super_user_Recruitment) {
        // ล็อก applicant (รองรับทั้ง role_name และ role_id=5)
        $sqlA .= " AND (r.role_name = 'applicant' OR r.role_id = 5)";
    } else {
        if ($role_filter === '1') {
            $sqlA .= " AND r.role_name = 'associate'";
        } elseif ($role_filter === '2') {
            $sqlA .= " AND (r.role_name = 'applicant' OR r.role_id = 5)";
        } else {
            $sqlA .= " AND (r.role_name IN ('associate','applicant') OR r.role_id = 5)";
        }
    }

    $rsA = $conn->query($sqlA);
    if ($rsA && ($rowA = $rsA->fetch_assoc())) $active_total = (int)$rowA['c'];
    if ($rsA) $rsA->close();
} catch (Throwable $e) { /* ignore */ }

// ---------------------------------------------------
// ดึง "latest attempt ต่อผู้ใช้" ตามฟิลเตอร์ เพื่อใช้ทำกราฟ/ตาราง
// ---------------------------------------------------
$rows = [];         // latest attempt per user (ตามฟิลเตอร์)
$attemptIds = [];   // เก็บ attempt_id สำหรับใช้คำนวณ Radar (category)
try {
    $sql = "
        SELECT
            u.user_id,
            u.full_name AS user_name,
            r.role_name AS user_role,
            r.role_id   AS role_id,

            uta_latest.attempt_id,
            uta_latest.test_id,
            t.test_name,
            uta_latest.start_time,
            uta_latest.end_time,
            uta_latest.total_score,
            uta_latest.is_completed,
            t.min_passing_score,

            COALESCE(test_max_scores.max_test_score, 0) AS max_test_score,
            COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) AS max_applicable_score,
            COALESCE(critical_failures.has_critical_fail, 0) AS has_critical_fail
        FROM users u
        JOIN roles r ON u.role_id = r.role_id

        LEFT JOIN (
            SELECT uta.*
            FROM iga_user_test_attempts uta
            JOIN (
                SELECT user_id, MAX(start_time) AS max_start_time
                FROM iga_user_test_attempts
                GROUP BY user_id
            ) latest ON latest.user_id = uta.user_id
                  AND latest.max_start_time = uta.start_time
        ) AS uta_latest ON uta_latest.user_id = u.user_id

        LEFT JOIN iga_tests t ON uta_latest.test_id = t.test_id

        /* คะแนนเต็มของ test ทั้งก้อน (ใช้กรณีไม่มีรายการแสดงจริง) */
        LEFT JOIN (
            SELECT s.test_id, SUM(COALESCE(q.score,0)) AS max_test_score
            FROM iga_sections s
            JOIN iga_questions q ON s.section_id = q.section_id
            GROUP BY s.test_id
        ) AS test_max_scores ON t.test_id = test_max_scores.test_id

        /* คะแนนเต็มของ attempt ตาม 'ข้อที่แสดงจริง' เท่านั้น */
        LEFT JOIN (
            SELECT uaq.attempt_id, SUM(COALESCE(q.score,0)) AS max_attempt_score
            FROM iga_user_attempt_questions uaq
            JOIN iga_questions q ON q.question_id = uaq.question_id
            GROUP BY uaq.attempt_id
        ) AS ams ON uta_latest.attempt_id = ams.attempt_id

        /* fail critical ถ้ามีโจทย์ critical ทำผิดอย่างน้อย 1 ข้อ */
        LEFT JOIN (
            SELECT ua.attempt_id,
                   MAX(CASE WHEN q.is_critical = 1 AND ua.is_correct = 0 THEN 1 ELSE 0 END) AS has_critical_fail
            FROM iga_user_answers ua
            JOIN iga_questions q ON ua.question_id = q.question_id
            GROUP BY ua.attempt_id
        ) AS critical_failures ON uta_latest.attempt_id = critical_failures.attempt_id
    ";

    $where = [];
    $params = [];
    $types  = "";

    /* ----- ROLE (exactly one block) ----- */
    if ($is_Super_user_Recruitment) {
        $where[] = "(r.role_name='applicant' OR r.role_id=5)";
    } else {
        if ($role_filter === '1') {
            $where[] = "r.role_name='associate'";
        } elseif ($role_filter === '2') {
            $where[] = "(r.role_name='applicant' OR r.role_id=5)";
        } else {
            $where[] = "(r.role_name IN ('associate','applicant') OR r.role_id=5)";
        }
    }

    /* ----- STATUS ----- */
    if ($status_filter === '0' || $status_filter === '1') {
        $where[]  = "uta_latest.is_completed = ?";
        $params[] = (int)$status_filter;
        $types   .= "i";
    }

    /* ----- TEST ----- */
    if (!empty($test_filter_id)) {
        $where[]  = "uta_latest.test_id = ?";
        $params[] = $test_filter_id;
        $types   .= "i";
    }

    /* ----- DATE RANGE (start_time ของ latest attempt) ----- */
    if ($df_valid) { $where[]="uta_latest.start_time >= ?"; $params[]=$df_valid; $types.="s"; }
    if ($dt_valid) { $where[]="uta_latest.start_time <= ?"; $params[]=$dt_valid; $types.="s"; }

    /* ต้องมี attempt */
    $where[] = "uta_latest.attempt_id IS NOT NULL";

    if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY uta_latest.start_time DESC";

    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        if (!empty($r['attempt_id'])) $attemptIds[] = (int)$r['attempt_id'];

        // Normalize role (ครอบคลุม applicant ทั้งจากชื่อและ role_id=5)
        $role_name = strtolower($r['user_role'] ?? '');
        if ($role_name === '' && ((int)($r['role_id'] ?? 0)) === 5) {
            $role_name = 'applicant';
        }
        $r['user_role'] = $role_name;

        $rows[] = $r;
    }
    $stmt->close();
} catch (Throwable $e) {
    set_alert(get_text('error_loading_reports') . ': ' . $e->getMessage(), 'danger');
}

// ---------------------------------------------------
// ประมวลผล metrics
// ---------------------------------------------------
$byRole = [
    'associate' => ['total'=>0, 'completed'=>0, 'passed'=>0, 'failed'=>0],
    'applicant' => ['total'=>0, 'completed'=>0, 'passed'=>0, 'failed'=>0],
];
$overall = ['total'=>0, 'completed'=>0, 'passed'=>0, 'failed'=>0];

$trend = []; // 'Y-m-d' => ['started'=>n, 'completed'=>n, 'passed'=>n]

$hist_values = []; // raw earned (0..denominator ของ attempt นั้น)
$hist_full   = 0;  // max denominator เจอสูงสุด

$topTests = []; // test_id => agg

foreach ($rows as $row) {
    $overall['total']++;

    $role_name = strtolower($row['user_role'] ?? '');
    // map role_id=5 เป็น applicant หากชื่อว่าง (กันข้อมูลไม่สมบูรณ์)
    if ($role_name === '' && ((int)($row['role_id'] ?? 0)) === 5) $role_name = 'applicant';
    if (!isset($byRole[$role_name])) continue;
    $byRole[$role_name]['total']++;

    $is_completed = (int)($row['is_completed'] ?? 0) === 1;
    $den   = (float)($row['max_applicable_score'] ?? 0);
    $score = (float)($row['total_score'] ?? 0);
    $pct   = ($den > 0 ? ($score / $den) * 100.0 : 0.0);

    $pass = false;
    if ($is_completed) {
        $overall['completed']++;
        $byRole[$role_name]['completed']++;

        $minp = (float)($row['min_passing_score'] ?? 0);
        $crit = (int)($row['has_critical_fail'] ?? 0);
        $pass = ($den > 0 && $pct + 1e-9 >= $minp && $crit === 0);

        if ($pass) {
            $overall['passed']++;
            $byRole[$role_name]['passed']++;
        } else {
            $overall['failed']++;
            $byRole[$role_name]['failed']++;
        }
    }

    if (!empty($row['start_time'])) {
        $d = substr($row['start_time'], 0, 10);
        if (!isset($trend[$d])) $trend[$d] = ['started'=>0, 'completed'=>0, 'passed'=>0];
        $trend[$d]['started']++;
        if ($is_completed) {
            $trend[$d]['completed']++;
            if ($pass) $trend[$d]['passed']++;
        }
    }

    if ($den > 0) {
        $hist_values[] = $score;
        if ($den > $hist_full) $hist_full = $den;
    }

    $tid = (int)($row['test_id'] ?? 0);
    if ($tid) {
        if (!isset($topTests[$tid])) {
            $topTests[$tid] = ['name'=> $row['test_name'] ?? ('Test #' . $tid),
                               'n'=>0, 'sum_pct'=>0.0, 'completed'=>0, 'pass'=>0];
        }
        $topTests[$tid]['n']++;
        $topTests[$tid]['sum_pct'] += $pct;
        if ($is_completed) {
            $topTests[$tid]['completed']++;
            if ($pass) $topTests[$tid]['pass']++;
        }
    }
}

// Top tests table
$topTestsRows = [];
foreach ($topTests as $tid => $t) {
    $avg     = ($t['n']>0 ? $t['sum_pct']/$t['n'] : 0);
    $passRate= ($t['completed']>0 ? ($t['pass']/$t['completed'])*100.0 : 0);
    $topTestsRows[] = ['test_id'=>$tid,'test_name'=>$t['name'],'n'=>$t['n'],'completed'=>$t['completed'],'avg_pct'=>$avg,'pass_rate'=>$passRate];
}
usort($topTestsRows, fn($a,$b)=> ($b['pass_rate'] <=> $a['pass_rate']) ?: ($b['avg_pct'] <=> $a['avg_pct']));
$topTestsRows = array_slice($topTestsRows, 0, 10);

// Trend series
$trend_dates = array_keys($trend);
sort($trend_dates);
$trend_started   = array_map(fn($d)=> $trend[$d]['started'],   $trend_dates);
$trend_completed = array_map(fn($d)=> $trend[$d]['completed'], $trend_dates);
$trend_passed    = array_map(fn($d)=> $trend[$d]['passed'],    $trend_dates);

// ---------------------------------------------------
// RADAR: ค่าเฉลี่ย % ต่อ "หมวด Category" (รวม latest attempts ที่ถูกคัดมาข้างบน)
// - ใช้เฉพาะ questions ที่แสดงจริง (min shown_order ต่อคู่ attempt_id+question_id)
// ---------------------------------------------------
$radar_labels = [];
$radar_values = []; // ค่า % เฉลี่ยต่อหมวด
if (!empty($attemptIds)) {
    try {
        // ทำ IN placeholders
        $in = implode(',', array_fill(0, count($attemptIds), '?'));
        $typesR = str_repeat('i', count($attemptIds));

        $sqlR = "
            WITH shown AS (
                SELECT attempt_id, question_id, MIN(shown_order) AS min_shown
                FROM iga_user_attempt_questions
                WHERE attempt_id IN ($in)
                GROUP BY attempt_id, question_id
            )
            SELECT
                COALESCE(qc.category_id, 0) AS cat_id,
                COALESCE(qc.category_name, 'Uncategorized') AS cat_name,
                SUM(COALESCE(ua.score_earned,0)) AS earned_sum,
                SUM(COALESCE(q.score,0))        AS max_sum
            FROM shown s
            JOIN iga_questions q           ON q.question_id = s.question_id
            LEFT JOIN iga_question_categories qc ON qc.category_id = q.category_id
            LEFT JOIN iga_user_answers ua   ON ua.attempt_id = s.attempt_id AND ua.question_id = s.question_id
            GROUP BY COALESCE(qc.category_id,0), COALESCE(qc.category_name, 'Uncategorized')
            HAVING SUM(COALESCE(q.score,0)) > 0
            ORDER BY cat_name ASC
        ";

        $stmtR = $conn->prepare($sqlR);
        $stmtR->bind_param($typesR, ...$attemptIds);
        $stmtR->execute();
        $resR = $stmtR->get_result();
        while ($r = $resR->fetch_assoc()) {
            $radar_labels[] = (string)$r['cat_name'];
            $max = (float)$r['max_sum'];
            $earn= (float)$r['earned_sum'];
            $radar_values[] = ($max > 0 ? ($earn / $max) * 100.0 : 0.0);
        }
        $stmtR->close();
    } catch (Throwable $e) {
        // ถ้า error ปล่อย radar ว่าง ๆ
    }
}

echo get_alert();
?>

<div class="container-fluid w-80-custom py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0 text-primary-custom"><?php echo get_text('dashboard_heading') ?: 'Assessment Dashboard'; ?></h1>
    <a href="/admin/reports-individual" class="btn btn-outline-secondary">
      <i class="fas fa-list-ul me-2"></i><?php echo get_text('go_to_individual_list') ?: 'Individual List'; ?>
    </a>
  </div>

  <!-- ฟิลเตอร์ -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
      <strong><?php echo get_text('filters') ?: 'Filters'; ?></strong>
    </div>
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-2">
          <label class="form-label"><?php echo get_text('role_label') ?: 'Role'; ?></label>
          <select name="role" class="form-select">
            <?php if ($is_Super_user_Recruitment): ?>
              <option value="2" selected><?php echo get_text('role_applicant') ?: 'Applicant'; ?></option>
            <?php else: ?>
              <option value="-1" <?php echo ($role_filter==='-1'?'selected':''); ?>><?php echo get_text('all_roles') ?: 'All roles'; ?></option>
              <option value="1"  <?php echo ($role_filter==='1'?'selected':''); ?>><?php echo get_text('role_associate') ?: 'Associate'; ?></option>
              <option value="2"  <?php echo ($role_filter==='2'?'selected':''); ?>><?php echo get_text('role_applicant') ?: 'Applicant'; ?></option>
            <?php endif; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label"><?php echo get_text('status_label') ?: 'Status'; ?></label>
          <select name="status" class="form-select">
            <option value="-1" <?php echo ($status_filter==='-1'?'selected':''); ?>><?php echo get_text('all_status') ?: 'All'; ?></option>
            <option value="1"  <?php echo ($status_filter==='1'?'selected':''); ?>><?php echo get_text('status_completed') ?: 'Completed'; ?></option>
            <option value="0"  <?php echo ($status_filter==='0'?'selected':''); ?>><?php echo get_text('status_in_progress') ?: 'In progress'; ?></option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label"><?php echo get_text('test_name_label') ?: 'Test'; ?></label>
          <select name="test_id" class="form-select">
            <option value=""><?php echo get_text('all_tests') ?: 'All tests'; ?></option>
            <?php foreach ($tests as $t): ?>
              <option value="<?php echo (int)$t['test_id']; ?>" <?php echo ($test_filter_id===(int)$t['test_id']?'selected':''); ?>>
                <?php echo htmlspecialchars($t['test_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label"><?php echo get_text('start_date') ?: 'Start date'; ?></label>
          <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo get_text('end_date') ?: 'End date'; ?></label>
          <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>

        <div class="col-md-1 d-grid">
          <label class="form-label">&nbsp;</label>
          <button class="btn btn-success"><?php echo get_text('apply_filter') ?: 'Apply'; ?></button>
        </div>
        <div class="col-md-12">
          <a class="btn btn-link p-0" href="/admin/reports/dashboard">
            <?php echo get_text('reset_filter') ?: 'Reset filters'; ?>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <?php
      $kpi = [
        // ✅ KPI แรก: Active users เท่านั้น
        ['title'=>get_text('kpi_total_users') ?: 'Active users', 'v'=>$active_total, 'icon'=>'fa-users', 'cls'=>'bg-primary'],
        ['title'=>get_text('kpi_completed')   ?: 'Completed',     'v'=>$overall['completed'], 'icon'=>'fa-check-circle', 'cls'=>'bg-success'],
        ['title'=>get_text('kpi_passed')      ?: 'Passed',        'v'=>$overall['passed'],    'icon'=>'fa-medal', 'cls'=>'bg-info'],
        ['title'=>get_text('kpi_failed')      ?: 'Failed',        'v'=>$overall['failed'],    'icon'=>'fa-times-circle', 'cls'=>'bg-danger'],
      ];
    ?>
    <?php foreach ($kpi as $card): ?>
      <div class="col-md-3">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex align-items-center">
            <div class="me-3">
              <span class="badge <?php echo $card['cls']; ?> text-white p-3 rounded-circle">
                <i class="fas <?php echo $card['icon']; ?>"></i>
              </span>
            </div>
            <div>
              <div class="text-muted small"><?php echo $card['title']; ?></div>
              <div class="fs-4 fw-bold"><?php echo number_format($card['v']); ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row mb-4 g-3">
    <!-- Pass/Fail by Role -->
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary-custom text-white">
          <strong><?php echo get_text('pass_fail_by_role') ?: 'Pass/Fail by role'; ?></strong>
        </div>
        <div class="card-body">
          <canvas id="barPassFailRole" style="max-height:340px;"></canvas>
          <div class="small text-muted mt-2">
            <?php echo get_text('note_latest_attempt') ?: 'Based on latest attempt per user'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Completion Trend -->
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary-custom text-white">
          <strong><?php echo get_text('completion_trend') ?: 'Completion/Pass Trend'; ?></strong>
        </div>
        <div class="card-body">
          <canvas id="lineTrend" style="max-height:340px;"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4 g-3">
    <!-- Role distribution -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary-custom text-white">
          <strong><?php echo get_text('role_distribution') ?: 'Role distribution'; ?></strong>
        </div>
        <div class="card-body d-flex flex-column justify-content-center align-items-center">
          <canvas id="pieRole" style="max-height:300px; max-width:300px;"></canvas>
          <div class="small text-muted mt-2">
            <?php echo get_text('note_latest_attempt') ?: 'Based on latest attempt per user'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Histogram + Normal curve (รวม) -->
    <div class="col-md-8">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary-custom text-white d-flex justify-content-between">
          <strong><?php echo get_text('histogram_overall') ?: 'Score distribution (overall)'; ?></strong>
          <small class="text-white-50">
            <?php echo get_text('label_score') ?: 'Score'; ?> 0–<?php echo (int)ceil($hist_full ?: 1); ?>
          </small>
        </div>
        <div class="card-body">
          <canvas id="histOverall" style="max-height:340px;"></canvas>
          <div class="small text-muted mt-2">
            <?php echo get_text('note_hist_deno') ?: 'Histogram uses raw earned scores of shown iga_questions. Normal curve is scaled to bin counts.'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Radar: Average % by Category -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
      <strong><?php echo get_text('radar_chart_title_category') ?: 'Average % by Category'; ?></strong>
      <small class="text-white-50"><?php echo get_text('note_latest_attempt') ?: 'Based on latest attempt per user'; ?></small>
    </div>
    <div class="card-body">
      <?php if (!empty($radar_labels)): ?>
        <canvas id="radarCategories" style="max-height:360px;"></canvas>
      <?php else: ?>
        <div class="text-center text-muted py-3"><?php echo get_text('no_data_found') ?: 'No data'; ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top Tests -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-primary-custom text-white">
      <strong><?php echo get_text('top_tests') ?: 'Top tests by pass rate'; ?></strong>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th><?php echo get_text('test_name_label') ?: 'Test'; ?></th>
            <th><?php echo get_text('attempts_label') ?: 'Attempts (latest/users)'; ?></th>
            <th><?php echo get_text('completed_label') ?: 'Completed'; ?></th>
            <th><?php echo get_text('avg_percent_label') ?: 'Avg %'; ?></th>
            <th><?php echo get_text('pass_rate_label') ?: 'Pass rate'; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($topTestsRows)): $i=1; foreach ($topTestsRows as $r): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($r['test_name']); ?></td>
              <td><?php echo number_format($r['n']); ?></td>
              <td><?php echo number_format($r['completed']); ?></td>
              <td><?php echo number_format($r['avg_pct'], 2); ?>%</td>
              <td>
                <span class="badge <?php echo ($r['pass_rate']>=50?'bg-success':'bg-warning text-dark'); ?>">
                  <?php echo number_format($r['pass_rate'], 2); ?>%
                </span>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted"><?php echo get_text('no_data_found') ?: 'No data'; ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .bg-primary-custom { background:#0d6efd; }
  .w-80-custom { max-width: 1400px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ===== Data from PHP =====
const roleData = <?php echo json_encode([
  'associate' => $byRole['associate'],
  'applicant' => $byRole['applicant'],
]); ?>;

const trendDates   = <?php echo json_encode($trend_dates); ?>;
const trendStarted = <?php echo json_encode($trend_started); ?>;
const trendComp    = <?php echo json_encode($trend_completed); ?>;
const trendPass    = <?php echo json_encode($trend_passed); ?>;

// Histogram values (raw) + full
const histVals  = <?php echo json_encode(array_map(fn($v)=> round($v,2), $hist_values)); ?>;
const histFull  = <?php echo json_encode((int)ceil($hist_full ?: 1)); ?>;

// Radar category dataset
const radarLabels = <?php echo json_encode($radar_labels); ?>;
const radarValues = <?php echo json_encode(array_map(fn($v)=> round($v,2), $radar_values)); ?>;

// ===== Bar: Pass/Fail by Role =====
new Chart(document.getElementById('barPassFailRole').getContext('2d'), {
  type: 'bar',
  data: {
    labels: ['Associate','Applicant'],
    datasets: [
      { label: '<?php echo get_text('completed_label') ?: 'Completed'; ?>',
        data: [roleData.associate.completed, roleData.applicant.completed],
        backgroundColor: 'rgba(75, 192, 192, 0.7)' },
      { label: '<?php echo get_text('passed_label') ?: 'Passed'; ?>',
        data: [roleData.associate.passed, roleData.applicant.passed],
        backgroundColor: 'rgba(54, 162, 235, 0.7)' },
      { label: '<?php echo get_text('failed_label') ?: 'Failed'; ?>',
        data: [roleData.associate.failed, roleData.applicant.failed],
        backgroundColor: 'rgba(255, 99, 132, 0.7)' },
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{ position:'top' } },
    scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
  }
});

// ===== Line: Trend =====
new Chart(document.getElementById('lineTrend').getContext('2d'), {
  type: 'line',
  data: {
    labels: trendDates,
    datasets: [
      { label: '<?php echo get_text('started_label') ?: 'Started'; ?>',
        data: trendStarted, borderWidth:2, tension:.3 },
      { label: '<?php echo get_text('completed_label') ?: 'Completed'; ?>',
        data: trendComp, borderWidth:2, tension:.3 },
      { label: '<?php echo get_text('passed_label') ?: 'Passed'; ?>',
        data: trendPass, borderWidth:2, tension:.3 },
    ]
  },
  options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
});

// ===== Pie: Role distribution =====
new Chart(document.getElementById('pieRole').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: ['Associate','Applicant'],
    datasets: [{
      data: [roleData.associate.total, roleData.applicant.total],
      backgroundColor: ['rgba(54, 162, 235, .8)', 'rgba(255, 159, 64, .8)'],
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
});

// ===== Histogram + Normal curve (รวม) =====
(function(){
  const N = Math.max(1, parseInt(histFull,10));
  const labels = Array.from({length: N+1}, (_,i)=> String(i));
  const counts = new Array(N+1).fill(0);

  (histVals||[]).forEach(v=>{
    if (!Number.isFinite(v)) return;
    let k = Math.min(N, Math.max(0, Math.floor(v)));
    counts[k]++;
  });

  // คำนวณ mean/sd จากข้อมูลจริง แล้วสเกล pdf ให้ทาบกับความถี่ (คน/บิ้น)
  const n = histVals.length;
  const sum = histVals.reduce((s,v)=> s+v, 0);
  const mean = n ? (sum/n) : 0;
  const variance = n ? (histVals.reduce((s,v)=> s + Math.pow(v-mean,2), 0) / n) : 0;
  const sd = Math.max(Math.sqrt(variance), 1e-6);

  function pdfNorm(x, mu, s){ const z=(x-mu)/s; return Math.exp(-0.5*z*z)/(s*Math.sqrt(2*Math.PI)); }

  const curve = new Array(N+1).fill(0);
  if (n > 0) {
    for (let k=0; k<=N; k++){
      const xMid = k + 0.5; // กึ่งกลางบิ้น
      curve[k] = pdfNorm(xMid, mean, sd) * n * 1.0; // สเกลเป็น "จำนวนคนต่อบิ้น"
    }
  }

  // y สูงสุดครอบทั้งแท่งและเส้น
  const peakBars  = Math.max(...counts, 1);
  const peakCurve = Math.max(...curve, 0);
  const yMax = Math.ceil(Math.max(peakBars, peakCurve) * 1.1) || 1;

  new Chart(document.getElementById('histOverall').getContext('2d'), {
    data: {
      labels,
      datasets: [
        {
          type: 'bar',
          label: '<?php echo get_text('histogram_label') ?: 'Frequency'; ?>',
          data: counts,
          backgroundColor: 'rgba(121, 224, 255, 0.35)',
          borderColor: 'rgba(0, 20, 110, 0.8)',
          borderWidth: 1,
          barPercentage: 1.0,
          categoryPercentage: 1.0,
          yAxisID: 'y',
          order: 1
        },
        {
          type: 'line',
          label: '<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>',
          data: curve,
          borderColor: 'rgba(245, 158, 11, 1)',
          backgroundColor: 'rgba(245, 158, 11, 0.10)',
          fill: true,
          pointRadius: 0,
          borderWidth: 2,
          yAxisID: 'y',
          order: 2
        }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{ display:true, position:'top' },
        tooltip:{
          mode:'nearest', intersect:false,
          callbacks:{
            title:(items)=> (items?.[0]?.label ? '<?php echo get_text('label_score') ?: 'Score'; ?>: ' + items[0].label : ''),
            label:(ctx)=> {
              if (ctx.dataset.type === 'bar') return '<?php echo get_text('count_label') ?: 'Count'; ?>: ' + (ctx.parsed?.y ?? 0);
              return ctx.dataset.label;
            }
          }
        }
      },
      scales:{
        x:{ title:{ display:true, text:'<?php echo get_text('label_score') ?: 'Score'; ?> (0–' + N + ')' },
            ticks:{ autoSkip:false, maxRotation:0, minRotation:0 } },
        y:{ beginAtZero:true, max: yMax,
            title:{ display:true, text:'<?php echo get_text('count_label') ?: 'Count'; ?>' },
            ticks:{ stepSize: 1, callback:(v)=> Number.isInteger(v) ? v : '' } }
      }
    }
  });
})();

// ===== Radar: Average % by Category =====
if (document.getElementById('radarCategories') && radarLabels && radarLabels.length) {
  new Chart(document.getElementById('radarCategories').getContext('2d'), {
    type: 'radar',
    data: {
      labels: radarLabels,
      datasets: [{
        label: '<?php echo get_text('radar_chart_dataset_label') ?: 'Average %'; ?>',
        data: radarValues,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        pointBackgroundColor: 'rgba(54, 162, 235, 1)'
      }]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins: {
        legend: { display:false },
        tooltip: { callbacks:{ label:(c)=> (c.parsed.r !== null ? `${Math.round(c.parsed.r)}%` : '') } }
      },
      scales: {
        r: {
          angleLines:{ display:false }, suggestedMin:0, suggestedMax:100,
          pointLabels:{ font:{size:11}, maxWidth:90, padding:40, color:'black' },
          ticks:{ display:false }
        }
      }
    }
  });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

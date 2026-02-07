<?php
// user/results/index.php — แสดงผลลัพธ์แบบทดสอบ (โหมดสุ่มได้) + แผนภูมิแบบหน้าแอดมิน (ใช้ CDN ทั้งหมด)
// หมายเหตุ: เวอร์ชันนี้ "คงคอมเมนต์" get_test_result_alert() ตามที่คุณแจ้งไว้ และเพิ่มกราฟด้านล่างให้แล้ว

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
$questions_and_answers = [];
$total_score_earned    = 0.0;
$total_max_score       = 0.0;
$user_percentage_score = 0.0;
$pass_fail_status      = 'completed';
$has_unchecked_short_answer = false;

// ===== สำหรับกราฟ =====
$sections_agg = [];       // รวมคะแนนตาม Section
$all_attempt_scores = []; // ใช้ทำฮิสโตแกรม/โค้งปกติ
$current_attempt_score = 0.0;

try {
    // 1) attempt
    $stmt = $conn->prepare("
        SELECT attempt_id, test_id, user_id, start_time, end_time, time_spent_seconds, is_completed
        FROM iga_user_test_attempts
        WHERE attempt_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $attempt_id, $user_id);
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
        if ($cfg) {
            $random_mode = (bool)$cfg['is_random_mode'];
        }
    }

    // 4) ดึงคำถาม/คำตอบ + รวม Section
    if ($random_mode) {
        $stmt = $conn->prepare("
            SELECT 
                q.question_id,
                q.question_text,
                q.question_type,
                COALESCE(q.score,0) AS question_max_score,
                ua.user_answer_text,
                ua.is_correct,
                ua.score_earned,
                s.section_id,
                s.section_name,
                s.section_order
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
                q.question_id,
                q.question_text,
                q.question_type,
                COALESCE(q.score,0) AS question_max_score,
                ua.user_answer_text,
                ua.is_correct,
                ua.score_earned,
                s.section_id,
                s.section_name,
                s.section_order
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
        $qid = (int)$row['question_id'];
        $row['options'] = [];

        if ($row['question_type'] === 'multiple_choice' || $row['question_type'] === 'true_false') {
            $opt = $conn->prepare("
                SELECT option_id, option_text, is_correct
                FROM iga_question_options
                WHERE question_id = ?
                ORDER BY option_id ASC
            ");
            $opt->bind_param("i", $qid);
            $opt->execute();
            $opt_rs = $opt->get_result();
            while ($op = $opt_rs->fetch_assoc()) {
                $row['options'][] = $op;
            }
            $opt->close();
        }

        $questions_and_answers[] = $row;

        // รวม Section
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
    }
    $stmt->close();

    // 5) คะแนนเต็ม
    if ($random_mode) {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(COALESCE(q.score,0)),0) AS max_sum
            FROM iga_user_attempt_questions uaq
            JOIN iga_questions q ON q.question_id = uaq.question_id
            WHERE uaq.attempt_id = ?
        ");
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total_max_score = (float)($row['max_sum'] ?? 0);
    } else {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(COALESCE(q.score,0)),0) AS max_sum
            FROM iga_questions q
            JOIN iga_sections  s ON q.section_id = s.section_id
            WHERE s.test_id = ?
        ");
        $stmt->bind_param("i", $attempt_info['test_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total_max_score = (float)($row['max_sum'] ?? 0);
    }

    // 6) คะแนนจริงของ attempt นี้
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

    // 7) distribution ของทุก attempt ใน test นี้ (completed)
    $stmt = $conn->prepare("
        SELECT uta.attempt_id, COALESCE(SUM(COALESCE(ua.score_earned,0)),0) AS earned_total
        FROM iga_user_test_attempts uta
        LEFT JOIN iga_user_answers ua ON ua.attempt_id = uta.attempt_id
        WHERE uta.test_id = ? AND uta.is_completed = 1
        GROUP BY uta.attempt_id
    ");
    $stmt->bind_param("i", $attempt_info['test_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $sc = (float)($r['earned_total'] ?? 0);
        if (is_finite($sc)) $all_attempt_scores[] = $sc;
    }
    $stmt->close();
    if (empty($all_attempt_scores) && is_finite($current_attempt_score)) {
        $all_attempt_scores[] = $current_attempt_score;
    }

} catch (Throwable $e) {
    set_alert(get_text('alert_load_result_error') . ": " . $e->getMessage(), "danger");
    header("Location: /user");
    exit();
}

// 8) เช็คคำถามอัตนัยค้างตรวจ
foreach ($questions_and_answers as $qa) {
    if ($qa['question_type'] === 'short_answer' && $qa['is_correct'] === null) {
        $has_unchecked_short_answer = true;
        break;
    }
}

// 9) คิดสถานะผ่าน/ไม่ผ่าน
$user_percentage_score = ($total_max_score > 0)
    ? ($total_score_earned / $total_max_score) * 100.0
    : 0.0;

$pass_fail_status = 'completed';

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
        $pass_fail_status = ($user_percentage_score >= $effective_passing) ? 'passed' : 'failed';
    } else {
        $pass_fail_status = 'passed';
    }
}
?>

<?php echo get_alert(); ?>

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
    // *** สำคัญ: ตามที่คุณแจ้งไว้ ให้คงคอมเมนต์บล็อกนี้เพื่อหลีกเลี่ยงปัญหา JS ไม่โหลด ***
    // echo get_test_result_alert(
    //     $has_unchecked_short_answer ?? false,
    //     $pass_fail_status ?? '',
    //     $test_info ?? [],
    // );
    ?>

    <!-- ===== กราฟ (เหมือนหน้าแอดมิน / ใช้ CDN) ===== -->
    <hr class="my-4">

    <div class="row mb-4">
        <!-- Pie -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white">
                    <h5 class="mb-0"><?php echo get_text('pie_chart_title') ?: 'Score Breakdown'; ?></h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <canvas id="pieChart" style="max-height:300px;height:max-content;max-width:300px;"></canvas>
                    <div class="mt-3 text-center">
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_earned_label') ?: 'Earned'; ?>:</strong>
                            <span class="text-success"><?php echo htmlspecialchars(number_format($total_score_earned, 2)); ?> <strong><?php echo get_text('label_score') ?: 'Score'; ?></strong></span>
                        </p>
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_possible_label') ?: 'Possible'; ?>:</strong>
                            <span class="text-muted"><?php echo htmlspecialchars(number_format($total_max_score, 2)); ?> <strong><?php echo get_text('label_score') ?: 'Score'; ?></strong></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Radar/Bar/Histogram/Normal (สลับได้) -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php echo get_text('radar_chart_title') ?: 'Section performance'; ?>
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="chartTypeSelect" class="me-2 mb-0 fw-semibold"><?php echo get_text('chart_type_label') ?: 'Chart type'; ?>:</label>
                        <select id="chartTypeSelect" class="form-select form-select-sm" style="width:auto">
                            <option value="radar"><?php echo get_text('radar_chart_option') ?: 'Radar chart'; ?></option>
                            <option value="bar"><?php echo get_text('bar_chart_option') ?: 'Bar chart'; ?></option>
                            <option value="hist"><?php echo get_text('histogram_normal_curve_option') ?: 'Histogram'; ?></option>
                            <option value="normal"><?php echo get_text('normal_curve_option') ?: 'Normal curve'; ?></option>
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
      #radarLegend{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-bottom:8px;font-size:12px}
      #radarLegend .item{display:inline-flex;align-items:center;gap:8px;border:1px solid #e0e0e0;border-radius:8px;padding:6px 8px;cursor:pointer;user-select:none;transition:opacity .15s ease;background:#fff}
      #radarLegend .swatch{width:10px;height:10px;border:2px solid rgba(0,0,0,.2);border-radius:3px}
      #radarLegend .item.off{opacity:.5}
      #radarLegend .item.off .name{text-decoration:line-through}
      #statsBox .stat-item{display:inline-flex;gap:6px;align-items:center;padding:4px 8px;border:1px dashed #e0e0e0;border-radius:8px;background:#fff}
      #statsBox .label{opacity:.75;font-weight:600}
      #statsBox .value{font-variant-numeric:tabular-nums}
    </style>

    <!-- ===== JS CDN (ออนไลน์) : Bootstrap + Chart.js + Datalabels ===== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <script>
    // ปิด default anchor ที่เป็น '#'
    document.addEventListener('click', function(e){
      const a = e.target.closest('a[href="#"]');
      if (a) e.preventDefault();
    });

    window.addEventListener('load', function(){
      if (window.Chart && window.ChartDataLabels) Chart.register(ChartDataLabels);

      // ---------- PIE ----------
      const pieEarned = <?php echo json_encode((float)$total_score_earned); ?>;
      const pieMax    = <?php echo json_encode((float)$total_max_score); ?>;
      const pieRemain = Math.max(0, pieMax - pieEarned);

      new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
          labels: ['<?php echo get_text('chart_score_earned') ?: 'Earned'; ?>', '<?php echo get_text('chart_score_fail') ?: 'Remaining'; ?>'],
          datasets: [{ data: [pieEarned, pieRemain], backgroundColor: ['rgba(75,192,192,.85)','rgba(255,99,132,.85)'] }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top' },
            datalabels: {
              color:'#fff', borderRadius:4, font:{weight:'bold',size:14},
              formatter: (_,ctx)=> ctx.dataIndex===0
                ? `<?php echo get_text('chart_score_earned') ?: 'Earned'; ?>\n${pieEarned.toFixed(2)}`
                : `<?php echo get_text('chart_score_fail') ?: 'Remaining'; ?>\n${pieRemain.toFixed(2)}`
            }
          }
        }
      });

      // ---------- DATA: Section % ----------
      const radarLabels = [];
      const radarValues = [];
      <?php foreach ($sections_agg as $sec):
        $lbl = $sec['section_name'] ?? ('Sec#'.$sec['section_id']);
        $max = (float)$sec['max']; $earn = (float)$sec['earned'];
        $pct = $max>0 ? ($earn/$max)*100.0 : 0.0;
      ?>
      radarLabels.push(<?php echo json_encode($lbl); ?>);
      radarValues.push(<?php echo json_encode(round($pct,2)); ?>);
      <?php endforeach; ?>

      // ---------- Histogram/Normal ----------
      const allAttemptScores    = <?php echo json_encode(array_values(array_map(fn($v)=> round((float)$v, 2), $all_attempt_scores))); ?>;
      const currentAttemptScore = <?php echo json_encode(round((float)$current_attempt_score, 2)); ?>;
      const fullScoreCurrent    = <?php echo json_encode((float)$total_max_score); ?>;
      const FULL_INT            = Math.max(1, Math.ceil(fullScoreCurrent));

      const shown = new Set(radarLabels.map((_,i)=>i));
      const chartEl = document.getElementById('switchableChart');
      let switchChart = null;

      function makeColor(i,a=.8){ const h=(i*47)%360; return `hsla(${h},70%,55%,${a})`; }
      function subset(arr){ return arr.filter((_,i)=> shown.has(i)); }
      function destroy(){ if (switchChart){ switchChart.destroy(); switchChart=null; } }
      function setLegendVisible(v){ document.getElementById('radarLegend').style.display = v ? 'flex' : 'none'; }
      function setStatsVisible(v){ const b=document.getElementById('statsBox'); v ? b.classList.remove('d-none') : b.classList.add('d-none'); }

      function buildLegend(){
        const box=document.getElementById('radarLegend'); box.innerHTML='';
        radarLabels.forEach((n,i)=>{
          const item=document.createElement('div'); item.className='item'; item.dataset.index=i;
          const sw=document.createElement('span'); sw.className='swatch'; sw.style.background=makeColor(i,.6); sw.style.borderColor=makeColor(i,1);
          const nm=document.createElement('span'); nm.className='name'; nm.textContent=n;
          item.appendChild(sw); item.appendChild(nm);
          item.addEventListener('click',()=>{ if(shown.has(i)){shown.delete(i); item.classList.add('off');} else {shown.add(i); item.classList.remove('off');} if(shown.size===0){shown.add(i); item.classList.remove('off');} rerender(); });
          box.appendChild(item);
        });
      }

      function renderRadar(labels, values){
        destroy(); setLegendVisible(true); setStatsVisible(false);
        switchChart = new Chart(chartEl, {
          type:'radar',
          data:{ labels, datasets:[{ label:'<?php echo get_text('radar_chart_dataset_label') ?: 'Performance'; ?>', data:values,
            backgroundColor:'rgba(54,162,235,.2)', borderColor:'rgba(54,162,235,1)', borderWidth:2 }]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} },
                    scales:{ r:{ suggestedMin:0, suggestedMax:100, ticks:{display:false} } } }
        });
      }

      function renderBar(labels, values){
        destroy(); setLegendVisible(true); setStatsVisible(false);
        switchChart = new Chart(chartEl, {
          type:'bar',
          data:{ labels, datasets:[{ label:'<?php echo get_text('bar_chart_dataset_label') ?: 'Score %'; ?>', data:values,
            backgroundColor:values.map((_,i)=>makeColor(i,.6)), borderColor:values.map((_,i)=>makeColor(i,1)), borderWidth:1 }]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} },
                    scales:{ y:{ suggestedMin:0, suggestedMax:100, ticks:{ callback:(v)=> v+'%' } } } }
        });
      }

      function renderHistogram(){
        destroy(); setLegendVisible(false); setStatsVisible(true);
        const N=Math.max(1,parseInt(FULL_INT,10)), binW=1,
              labels=Array.from({length:N+1},(_,i)=>String(i)),
              counts=new Array(N+1).fill(0);
        (allAttemptScores||[]).forEach(s=>{ if(!Number.isFinite(s))return; let k=Math.floor(s/binW); k=Math.min(N,Math.max(0,k)); counts[k]++; });
        const youData=new Array(N+1).fill(null);
        if(Number.isFinite(currentAttemptScore)){ let idx=Math.min(N,Math.max(0,Math.floor(currentAttemptScore/binW))); youData[idx]=counts[idx]; }
        switchChart = new Chart(chartEl, {
          data:{ labels, datasets:[
            {type:'bar', label:'<?php echo get_text('histogram_label') ?: 'Frequency'; ?>', data:counts, backgroundColor:'rgba(121,224,255,.35)', borderColor:'rgba(0,20,110,.8)', borderWidth:1, barPercentage:1.0, categoryPercentage:1.0, grouped:false},
            {type:'line', label:'<?php echo get_text('you_are_here') ?: 'Your score'; ?>', data:youData, showLine:false, pointRadius:Number.isFinite(currentAttemptScore)?7:0, pointBackgroundColor:'rgba(255,99,132,1)', pointBorderColor:'#fff', pointBorderWidth:2}
          ]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:true} }, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
        });
      }

      function renderNormal(){
        destroy(); setLegendVisible(false); setStatsVisible(false);
        const N=Math.max(1,parseInt(FULL_INT,10)), n=(allAttemptScores||[]).length;
        let mean=0,sd=0; if(n>0){ mean=allAttemptScores.reduce((s,v)=>s+v,0)/n; const vp=allAttemptScores.reduce((s,v)=>s+Math.pow(v-mean,2),0)/n; sd=Math.sqrt(vp); }
        function pdf(x,mu,s){ if(!(s>0)) return 0; const z=(x-mu)/s; return Math.exp(-0.5*z*z)/(s*Math.sqrt(2*Math.PI)); }
        const step=Math.max(0.25,N/200), xs=[], ys=[];
        for(let x=0;x<=N+1e-9;x+=step){ xs.push(+x.toFixed(3)); ys.push(pdf(x,mean,sd)); }
        const youIdx=xs.reduce((b,_,i)=> Math.abs(xs[i]-currentAttemptScore) < Math.abs(xs[b]-currentAttemptScore) ? i : b, 0);
        const youData=new Array(xs.length).fill(null); youData[youIdx]=ys[youIdx];
        switchChart=new Chart(chartEl,{
          data:{ labels:xs.map(v=>String(v)), datasets:[
            {type:'line', label:'<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>', data:ys, borderColor:'rgba(245,158,11,1)', backgroundColor:'rgba(245,158,11,.10)', fill:false, pointRadius:0, borderWidth:2},
            {type:'line', label:'<?php echo get_text('you_are_here') ?: 'You are here'; ?>', data:youData, showLine:false, pointRadius:6, pointBackgroundColor:'rgba(220,38,38,1)', pointBorderColor:'#fff', pointBorderWidth:2}
          ]},
          options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:true} } }
        });
      }

      function rerender(){
        const type=document.getElementById('chartTypeSelect').value;
        const labels=subset(radarLabels), values=subset(radarValues);
        if(type==='radar') return renderRadar(labels, values);
        if(type==='bar')  return renderBar(labels, values);
        if(type==='hist') return renderHistogram();
        return renderNormal();
      }

      // เตรียม legend + แสดงกราฟเริ่มต้น
      const legend = document.getElementById('radarLegend');
      (function buildLegend(){
        legend.innerHTML='';
        radarLabels.forEach((n,i)=>{
          const item=document.createElement('div'); item.className='item'; item.dataset.index=i;
          const sw=document.createElement('span'); sw.className='swatch'; sw.style.background=makeColor(i,.6); sw.style.borderColor=makeColor(i,1);
          const nm=document.createElement('span'); nm.className='name'; nm.textContent=n;
          item.appendChild(sw); item.appendChild(nm);
          item.addEventListener('click',()=>{ if(shown.has(i)){shown.delete(i); item.classList.add('off');} else {shown.add(i); item.classList.remove('off');} if(shown.size===0){shown.add(i); item.classList.remove('off');} rerender(); });
          legend.appendChild(item);
        });
      })();

      document.getElementById('chartTypeSelect').addEventListener('change', rerender);
      rerender();
    });
    </script>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

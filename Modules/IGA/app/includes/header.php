<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
  session_start();

  if (!isset($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
  }
}

require_once __DIR__ . '/functions.php';

$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$is_test_page = ($current_script === 'take_test.php');
$is_language_blocked = $is_test_page;

if (isset($_GET['lang'])) {
  $current_script = basename($_SERVER['PHP_SELF'] ?? '');
  $is_test_page = ($current_script === 'take_test.php');

  if ($is_test_page) {
    $current_url_params = $_GET;
    unset($current_url_params['lang']);
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($current_url_params)) {
      $query_string = http_build_query($current_url_params);
      if (!empty($query_string)) {
        $redirect_url .= '?' . $query_string;
      }
    }
    set_alert(get_text('alert_language_switch_disabled_during_test') ?? 'Language switching is disabled during the test.', 'warning');
    header("Location: " . $redirect_url);
    exit();
  } else {
    $new_lang = htmlspecialchars(trim($_GET['lang']));
    set_language($new_lang);
    $current_url_params = $_GET;
    unset($current_url_params['lang']);
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($current_url_params)) {
      $query_string = http_build_query($current_url_params);
      if (!empty($query_string)) {
        $redirect_url .= '?' . $query_string;
      }
    }
    header("Location: " . $redirect_url);
    exit();
  }
}

if (isset($_SESSION['user_id']) && $conn) {
  $current_user_id = $_SESSION['user_id'];
  $conn->query("SET @user_id = '" . $conn->real_escape_string($current_user_id) . "'");

  $stmt_active = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
  $stmt_active->bind_param("s", $current_user_id);
  $stmt_active->execute();
  $result_active = $stmt_active->get_result();
  $user_data = $result_active->fetch_assoc();
  $stmt_active->close();

  if ($user_data && $user_data['is_active'] == 0) {
    session_unset();
    session_destroy();
    set_alert(get_text('alert_account_deactivated'), "danger");
    header("Location: /login");
    exit();
  }
} else {
  $conn->query("SET @user_id = NULL");
}
$page_title = $page_title ?? get_text('app_name') . " System";
$current_username = $_SESSION['username'] ?? 'Guest';
$current_role_name = $_SESSION['role_name'] ?? 'guest';
$current_full_name = $_SESSION['full_name'] ?? $current_username;
$is_super_user = ($current_role_name === 'super_user');
$is_editor = ($current_role_name === 'editor');
$is_Super_user_Recruitment = ($current_role_name === 'Super_user_Recruitment');

// Fetch additional user data for the modal
$user_profile_data = [];
if (isset($current_user_id) && $conn) {
  // Corrected query to join only with roles and select all from users
  $stmt_profile = $conn->prepare("SELECT u.*, r.role_name, el.level_code
                                       FROM users u
                                       LEFT JOIN roles r ON u.role_id = r.role_id
                                       LEFT JOIN emplevelcode el ON u.emplevel_id = el.level_id
                                       WHERE u.user_id = ?");
  $stmt_profile->bind_param("s", $current_user_id);
  $stmt_profile->execute();
  $result_profile = $stmt_profile->get_result();
  $user_profile_data = $result_profile->fetch_assoc();
  $stmt_profile->close();
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang']); ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $_SESSION['_csrf_token'] ?? ''; ?>">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="icon" type="image/png" href="/images/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap">
  <link rel="stylesheet" href="/css/custom.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    :root {
      --primary-color: #A21D21;
      --accent-color: #C0C0C0;
      --text-light: #FFFFFF;
      --hover-bg: rgba(255, 255, 255, 0.1);
      --transition-speed: 0.2s;
      --active-color: #ffd700;
      /* Gold color for active state */
    }

    body {
      font-family: 'Kanit', sans-serif;
    }

    .navbar-custom {
      background: var(--primary-color);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      padding: 0.4rem 1.5rem;
      position: relative;
      z-index: 1000;
    }

    .navbar-container {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      width: 100%;
    }

    .navbar-brand {
      font-weight: 500;
      font-size: 1.2rem;
      display: inline-flex;
      align-items: center;
      color: var(--text-light);
      white-space: nowrap;
      padding: 0.3rem 0;
      margin-right: 1.5rem;
      position: relative;
      flex-shrink: 0;
    }

    .navbar-brand::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: var(--accent-color);
      transition: width var(--transition-speed) ease;
    }

    .navbar-brand:hover::after {
      width: 100%;
    }

    .navbar-brand i {
      color: var(--accent-color);
      margin-right: 0.6rem;
      font-size: 1.4rem;
      transition: transform 0.2s ease;
      flex-shrink: 0;
    }

    .navbar-brand:hover i {
      transform: rotate(-5deg) scale(1.1);
    }

    .navbar-nav {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      margin: 0;
      padding: 0.2rem 0;
    }

    .navbar-nav::-webkit-scrollbar {
      display: none;
    }

    .nav-item {
      white-space: nowrap;
      flex-shrink: 0;
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.9);
      padding: 0.5rem 0.9rem;
      margin: 0 0.15rem;
      border-radius: 4px;
      transition: all var(--transition-speed) ease;
      font-weight: 400;
      position: relative;
      display: inline-block;
      font-size: 0.95rem;
    }

    /* Active state for current page */
    .nav-link.active {
      color: var(--active-color);
      font-weight: 500;
    }

    .nav-link.active::before {
      width: 70% !important;
      background-color: var(--active-color);
    }

    .nav-link::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background-color: var(--accent-color);
      transition: all var(--transition-speed) ease;
      transform: translateX(-50%);
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--text-light);
      background-color: var(--hover-bg);
      transform: none;
    }

    .nav-link:hover::before,
    .nav-link:focus::before {
      width: 70%;
    }

    .navbar-toggler {
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 0.35rem 0.6rem;
      transition: all var(--transition-speed) ease;
      margin-left: 0.5rem;
      flex-shrink: 0;
    }

    .navbar-toggler:hover {
      background-color: var(--hover-bg);
      transform: none;
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
      width: 1.2em;
      height: 1.2em;
    }

    .dropdown-menu {
      border: none;
      border-radius: 6px;
      background-color: #fff;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 0.4rem 0;
      margin-top: 0.4rem;
      min-width: 200px;
      display: none;
    }

    .dropdown.show .dropdown-menu {
      display: block;
      /* Show when parent has 'show' class */
    }

    .dropdown-item {
      padding: 0.5rem 1.25rem;
      transition: all var(--transition-speed) ease;
      color: #333;
      font-weight: 400;
      position: relative;
      font-size: 0.9rem;
    }

    .dropdown-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 3px;
      height: 100%;
      background-color: var(--primary-color);
      transform: scaleY(0);
      transform-origin: top;
      transition: transform var(--transition-speed) ease;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
      background-color: #f8f9fa;
      color: var(--primary-color);
      padding-left: 1.5rem;
      text-decoration: none;
    }

    .dropdown-item:hover::before,
    .dropdown-item:focus::before {
      transform: scaleY(1);
    }

    .dropdown-toggle::after {
      vertical-align: middle;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
      .navbar-nav {
        overflow-x: auto;
        padding-bottom: 0.5rem;
      }

      .nav-link {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
      }

      .language-switcher {
        margin: 0.5rem 0;
      }
    }

    /* Minimal Card Design for Modal */
    .profile-card {
      background-color: #f8f9fa;
      /* Light grey background */
      border: none;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .profile-row {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid #e9ecef;
      /* Subtle divider */
    }

    .profile-row:last-child {
      border-bottom: none;
    }

    .profile-row i {
      color: var(--primary-color);
      margin-right: 1rem;
      width: 24px;
      text-align: center;
    }

    .profile-row span.label {
      font-weight: 500;
      color: #6c757d;
      /* Grey text for labels */
      min-width: 120px;
      /* Align labels */
    }

    .profile-row span.value {
      font-weight: 400;
      color: #212529;
      /* Dark text for values */
    }

    body.hide-scrollbar {
      /* Firefox */
      scrollbar-width: none;
      /* IE / Edge legacy */
      -ms-overflow-style: none;
    }

    body.hide-scrollbar::-webkit-scrollbar {
      display: none;
      /* Chrome, Safari, Edge (Chromium) */
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100 hide-scrollbar" style="overflow: auto; max-height: 400px;">
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
      <a class="navbar-brand" href="<?php echo ($current_role_name === 'admin' || $current_role_name === 'super_user' || $current_role_name === 'editor' || $current_role_name === 'Super_user_Recruitment' ? '/admin' : '/user'); ?>">
        <i class="fas fa-clipboard-check"></i>
        <span class="d-none d-sm-inline"><?php echo get_text('app_name'); ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if ($current_role_name === 'admin' || $current_role_name === 'super_user' || $current_role_name === 'editor' || $current_role_name === 'Super_user_Recruitment'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('admin_dashboard_title_partial')) !== false ? 'active' : '') ?>"
                href="/admin">
                <?php echo get_text('admin_dashboard_menu'); ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('manage_tests_title_partial')) !== false || strpos($page_title, get_text('sections_questions_title_partial')) !== false ? 'active' : '') . ($is_super_user ? ' disabled-nav-link' : ''); ?>"
                href="tests">
                <?php echo get_text('manage_tests_menu'); ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('manage_users_title_partial')) !== false ? 'active' : '') . ($is_super_user || $is_editor ? ' disabled-nav-link' : ''); ?>"
                href="users">
                <?php echo get_text('manage_users_menu'); ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('review_short_answers_title_partial')) !== false ? 'active' : '') . ($is_super_user || $is_editor || $is_Super_user_Recruitment ? ' disabled-nav-link' : ''); ?>"
                href="review-answers">
                <?php echo get_text('review_short_answers_menu'); ?>
              </a>
            </li>
          <?php elseif ($current_role_name === 'associate' || $current_role_name === 'applicant'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('user_dashboard_title_partial')) !== false ? 'active' : ''); ?>" href="/user"><?php echo get_text('user_dashboard_menu'); ?></a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (strpos($page_title, get_text('my_test_history_title_partial')) !== false ? 'active' : ''); ?>" href="/user/history"><?php echo get_text('my_test_history_menu'); ?></a>
            </li>
          <?php endif; ?>
        </ul>
        <div class="d-flex flex-column flex-md-row align-items-md-center ms-auto">
          <div class="d-flex flex-column flex-md-row align-items-md-center ms-auto">

            <?php if ($current_role_name === 'admin' || $current_role_name === 'Super_user_Recruitment'):?>
              <div class="dropdown mb-2 mb-md-0 me-md-2 w-100 w-md-auto">
                <a class="nav-link dropdown-toggle d-flex align-items-center"
                  href="#"
                  id="reportsDropdownRight"
                  role="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                  data-bs-display="static">
                  <i class="fas fa-chart-bar me-1"></i> <?php echo get_text('view_reports_menu'); ?>
                </a>
                
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="reportsDropdownRight">
                  <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center <?php echo ($current_script === 'view_reports.php' ? 'active' : '') ?>" href="reports">
                      <span>
                        <i class="fas fa-chart-line me-2"></i> <?php echo get_text('report_overall_menu') ?? 'Overall Reports'; ?>
                      </span>
                      <?php if ($current_script === 'view_reports.php'): ?>
                        <i class="fas fa-check"></i>
                      <?php endif; ?>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item d-flex justify-content-between align-items-center <?php echo ($current_script === 'view_reports_individual.php' ? 'active' : '') ?>" href="reports-individual">
                      <span>
                        <i class="fas fa-file-invoice me-2"></i> <?php echo get_text('report_individual_menu') ?? 'Detailed Reports'; ?>
                      </span>
                      <?php if ($current_script === 'view_reports_individual.php'): ?>
                        <i class="fas fa-check"></i>
                      <?php endif; ?>
                    </a>
                  </li>
                </ul>
                </div>
            <?php endif; ?>
          </div>

          <div class="dropdown mb-2 mb-md-0 me-md-2 w-100 w-md-auto">
            <a class="nav-link dropdown-toggle d-flex align-items-center <?php echo $is_language_blocked ? 'disabled' : ''; ?>" href="#" id="languageDropdown" role="button" <?php echo $is_language_blocked ? 'aria-disabled="true"' : 'data-bs-toggle="dropdown"'; ?> aria-expanded="false" style="<?php echo $is_language_blocked ? 'pointer-events: none; opacity: 0.7;' : ''; ?>">
              <i class="fas fa-globe me-1"></i>
              <?php
              $current_lang = $_SESSION['lang'] ?? 'th';
              echo $current_lang === 'th' ? 'ไทย' : ($current_lang === 'en' ? 'English' : 'မြန်မာ');
              ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown" <?php echo $is_language_blocked ? 'style="display:none;"' : ''; ?>>
              <li>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="px-2">
                  <?php
                  foreach ($_GET as $key => $value) {
                    if ($key !== 'lang') {
                      echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                  }
                  ?>
                  <button type="submit" name="lang" value="th" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'th' ? 'active' : ''; ?>">
                    ไทย
                    <?php if ($current_lang === 'th'): ?>
                      <i class="fas fa-check"></i>
                    <?php endif; ?>
                  </button>
                  <button type="submit" name="lang" value="en" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'en' ? 'active' : ''; ?>">
                    English
                    <?php if ($current_lang === 'en'): ?>
                      <i class="fas fa-check"></i>
                    <?php endif; ?>
                  </button>
                  <button type="submit" name="lang" value="my" class="dropdown-item d-flex justify-content-between align-items-center <?php echo $current_lang === 'my' ? 'active' : ''; ?>">
                    မြန်မာ
                    <?php if ($current_lang === 'my'): ?>
                      <i class="fas fa-check"></i>
                    <?php endif; ?>
                  </button>
                </form>
              </li>
            </ul>
          </div>
          <div class="dropdown mt-2 mt-md-0 w-100 w-md-auto">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" <?php echo $is_language_blocked ? 'aria-disabled="true"' : 'data-bs-toggle="dropdown"'; ?> aria-expanded="false" style="<?php echo $is_language_blocked ? 'pointer-events: none; opacity: 0.7;' : ''; ?>">
              <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($current_full_name); ?> <small> (<?php echo htmlspecialchars($current_role_name); ?></small>)
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal">
                  <i class="fas fa-user me-2"></i><?php echo get_text('profile_menu'); ?>
                </button>
              </li>
              <?php if (isset($current_role_name) && ($current_role_name === 'admin')): ?>
                <li><a class="dropdown-item" href="/admin/iga_email_templates.php"><i class="fas fa-envelope me-2"></i><?php echo get_text('email_management'); ?></a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="/self-reset"><i class="fas fa-key me-2"></i><? echo get_text('reset_password') ?></a></li>
              <li>
                <hr class="dropdown-divider m-0">
              </li>
              <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i><?php echo get_text('logout_button'); ?></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <main class="flex-grow-1 container mt-4">
    <?php echo get_alert(); ?>

    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="profileModalLabel">
              <i class="fas fa-id-card-alt me-2"></i><?php echo get_text('profile_menu'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="profile-card">
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-user-tag me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('username'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['username'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-user-circle me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('full_name'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['full_name'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-envelope me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('email'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['email'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-briefcase me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('role'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($current_role_name); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-layer-group me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('label_emplevel'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['level_code'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-venus-mars me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('gender'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['Gender'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-birthday-cake me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('birth_date'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['BirthDate'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-flag me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('nationality'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['Nationality'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-users-cog me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('emp_type'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['EmpType'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="profile-row d-flex align-items-center">
                    <i class="fas fa-building me-2"></i>
                    <div>
                      <div class="label fw-semibold"><?php echo get_text('org_unit_name'); ?></div>
                      <div class="value"><?php echo htmlspecialchars($user_profile_data['OrgUnitName'] ?? '-'); ?></div>
                    </div>
                  </div>
                </div>

              </div></div></div><div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <?php echo get_text('close'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>
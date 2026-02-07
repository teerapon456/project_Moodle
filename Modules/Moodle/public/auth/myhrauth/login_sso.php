<?php
define('CLI_SCRIPT', false); // This is a web script
require('../../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php'); // เพิ่มบรรทัดนี้เพื่อเรียกใช้ user_create_user()

$token = optional_param('token', '', PARAM_ALPHANUM);

if (empty($token)) {
    redirect($CFG->wwwroot, 'No token provided.', 5);
}

// Connect to Portal DB
$dbHost = 'db';
$dbName = 'myhr_portal';
$dbUser = 'myhr_user';
$dbPass = 'MyHR_S3cur3_P@ss_2026!';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Validate Token
    $stmt = $pdo->prepare("SELECT * FROM sso_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $sso = $stmt->fetch();

    if (!$sso) {
        throw new Exception("Invalid or expired token.");
    }

    // 2. Get User Details
    $userId = $sso['user_id'];
    $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        throw new Exception("User not found in portal.");
    }

    $username = $user['username'];

    // 3. Login to Moodle
    $userObj = get_complete_user_data('username', $username);

    if ($userObj) {
        // ถ้ามี Session เดิมค้างอยู่ ให้ Logout ก่อน เพื่อเปลี่ยน User ใหม่ตาม Token
        if (isloggedin() && !isguestuser()) {
            \core\session\manager::terminate_current();
        }

        // User exists in Moodle -> Log them in
        complete_user_login($userObj);

        // Mark token as used
        $pdo->prepare("DELETE FROM sso_tokens WHERE token = ?")->execute([$token]);

        // Redirect to Home
        redirect($CFG->wwwroot);
    } else {
        // User does NOT exist in Moodle.
        // Fetch full user data for creation
        $stmtDetails = $pdo->prepare("SELECT firstname, lastname, email FROM moodle_users WHERE username = ?");
        $stmtDetails->execute([$username]);
        $details = $stmtDetails->fetch();

        if ($details) {
            $newUser = new stdClass();
            $newUser->username = $username;
            $newUser->firstname = $details['firstname'];
            $newUser->lastname = $details['lastname'];
            $newUser->email = $details['email'];
            $newUser->auth = 'db';
            $newUser->mnethostid = $CFG->mnet_localhost_id;
            $newUser->confirmed = 1;
            $newUser->lang = $CFG->lang;

            // สร้าง User ใหม่
            $userId = user_create_user($newUser, false, false);

            // Now log in
            $userObj = get_complete_user_data('id', $userId);
            complete_user_login($userObj);

            // Mark token as used
            $pdo->prepare("DELETE FROM sso_tokens WHERE token = ?")->execute([$token]);

            redirect($CFG->wwwroot);
        } else {
            throw new Exception("Could not retrieve user details for creation.");
        }
    }
} catch (Exception $e) {
    redirect($CFG->wwwroot, 'SSO Failed: ' . $e->getMessage(), 10);
}

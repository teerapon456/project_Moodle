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
$dbHost = getenv('DB_HOST') ?: 'db';
$dbName = getenv('DB_NAME') ?: 'myhr_portal';
$dbUser = getenv('DB_USER') ?: 'myhr_user';
$dbPass = getenv('DB_PASS') ?: 'MyHR_S3cur3_P@ss_2026!';

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

    // 3. Login to Moodle - Sync User Data

    // Fetch full user data from users table (JOIN with iga_orgunit for OrgCode)
    $stmtDetails = $pdo->prepare("
        SELECT u.fullname, u.email, u.Level3Name, u.password_hash, o.OrgCode, e.level_code
        FROM users u
        LEFT JOIN iga_orgunit o ON u.OrgID = o.OrgID
        LEFT JOIN emplevelcode e ON u.emplevel_id = e.level_id
        WHERE u.username = ?
    ");
    $stmtDetails->execute([$username]);
    $details = $stmtDetails->fetch();

    if ($details) {
        // Prepare user data mapping
        $fullnameParts = explode(' ', trim($details['fullname'] ?? ''), 2);
        $firstname = $fullnameParts[0] ?? $username;
        $lastname = $fullnameParts[1] ?? '.'; // Moodle requires lastname
        $email = $details['email'];
        $department = $details['Level3Name'] ?? ''; // Changed from Level3Code
        $institution = $details['OrgCode'] ?? '';
        $idnumber = $details['level_code'] ?? '';
        $passwordHash = $details['password_hash'] ?? '';

        // Check if user exists in Moodle
        $userObj = get_complete_user_data('username', $username);

        if ($userObj) {
            // User exists -> Update data
            $updateUser = new stdClass();
            $updateUser->id = $userObj->id;
            $updateUser->firstname = $firstname;
            $updateUser->lastname = $lastname;
            $updateUser->email = $email;
            $updateUser->department = $department;
            $updateUser->institution = $institution;
            $updateUser->idnumber = $idnumber;

            // Perform update
            user_update_user($updateUser);

            // Sync password (direct DB update to avoid re-hashing)
            if (!empty($passwordHash)) {
                global $DB;
                $DB->set_field('user', 'password', $passwordHash, ['id' => $userObj->id]);
            }
        } else {
            // User does NOT exist -> Create new user
            $newUser = new stdClass();
            $newUser->username = $username;
            $newUser->firstname = $firstname;
            $newUser->lastname = $lastname;
            $newUser->email = $email;
            $newUser->department = $department;
            $newUser->institution = $institution;
            $newUser->idnumber = $idnumber;
            $newUser->auth = 'db';
            $newUser->mnethostid = $CFG->mnet_localhost_id;
            $newUser->confirmed = 1;
            $newUser->lang = $CFG->lang;

            // Create user
            $userId = user_create_user($newUser, false, false);

            // Sync password (direct DB update to avoid re-hashing)
            if (!empty($passwordHash)) {
                global $DB;
                $DB->set_field('user', 'password', $passwordHash, ['id' => $userId]);
            }
        }

        // Load user data if redirected from creation
        if (!isset($userObj)) {
            $userObj = get_complete_user_data('username', $username);
        }

        // --- Session Handling Logic ---
        global $USER;
        if (!isloggedin() || isguestuser() || $USER->username !== $username) {
            // Log in if not logged in, if guest, or if a different user
            complete_user_login($userObj);
        }

        // Mark token as used
        $pdo->prepare("DELETE FROM sso_tokens WHERE token = ?")->execute([$token]);

        // Fallback: Redirect to Moodle home
        redirect($CFG->wwwroot);
    } else {
        throw new Exception("Could not retrieve user details from Portal DB.");
    }
} catch (Exception $e) {
    redirect($CFG->wwwroot, 'SSO Failed: ' . $e->getMessage(), 10);
}

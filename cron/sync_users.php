<?php
// cron/sync_users.php

// 1. Load Portal Database Connection
require_once __DIR__ . '/../core/Database/Database.php';

// Moodle Database Config (Hardcoded as per environment)
$moodleHost = 'myhr-moodle-db'; // Docker internal hostname
$moodleDb   = 'moodle';
$moodleUser = 'root';
$moodlePass = 'R00t_S3cur3_P@ss_2026!';

echo "[SYNC] Starting User Synchronization at " . date('Y-m-d H:i:s') . "\n";

try {
    // ---------------------------------------------------------
    // 1. Connect to Databases
    // ---------------------------------------------------------

    // Connect to Portal DB
    $db = new Database();
    $portalConn = $db->getConnection();
    $portalConn->exec("SET NAMES 'utf8mb4'"); // Force UTF8mb4
    echo "[SYNC] Connected to Portal DB.\n";

    // Connect to Moodle DB
    $dsn = "mysql:host=$moodleHost;dbname=$moodleDb;charset=utf8mb4";
    $moodleConn = new PDO($dsn, $moodleUser, $moodlePass);
    $moodleConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $moodleConn->exec("SET NAMES 'utf8mb4'"); // Force UTF8mb4
    echo "[SYNC] Connected to Moodle DB.\n";

    // ---------------------------------------------------------
    // 2. Fetch Users from Portal
    // ---------------------------------------------------------
    // Fetch all needed fields. 
    // We map:
    // - fullname -> firstname, lastname
    // - Level3Name -> department
    // - OrgUnitName -> institution
    // - is_active -> suspended
    $sql = "SELECT id, username, email, fullname, Level3Name, OrgUnitName, is_active FROM users";
    $stmt = $portalConn->prepare($sql);
    $stmt->execute();
    $portalUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[SYNC] Found " . count($portalUsers) . " users in Portal.\n";

    $stats = ['created' => 0, 'updated' => 0, 'suspended' => 0, 'errors' => 0];

    // ---------------------------------------------------------
    // 3. Sync Logic
    // ---------------------------------------------------------
    foreach ($portalUsers as $user) {
        $username = trim(strtolower($user['username']));
        $email = trim(strtolower($user['email']));

        // Skip invalid users
        if (empty($username) || empty($email)) {
            continue;
        }

        // Split Fullname
        $parts = explode(' ', trim($user['fullname']));
        $firstname = array_shift($parts) ?: 'User';
        $lastname = implode(' ', $parts) ?: '-'; // Fallback if no last name

        $department = $user['Level3Name'] ?: '';
        $institution = $user['OrgUnitName'] ?: '';
        $suspended = ($user['is_active'] == 1) ? 0 : 1;
        $authMethod = 'myhrauth'; // Force SSO plugin

        try {
            // Check if user exists in Moodle
            $checkStmt = $moodleConn->prepare("SELECT id, username, auth, suspended, department, institution FROM mdl_user WHERE username = ?");
            $checkStmt->execute([$username]);
            $moodleUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($moodleUser) {
                // UPDATE existing user
                $updateSql = "UPDATE mdl_user SET 
                    email = ?, 
                    firstname = ?, 
                    lastname = ?, 
                    department = ?, 
                    institution = ?, 
                    suspended = ?,
                    timemodified = ?
                    WHERE id = ?";
                $updateStmt = $moodleConn->prepare($updateSql);
                $updateStmt->execute([
                    $email,
                    $firstname,
                    $lastname,
                    $department,
                    $institution,
                    $suspended,
                    time(),
                    $moodleUser['id']
                ]);
                $stats['updated']++;
                if ($suspended === 1) $stats['suspended']++;
            } else {
                // CREATE new user
                // Moodle requires a password. We'll generate a random hash since they use SSO.
                // But we must respect Moodle's password policies if checked, usually handled by direct insert okay.
                $passwordHash = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT); // Dummy password

                $insertSql = "INSERT INTO mdl_user (
                    auth, confirmed, policyagreed, deleted, suspended, mnethostid, 
                    username, password, email, firstname, lastname, 
                    department, institution, city, country, lang, 
                    timezone, firstaccess, lastaccess, timecreated, timemodified
                ) VALUES (
                    ?, 1, 0, 0, ?, 1, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, 'Bangkok', 'TH', 'th', 
                    'Asia/Bangkok', 0, 0, ?, ?
                )";

                $insertStmt = $moodleConn->prepare($insertSql);
                $insertStmt->execute([
                    $authMethod,
                    $suspended,
                    $username,
                    $passwordHash,
                    $email,
                    $firstname,
                    $lastname,
                    $department,
                    $institution,
                    time(),
                    time()
                ]);
                $stats['created']++;
            }
        } catch (Exception $e) {
            echo "[ERROR] Failed to sync user {$username}: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }

    // ---------------------------------------------------------
    // 4. Report
    // ---------------------------------------------------------
    echo "[SYNC] Completed.\n";
    echo "Summary:\n";
    echo "- Created: {$stats['created']}\n";
    echo "- Updated: {$stats['updated']}\n";
    echo "- Suspended: {$stats['suspended']}\n";
    echo "- Errors: {$stats['errors']}\n";
} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

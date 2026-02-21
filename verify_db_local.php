<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=myhr_portal', 'myhr_user', 'MyHR_S3cur3_P@ss_2026!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_logins LIKE 'details'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE user_logins ADD COLUMN details TEXT NULL");
        echo "Column 'details' added successfully.\n";
    } else {
        echo "Column 'details' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

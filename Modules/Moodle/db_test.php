<?php
$host = 'db';
$user = 'myhr_user';
$pass = 'MyHR_S3cur3_P@ss_2026!';
$db   = 'moodle';

echo "Attempting to connect to $host as $user...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error . "\n");
    }
    echo "Connection successful!\n";
    $mysqli->close();
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
}

<?php
/**
 * config.php (portable + hardened)
 * - Auto-detects project root for both host layout (/srv/iga/app/includes)
 * and container layout (/var/www/html/includes).
 * - Autoloads Composer (supports /vendor and /app/vendor).
 * - Loads .env from root or /app (whichever is found), overwriting any existing
 * environment variables to prevent issues with empty cached values.
 * - Defines SMTP variables and sets Timezone.
 */

//// 0) Tiny bootstrap log to /tmp for early diagnostics (always writable)
$BOOT_LOG = sys_get_temp_dir() . '/iga_bootstrap.log';
$bootlog = function(string $msg) use ($BOOT_LOG) {
    @error_log('[BOOT] '.$msg."\n", 3, $BOOT_LOG);
};

// ------------------------------------------------------------------
// 1) Resolve project root intelligently
// ------------------------------------------------------------------
$dir = __DIR__; // e.g. /var/www/html/includes
$candidateRoots = [
    dirname($dir, 2), // Try two levels up (e.g., /srv/iga)
    dirname($dir, 1), // Try one level up (e.g., /var/www/html)
];

$projectRoot = null;
foreach ($candidateRoots as $cand) {
    // Check for common signs of a project root (composer.json or vendor/env files)
    if (is_dir($cand) && (is_file($cand.'/composer.json') || is_file($cand.'/.env') || is_dir($cand.'/vendor') || is_dir($cand.'/app/vendor'))) {
        $projectRoot = $cand;
        break;
    }
}
// Fallback
if ($projectRoot === null) {
    $projectRoot = dirname($dir, 1);
}
$bootlog("projectRoot={$projectRoot}");

// ------------------------------------------------------------------
// 2) Autoload Composer (supports /vendor and /app/vendor)
// ------------------------------------------------------------------
$autoloadUsed = '(none)';
foreach ([$projectRoot.'/vendor/autoload.php', $projectRoot.'/app/vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        $autoloadUsed = $autoload;
        break;
    }
}
$bootlog("autoloadUsed={$autoloadUsed}");


// ------------------------------------------------------------------
// 3) Load environment (.env) using Dotenv
// ------------------------------------------------------------------
$envLoaded = false;
$envFileLoaded = '';
$envFiles = [];

// Try to find .env files
foreach ([$projectRoot.'/.env', $projectRoot.'/app/.env'] as $f) {
    if (is_file($f)) {
        $envFiles[] = $f;
    }
}
$bootlog('envFilesFound='.implode(',', $envFiles));

if (class_exists(\Dotenv\Dotenv::class)) {
    // Use mutable->load() to force an overwrite of existing environment variables
    foreach ($envFiles as $envFilePath) {
        try {
            \Dotenv\Dotenv::createMutable(dirname($envFilePath))->load();
            $envLoaded = true;
            $envFileLoaded = $envFilePath;
            $bootlog("dotenvLoadedFrom={$envFilePath}");
            break;
        } catch (Throwable $e) {
            // Display Dotenv Syntax Error
            echo "<h2>Dotenv Load Error</h2>";
            echo "<strong>File:</strong> {$envFilePath}<br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<pre>File Content:<br>" . htmlspecialchars(file_get_contents($envFilePath)) . "</pre>";
            $bootlog("dotenvLoadFailed: {$e->getMessage()}");
        }
    }
}
// Fallback micro parser if Dotenv absent or keys still missing
if (!$envLoaded || !getenv('DB_HOST') || !getenv('DB_NAME') || !getenv('DB_USER')) {
    foreach ($envFiles as $f) {
        $lines = @file($f, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        if ($lines === false) { $bootlog("fallbackReadFailed={$f}"); continue; }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line==='' || $line[0]==='#' || strpos($line,'=')===false) continue;
            [$k,$v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B'\"");
            putenv("$k=$v"); $_ENV[$k]=$v; $_SERVER[$k]=$v;
        }
        $bootlog("fallbackLoaded={$f}");
    }
}

// ------------------------------------------------------------------
// 4) Timezone + Logging
// ------------------------------------------------------------------
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Bangkok');

// Default LOG_FILE under <root>/logs (portable on host and in container)
$logCandidate = getenv('LOG_FILE') ?: ($projectRoot . '/logs/app_errors.log');
$logDir = dirname($logCandidate);
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }

$finalLog = $logCandidate;
if (!@file_put_contents($finalLog, "", FILE_APPEND)) {
    $finalLog = sys_get_temp_dir() . '/app_errors.log';
    @file_put_contents($finalLog, "", FILE_APPEND);
    $bootlog("LOG_FILE not writable, fallback to {$finalLog}");
} else {
    $bootlog("LOG_FILE={$finalLog}");
}
if (!defined('LOG_FILE')) define('LOG_FILE', $finalLog);

ini_set('display_errors', getenv('APP_DISPLAY_ERRORS') === '1' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_FILE);

// Stitch bootstrap lines into final log then remove bootstrap file
if (is_file($BOOT_LOG)) {
    @error_log(file_get_contents($BOOT_LOG), 3, LOG_FILE);
    @unlink($BOOT_LOG);
}

// ------------------------------------------------------------------
// 5) SMTP settings (Load and Cast with sensible defaults)
// ------------------------------------------------------------------
// Get values from environment variables
$smtp_host       = getenv('SMTP_HOST');
$smtp_port       = (int)(getenv('SMTP_PORT') ?: 587); // Set default port to 587
$smtp_username   = getenv('SMTP_USERNAME');
$smtp_password   = getenv('SMTP_PASSWORD');
$smtp_from_email = getenv('SMTP_FROM_EMAIL');
$smtp_from_name  = getenv('SMTP_FROM_NAME');
$smtp_secure     = getenv('SMTP_SECURE') ?: 'tls'; // Set default secure to 'tls'
$smtp_charset    = getenv('SMTP_CHARSET') ?: 'UTF-8';

// Define them as constants for use in other files
if (!defined('SMTP_HOST')) define('SMTP_HOST', $smtp_host);
if (!defined('SMTP_PORT')) define('SMTP_PORT', $smtp_port);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', $smtp_username);
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', $smtp_password);
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', $smtp_from_email);
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', $smtp_from_name);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', $smtp_secure);
if (!defined('SMTP_CHARSET')) define('SMTP_CHARSET', $smtp_charset);

// ------------------------------------------------------------------
// 6) Debug: Uncomment to verify settings are loaded correctly
// ------------------------------------------------------------------
/*
echo "<h2>SMTP Config Debug</h2>";
echo "<strong>Project Root Found:</strong> " . $projectRoot . "<br>";
echo "<strong>Timezone:</strong> " . date_default_timezone_get() . "<br>";
echo "<strong>.env Loaded Status:</strong> " . ($envLoaded ? 'SUCCESS' : 'FAILURE/SKIPPED') . "<br>";
if ($envLoaded) {
    echo "<strong>.env File Used:</strong> " . $envFileLoaded . "<br>";
}
echo "<pre>";
print_r([
    'smtp_host'       => SMTP_HOST,
    'smtp_port'       => SMTP_PORT,
    'smtp_username'   => SMTP_USERNAME,
    // **Warning:** Hide password
    'smtp_password'   => strlen(SMTP_PASSWORD) > 0 ? '[HIDDEN - ' . strlen(SMTP_PASSWORD) . ' chars]' : '[EMPTY]',
    'smtp_from_email' => SMTP_FROM_EMAIL,
    'smtp_from_name'  => SMTP_FROM_NAME,
    'smtp_secure'     => SMTP_SECURE,
    'smtp_charset'    => SMTP_CHARSET,
]);
echo "</pre>";
*/

<?php

/**
 * Moodle Configuration File
 * Auto-generated for Docker environment
 */

unset($CFG);
global $CFG;
$CFG = new stdClass();

//=========================================================================
// Database Settings
//=========================================================================
$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER') ?: 'myhr_user';
$CFG->dbpass    = getenv('MOODLE_DB_PASS') ?: 'MyHR_S3cur3_P@ss_2026!';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport' => 3306,
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

//=========================================================================
// Web Address Settings (from environment variable)
//=========================================================================
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$CFG->wwwroot   = getenv('MOODLE_WWWROOT') ?: getenv('MOODLE_PUBLIC_URL') ?: "$protocol://$host/moodle";

$CFG->dataroot = '/var/www/moodledata';
$CFG->lang = 'th';
$CFG->admin = 'admin';
$CFG->siteadmins = '2,2273'; // Explicitly set site admins (admin, superadmin)
$CFG->directorypermissions = 0777;

//=========================================================================
// Session Handling - File (Default)
// Database sessions cause redirect loops if not properly initialized
//=========================================================================
// $CFG->session_handler_class = '\core\session\database';
$CFG->sessioncookiepath = '/moodle';

//=========================================================================
// Performance & Cache
//=========================================================================
$CFG->cachedir = '/var/www/moodledata/cache';
$CFG->localcachedir = '/var/www/moodledata/localcache';
$CFG->tempdir = '/var/www/moodledata/temp';

$CFG->debug = 0;
$CFG->debugdeveloper = false;

//=========================================================================
// Email (SMTP) Settings
//=========================================================================
$CFG->smtpmode = 'smtp';
$CFG->smtphosts = getenv('SMTP_HOST') ?: '172.17.100.6';
$CFG->smtpport = getenv('SMTP_PORT') ?: 25;
$CFG->noreplyaddress = getenv('SMTP_FROM_EMAIL') ?: 'no-reply@inteqc.com';
$CFG->supportemail = getenv('SMTP_FROM_EMAIL') ?: 'e-hris@inteqc.com';

// If credentials are needed in the future, they can be added here
// $CFG->smtpuser = getenv('SMTP_USERNAME');
// $CFG->smtppass = getenv('SMTP_PASSWORD');
// $CFG->smtpsecure = getenv('SMTP_SECURE');

//=========================================================================
// Reverse Proxy Settings
//=========================================================================
$CFG->sslproxy = true;

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

//=========================================================================
// DO NOT EDIT BELOW THIS LINE
//=========================================================================
require_once(__DIR__ . '/lib/setup.php');

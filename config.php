<?php
// Database configuration - Production Ready
// On AwardSpace and shared hosting, update these values with your hosting provider's credentials

function ensure_deployment_structure() {
    $base = __DIR__;
    $required_files = [
        '/config.php',
        '/includes/Auth.php',
        '/includes/Employee.php',
        '/includes/Payroll.php',
    ];

    $missing = [];
    foreach ($required_files as $file) {
        if (!file_exists($base . $file)) {
            $missing[] = $file;
        }
    }

    if (empty($missing)) {
        return;
    }

    $report = [];
    $fix_file = $base . '/fix_deployment_structure.php';
    if (file_exists($fix_file)) {
        require_once $fix_file;
        if (function_exists('run_deployment_structure_fix')) {
            $report = run_deployment_structure_fix();
        } else {
            $report[] = 'ERROR: fix_deployment_structure.php loaded but run_deployment_structure_fix() is not available.';
        }
    } else {
        $report[] = 'ERROR: fix_deployment_structure.php is missing from the deployment root.';
    }

    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Deployment Repair Required</title>' .
            '<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}pre{background:#fff;padding:15px;border:1px solid #ccc;overflow:auto;}</style>' .
            '</head><body><h1>Deployment Repair Required</h1>' .
            '<p>The application detected missing files or folders required for correct operation.</p>' .
            '<h2>Missing Files</h2><ul>' .
            implode('', array_map(function ($item) { return '<li>' . htmlspecialchars($item) . '</li>'; }, $missing)) .
            '</ul>' .
            '<h2>Repair Output</h2><pre>' . htmlspecialchars(implode("\n", $report)) . '</pre>' .
            '<p>Please verify the file layout and remove this repair script when the deployment is fixed.</p>' .
            '</body></html>';
    die($html);
}

ensure_deployment_structure();

// Database credentials (modify for your hosting)
$env_db_host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: getenv('CLEARDB_DATABASE_HOST') ?: 'localhost';
$env_db_user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: getenv('CLEARDB_DATABASE_USER') ?: 'root';
$env_db_pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: getenv('CLEARDB_DATABASE_PASSWORD') ?: '';
$env_db_name = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('CLEARDB_DATABASE_NAME') ?: getenv('CLEARDB_DATABASE_DB') ?: 'natcorp_payroll';

define('DB_HOST', $env_db_host);
define('DB_USER', $env_db_user);
define('DB_PASS', $env_db_pass);
define('DB_NAME', $env_db_name);

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ". Please verify database credentials in config.php");
}

// Set charset
$conn->set_charset("utf8");

// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching for authenticated users to avoid back-button access
if (isset($_SESSION['user_id'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
}

// User role constants
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');

// Define base path for includes
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Define cookie path for remember-me functionality
// Automatically detect the correct cookie path based on the script location
if (!defined('COOKIE_PATH')) {
    // Try to detect the cookie path dynamically
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $cookie_path = dirname($script_path);
    if (empty($cookie_path) || $cookie_path === '.') {
        $cookie_path = '/';
    }
    define('COOKIE_PATH', $cookie_path);
}

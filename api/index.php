<?php
// Vercel PHP function entrypoint.
// Routes all app requests into the root PHP application.

$path = $_GET['__path'] ?? '/';
$path = urldecode($path);
$path = preg_replace('#/+#', '/', $path);
if ($path === '') {
    $path = '/';
}

defined('DOCUMENT_ROOT') || define('DOCUMENT_ROOT', realpath(__DIR__ . '/..'));
$documentRoot = DOCUMENT_ROOT;

// Normalize the request file path to the project root.
$requestFile = $path === '/' ? $documentRoot . '/index.php' : $documentRoot . $path;
$realRequestFile = realpath($requestFile);

if ($realRequestFile === false || strpos($realRequestFile, $documentRoot) !== 0 || !is_file($realRequestFile)) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p>The requested path ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . ' was not found.</p>';
    exit;
}

if (!str_ends_with($realRequestFile, '.php')) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p>Only PHP application routes are served through this entrypoint.</p>';
    exit;
}

// Preserve URL info for included application files.
$_SERVER['DOCUMENT_ROOT'] = $documentRoot;
$_SERVER['SCRIPT_NAME'] = $path;
$_SERVER['PHP_SELF'] = $path;
$_SERVER['SCRIPT_FILENAME'] = $realRequestFile;
$_SERVER['REQUEST_URI'] = $path . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

chdir($documentRoot);

require $realRequestFile;

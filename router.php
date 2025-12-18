<?php
/**
 * Router for PHP built-in server
 * Simulates .htaccess URL rewriting
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $uri)) {
    return false; // Serve the file directly
}

// Extract username from URI
if ($uri === '/' || $uri === '') {
    // No username provided, let index.php handle it
    $_GET['username'] = '';
    require 'index.php';
} elseif (preg_match('/^\/([a-zA-Z0-9_]+)$/', $uri, $matches)) {
    // Username found in URL
    $_GET['username'] = $matches[1];
    require 'index.php';
} elseif ($uri === '/404.php') {
    require '404.php';
} else {
    // For other paths, try to serve the file or show 404
    $file = __DIR__ . $uri;
    if (file_exists($file) && is_file($file)) {
        return false; // Serve the file directly
    } else {
        require '404.php';
    }
}

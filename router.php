<?php
/**
 * Router for PHP built-in server
 * Simulates .htaccess URL rewriting with advanced security
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

// ============================================
// SECURITY: Anti-Scanner & Bot Protection
// ============================================

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// List of known malicious scanners and bots
$blockedUserAgents = [
    'nikto', 'sqlmap', 'dirbuster', 'nmap', 'masscan',
    'wpscan', 'acunetix', 'nessus', 'openvas', 'burpsuite',
    'havij', 'w3af', 'webscarab', 'skipfish', 'grabber',
    'vega', 'zap', 'arachni', 'jbrofuzz', 'fierce',
    'whatweb', 'gobuster', 'dirb', 'ffuf', 'nuclei',
    'httpx', 'subfinder', 'amass', 'waybackurls'
];

// Check for blocked user agents
$userAgentLower = strtolower($userAgent);
foreach ($blockedUserAgents as $blocked) {
    if (strpos($userAgentLower, $blocked) !== false) {
        http_response_code(403);
        header('Connection: close');
        exit;
    }
}

// ============================================
// SECURITY: Path Traversal Protection
// ============================================

// Block path traversal attempts
if (preg_match('/\.\./', $requestUri) || 
    preg_match('/\.\.%/', $requestUri) ||
    preg_match('/%2e%2e/i', $requestUri)) {
    http_response_code(403);
    header('Connection: close');
    exit;
}

// ============================================
// ROUTING
// ============================================

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Redirect URL for wespee.app
$redirectUrl = 'https://wespee.app';

// ============================================
// SECURITY: Block direct access to sensitive files
// ============================================

// Block access to config files and sensitive paths
$blockedExactPaths = [
    '/config.php',
    '/router.php', 
    '/.env',
    '/.env.example',
    '/.gitignore',
    '/.htaccess',
    '/.htpasswd',
    '/composer.json',
    '/composer.lock',
    '/package.json',
    '/replit.md',
    '/README.md',
];

if (in_array(strtolower($uri), array_map('strtolower', $blockedExactPaths))) {
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// Block access to hidden files and directories (starting with .)
if (preg_match('/\/\.[a-zA-Z]/', $uri)) {
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// Block direct access to PHP files (except through router)
if (preg_match('/\.php$/i', $uri) && $uri !== '/') {
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// Block WordPress/common CMS attack paths
$blockedPatterns = [
    '/^\/wp-/i',
    '/^\/wordpress/i',
    '/^\/phpmyadmin/i',
    '/^\/adminer/i',
    '/^\/mysql/i',
    '/\.sql$/i',
    '/\.bak$/i',
    '/\.backup$/i',
    '/\.old$/i',
    '/\.log$/i',
    '/\.ini$/i',
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $uri)) {
        http_response_code(404);
        exit;
    }
}

// ============================================
// ALLOWED ROUTES
// ============================================

// Serve robots.txt
if ($uri === '/robots.txt') {
    header('Content-Type: text/plain');
    header('X-Content-Type-Options: nosniff');
    if (file_exists(__DIR__ . '/robots.txt')) {
        readfile(__DIR__ . '/robots.txt');
    } else {
        echo "User-agent: *\nDisallow: /";
    }
    exit;
}

// Serve favicon
if ($uri === '/favicon.ico') {
    $faviconPath = __DIR__ . '/assets/images/favicon.ico';
    if (file_exists($faviconPath)) {
        header('Content-Type: image/x-icon');
        readfile($faviconPath);
    } else {
        http_response_code(204);
    }
    exit;
}

// Serve static assets (CSS, JS, images, fonts) - required for pages to work
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|otf|map)$/i', $uri)) {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        // Prevent directory listing by checking it's a real file
        return false; // Let PHP built-in server handle it
    }
    http_response_code(404);
    exit;
}

// Block directory listing (paths ending with /)
if (preg_match('/\/$/', $uri) && $uri !== '/') {
    http_response_code(403);
    exit;
}

// Route: Homepage - redirect to wespee.app
if ($uri === '/' || $uri === '') {
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// Route: OG Image generation
if (preg_match('/^\/og-image\/([a-zA-Z0-9_]+)$/', $uri, $matches)) {
    $_GET['username'] = $matches[1];
    $_GET['avatar'] = isset($_GET['avatar']) ? $_GET['avatar'] : '';
    require 'og-image.php';
    exit;
}

// Route: User profile (clean URL) - only alphanumeric and underscore
if (preg_match('/^\/([a-zA-Z0-9_]+)$/', $uri, $matches)) {
    $_GET['username'] = $matches[1];
    require 'index.php';
    exit;
}

// Everything else: 404
http_response_code(404);
require '404.php';

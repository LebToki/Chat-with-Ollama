<?php
/**
 * Router script for PHP built-in server
 * Handles /public/* redirects to /* when server runs from public/ directory
 * 
 * Usage: php -S localhost:8080 router.php
 */

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Handle /public/* requests - redirect to /* 
if (strpos($path, '/public/') === 0) {
    $newPath = substr($path, 7); // Remove '/public' prefix
    if ($queryString) {
        $newPath .= '?' . $queryString;
    }
    header("Location: $newPath", true, 301);
    exit;
}

// Handle /public (without trailing slash)
if ($path === '/public') {
    header("Location: /", true, 301);
    exit;
}

// Check if file exists
$filePath = __DIR__ . $path;

// If it's a directory, try index.php
if (is_dir($filePath)) {
    $indexFile = $filePath . '/index.php';
    if (file_exists($indexFile)) {
        return false; // Let PHP server handle it
    }
    // Redirect to add trailing slash
    if (substr($path, -1) !== '/') {
        header("Location: $path/", true, 301);
        exit;
    }
}

// If file exists, let PHP server serve it
if (file_exists($filePath) && is_file($filePath)) {
    return false; // Let PHP server handle it
}

// 404 - file not found
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>";
echo "<h1>404 - File Not Found</h1>";
echo "<p>The requested resource <code>$path</code> was not found.</p>";
echo "<p>If you're trying to access a page, try:</p>";
echo "<ul>";
echo "<li><a href='/index.php'>/index.php</a></li>";
echo "<li><a href='/documents.php'>/documents.php</a></li>";
echo "<li><a href='/settings.php'>/settings.php</a></li>";
echo "</ul>";
echo "<p><strong>Note:</strong> When the server runs from the <code>public/</code> directory, URLs should NOT include <code>/public/</code>.</p>";
echo "</body></html>";

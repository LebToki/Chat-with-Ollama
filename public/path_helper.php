<?php
/**
 * Helper function to get the assets base path
 * Since the document root is the public/ directory, assets are at /assets
 * 
 * DEBUG MODE: Set ASSETS_DEBUG=true in your environment to enable logging
 */
function getAssetsPath() {
    // Debug mode - can be enabled via environment variable
    $debug = isset($_ENV['ASSETS_DEBUG']) && $_ENV['ASSETS_DEBUG'] === 'true';
    
    // Get the current directory (public/)
    $currentDir = __DIR__;
    
    // Get document root (where web server is serving from)
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Convert both to absolute paths and normalize
    $currentDirAbs = realpath($currentDir);
    $docRootAbs = realpath($docRoot);
    
    // Fallback if realpath fails
    if ($currentDirAbs === false) {
        $currentDirAbs = $currentDir;
    }
    if ($docRootAbs === false) {
        $docRootAbs = $docRoot;
    }
    
    // Normalize: convert backslashes to forward slashes and remove trailing slashes
    $docRootNormalized = str_replace('\\', '/', rtrim($docRootAbs, '/\\'));
    $currentDirNormalized = str_replace('\\', '/', rtrim($currentDirAbs, '/\\'));
    
    // Case-insensitive comparison for Windows compatibility
    $docRootLower = strtolower($docRootNormalized);
    $currentDirLower = strtolower($currentDirNormalized);
    
    // Debug logging
    if ($debug) {
        error_log("ASSETS_DEBUG: Current Dir = $currentDirLower");
        error_log("ASSETS_DEBUG: Doc Root = $docRootLower");
        error_log("ASSETS_DEBUG: Match = " . ($docRootLower === $currentDirLower ? 'YES' : 'NO'));
    }
    
    // If current directory (public/) IS the document root, assets are at /assets
    if ($docRootLower === $currentDirLower) {
        $path = '/assets';
        if ($debug) error_log("ASSETS_DEBUG: Returning path = $path");
        return $path;
    }
    
    // Check if current directory is a subdirectory of document root
    $docRootWithSlash = $docRootLower . '/';
    if (strpos($currentDirLower, $docRootWithSlash) === 0) {
        // Extract relative path (remove document root prefix)
        $relativePath = substr($currentDirLower, strlen($docRootLower));
        $relativePath = trim($relativePath, '/');
        
        // Return web path
        $path = '/' . $relativePath . '/assets';
        if ($debug) error_log("ASSETS_DEBUG: Returning path = $path (relative)");
        return $path;
    }
    
    // Default: assume document root is public/ directory
    $path = '/assets';
    if ($debug) error_log("ASSETS_DEBUG: Returning default path = $path");
    return $path;
}

/**
 * Debug function to check if asset files exist
 */
function debugAssetFiles() {
    $assetsPath = getAssetsPath();
    $baseDir = __DIR__ . '/assets';
    
    $files = [
        'Bootstrap CSS' => $baseDir . '/libs/bootstrap/css/bootstrap.min.css',
        'Font Awesome CSS' => $baseDir . '/libs/font-awesome/css/all.min.css',
        'Modern CSS' => $baseDir . '/css/modern.css',
        'jQuery JS' => $baseDir . '/libs/jquery/jquery.min.js',
        'Axios JS' => $baseDir . '/libs/axios/axios.min.js',
        'Bootstrap JS' => $baseDir . '/libs/bootstrap/js/bootstrap.bundle.min.js',
        'Modern Chat JS' => $baseDir . '/js/modern-chat.js',
        'Bot Avatar' => $baseDir . '/img/bot-avatar.png',
    ];
    
    $results = [];
    foreach ($files as $name => $path) {
        $results[$name] = [
            'exists' => file_exists($path),
            'path' => $path,
            'web_path' => $assetsPath . str_replace($baseDir, '', $path)
        ];
    }
    
    return $results;
}


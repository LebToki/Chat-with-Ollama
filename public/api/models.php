<?php
// API endpoint for models list
header('Content-Type: application/json');

$modelsFile = __DIR__ . '/../../src/Models/models.json';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (file_exists($modelsFile)) {
    $content = file_get_contents($modelsFile);
    $models = json_decode($content, true);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in models.json: " . json_last_error_msg());
        echo json_encode(['error' => 'Invalid JSON in models file', 'details' => json_last_error_msg()]);
        http_response_code(500);
        exit;
    }
    
    // Return models array or empty array
    echo json_encode($models ?: [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    error_log("Models file not found: " . $modelsFile);
    echo json_encode(['error' => 'Models file not found'], JSON_UNESCAPED_SLASHES);
    http_response_code(404);
}


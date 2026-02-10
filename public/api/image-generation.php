<?php
// API endpoint for image generation

header('Content-Type: application/json');

try {
    require __DIR__ . '/../../src/Controllers/ImageGenerationController.php';
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Image Generation API Fatal Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500,
        'details' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : []
    ]);
    exit;
}

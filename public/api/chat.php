<?php
// API endpoint for chat messages

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'code' => 500,
            'details' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ] : []
        ]);
    }
});

try {
    require __DIR__ . '/../../src/Controllers/ChatController.php';
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Chat API Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
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
}


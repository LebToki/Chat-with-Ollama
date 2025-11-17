<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\GenAI\GenAIFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $providers = GenAIFactory::getAvailableProviders();
        
        echo json_encode([
            'success' => true,
            'providers' => $providers
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
}

<?php
require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client;

$config = require __DIR__ . '/../config.php';

$ollamaApiUrl = $config['ollamaApiUrl'];
$jwtToken = $config['jwtToken'];

$model = $_GET['model'] ?? 'llama3.2:latest';

// Validate and sanitize the model parameter to prevent SSRF and Path Traversal
if (!preg_match('/^[a-zA-Z0-9\-_:\.]+$/', $model)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid model name format.']);
    exit;
}

$client = new Client([
    'base_uri' => $ollamaApiUrl,
    'timeout' => 30.0,
    'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $jwtToken,
    ],
]);

try {
    $response = $client->get('models/' . $model);
    $result = json_decode($response->getBody()->getContents(), true);
    echo json_encode(['status' => $result['status']]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

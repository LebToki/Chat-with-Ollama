<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

$config = require __DIR__ . '/../src/config.php';

$ollamaApiUrl = $config['ollamaApiUrl'];
$jwtToken = $config['jwtToken'];

$model = $_GET['model'] ?? 'llama3';

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

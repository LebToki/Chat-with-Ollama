<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from the project root .env file
try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
} catch (Exception $e) {
    // If .env file is missing or invalid, use defaults
    error_log('Warning: Could not load .env file: ' . $e->getMessage());
}

return [
    'ollamaApiUrl' => $_ENV['OLLAMA_API_URL'] ?? 'http://localhost:11434/api/',
    'jwtToken'      => $_ENV['OLLAMA_JWT_TOKEN'] ?? ''
];

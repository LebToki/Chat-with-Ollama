<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from the project root .env file
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

return [
    'ollamaApiUrl' => $_ENV['OLLAMA_API_URL'] ?? 'http://localhost:11434/api/',
    'jwtToken'      => $_ENV['OLLAMA_JWT_TOKEN'] ?? ''
];

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
    'jwtToken'      => $_ENV['OLLAMA_JWT_TOKEN'] ?? '',
    'ollamaCloudApiKey' => $_ENV['OLLAMA_CLOUD_API_KEY'] ?? '',
    'ollamaCloudMode' => filter_var($_ENV['OLLAMA_CLOUD_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    
    // Multi-Provider API Keys
    'groqApiKey' => $_ENV['GROQ_API_KEY'] ?? '',
    'huggingfaceApiKey' => $_ENV['HUGGINGFACE_API_KEY'] ?? '',
    'togetheraiApiKey' => $_ENV['TOGETHERAI_API_KEY'] ?? '',
    'openrouterApiKey' => $_ENV['OPENROUTER_API_KEY'] ?? '',
    
    // Default provider
    'defaultProvider' => 'ollama',
    
    // Branding/Subscription Configuration
    // These can be customized per installation/subscription
    'developerName' => $_ENV['DEVELOPER_NAME'] ?? '{{DEVELOPER_NAME}}',
    'companyName' => $_ENV['COMPANY_NAME'] ?? '{{COMPANY_NAME}}',
    'companyUrl' => $_ENV['COMPANY_URL'] ?? '{{COMPANY_URL}}',
    'githubUsername' => $_ENV['GITHUB_USERNAME'] ?? '{{GITHUB_USERNAME}}',
    'githubRepo' => $_ENV['GITHUB_REPO'] ?? 'chat-with-ollama',
    
    // Environment Configuration
    'appEnv' => $_ENV['APP_ENV'] ?? 'development', // 'development' or 'production'
    'appDebug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    
    // SSL Configuration (for local development with Laragon)
    // SSL verification is ALWAYS enabled in production, regardless of this setting
    // Set to false to disable SSL verification in development only (NOT recommended)
    'verifySSL' => filter_var($_ENV['VERIFY_SSL'] ?? true, FILTER_VALIDATE_BOOLEAN),
    
    // Path to CA bundle (optional, for custom certificate locations)
    // Common locations:
    // - Windows: C:\laragon\bin\php\php-8.x.x\extras\ssl\cacert.pem
    // - Or download from: https://curl.se/ca/cacert.pem
    'caBundle' => $_ENV['CA_BUNDLE'] ?? null,
    
    // Request timeout in seconds (default: 120 seconds)
    'timeout' => filter_var($_ENV['API_TIMEOUT'] ?? 120.0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 1, 'max_range' => 600]]),
];

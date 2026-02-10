<?php
// Image Generation Controller

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\GenAI\ImageGenerationFactory;
use App\Http\RequestHelper;
use App\Http\ApiResponse;

header('Content-Type: application/json');

// Enable detailed error reporting
$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    if (RequestHelper::isMethod('POST')) {
        $prompt = RequestHelper::getInput('prompt', '');
        $provider = RequestHelper::getInput('provider', 'dalle');
        $model = RequestHelper::getInput('model', '');
        $size = RequestHelper::getInput('size', '1024x1024');
        $quality = RequestHelper::getInput('quality', 'standard');
        $n = RequestHelper::getInput('n', 1);
        
        if (empty($prompt)) {
            ApiResponse::error('Prompt is required', 400);
        }
        
        try {
            $imageProvider = ImageGenerationFactory::getProvider($provider);
        } catch (Exception $e) {
            error_log("ImageGenerationController: Provider '{$provider}' not available: " . $e->getMessage());
            throw $e;
        }
        
        // Prepare options
        $options = [
            'size' => $size,
            'quality' => $quality,
            'n' => $n,
        ];
        
        if (!empty($model)) {
            $options['model'] = $model;
        }
        
        try {
            $result = $imageProvider->generateImage($prompt, $options);
            
            $images = $result['images'] ?? [];
            $modelUsed = $result['model_used'] ?? $model;
            $providerUsed = $result['provider'] ?? $provider;
            
            if (empty($images)) {
                throw new Exception('No images generated');
            }
            
            echo json_encode([
                'success' => true,
                'images' => $images,
                'provider' => $providerUsed,
                'model_used' => $modelUsed,
                'count' => count($images),
            ]);
        } catch (Exception $e) {
            error_log("ImageGenerationController Error: " . $e->getMessage());
            error_log("ImageGenerationController Error File: " . $e->getFile() . " Line: " . $e->getLine());
            
            $details = $isDebug ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : [];
            
            ApiResponse::serverError($e->getMessage(), $details);
        }
    } elseif (RequestHelper::isMethod('GET')) {
        // Get available providers
        $providers = ImageGenerationFactory::getAvailableProviders();
        
        echo json_encode([
            'success' => true,
            'providers' => $providers,
        ]);
    } else {
        ApiResponse::methodNotAllowed('GET, POST');
    }
} catch (Exception $e) {
    error_log("ImageGenerationController Fatal Error: " . $e->getMessage());
    error_log("ImageGenerationController Fatal Error File: " . $e->getFile() . " Line: " . $e->getLine());
    
    $details = $isDebug ? [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ] : [];
    
    ApiResponse::serverError($e->getMessage(), $details);
}

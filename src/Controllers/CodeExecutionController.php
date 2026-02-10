<?php
// Code Execution Controller

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\CodeExecutionService;
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
        $code = RequestHelper::getInput('code', '');
        $language = RequestHelper::getInput('language', 'python');
        $timeout = RequestHelper::getInput('timeout', 30);
        
        if (empty($code)) {
            ApiResponse::error('Code is required', 400);
        }
        
        if (empty($language)) {
            ApiResponse::error('Language is required', 400);
        }
        
        try {
            $codeService = new CodeExecutionService($timeout);
        } catch (Exception $e) {
            error_log("CodeExecutionController: Failed to create service: " . $e->getMessage());
            throw $e;
        }
        
        try {
            $result = $codeService->execute($code, $language);
            
            echo json_encode([
                'success' => true,
                'result' => $result,
            ]);
        } catch (Exception $e) {
            error_log("CodeExecutionController Error: " . $e->getMessage());
            error_log("CodeExecutionController Error File: " . $e->getFile() . " Line: " . $e->getLine());
            
            $details = $isDebug ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : [];
            
            ApiResponse::serverError($e->getMessage(), $details);
        }
    } elseif (RequestHelper::isMethod('GET')) {
        // Get supported languages
        $codeService = new CodeExecutionService();
        $languages = $codeService->getSupportedLanguages();
        
        echo json_encode([
            'success' => true,
            'languages' => $languages,
        ]);
    } else {
        ApiResponse::methodNotAllowed('GET, POST');
    }
} catch (Exception $e) {
    error_log("CodeExecutionController Fatal Error: " . $e->getMessage());
    error_log("CodeExecutionController Fatal Error File: " . $e->getFile() . " Line: " . $e->getLine());
    
    $details = $isDebug ? [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ] : [];
    
    ApiResponse::serverError($e->getMessage(), $details);
}

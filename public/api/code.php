<?php

require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Services/CodeExecutionService.php';
require __DIR__ . '/../src/Http/RequestHelper.php';
require __DIR__ . '/../src/Http/Response.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$codeService = new \App\Services\CodeExecutionService();

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'execute':
            $code = RequestHelper::getInput('code', '');
            $language = RequestHelper::getInput('language', 'python');
            $options = json_decode(RequestHelper::getInput('options', '{}'), true);
            
            if (empty($code)) {
                Response::json(['success' => false, 'error' => 'Code is required'], 400);
                exit;
            }
            
            try {
                $result = $codeService->execute($code, $language, $options);
                Response::json($result);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'validate':
            $code = RequestHelper::getInput('code', '');
            $language = RequestHelper::getInput('language', 'python');
            
            if (empty($code)) {
                Response::json(['success' => false, 'error' => 'Code is required'], 400);
                exit;
            }
            
            try {
                // Basic syntax validation
                $sanitizedCode = $codeService->sanitizeCode($code, $language);
                $isValid = !empty($sanitizedCode) && $sanitizedCode !== $code;
                
                Response::json([
                    'success' => true,
                    'valid' => $isValid,
                    'sanitized' => $sanitizedCode !== $code,
                    'warnings' => $isValid ? ['Code contains potentially dangerous operations'] : []
                ]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'get_languages':
            $languages = $codeService->getSupportedLanguages();
            Response::json(['success' => true, 'languages' => $languages]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('GET')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'status':
            $status = [
                'supported_languages' => $codeService->getSupportedLanguages(),
                'max_timeout' => $codeService->getMaxTimeout(),
                'max_memory' => $codeService->getMaxMemory(),
                'sandbox_enabled' => true
            ];
            
            Response::json(['success' => true, 'status' => $status]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
}
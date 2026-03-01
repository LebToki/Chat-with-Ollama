<?php

require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Services/MEMUService.php';
require __DIR__ . '/../src/Services/PersistentRuntimeService.php';
require __DIR__ . '/../src/Http/RequestHelper.php';
require __DIR__ . '/../src/Http/Response.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$memuService = new \App\Services\MEMUService($db);
$runtimeService = new \App\Services\PersistentRuntimeService($db);

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'store_memory':
            $content = RequestHelper::getInput('content', '');
            $contextType = RequestHelper::getInput('context_type', 'conversation');
            $sessionId = RequestHelper::getInput('session_id');
            $userId = RequestHelper::getInput('user_id', 'default');
            
            if (empty($content)) {
                Response::json(['success' => false, 'error' => 'Content is required'], 400);
                exit;
            }
            
            try {
                $result = $memuService->storeMemory($content, $contextType, $sessionId, $userId);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'get_memories':
            $context = RequestHelper::getInput('context', '');
            $limit = (int)RequestHelper::getInput('limit', 5);
            $userId = RequestHelper::getInput('user_id', 'default');
            
            if (empty($context)) {
                Response::json(['success' => false, 'error' => 'Context is required'], 400);
                exit;
            }
            
            try {
                $memories = $memuService->getRelevantMemories($context, $limit, $userId);
                Response::json(['success' => true, 'memories' => $memories]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'create_runtime':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            $modelName = RequestHelper::getInput('model_name', '');
            $config = json_decode(RequestHelper::getInput('config', '{}'), true);
            
            if (empty($runtimeName) || empty($modelName)) {
                Response::json(['success' => false, 'error' => 'Runtime name and model name are required'], 400);
                exit;
            }
            
            try {
                $result = $runtimeService->createRuntime($runtimeName, $modelName, $config);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'execute_conversation':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            $userMessage = RequestHelper::getInput('user_message', '');
            $sessionId = RequestHelper::getInput('session_id');
            $userId = RequestHelper::getInput('user_id', 'default');
            
            if (empty($runtimeName) || empty($userMessage)) {
                Response::json(['success' => false, 'error' => 'Runtime name and user message are required'], 400);
                exit;
            }
            
            try {
                $result = $runtimeService->executeConversation($runtimeName, $userMessage, $sessionId, $userId);
                Response::json($result);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'set_runtime_state':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            $key = RequestHelper::getInput('key', '');
            $value = json_decode(RequestHelper::getInput('value', 'null'), true);
            
            if (empty($runtimeName) || empty($key)) {
                Response::json(['success' => false, 'error' => 'Runtime name and key are required'], 400);
                exit;
            }
            
            try {
                $result = $runtimeService->setRuntimeState($runtimeName, $key, $value);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('GET')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'memory_stats':
            $userId = RequestHelper::getInput('user_id', 'default');
            
            try {
                $stats = $memuService->getMemoryStats($userId);
                Response::json(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'runtime_stats':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            
            if (empty($runtimeName)) {
                Response::json(['success' => false, 'error' => 'Runtime name is required'], 400);
                exit;
            }
            
            try {
                $stats = $runtimeService->getRuntimeStats($runtimeName);
                Response::json(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'list_runtimes':
            try {
                $runtimes = $runtimeService->listRuntimes();
                Response::json(['success' => true, 'runtimes' => $runtimes]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'get_runtime':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            
            if (empty($runtimeName)) {
                Response::json(['success' => false, 'error' => 'Runtime name is required'], 400);
                exit;
            }
            
            try {
                $runtime = $runtimeService->getRuntime($runtimeName);
                Response::json(['success' => true, 'runtime' => $runtime]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('DELETE')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'delete_runtime':
            $runtimeName = RequestHelper::getInput('runtime_name', '');
            
            if (empty($runtimeName)) {
                Response::json(['success' => false, 'error' => 'Runtime name is required'], 400);
                exit;
            }
            
            try {
                $result = $runtimeService->deleteRuntime($runtimeName);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
}
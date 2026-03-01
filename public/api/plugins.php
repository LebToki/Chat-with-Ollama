<?php

require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Plugin/PluginManager.php';
require __DIR__ . '/../src/Http/RequestHelper.php';
require __DIR__ . '/../src/Http/Response.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$pluginManager = new \App\Plugin\PluginManager($db);

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'install':
            $pluginData = json_decode(file_get_contents('php://input'), true);
            $pluginName = RequestHelper::getInput('plugin_name', '');
            
            if (empty($pluginName) || empty($pluginData)) {
                Response::json(['success' => false, 'error' => 'Plugin name and data are required'], 400);
                exit;
            }
            
            try {
                $result = $pluginManager->installPlugin($pluginName, $pluginData);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'uninstall':
            $pluginName = RequestHelper::getInput('plugin_name', '');
            
            if (empty($pluginName)) {
                Response::json(['success' => false, 'error' => 'Plugin name is required'], 400);
                exit;
            }
            
            try {
                $result = $pluginManager->uninstallPlugin($pluginName);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'activate':
            $pluginName = RequestHelper::getInput('plugin_name', '');
            
            if (empty($pluginName)) {
                Response::json(['success' => false, 'error' => 'Plugin name is required'], 400);
                exit;
            }
            
            try {
                $result = $pluginManager->activatePlugin($pluginName);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'deactivate':
            $pluginName = RequestHelper::getInput('plugin_name', '');
            
            if (empty($pluginName)) {
                Response::json(['success' => false, 'error' => 'Plugin name is required'], 400);
                exit;
            }
            
            try {
                $result = $pluginManager->deactivatePlugin($pluginName);
                Response::json(['success' => $result]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'trigger_event':
            $eventName = RequestHelper::getInput('event_name', '');
            $data = json_decode(RequestHelper::getInput('data', '{}'), true);
            
            if (empty($eventName)) {
                Response::json(['success' => false, 'error' => 'Event name is required'], 400);
                exit;
            }
            
            try {
                $results = $pluginManager->triggerEvent($eventName, $data);
                Response::json(['success' => true, 'results' => $results]);
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
        case 'list':
            $plugins = $pluginManager->getPlugins();
            Response::json(['success' => true, 'plugins' => $plugins]);
            break;
            
        case 'get':
            $pluginName = RequestHelper::getInput('plugin_name', '');
            
            if (empty($pluginName)) {
                Response::json(['success' => false, 'error' => 'Plugin name is required'], 400);
                exit;
            }
            
            $plugin = $pluginManager->getPlugin($pluginName);
            if ($plugin) {
                Response::json(['success' => true, 'plugin' => $plugin]);
            } else {
                Response::json(['success' => false, 'error' => 'Plugin not found'], 404);
            }
            break;
            
        case 'status':
            $status = [
                'plugin_dir' => $pluginManager->getPluginDir(),
                'total_plugins' => count($pluginManager->getPlugins()),
                'active_plugins' => count(array_filter($pluginManager->getPlugins(), function($p) { return $p['is_active']; }))
            ];
            
            Response::json(['success' => true, 'status' => $status]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
}
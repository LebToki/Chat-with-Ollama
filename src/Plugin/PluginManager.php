<?php

namespace App\Plugin;

use App\Http\RequestHelper;
use App\Http\Response;

class PluginManager
{
    private $plugins = [];
    private $pluginDir;
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->pluginDir = __DIR__ . '/../../plugins';
        $this->initPluginSystem();
    }
    
    private function initPluginSystem()
    {
        // Create plugins directory if it doesn't exist
        if (!file_exists($this->pluginDir)) {
            mkdir($this->pluginDir, 0755, true);
        }
        
        // Initialize database tables for plugins
        $this->createPluginTables();
        
        // Load active plugins
        $this->loadPlugins();
    }
    
    private function createPluginTables()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS plugins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                version TEXT NOT NULL,
                author TEXT,
                description TEXT,
                is_active BOOLEAN DEFAULT 1,
                config TEXT DEFAULT '{}',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS plugin_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plugin_name TEXT NOT NULL,
                event_name TEXT NOT NULL,
                callback_method TEXT NOT NULL,
                priority INTEGER DEFAULT 10,
                FOREIGN KEY (plugin_name) REFERENCES plugins(name)
            )
        ");
    }
    
    /**
     * Load all active plugins
     */
    public function loadPlugins()
    {
        $stmt = $this->db->query("
            SELECT * FROM plugins 
            WHERE is_active = 1 
            ORDER BY name
        ");
        
        $plugins = $stmt->fetchAll();
        
        foreach ($plugins as $pluginData) {
            $pluginClass = $this->getPluginClass($pluginData['name']);
            
            if (class_exists($pluginClass)) {
                try {
                    $plugin = new $pluginClass($this->db, $pluginData);
                    $this->plugins[$pluginData['name']] = $plugin;
                    
                    // Register event listeners
                    $this->registerEventListeners($plugin);
                    
                } catch (Exception $e) {
                    error_log("Failed to load plugin {$pluginData['name']}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Register event listeners for a plugin
     */
    private function registerEventListeners($plugin)
    {
        $events = $plugin->getEvents() ?? [];
        
        foreach ($events as $eventName => $callback) {
            $this->registerEvent($plugin->getName(), $eventName, $callback);
        }
    }
    
    /**
     * Register an event listener
     */
    public function registerEvent(string $pluginName, string $eventName, string $callbackMethod, int $priority = 10)
    {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO plugin_events 
            (plugin_name, event_name, callback_method, priority) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$pluginName, $eventName, $callbackMethod, $priority]);
    }
    
    /**
     * Trigger an event and get all responses
     */
    public function triggerEvent(string $eventName, array $data = [])
    {
        $stmt = $this->db->prepare("
            SELECT pe.*, p.name, p.config 
            FROM plugin_events pe
            JOIN plugins p ON pe.plugin_name = p.name
            WHERE pe.event_name = ? AND p.is_active = 1
            ORDER BY pe.priority ASC
        ");
        
        $stmt->execute([$eventName]);
        $eventListeners = $stmt->fetchAll();
        
        $results = [];
        
        foreach ($eventListeners as $listener) {
            if (isset($this->plugins[$listener['plugin_name']])) {
                $plugin = $this->plugins[$listener['plugin_name']];
                
                if (method_exists($plugin, $listener['callback_method'])) {
                    try {
                        $result = $plugin->{$listener['callback_method']}($data);
                        if ($result !== null) {
                            $results[] = $result;
                        }
                    } catch (Exception $e) {
                        error_log("Plugin {$listener['plugin_name']} event {$eventName} failed: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Install a new plugin
     */
    public function installPlugin(string $pluginName, array $pluginData)
    {
        // Validate plugin structure
        if (!$this->validatePluginStructure($pluginName)) {
            throw new Exception("Invalid plugin structure for {$pluginName}");
        }
        
        // Insert plugin into database
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO plugins 
            (name, version, author, description, config) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $pluginData['name'],
            $pluginData['version'],
            $pluginData['author'] ?? '',
            $pluginData['description'] ?? '',
            json_encode($pluginData['config'] ?? [])
        ]);
        
        // Load the plugin
        $this->loadPlugins();
        
        return true;
    }
    
    /**
     * Uninstall a plugin
     */
    public function uninstallPlugin(string $pluginName)
    {
        $stmt = $this->db->prepare("DELETE FROM plugins WHERE name = ?");
        $stmt->execute([$pluginName]);
        
        $stmt = $this->db->prepare("DELETE FROM plugin_events WHERE plugin_name = ?");
        $stmt->execute([$pluginName]);
        
        unset($this->plugins[$pluginName]);
        
        return true;
    }
    
    /**
     * Activate a plugin
     */
    public function activatePlugin(string $pluginName)
    {
        $stmt = $this->db->prepare("UPDATE plugins SET is_active = 1 WHERE name = ?");
        $stmt->execute([$pluginName]);
        
        $this->loadPlugins();
        
        return true;
    }
    
    /**
     * Deactivate a plugin
     */
    public function deactivatePlugin(string $pluginName)
    {
        $stmt = $this->db->prepare("UPDATE plugins SET is_active = 0 WHERE name = ?");
        $stmt->execute([$pluginName]);
        
        unset($this->plugins[$pluginName]);
        
        return true;
    }
    
    /**
     * Get plugin class name
     */
    private function getPluginClass(string $pluginName): string
    {
        return "App\\Plugin\\Plugins\\{$pluginName}\\{$pluginName}Plugin";
    }
    
    /**
     * Validate plugin structure
     */
    private function validatePluginStructure(string $pluginName): bool
    {
        $pluginPath = $this->pluginDir . '/' . $pluginName;
        
        if (!file_exists($pluginPath)) {
            return false;
        }
        
        // Check for required files
        $requiredFiles = [
            'plugin.json',
            $pluginName . 'Plugin.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($pluginPath . '/' . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get all plugins
     */
    public function getPlugins()
    {
        $stmt = $this->db->query("SELECT * FROM plugins ORDER BY name");
        return $stmt->fetchAll();
    }
    
    /**
     * Get plugin by name
     */
    public function getPlugin(string $pluginName)
    {
        if (isset($this->plugins[$pluginName])) {
            return $this->plugins[$pluginName];
        }
        
        return null;
    }
    
    /**
     * Handle plugin API requests
     */
    public function handlePluginRequest()
    {
        $action = RequestHelper::getInput('action', '');
        $pluginName = RequestHelper::getInput('plugin_name', '');
        
        switch ($action) {
            case 'list':
                return Response::json(['plugins' => $this->getPlugins()]);
                
            case 'install':
                $pluginData = json_decode(file_get_contents('php://input'), true);
                try {
                    $this->installPlugin($pluginName, $pluginData);
                    return Response::json(['success' => true]);
                } catch (Exception $e) {
                    return Response::json(['success' => false, 'error' => $e->getMessage()], 400);
                }
                
            case 'uninstall':
                try {
                    $this->uninstallPlugin($pluginName);
                    return Response::json(['success' => true]);
                } catch (Exception $e) {
                    return Response::json(['success' => false, 'error' => $e->getMessage()], 400);
                }
                
            case 'activate':
                try {
                    $this->activatePlugin($pluginName);
                    return Response::json(['success' => true]);
                } catch (Exception $e) {
                    return Response::json(['success' => false, 'error' => $e->getMessage()], 400);
                }
                
            case 'deactivate':
                try {
                    $this->deactivatePlugin($pluginName);
                    return Response::json(['success' => true]);
                } catch (Exception $e) {
                    return Response::json(['success' => false, 'error' => $e->getMessage()], 400);
                }
                
            default:
                return Response::json(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }
}
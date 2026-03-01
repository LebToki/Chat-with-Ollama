<?php

namespace App\Services;

use Exception;

class PersistentRuntimeService
{
    private $db;
    private $ollamaHost;
    private $ollamaTimeout;
    private $runtimeCache = [];
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->ollamaHost = getenv('OLLAMA_HOST') ?: 'http://localhost:11434';
        $this->ollamaTimeout = (int)(getenv('OLLAMA_TIMEOUT') ?: 300);
        
        $this->initDatabase();
    }
    
    private function initDatabase()
    {
        // Create runtime tables (already created in MEMUService)
        // Just ensure they exist
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS persistent_runtime (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                runtime_name TEXT UNIQUE NOT NULL,
                runtime_config TEXT NOT NULL,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS runtime_state (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                runtime_id INTEGER,
                state_key TEXT NOT NULL,
                state_value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (runtime_id) REFERENCES persistent_runtime(id)
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS runtime_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                runtime_id INTEGER,
                log_level TEXT NOT NULL,
                message TEXT NOT NULL,
                context TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (runtime_id) REFERENCES persistent_runtime(id)
            )
        ");
    }
    
    /**
     * Create a persistent runtime environment
     */
    public function createRuntime(string $runtimeName, string $modelName, array $config = []): bool
    {
        try {
            // Default configuration
            $defaultConfig = [
                'model' => $modelName,
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'context_window' => 8000,
                'memory_enabled' => true,
                'code_execution_enabled' => true,
                'file_processing_enabled' => true,
                'plugins_enabled' => true,
                'auto_save_interval' => 300, // 5 minutes
                'memory_retention_hours' => 24,
                'max_concurrent_tasks' => 5,
                'system_prompt' => 'You are a persistent AI assistant with memory and context awareness. Maintain consistency across conversations and remember important user preferences and facts.',
                'custom_instructions' => []
            ];
            
            $finalConfig = array_merge($defaultConfig, $config);
            
            // Create runtime
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO persistent_runtime 
                (runtime_name, runtime_config, updated_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            
            $result = $stmt->execute([
                $runtimeName,
                json_encode($finalConfig)
            ]);
            
            if ($result) {
                // Initialize runtime state
                $this->setRuntimeState($runtimeName, 'status', 'initialized');
                $this->setRuntimeState($runtimeName, 'created_at', time());
                $this->setRuntimeState($runtimeName, 'last_activity', time());
                $this->setRuntimeState($runtimeName, 'conversation_count', 0);
                $this->setRuntimeState($runtimeName, 'total_tokens_used', 0);
                
                $this->logRuntimeEvent($runtimeName, 'info', "Runtime created with model: {$modelName}");
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logRuntimeEvent($runtimeName, 'error', "Failed to create runtime: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get runtime configuration
     */
    public function getRuntime(string $runtimeName): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM persistent_runtime 
                WHERE runtime_name = ? AND is_active = 1
            ");
            
            $stmt->execute([$runtimeName]);
            $runtime = $stmt->fetch();
            
            if ($runtime) {
                $runtime['runtime_config'] = json_decode($runtime['runtime_config'], true);
                return $runtime;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("PersistentRuntime getRuntime error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Execute a conversation with persistent context
     */
    public function executeConversation(string $runtimeName, string $userMessage, int $sessionId = null, string $userId = 'default'): array
    {
        try {
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return ['success' => false, 'error' => 'Runtime not found'];
            }
            
            // Update activity
            $this->setRuntimeState($runtimeName, 'last_activity', time());
            $this->incrementRuntimeState($runtimeName, 'conversation_count');
            
            // Get context and memories
            $context = $this->buildContext($runtimeName, $userMessage, $sessionId, $userId);
            
            // Prepare Ollama request
            $model = $runtime['runtime_config']['model'];
            $temperature = $runtime['runtime_config']['temperature'];
            
            $ollamaRequest = [
                'model' => $model,
                'prompt' => $context['prompt'],
                'stream' => false,
                'options' => [
                    'temperature' => $temperature,
                    'num_ctx' => $runtime['runtime_config']['context_window']
                ]
            ];
            
            // Execute with Ollama
            $response = $this->callOllama($ollamaRequest);
            
            if ($response['success']) {
                // Store conversation in memory
                $this->storeConversationMemory($runtimeName, $userMessage, $response['response'], $sessionId, $userId);
                
                // Update token usage
                $this->incrementRuntimeState($runtimeName, 'total_tokens_used', $response['usage']['total_tokens'] ?? 0);
                
                $this->logRuntimeEvent($runtimeName, 'info', "Conversation completed", [
                    'user_message' => substr($userMessage, 0, 100),
                    'response_length' => strlen($response['response']),
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0
                ]);
                
                return [
                    'success' => true,
                    'response' => $response['response'],
                    'usage' => $response['usage'],
                    'context_used' => count($context['memories'])
                ];
            } else {
                $this->logRuntimeEvent($runtimeName, 'error', "Ollama request failed: " . $response['error']);
                return ['success' => false, 'error' => $response['error']];
            }
            
        } catch (Exception $e) {
            $this->logRuntimeEvent($runtimeName, 'error', "Conversation execution failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Build context for conversation including memories and state
     */
    private function buildContext(string $runtimeName, string $userMessage, int $sessionId = null, string $userId = 'default'): array
    {
        $runtime = $this->getRuntime($runtimeName);
        $config = $runtime['runtime_config'];
        
        $context = [];
        
        // Add system prompt
        $context[] = "System: " . $config['system_prompt'];
        
        // Add custom instructions
        if (!empty($config['custom_instructions'])) {
            $context[] = "Instructions: " . implode("\n", $config['custom_instructions']);
        }
        
        // Add relevant memories
        if ($config['memory_enabled']) {
            $memuService = new MEMUService($this->db);
            $memories = $memuService->getRelevantMemories($userMessage, 5, $userId);
            
            if (!empty($memories)) {
                $context[] = "Memory Context:";
                foreach ($memories as $memory) {
                    $context[] = "- " . $memory['content'];
                }
            }
        }
        
        // Add runtime state
        $runtimeState = $this->getRuntimeState($runtimeName, 'status', 'active');
        $context[] = "Runtime State: {$runtimeState}";
        
        // Add current conversation
        $context[] = "User: {$userMessage}";
        $context[] = "Assistant:";
        
        return [
            'prompt' => implode("\n", $context),
            'memories' => $memories ?? []
        ];
    }
    
    /**
     * Store conversation in memory
     */
    private function storeConversationMemory(string $runtimeName, string $userMessage, string $response, int $sessionId = null, string $userId = 'default'): void
    {
        $memuService = new MEMUService($this->db);
        
        // Store user message
        $memuService->storeMemory($userMessage, 'conversation', $sessionId, $userId);
        
        // Store assistant response
        $memuService->storeMemory($response, 'conversation', $sessionId, $userId);
        
        // Store conversation summary
        $summary = "User asked: {$userMessage}\nAssistant responded: " . substr($response, 0, 200);
        $memuService->storeMemory($summary, 'conversation_summary', $sessionId, $userId);
    }
    
    /**
     * Call Ollama API
     */
    private function callOllama(array $request): array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->ollamaHost . '/api/generate');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->ollamaTimeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                return ['success' => false, 'error' => $error];
            }
            
            if ($httpCode !== 200) {
                return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
            }
            
            $data = json_decode($response, true);
            
            return [
                'success' => true,
                'response' => $data['response'] ?? '',
                'usage' => [
                    'prompt_eval_count' => $data['prompt_eval_count'] ?? 0,
                    'eval_count' => $data['eval_count'] ?? 0,
                    'total_tokens' => ($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0)
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Set runtime state
     */
    public function setRuntimeState(string $runtimeName, string $key, $value): bool
    {
        try {
            // Get runtime ID
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO runtime_state 
                (runtime_id, state_key, state_value, updated_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            return $stmt->execute([
                $runtime['id'],
                $key,
                json_encode($value)
            ]);
        } catch (Exception $e) {
            error_log("PersistentRuntime setRuntimeState error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get runtime state
     */
    public function getRuntimeState(string $runtimeName, string $key, $default = null)
    {
        try {
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return $default;
            }
            
            $stmt = $this->db->prepare("
                SELECT state_value FROM runtime_state 
                WHERE runtime_id = ? AND state_key = ?
            ");
            
            $stmt->execute([$runtime['id'], $key]);
            $result = $stmt->fetch();
            
            if ($result) {
                return json_decode($result['state_value'], true);
            }
            
            return $default;
        } catch (Exception $e) {
            error_log("PersistentRuntime getRuntimeState error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Increment runtime state value
     */
    private function incrementRuntimeState(string $runtimeName, string $key, int $increment = 1): bool
    {
        $current = $this->getRuntimeState($runtimeName, $key, 0);
        return $this->setRuntimeState($runtimeName, $key, $current + $increment);
    }
    
    /**
     * Log runtime events
     */
    private function logRuntimeEvent(string $runtimeName, string $level, string $message, array $context = []): bool
    {
        try {
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return false;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO runtime_logs 
                (runtime_id, log_level, message, context, created_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            return $stmt->execute([
                $runtime['id'],
                $level,
                $message,
                json_encode($context)
            ]);
        } catch (Exception $e) {
            error_log("PersistentRuntime logRuntimeEvent error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get runtime statistics
     */
    public function getRuntimeStats(string $runtimeName): array
    {
        try {
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return [];
            }
            
            $stats = [
                'runtime_name' => $runtime['runtime_name'],
                'model' => $runtime['runtime_config']['model'],
                'status' => $this->getRuntimeState($runtimeName, 'status', 'unknown'),
                'created_at' => $this->getRuntimeState($runtimeName, 'created_at'),
                'last_activity' => $this->getRuntimeState($runtimeName, 'last_activity'),
                'conversation_count' => $this->getRuntimeState($runtimeName, 'conversation_count', 0),
                'total_tokens_used' => $this->getRuntimeState($runtimeName, 'total_tokens_used', 0),
                'uptime_hours' => 0
            ];
            
            // Calculate uptime
            if ($stats['created_at']) {
                $stats['uptime_hours'] = round((time() - $stats['created_at']) / 3600, 2);
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("PersistentRuntime getRuntimeStats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * List all runtimes
     */
    public function listRuntimes(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT pr.*, 
                       rs1.state_value as status,
                       rs2.state_value as last_activity
                FROM persistent_runtime pr
                LEFT JOIN runtime_state rs1 ON pr.id = rs1.runtime_id AND rs1.state_key = 'status'
                LEFT JOIN runtime_state rs2 ON pr.id = rs2.runtime_id AND rs2.state_key = 'last_activity'
                WHERE pr.is_active = 1
                ORDER BY pr.created_at DESC
            ");
            
            $runtimes = $stmt->fetchAll();
            
            foreach ($runtimes as &$runtime) {
                $runtime['runtime_config'] = json_decode($runtime['runtime_config'], true);
                $runtime['status'] = json_decode($runtime['status'], true) ?: 'unknown';
                $runtime['last_activity'] = json_decode($runtime['last_activity'], true) ?: 0;
            }
            
            return $runtimes;
        } catch (Exception $e) {
            error_log("PersistentRuntime listRuntimes error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete runtime
     */
    public function deleteRuntime(string $runtimeName): bool
    {
        try {
            $runtime = $this->getRuntime($runtimeName);
            if (!$runtime) {
                return false;
            }
            
            // Deactivate runtime
            $stmt = $this->db->prepare("
                UPDATE persistent_runtime 
                SET is_active = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$runtime['id']]);
            
            if ($result) {
                $this->logRuntimeEvent($runtimeName, 'info', "Runtime deactivated");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("PersistentRuntime deleteRuntime error: " . $e->getMessage());
            return false;
        }
    }
}
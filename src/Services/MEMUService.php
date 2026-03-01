<?php

namespace App\Services;

use Exception;

class MEMUService
{
    private $db;
    private $memoryCache = [];
    private $maxMemorySize = 10000; // Maximum tokens to keep in memory
    private $memoryRetentionHours = 24; // How long to keep memories
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->initDatabase();
    }
    
    private function initDatabase()
    {
        // Create memory tables
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS memory_context (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                user_id TEXT DEFAULT 'default',
                context_type TEXT NOT NULL,
                content TEXT NOT NULL,
                importance_score REAL DEFAULT 0.5,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (session_id) REFERENCES chat_sessions(id)
            )
        ");
        
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
        
        // Create indexes for performance
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_memory_session ON memory_context(session_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_memory_type ON memory_context(context_type)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_memory_accessed ON memory_context(last_accessed)");
    }
    
    /**
     * Store memory context from conversation
     */
    public function storeMemory(string $content, string $contextType = 'conversation', int $sessionId = null, string $userId = 'default'): bool
    {
        try {
            // Calculate importance score based on content
            $importance = $this->calculateImportance($content, $contextType);
            
            $stmt = $this->db->prepare("
                INSERT INTO memory_context 
                (session_id, user_id, context_type, content, importance_score) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $sessionId,
                $userId,
                $contextType,
                $content,
                $importance
            ]);
            
            // Update cache
            $this->updateMemoryCache($content, $contextType, $importance);
            
            return $result;
        } catch (Exception $e) {
            error_log("MEMU storeMemory error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve relevant memories for current conversation
     */
    public function getRelevantMemories(string $currentContext, int $limit = 5, string $userId = 'default'): array
    {
        try {
            // Use RAG to find relevant memories
            $ragService = new RAGService();
            $similarMemories = $ragService->findSimilarDocuments($currentContext, $limit);
            
            // Also get high-importance recent memories
            $stmt = $this->db->prepare("
                SELECT * FROM memory_context 
                WHERE user_id = ? AND importance_score > 0.7
                ORDER BY importance_score DESC, last_accessed DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            $highImportanceMemories = $stmt->fetchAll();
            
            // Merge and deduplicate
            $allMemories = array_merge($similarMemories, $highImportanceMemories);
            $uniqueMemories = [];
            
            foreach ($allMemories as $memory) {
                $key = $memory['id'] ?? md5($memory['content']);
                $uniqueMemories[$key] = $memory;
            }
            
            // Update access times
            foreach ($uniqueMemories as $memory) {
                $this->updateMemoryAccess($memory['id']);
            }
            
            return array_values($uniqueMemories);
        } catch (Exception $e) {
            error_log("MEMU getRelevantMemories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create or update persistent runtime
     */
    public function createRuntime(string $runtimeName, array $config): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO persistent_runtime 
                (runtime_name, runtime_config, updated_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            
            return $stmt->execute([
                $runtimeName,
                json_encode($config)
            ]);
        } catch (Exception $e) {
            error_log("MEMU createRuntime error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get persistent runtime configuration
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
            error_log("MEMU getRuntime error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Store runtime state
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
            error_log("MEMU setRuntimeState error: " . $e->getMessage());
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
            error_log("MEMU getRuntimeState error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Clean up old memories
     */
    public function cleanupOldMemories(): int
    {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$this->memoryRetentionHours} hours"));
            
            $stmt = $this->db->prepare("
                DELETE FROM memory_context 
                WHERE last_accessed < ? AND importance_score < 0.5
            ");
            
            $stmt->execute([$cutoff]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("MEMU cleanupOldMemories error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get memory statistics
     */
    public function getMemoryStats(string $userId = 'default'): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_memories,
                    AVG(importance_score) as avg_importance,
                    MAX(created_at) as last_memory,
                    COUNT(CASE WHEN last_accessed > datetime('now', '-1 hour') THEN 1 END) as recent_accesses
                FROM memory_context 
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: [];
        } catch (Exception $e) {
            error_log("MEMU getMemoryStats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate importance score for memory
     */
    private function calculateImportance(string $content, string $contextType): float
    {
        $score = 0.5; // Base score
        
        // Boost score based on context type
        switch ($contextType) {
            case 'user_preference':
                $score += 0.3;
                break;
            case 'important_fact':
                $score += 0.4;
                break;
            case 'code_solution':
                $score += 0.2;
                break;
            case 'document_summary':
                $score += 0.1;
                break;
        }
        
        // Boost score for longer, more substantial content
        $length = strlen($content);
        if ($length > 100) {
            $score += min(0.2, $length / 1000);
        }
        
        // Boost score if content contains keywords
        $keywords = ['important', 'remember', 'note', 'key', 'critical', 'essential'];
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 0.1;
                break;
            }
        }
        
        return min(1.0, max(0.1, $score));
    }
    
    /**
     * Update memory cache
     */
    private function updateMemoryCache(string $content, string $contextType, float $importance): void
    {
        // Simple cache implementation - in production, consider Redis
        $cacheKey = md5($content);
        $this->memoryCache[$cacheKey] = [
            'content' => $content,
            'type' => $contextType,
            'importance' => $importance,
            'timestamp' => time()
        ];
        
        // Limit cache size
        if (count($this->memoryCache) > 100) {
            // Remove oldest entries
            asort(array_column($this->memoryCache, 'timestamp'));
            $this->memoryCache = array_slice($this->memoryCache, 0, 50, true);
        }
    }
    
    /**
     * Update memory access time
     */
    private function updateMemoryAccess(int $memoryId): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE memory_context 
                SET last_accessed = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$memoryId]);
        } catch (Exception $e) {
            // Silent fail for access updates
        }
    }
    
    /**
     * Get conversation summary for memory
     */
    public function getConversationSummary(int $sessionId): ?string
    {
        try {
            // Get recent messages from session
            $stmt = $this->db->prepare("
                SELECT content FROM chat_messages 
                WHERE session_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            
            $stmt->execute([$sessionId]);
            $messages = $stmt->fetchAll();
            
            if (empty($messages)) {
                return null;
            }
            
            // Create summary
            $summary = "Recent conversation:\n";
            foreach (array_reverse($messages) as $message) {
                $summary .= "- " . substr($message['content'], 0, 100) . "...\n";
            }
            
            return $summary;
        } catch (Exception $e) {
            error_log("MEMU getConversationSummary error: " . $e->getMessage());
            return null;
        }
    }
}
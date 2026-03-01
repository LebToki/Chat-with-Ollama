<?php

require_once __DIR__ . '/../src/Services/MEMUService.php';
require_once __DIR__ . '/../src/Services/PersistentRuntimeService.php';

class MEMUTest
{
    private $db;
    private $memuService;
    private $runtimeService;
    
    public function __construct()
    {
        $this->db = \App\Database\Database::getInstance()->getConnection();
        $this->memuService = new \App\Services\MEMUService($this->db);
        $this->runtimeService = new \App\Services\PersistentRuntimeService($this->db);
    }
    
    public function runAllTests()
    {
        echo "Running MEMU and Persistent Runtime Tests...\n\n";
        
        $tests = [
            'testMEMUService' => 'MEMU Memory Service',
            'testPersistentRuntime' => 'Persistent Runtime Service',
            'testMemoryPersistence' => 'Memory Persistence Across Chats',
            'testRuntimeStateManagement' => 'Runtime State Management',
            'testIntegration' => 'MEMU-Runtime Integration',
            'testPerformance' => 'Performance and Scalability'
        ];
        
        $results = [];
        
        foreach ($tests as $method => $name) {
            echo "Testing: {$name}... ";
            try {
                $result = $this->$method();
                $results[$name] = $result ? 'PASS' : 'FAIL';
                echo $result ? "PASS\n" : "FAIL\n";
            } catch (Exception $e) {
                $results[$name] = 'ERROR';
                echo "ERROR: {$e->getMessage()}\n";
            }
        }
        
        echo "\nMEMU and Runtime Test Results:\n";
        foreach ($results as $test => $status) {
            echo "- {$test}: {$status}\n";
        }
        
        return $results;
    }
    
    private function testMEMUService()
    {
        try {
            // Test memory storage
            $content = "This is a test memory for user preferences.";
            $result = $this->memuService->storeMemory($content, 'user_preference', null, 'test_user');
            
            if (!$result) {
                return false;
            }
            
            // Test memory retrieval
            $memories = $this->memuService->getRelevantMemories("What are my preferences?", 3, 'test_user');
            
            if (empty($memories)) {
                return false;
            }
            
            // Test memory stats
            $stats = $this->memuService->getMemoryStats('test_user');
            
            if (empty($stats) || !isset($stats['total_memories'])) {
                return false;
            }
            
            // Test conversation summary
            $summary = $this->memuService->getConversationSummary(1);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testPersistentRuntime()
    {
        try {
            // Test runtime creation
            $runtimeName = 'test_runtime_' . time();
            $result = $this->runtimeService->createRuntime($runtimeName, 'llama2', [
                'temperature' => 0.8,
                'max_tokens' => 2000
            ]);
            
            if (!$result) {
                return false;
            }
            
            // Test runtime retrieval
            $runtime = $this->runtimeService->getRuntime($runtimeName);
            
            if (!$runtime || $runtime['runtime_name'] !== $runtimeName) {
                return false;
            }
            
            // Test runtime state management
            $stateResult = $this->runtimeService->setRuntimeState($runtimeName, 'test_key', 'test_value');
            
            if (!$stateResult) {
                return false;
            }
            
            $stateValue = $this->runtimeService->getRuntimeState($runtimeName, 'test_key');
            
            if ($stateValue !== 'test_value') {
                return false;
            }
            
            // Test runtime stats
            $stats = $this->runtimeService->getRuntimeStats($runtimeName);
            
            if (empty($stats) || $stats['runtime_name'] !== $runtimeName) {
                return false;
            }
            
            // Test runtime listing
            $runtimes = $this->runtimeService->listRuntimes();
            
            if (!is_array($runtimes)) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testMemoryPersistence()
    {
        try {
            $userId = 'persistence_test_user_' . time();
            
            // Store multiple memories
            $memories = [
                'user_preference' => 'I prefer technical explanations.',
                'important_fact' => 'My name is John Doe.',
                'code_solution' => 'Here is a solution for the sorting problem.',
                'conversation_summary' => 'We discussed AI and machine learning.'
            ];
            
            foreach ($memories as $type => $content) {
                $this->memuService->storeMemory($content, $type, null, $userId);
            }
            
            // Test retrieval with different contexts
            $testContexts = [
                "What do I prefer?",
                "Tell me about John Doe.",
                "How to solve sorting?",
                "What did we discuss?"
            ];
            
            foreach ($testContexts as $context) {
                $relevantMemories = $this->memuService->getRelevantMemories($context, 3, $userId);
                
                if (empty($relevantMemories)) {
                    return false;
                }
            }
            
            // Test memory cleanup
            $cleanupCount = $this->memuService->cleanupOldMemories();
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testRuntimeStateManagement()
    {
        try {
            $runtimeName = 'state_test_runtime_' . time();
            
            // Create runtime
            $this->runtimeService->createRuntime($runtimeName, 'llama2');
            
            // Test various state operations
            $states = [
                'status' => 'active',
                'conversation_count' => 5,
                'total_tokens_used' => 1000,
                'custom_data' => ['key' => 'value', 'array' => [1, 2, 3]]
            ];
            
            foreach ($states as $key => $value) {
                $result = $this->runtimeService->setRuntimeState($runtimeName, $key, $value);
                
                if (!$result) {
                    return false;
                }
                
                $retrievedValue = $this->runtimeService->getRuntimeState($runtimeName, $key);
                
                if ($retrievedValue !== $value) {
                    return false;
                }
            }
            
            // Test increment operation
            $initialCount = $this->runtimeService->getRuntimeState($runtimeName, 'conversation_count', 0);
            $this->runtimeService->incrementRuntimeState($runtimeName, 'conversation_count');
            $newCount = $this->runtimeService->getRuntimeState($runtimeName, 'conversation_count', 0);
            
            if ($newCount !== $initialCount + 1) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testIntegration()
    {
        try {
            $userId = 'integration_test_user_' . time();
            $runtimeName = 'integration_test_runtime_' . time();
            
            // Create runtime with memory enabled
            $this->runtimeService->createRuntime($runtimeName, 'llama2', [
                'memory_enabled' => true,
                'temperature' => 0.7
            ]);
            
            // Store some memories
            $this->memuService->storeMemory("I love programming in Python.", 'user_preference', null, $userId);
            $this->memuService->storeMemory("I work as a software developer.", 'important_fact', null, $userId);
            
            // Simulate conversation that should use memories
            $userMessage = "What programming languages do you think I might like?";
            
            // This would normally call Ollama, but we'll test the context building
            $context = $this->runtimeService->buildContext($runtimeName, $userMessage, null, $userId);
            
            if (empty($context['prompt']) || empty($context['memories'])) {
                return false;
            }
            
            // Verify that memories are included in context
            $prompt = $context['prompt'];
            if (strpos($prompt, 'Python') === false || strpos($prompt, 'software developer') === false) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testPerformance()
    {
        try {
            $userId = 'performance_test_user_' . time();
            $startTime = microtime(true);
            
            // Store many memories
            for ($i = 0; $i < 100; $i++) {
                $content = "Performance test memory {$i}: This is a longer piece of text to test memory storage and retrieval performance. It contains various keywords like important, remember, and key information that should be indexed properly.";
                $this->memuService->storeMemory($content, 'conversation', null, $userId);
            }
            
            $storageTime = microtime(true) - $startTime;
            
            // Test retrieval performance
            $startTime = microtime(true);
            
            for ($i = 0; $i < 10; $i++) {
                $memories = $this->memuService->getRelevantMemories("Find important information about test {$i}", 5, $userId);
                
                if (empty($memories)) {
                    return false;
                }
            }
            
            $retrievalTime = microtime(true) - $startTime;
            
            // Performance should be reasonable (adjust thresholds as needed)
            if ($storageTime > 5.0 || $retrievalTime > 2.0) {
                return false;
            }
            
            // Test memory stats performance
            $stats = $this->memuService->getMemoryStats($userId);
            
            if (empty($stats) || $stats['total_memories'] < 90) { // Allow for some cleanup
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $test = new MEMUTest();
    $test->runAllTests();
}
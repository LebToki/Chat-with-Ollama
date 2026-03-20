<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Services/CodeExecutionService.php';
require_once __DIR__ . '/../src/Services/RAGService.php';
require_once __DIR__ . '/../src/Services/DocumentService.php';

class IntegrationTest
{
    private $db;
    private $codeService;
    private $ragService;
    private $documentService;
    
    public function __construct()
    {
        $this->db = \App\Database\Database::getInstance()->getConnection();
        // Create mock EmbeddingService for testing RAGService
        $mockEmbeddingService = new class('http://localhost', 'token') extends \App\Services\EmbeddingService {
            public function generateEmbedding($text, $model = null) {
                return array_fill(0, 1536, 0.1); // Mock embedding vector
            }
        };

        $this->codeService = new \App\Services\CodeExecutionService();
        $this->ragService = new \App\Services\RAGService($mockEmbeddingService);
        $this->documentService = new \App\Services\DocumentService();
    }
    
    public function runAllTests()
    {
        echo "Running Integration Tests...\n\n";
        
        $tests = [
            'testDatabaseConnection' => 'Database Connection',
            'testCodeExecution' => 'Code Execution Service',
            'testDocumentProcessing' => 'Document Processing',
            'testRAGIntegration' => 'RAG Integration',
            'testSecurityFeatures' => 'Security Features',
            'testPerformance' => 'Performance Tests'
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
        
        echo "\nTest Results Summary:\n";
        foreach ($results as $test => $status) {
            echo "- {$test}: {$status}\n";
        }
        
        return $results;
    }
    
    private function testDatabaseConnection()
    {
        try {
            $stmt = $this->db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            return $result && $result['test'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testCodeExecution()
    {
        try {
            // Test Python execution
            $pythonResult = $this->codeService->execute('print("Hello from Python!")', 'python');
            if (!$pythonResult['success'] || !str_contains($pythonResult['output'], 'Hello from Python!')) {
                return false;
            }
            
            // Test JavaScript execution
            $jsResult = $this->codeService->execute('console.log("Hello from JavaScript!");', 'javascript');
            if (!$jsResult['success'] || !str_contains($jsResult['output'], 'Hello from JavaScript!')) {
                return false;
            }
            
            // Test security - dangerous code should be blocked
            $dangerousResult = $this->codeService->execute('import os\nos.system("echo test")', 'python');
            if ($dangerousResult['success']) {
                return false; // Dangerous code should not execute successfully
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testDocumentProcessing()
    {
        try {
            // Create test document
            $testContent = "This is a test document for RAG functionality.\nIt contains multiple lines of text.\nThe quick brown fox jumps over the lazy dog.";
            $testFile = tempnam(sys_get_temp_dir(), 'test_doc_');
            file_put_contents($testFile, $testContent);
            
            // Process document
            $result = $this->documentService->processDocument([
                'name' => 'test.txt',
                'tmp_name' => $testFile,
                'size' => filesize($testFile)
            ]);
            
            if (!$result || !isset($result['filename'])) {
                return false;
            }
            
            // Clean up
            unlink($testFile);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testRAGIntegration()
    {
        try {
            // Test similarity search
            $text = "This is a test sentence for similarity search.";
            $similarDocs = $this->ragService->retrieveRelevantChunks($text, 3);
            
            return is_array($similarDocs);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testSecurityFeatures()
    {
        try {
            // Test input validation
            $validationRules = [
                'email' => ['required' => true, 'type' => 'email'],
                'message' => ['max_length' => 500, 'xss_safe' => true]
            ];
            
            $testData = [
                'email' => 'test@example.com',
                'message' => 'This is a safe message.'
            ];
            
            $validated = \App\Middleware\SecurityMiddleware::validateInput($testData, $validationRules);
            
            if ($validated['email'] !== 'test@example.com') {
                return false;
            }
            
            // Test SQL injection prevention
            $dangerousInput = "'; DROP TABLE users; --";
            try {
                \App\Middleware\SecurityMiddleware::sanitizeSQLInput($dangerousInput);
                return false; // Should have thrown an exception
            } catch (Exception $e) {
                // Expected behavior
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testPerformance()
    {
        try {
            $startTime = microtime(true);
            
            // Test multiple code executions
            for ($i = 0; $i < 10; $i++) {
                $result = $this->codeService->execute('print("Test")', 'python');
                if (!$result['success']) {
                    return false;
                }
            }
            
            $executionTime = microtime(true) - $startTime;
            
            // Should complete 10 executions in under 30 seconds
            return $executionTime < 30.0;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $test = new IntegrationTest();
    $test->runAllTests();
}
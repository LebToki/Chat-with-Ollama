<?php

require_once __DIR__ . '/../src/Services/VoiceService.php';
require_once __DIR__ . '/../src/Services/CodeExecutionService.php';
require_once __DIR__ . '/../src/Plugin/PluginManager.php';
require_once __DIR__ . '/../src/Services/EnhancedDocumentService.php';

class AdvancedFeaturesTest
{
    private $db;
    private $voiceService;
    private $codeService;
    private $pluginManager;
    private $documentService;
    
    public function __construct()
    {
        $this->db = \App\Database\Database::getInstance()->getConnection();
        $this->voiceService = new \App\Services\VoiceService();
        $this->codeService = new \App\Services\CodeExecutionService();
        $this->pluginManager = new \App\Plugin\PluginManager($this->db);
        $this->documentService = new \App\Services\EnhancedDocumentService();
    }
    
    public function runAllTests()
    {
        echo "Running Advanced Features Tests...\n\n";
        
        $tests = [
            'testVoiceService' => 'Voice Input/Output Service',
            'testAdvancedCodeExecution' => 'Advanced Code Execution',
            'testPluginSystem' => 'Plugin System',
            'testEnhancedDocumentProcessing' => 'Enhanced Document Processing',
            'testIntegrationFeatures' => 'Feature Integration',
            'testSecurityEnhancements' => 'Security Enhancements'
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
        
        echo "\nAdvanced Features Test Results:\n";
        foreach ($results as $test => $status) {
            echo "- {$test}: {$status}\n";
        }
        
        return $results;
    }
    
    private function testVoiceService()
    {
        try {
            // Test TTS provider detection
            $ttsProvider = $this->voiceService->getTTSProvider();
            $sttProvider = $this->voiceService->getSTTProvider();
            
            // Test voice availability
            $voices = $this->voiceService->getAvailableVoices();
            
            // Test text-to-speech (basic functionality)
            $ttsResult = $this->voiceService->textToSpeech('Test message', 'en', 'default');
            
            // Test speech-to-text with dummy file (should fail gracefully)
            $dummyFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($dummyFile, 'dummy content');
            
            $sttResult = $this->voiceService->speechToText($dummyFile, 'en');
            unlink($dummyFile);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testAdvancedCodeExecution()
    {
        try {
            // Test advanced options
            $options = [
                'timeout' => 10,
                'memory_limit' => 64,
                'dependencies' => ['json', 'os'],
                'environment' => ['NODE_ENV' => 'test']
            ];
            
            // Test Python with dependencies
            $pythonResult = $this->codeService->execute(
                'import json\nprint(json.dumps({"test": "value"}))',
                'python',
                $options
            );
            
            if (!$pythonResult['success'] || !isset($pythonResult['metadata'])) {
                return false;
            }
            
            // Test JavaScript execution
            $jsResult = $this->codeService->execute(
                'console.log("Advanced JS execution");',
                'javascript',
                $options
            );
            
            if (!$jsResult['success'] || !isset($jsResult['metadata'])) {
                return false;
            }
            
            // Test security features
            $dangerousResult = $this->codeService->execute(
                'import os\nos.system("echo test")',
                'python',
                $options
            );
            
            if ($dangerousResult['success']) {
                return false; // Dangerous code should be blocked
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testPluginSystem()
    {
        try {
            // Test plugin manager initialization
            $plugins = $this->pluginManager->getPlugins();
            
            // Test plugin validation
            $pluginDir = $this->pluginManager->getPluginDir();
            if (!file_exists($pluginDir)) {
                return false;
            }
            
            // Test event system
            $eventResults = $this->pluginManager->triggerEvent('test_event', ['test' => 'data']);
            
            // Test plugin operations (should not throw errors)
            $this->pluginManager->activatePlugin('test_plugin');
            $this->pluginManager->deactivatePlugin('test_plugin');
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testEnhancedDocumentProcessing()
    {
        try {
            // Test supported formats
            $formats = $this->documentService->getSupportedFormats();
            if (empty($formats)) {
                return false;
            }
            
            // Test file validation
            $testFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($testFile, 'Test document content');
            
            $validation = $this->documentService->validateFile($testFile, 'test.txt');
            
            unlink($testFile);
            
            if (!$validation['valid']) {
                return false;
            }
            
            // Test text extraction
            $testFile2 = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($testFile2, 'Test content for extraction');
            
            $fileInfo = $validation['file_info'];
            $textContent = $this->documentService->extractText($testFile2, $fileInfo['extension']);
            
            unlink($testFile2);
            
            if (empty($textContent)) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testIntegrationFeatures()
    {
        try {
            // Test RAG integration with enhanced documents
            $testContent = "This is test content for RAG processing.\nIt contains multiple lines of text.";
            $testFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($testFile, $testContent);
            
            $validation = $this->documentService->validateFile($testFile, 'test.txt');
            
            if ($validation['valid']) {
                $fileInfo = $validation['file_info'];
                $textContent = $this->documentService->extractText($testFile, $fileInfo['extension']);
                
                if (!empty($textContent)) {
                    // Test that the content was properly extracted
                    if (strpos($textContent, 'test content') !== false) {
                        unlink($testFile);
                        return true;
                    }
                }
            }
            
            unlink($testFile);
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function testSecurityEnhancements()
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
            
            // Test rate limiting
            $rateLimitResult = \App\Middleware\SecurityMiddleware::checkRateLimit('test_user', 5, 60);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $test = new AdvancedFeaturesTest();
    $test->runAllTests();
}
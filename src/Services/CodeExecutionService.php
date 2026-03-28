<?php

namespace App\Services;

use Exception;

class CodeExecutionService
{
    private $timeout;
    private $maxMemory;
    private $allowedLanguages;
    
    public function __construct(int $timeout = 30, int $maxMemory = 128)
    {
        $this->timeout = $timeout;
        $this->maxMemory = $maxMemory;
        $this->allowedLanguages = ['python', 'javascript', 'php', 'bash', 'ruby', 'go', 'rust'];
    }
    
    /**
     * Execute code in a sandboxed environment with advanced features
     * 
     * @param string $code The code to execute
     * @param string $language The programming language
     * @param array $options Additional options (timeout, memory_limit, dependencies, etc.)
     * @return array Result with output, errors, execution time, and metadata
     */
    public function execute(string $code, string $language, array $options = []): array
    {
        $language = strtolower($language);
        
        if (!in_array($language, $this->allowedLanguages)) {
            throw new Exception("Language '{$language}' is not supported");
        }
        
        // Apply advanced options
        $timeout = $options['timeout'] ?? $this->timeout;
        $memoryLimit = $options['memory_limit'] ?? $this->maxMemory;
        $dependencies = $options['dependencies'] ?? [];
        $environment = $options['environment'] ?? [];
        
        // Sanitize code to prevent dangerous operations
        $sanitizedCode = $this->sanitizeCode($code, $language);
        
        // Add dependencies if specified
        if (!empty($dependencies)) {
            $sanitizedCode = $this->addDependencies($sanitizedCode, $language, $dependencies);
        }
        
        $startTime = microtime(true);
        
        try {
            switch ($language) {
                case 'python':
                    $result = $this->executePython($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'javascript':
                case 'js':
                    $result = $this->executeJavaScript($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'php':
                    $result = $this->executePHP($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'bash':
                case 'sh':
                    $result = $this->executeBash($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'ruby':
                    $result = $this->executeRuby($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'go':
                    $result = $this->executeGo($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                case 'rust':
                    $result = $this->executeRust($sanitizedCode, $options, $timeout, $memoryLimit);
                    break;
                default:
                    throw new Exception("Language '{$language}' is not implemented");
            }
            
            // Add execution metadata
            $result['metadata'] = [
                'language' => $language,
                'timeout' => $timeout,
                'memory_limit' => $memoryLimit,
                'dependencies' => $dependencies,
                'environment' => $environment,
                'timestamp' => date('Y-m-d H:i:s'),
                'execution_time' => $result['execution_time']
            ];
            
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime,
                'metadata' => [
                    'language' => $language,
                    'error_type' => get_class($e),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        $result['execution_time'] = microtime(true) - $startTime;
        return $result;
    }
    
    /**
     * Add dependencies to code based on language
     */
    private function addDependencies(string $code, string $language, array $dependencies): string
    {
        switch ($language) {
            case 'python':
                $imports = '';
                foreach ($dependencies as $dep) {
                    $imports .= "import {$dep}\n";
                }
                return $imports . $code;
                
            case 'javascript':
                $imports = '';
                foreach ($dependencies as $dep) {
                    $imports .= "const {$dep} = require('{$dep}');\n";
                }
                return $imports . $code;
                
            case 'go':
                // Go dependencies would need to be handled differently
                // This is a simplified version
                return $code;
                
            default:
                return $code;
        }
    }
    
    /**
     * Sanitize code to prevent dangerous operations
     */
    private function sanitizeCode(string $code, string $language): string
    {
        // Remove dangerous imports and functions
        $dangerousPatterns = [
            'python' => [
                '/import\s+os\s*;/i',
                '/import\s+subprocess\s*;/i',
                '/import\s+shutil\s*;/i',
                '/from\s+os\s+import/i',
                '/from\s+subprocess\s+import/i',
                '/exec\s*\(/i',
                '/eval\s*\(/i',
                '/__import__\s*\(/i',
            ],
            'javascript' => [
                '/require\s*\(\s*[\'"]child_process[\'"]\s*\)/i',
                '/require\s*\(\s*[\'"]fs[\'"]\s*\)/i',
                '/eval\s*\(/i',
                '/Function\s*\(/i',
            ],
            'php' => [
                '/exec\s*\(/i',
                '/system\s*\(/i',
                '/passthru\s*\(/i',
                '/shell_exec\s*\(/i',
                '/eval\s*\(/i',
            ],
            'bash' => [
                '/rm\s+-rf/i',
                '/dd\s+/i',
                '/mkfs/i',
                '/chmod\s+777/i',
            ],
        ];
        
        if (isset($dangerousPatterns[$language])) {
            foreach ($dangerousPatterns[$language] as $pattern) {
                $code = preg_replace($pattern, '/* BLOCKED */', $code);
            }
        }
        
        return $code;
    }
    
    /**
     * Execute Python code with enhanced security
     */
    private function executePython(string $code, array $options): array
    {
        // Create secure temporary directory
        $tempDir = sys_get_temp_dir() . '/ollama_code_exec';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0700, true);
        }
        
        $tempFile = tempnam($tempDir, 'python_');
        $tempFileWithExt = $tempFile . '.py';
        rename($tempFile, $tempFileWithExt);
        $tempFile = $tempFileWithExt;
        
        // Write code with proper permissions
        file_put_contents($tempFile, $code);
        chmod($tempFile, 0600);
        
        // Enhanced command with resource limits and sandboxing
        $command = sprintf(
            'timeout %d ulimit -v %d && python3 -I %s 2>&1',
            $this->timeout,
            $this->maxMemory * 1024, // Convert MB to KB
            escapeshellarg($tempFile)
        );
        
        // Execute with timeout and capture result
        $result = $this->executeWithTimeout($command, $this->timeout);
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute JavaScript code
     */
    private function executeJavaScript(string $code, array $options): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'js_');
        file_put_contents($tempFile, $code);
        
        $command = sprintf(
            'timeout %d node %s 2>&1',
            $this->timeout,
            escapeshellarg($tempFile)
        );
        
        $result = $this->executeWithTimeout($command, $this->timeout);
        
        unlink($tempFile);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute PHP code
     */
    private function executePHP(string $code, array $options): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_');
        file_put_contents($tempFile, '<?php ' . $code);
        
        // 🛡️ Sentinel: Disable dangerous functions to prevent RCE bypass
        $command = sprintf(
            'timeout %d php -d disable_functions=exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source %s 2>&1',
            $this->timeout,
            escapeshellarg($tempFile)
        );
        
        $result = $this->executeWithTimeout($command, $this->timeout);
        
        unlink($tempFile);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute Bash code
     */
    private function executeBash(string $code, array $options): array
    {
        $command = sprintf(
            'timeout %d bash -c %s 2>&1',
            $this->timeout,
            escapeshellarg($code)
        );
        
        $result = $this->executeWithTimeout($command, $this->timeout);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute Ruby code
     */
    private function executeRuby(string $code, array $options): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ruby_');
        file_put_contents($tempFile, $code);
        
        $command = sprintf(
            'timeout %d ruby %s 2>&1',
            $this->timeout,
            escapeshellarg($tempFile)
        );
        
        $result = $this->executeWithTimeout($command, $this->timeout);
        
        unlink($tempFile);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute Go code
     */
    private function executeGo(string $code, array $options): array
    {
        $tempDir = sys_get_temp_dir() . '/go_' . uniqid();
        mkdir($tempDir);
        
        $mainFile = $tempDir . '/main.go';
        file_put_contents($mainFile, $code);
        
        // Compile
        $compileCommand = sprintf(
            'cd %s && timeout %d go build -o main main.go 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $compileResult = $this->executeWithTimeout($compileCommand, $this->timeout);
        
        if ($compileResult['exitCode'] !== 0 || strpos($compileResult['output'] . $compileResult['error'], 'error') !== false) {
            $this->cleanupTempDir($tempDir);
            return [
                'success' => false,
                'output' => '',
                'error' => $compileResult['error'] ?: $compileResult['output'],
            ];
        }
        
        // Execute
        $execCommand = sprintf(
            'cd %s && timeout %d ./main 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $result = $this->executeWithTimeout($execCommand, $this->timeout);
        
        $this->cleanupTempDir($tempDir);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute Rust code
     */
    private function executeRust(string $code, array $options): array
    {
        $tempDir = sys_get_temp_dir() . '/rust_' . uniqid();
        mkdir($tempDir);
        
        $mainFile = $tempDir . '/main.rs';
        file_put_contents($mainFile, $code);
        
        // Compile
        $compileCommand = sprintf(
            'cd %s && timeout %d rustc -o main main.rs 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $compileResult = $this->executeWithTimeout($compileCommand, $this->timeout);
        
        if ($compileResult['exitCode'] !== 0 || strpos($compileResult['output'] . $compileResult['error'], 'error') !== false) {
            $this->cleanupTempDir($tempDir);
            return [
                'success' => false,
                'output' => '',
                'error' => $compileResult['error'] ?: $compileResult['output'],
            ];
        }
        
        // Execute
        $execCommand = sprintf(
            'cd %s && timeout %d ./main 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $result = $this->executeWithTimeout($execCommand, $this->timeout);
        
        $this->cleanupTempDir($tempDir);
        
        return [
            'success' => $result['exitCode'] == 0,
            'output' => $result['output'] ?? '',
            'error' => $result['error'] ?? '',
            'execution_time' => $result['execution_time'] ?? 0,
        ];
    }
    
    /**
     * Execute command with timeout and resource limits
     */
    private function executeWithTimeout(string $command, int $timeout): array
    {
        $startTime = microtime(true);
        $output = '';
        $error = '';
        $exitCode = 0;
        
        // Use proc_open for better control
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Close stdin
            fclose($pipes[0]);
            
            // Set non-blocking reads
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            
            $timeoutMicroseconds = $timeout * 1000000;
            $startMicroseconds = microtime(true) * 1000000;
            
            while (true) {
                $status = proc_get_status($process);
                
                // Read output
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                
                if ($stdout !== false) {
                    $output .= $stdout;
                }
                if ($stderr !== false) {
                    $error .= $stderr;
                }
                
                // Check if process finished
                if (!$status['running']) {
                    $exitCode = $status['exitcode'];
                    break;
                }
                
                // Check timeout
                $currentMicroseconds = microtime(true) * 1000000;
                $elapsedMicroseconds = $currentMicroseconds - $startMicroseconds;
                
                if ($elapsedMicroseconds >= $timeoutMicroseconds) {
                    // Kill process
                    proc_terminate($process, 9);
                    $error = "Process killed due to timeout ({$timeout}s)";
                    $exitCode = 1;
                    break;
                }
                
                // Sleep briefly to avoid busy waiting
                usleep(10000); // 10ms
            }
            
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            $error = "Failed to start process";
            $exitCode = 1;
        }
        
        return [
            'output' => $output,
            'error' => $error,
            'exitCode' => $exitCode,
            'execution_time' => microtime(true) - $startTime,
        ];
    }
    
    /**
     * Clean up temporary directory
     */
    private function cleanupTempDir(string $dir): void
    {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Get list of supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->allowedLanguages;
    }
}

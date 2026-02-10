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
     * Execute code in a sandboxed environment
     * 
     * @param string $code The code to execute
     * @param string $language The programming language
     * @param array $options Additional options
     * @return array Result with output, errors, and execution time
     */
    public function execute(string $code, string $language, array $options = []): array
    {
        $language = strtolower($language);
        
        if (!in_array($language, $this->allowedLanguages)) {
            throw new Exception("Language '{$language}' is not supported");
        }
        
        // Sanitize code to prevent dangerous operations
        $sanitizedCode = $this->sanitizeCode($code, $language);
        
        $startTime = microtime(true);
        
        try {
            switch ($language) {
                case 'python':
                    $result = $this->executePython($sanitizedCode, $options);
                    break;
                case 'javascript':
                case 'js':
                    $result = $this->executeJavaScript($sanitizedCode, $options);
                    break;
                case 'php':
                    $result = $this->executePHP($sanitizedCode, $options);
                    break;
                case 'bash':
                case 'sh':
                    $result = $this->executeBash($sanitizedCode, $options);
                    break;
                case 'ruby':
                    $result = $this->executeRuby($sanitizedCode, $options);
                    break;
                case 'go':
                    $result = $this->executeGo($sanitizedCode, $options);
                    break;
                case 'rust':
                    $result = $this->executeRust($sanitizedCode, $options);
                    break;
                default:
                    throw new Exception("Language '{$language}' is not implemented");
            }
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime,
            ];
        }
        
        $result['execution_time'] = microtime(true) - $startTime;
        return $result;
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
     * Execute Python code
     */
    private function executePython(string $code, array $options): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'python_');
        file_put_contents($tempFile, $code);
        
        $command = sprintf(
            'timeout %d python3 %s 2>&1',
            $this->timeout,
            escapeshellarg($tempFile)
        );
        
        $output = shell_exec($command);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        unlink($tempFile);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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
        
        $output = shell_exec($command);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        unlink($tempFile);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
        ];
    }
    
    /**
     * Execute PHP code
     */
    private function executePHP(string $code, array $options): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_');
        file_put_contents($tempFile, '<?php ' . $code);
        
        $command = sprintf(
            'timeout %d php %s 2>&1',
            $this->timeout,
            escapeshellarg($tempFile)
        );
        
        $output = shell_exec($command);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        unlink($tempFile);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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
        
        $output = shell_exec($command);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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
        
        $output = shell_exec($command);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        unlink($tempFile);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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
        
        $compileOutput = shell_exec($compileCommand);
        
        if ($compileOutput !== null && strpos($compileOutput, 'error') !== false) {
            $this->cleanupTempDir($tempDir);
            return [
                'success' => false,
                'output' => '',
                'error' => $compileOutput,
            ];
        }
        
        // Execute
        $execCommand = sprintf(
            'cd %s && timeout %d ./main 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $output = shell_exec($execCommand);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        $this->cleanupTempDir($tempDir);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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
        
        $compileOutput = shell_exec($compileCommand);
        
        if ($compileOutput !== null && strpos($compileOutput, 'error') !== false) {
            $this->cleanupTempDir($tempDir);
            return [
                'success' => false,
                'output' => '',
                'error' => $compileOutput,
            ];
        }
        
        // Execute
        $execCommand = sprintf(
            'cd %s && timeout %d ./main 2>&1',
            escapeshellarg($tempDir),
            $this->timeout
        );
        
        $output = shell_exec($execCommand);
        $exitCode = shell_exec(sprintf('echo $?'));
        
        $this->cleanupTempDir($tempDir);
        
        return [
            'success' => $exitCode == 0,
            'output' => $output ?? '',
            'error' => $exitCode != 0 ? $output : '',
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

<?php

namespace App\Middleware;

use App\Http\RequestHelper;

class SecurityMiddleware
{
    /**
     * Validate and sanitize input data
     */
    public static function validateInput($data, $rules = [])
    {
        $validated = [];
        
        foreach ($rules as $field => $rule) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                // Apply validation rules
                if (isset($rule['required']) && $rule['required'] && empty(trim($value))) {
                    throw new \Exception("Field '{$field}' is required");
                }
                
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    throw new \Exception("Field '{$field}' exceeds maximum length of {$rule['max_length']} characters");
                }
                
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'string':
                            $value = filter_var($value, FILTER_SANITIZE_STRING);
                            break;
                        case 'email':
                            $value = filter_var($value, FILTER_VALIDATE_EMAIL);
                            if (!$value) {
                                throw new \Exception("Field '{$field}' must be a valid email address");
                            }
                            break;
                        case 'int':
                            $value = filter_var($value, FILTER_VALIDATE_INT);
                            if ($value === false) {
                                throw new \Exception("Field '{$field}' must be an integer");
                            }
                            break;
                        case 'url':
                            $value = filter_var($value, FILTER_VALIDATE_URL);
                            if (!$value) {
                                throw new \Exception("Field '{$field}' must be a valid URL");
                            }
                            break;
                    }
                }
                
                // XSS prevention - HTML encoding
                if (isset($rule['xss_safe']) && $rule['xss_safe']) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
                
                $validated[$field] = $value;
            } elseif (isset($rule['required']) && $rule['required']) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        return $validated;
    }
    
    /**
     * Check for SQL injection patterns
     */
    public static function sanitizeSQLInput($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeSQLInput'], $input);
        }
        
        if (!is_string($input)) {
            return $input;
        }
        
        // Remove common SQL injection patterns
        $dangerousPatterns = [
            '/(union|select|insert|update|delete|drop|create|alter|exec|execute)/i',
            '/(script|javascript|vbscript|onload|onerror|onclick)/i',
            '/(\/\*|\*\/|\'|\"|;|--|\||&)/',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \Exception("Potentially dangerous input detected");
            }
        }
        
        return $input;
    }
    
    /**
     * Rate limiting implementation
     */
    public static function checkRateLimit($identifier = null, $limit = 60, $window = 3600)
    {
        if (!$identifier) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $currentTime = time();
            
            // Clean old entries
            $data['requests'] = array_filter($data['requests'], function($timestamp) use ($currentTime, $window) {
                return ($currentTime - $timestamp) < $window;
            });
            
            if (count($data['requests']) >= $limit) {
                return false; // Rate limit exceeded
            }
            
            $data['requests'][] = $currentTime;
        } else {
            $data = [
                'requests' => [time()]
            ];
        }
        
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }
    
    /**
     * CSRF token validation
     */
    public static function validateCSRFToken($token = null)
    {
        if (!$token) {
            $token = RequestHelper::getInput('csrf_token');
        }
        
        if (!$token) {
            return false;
        }
        
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if (!$sessionToken || !hash_equals($sessionToken, $token)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Add security headers
     */
    public static function addSecurityHeaders()
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\'');
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = [])
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log('SECURITY: ' . json_encode($logData));
    }
}
<?php

namespace App\Http;

/**
 * API Response Helper Class
 * Standardizes API response format across all controllers
 * Provides consistent error handling and response structure
 */
class ApiResponse
{
    /**
     * Send a successful response
     * 
     * @param mixed $data Response data
     * @param string $message Optional success message
     * @param int $code HTTP status code (default: 200)
     * @return void Outputs JSON and exits
     */
    public static function success($data = null, string $message = '', int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            // If data is already an array with keys, merge it
            if (is_array($data) && !isset($data[0])) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param array $details Additional error details
     * @return void Outputs JSON and exits
     */
    public static function error(string $message, int $code = 400, array $details = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code,
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a validation error response
     * 
     * @param array $errors Validation errors (field => message)
     * @param string $message Optional general message
     * @return void Outputs JSON and exits
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, 422, ['validation_errors' => $errors]);
    }

    /**
     * Send a not found response
     * 
     * @param string $resource Resource name (e.g., "Document", "Session")
     * @return void Outputs JSON and exits
     */
    public static function notFound(string $resource = 'Resource'): void
    {
        self::error("$resource not found", 404);
    }

    /**
     * Send an unauthorized response
     * 
     * @param string $message Optional message
     * @return void Outputs JSON and exits
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Send a forbidden response
     * 
     * @param string $message Optional message
     * @return void Outputs JSON and exits
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Send a method not allowed response
     * 
     * @param string $method Allowed method(s)
     * @return void Outputs JSON and exits
     */
    public static function methodNotAllowed(string $method = 'POST'): void
    {
        self::error("Method not allowed. Use $method", 405);
    }

    /**
     * Send a server error response
     * 
     * @param string $message Error message (generic in production)
     * @param array $details Additional details (only in debug mode)
     * @return void Outputs JSON and exits
     */
    public static function serverError(string $message = 'Internal server error', array $details = []): void
    {
        // In production, don't expose internal errors
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        if (!$isDebug) {
            $message = 'Internal server error';
            $details = [];
        }
        
        self::error($message, 500, $details);
    }
}


<?php

namespace App\Http;

/**
 * Request Helper Class
 * Centralizes request input parsing to eliminate code duplication
 * and provide consistent input handling across controllers
 */
class RequestHelper
{
    /**
     * Parsed input cache (to avoid parsing multiple times)
     * @var array|null
     */
    private static $parsedInput = null;

    /**
     * Get parsed input (JSON or POST)
     * Parses once and caches the result
     * 
     * @return array Parsed input data
     */
    public static function getParsedInput(): array
    {
        if (self::$parsedInput !== null) {
            return self::$parsedInput;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Check if request is JSON (not multipart/form-data)
        if (strpos($contentType, 'application/json') !== false && 
            strpos($contentType, 'multipart/form-data') === false) {
            $rawInput = file_get_contents('php://input');
            $decoded = json_decode($rawInput, true);
            self::$parsedInput = is_array($decoded) ? $decoded : [];
        } else {
            // Use POST data (for form-data or url-encoded)
            self::$parsedInput = $_POST ?? [];
        }

        return self::$parsedInput;
    }

    /**
     * Get a single input value
     * Checks parsed input, POST, then GET (in that order)
     * 
     * @param string $key Input key name
     * @param mixed $default Default value if key not found
     * @return mixed Input value or default
     */
    public static function getInput(string $key, $default = null)
    {
        $input = self::getParsedInput();
        
        // Check parsed input first, then POST, then GET
        if (isset($input[$key])) {
            return $input[$key];
        }
        
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        return $default;
    }

    /**
     * Get all input data
     * Returns parsed input merged with POST and GET (in priority order)
     * 
     * @return array All input data
     */
    public static function getAllInput(): array
    {
        $input = self::getParsedInput();
        return array_merge($_GET, $_POST, $input);
    }

    /**
     * Check if request method matches
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @return bool True if method matches
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }

    /**
     * Get request method
     * 
     * @return string HTTP method
     */
    public static function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get uploaded file
     * 
     * @param string $key File input name
     * @return array|null File data or null if not found
     */
    public static function getFile(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Check if request has file upload
     * 
     * @param string $key File input name
     * @return bool True if file exists and is valid
     */
    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get Content-Type header
     * 
     * @return string Content-Type or empty string
     */
    public static function getContentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    /**
     * Check if request is JSON
     * 
     * @return bool True if Content-Type is application/json
     */
    public static function isJson(): bool
    {
        $contentType = self::getContentType();
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Reset parsed input cache (useful for testing)
     */
    public static function reset(): void
    {
        self::$parsedInput = null;
    }
}


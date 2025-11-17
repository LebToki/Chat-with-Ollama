<?php

namespace App\Services\GenAI;

use InvalidArgumentException;

/**
 * Data Transfer Object for AI generation configuration
 * Ensures type safety and validation of generation parameters
 */
class GenerationConfig
{
    public float $temperature;
    public int $maxTokens;
    public ?string $systemMessage = null;

    /**
     * @param array $options Raw options array
     * @param array $defaults Default values to use
     */
    public function __construct(array $options = [], array $defaults = [])
    {
        $this->temperature = $this->validateTemperature(
            $options['temperature'] ?? $defaults['temperature'] ?? 0.7
        );
        
        $this->maxTokens = $this->validateMaxTokens(
            $options['max_tokens'] ?? $options['maxOutputTokens'] ?? $defaults['max_tokens'] ?? 2048
        );
        
        if (isset($options['system_message']) && !empty(trim($options['system_message']))) {
            $this->systemMessage = trim($options['system_message']);
        }
    }

    /**
     * Validate and normalize temperature value
     */
    private function validateTemperature($value): float
    {
        $temp = (float) $value;
        return max(0.0, min(2.0, $temp));
    }

    /**
     * Validate and normalize max tokens value
     */
    private function validateMaxTokens($value, int $maxLimit = 8192): int
    {
        $tokens = (int) $value;
        if ($tokens < 1) {
            throw new InvalidArgumentException('max_tokens must be at least 1');
        }
        if ($tokens > $maxLimit) {
            throw new InvalidArgumentException("max_tokens cannot exceed {$maxLimit}");
        }
        return $tokens;
    }

    /**
     * Convert to array for Gemini API format
     */
    public function toGeminiArray(): array
    {
        return [
            'temperature' => $this->temperature,
            'maxOutputTokens' => $this->maxTokens,
        ];
    }

    /**
     * Convert to array for OpenAI-compatible API format (DeepSeek, etc.)
     */
    public function toOpenAIArray(): array
    {
        return [
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];
    }

    /**
     * Build messages array with optional system message
     */
    public function buildMessages(string $userPrompt): array
    {
        $messages = [];
        
        if ($this->systemMessage !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemMessage];
        }
        
        $messages[] = ['role' => 'user', 'content' => $userPrompt];
        
        return $messages;
    }
}


<?php

namespace App\Services\GenAI;

interface GenAIProviderInterface
{
    /**
     * Generate a response from the AI model
     * 
     * @param string $prompt The user's prompt
     * @param string $model The model to use
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Response array with 'response' key
     */
    public function generate(string $prompt, string $model, array $options = []): array;

    /**
     * Stream a response from the AI model
     * 
     * @param string $prompt The user's prompt
     * @param string $model The model to use
     * @param array $options Additional options
     * @param callable $callback Callback function for each chunk: function($chunk, $done)
     * @return void
     */
    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void;

    /**
     * Get list of available models
     * 
     * @return array Array of model names
     */
    public function getModels(): array;

    /**
     * Check if provider is available/configured
     * 
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get provider name
     * 
     * @return string
     */
    public function getName(): string;
}

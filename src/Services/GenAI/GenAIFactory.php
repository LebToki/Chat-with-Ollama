<?php

namespace App\Services\GenAI;

use Exception;

class GenAIFactory
{
    private static $providers = [];

    /**
     * Get a GenAI provider instance
     * 
     * @param string $providerName Name of the provider (groq, huggingface, togetherai, openrouter, ollama)
     * @return GenAIProviderInterface
     * @throws Exception
     */
    public static function getProvider(string $providerName): GenAIProviderInterface
    {
        $providerName = strtolower($providerName);
        
        if (isset(self::$providers[$providerName])) {
            return self::$providers[$providerName];
        }

        switch ($providerName) {
            case 'groq':
                self::$providers[$providerName] = new GroqProvider();
                break;
            
            case 'huggingface':
            case 'hf':
                self::$providers[$providerName] = new HuggingFaceProvider();
                break;
            
            case 'togetherai':
            case 'together':
                self::$providers[$providerName] = new TogetherAIProvider();
                break;
            
            case 'openrouter':
                self::$providers[$providerName] = new OpenRouterProvider();
                break;
            
            case 'ollama':
                $config = require __DIR__ . '/../../config.php';
                self::$providers[$providerName] = new OllamaProvider(
                    $config['ollamaApiUrl'] ?? null,
                    $config['jwtToken'] ?? null
                );
                break;
            
            default:
                throw new Exception("Unknown provider: {$providerName}");
        }

        return self::$providers[$providerName];
    }

    /**
     * Get all available providers
     * 
     * @return array Array of provider names that are configured and available
     */
    public static function getAvailableProviders(): array
    {
        $available = [];
        $providers = ['groq', 'huggingface', 'togetherai', 'openrouter', 'ollama'];
        
        foreach ($providers as $providerName) {
            try {
                $provider = self::getProvider($providerName);
                if ($provider->isAvailable()) {
                    $available[] = [
                        'name' => $provider->getName(),
                        'id' => $providerName,
                        'models' => $provider->getModels(),
                    ];
                }
            } catch (Exception $e) {
                // Provider not available, skip
            }
        }
        
        return $available;
    }

    /**
     * Detect provider from model name
     * 
     * @param string $model Model name
     * @return string Provider name
     */
    public static function detectProviderFromModel(string $model): string
    {
        // Check model name patterns to detect provider
        if (str_contains($model, 'llama-3.1') || str_contains($model, 'mixtral') || str_contains($model, 'gemma')) {
            return 'groq';
        }
        
        if (str_contains($model, 'mistralai/') || str_contains($model, 'meta-llama/')) {
            if (str_contains($model, 'hf')) {
                return 'huggingface';
            }
            return 'togetherai';
        }
        
        if (str_contains($model, 'google/') || str_contains($model, 'qwen/')) {
            return 'openrouter';
        }
        
        // Default to ollama for local models
        return 'ollama';
    }
}

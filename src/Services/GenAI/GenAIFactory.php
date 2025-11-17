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
        
        // Free version: Only allow Ollama provider
        if ($providerName !== 'ollama') {
            throw new Exception("Provider '{$providerName}' is not available in the free version. Only Ollama is supported. Upgrade to NexusAI Chat for multi-provider support: https://2tinteractive.com");
        }
        
        if (isset(self::$providers[$providerName])) {
            return self::$providers[$providerName];
        }

        // Only Ollama provider is available in free version
        $config = require __DIR__ . '/../../config.php';
        self::$providers[$providerName] = new OllamaProvider(
            $config['ollamaApiUrl'] ?? null,
            $config['jwtToken'] ?? null
        );

        return self::$providers[$providerName];
    }

    /**
     * Get all available providers
     * 
     * @return array Array of provider names that are configured and available
     */
    public static function getAvailableProviders(): array
    {
        // Free version: Only return Ollama provider
        $available = [];
        
        try {
            $provider = self::getProvider('ollama');
            if ($provider->isAvailable()) {
                $available[] = [
                    'name' => $provider->getName(),
                    'id' => 'ollama',
                    'models' => $provider->getModels(),
                ];
            }
        } catch (Exception $e) {
            // Ollama not available
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
        // Free version: Always return Ollama
        // All models are assumed to be Ollama models in the free version
        return 'ollama';
    }
}

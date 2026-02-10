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

        $config = require __DIR__ . '/../../config.php';
        
        switch ($providerName) {
            case 'ollama':
                self::$providers[$providerName] = new OllamaProvider(
                    $config['ollamaApiUrl'] ?? null,
                    $config['jwtToken'] ?? null,
                    $config['ollamaCloudApiKey'] ?? null
                );
                break;
            case 'groq':
                self::$providers[$providerName] = new GroqProvider(
                    $config['groqApiKey'] ?? null
                );
                break;
            case 'huggingface':
                self::$providers[$providerName] = new HuggingFaceProvider(
                    $config['huggingfaceApiKey'] ?? null
                );
                break;
            case 'togetherai':
                self::$providers[$providerName] = new TogetherAIProvider(
                    $config['togetheraiApiKey'] ?? null
                );
                break;
            case 'openrouter':
                self::$providers[$providerName] = new OpenRouterProvider(
                    $config['openrouterApiKey'] ?? null
                );
                break;
            default:
                throw new Exception("Unknown provider: '{$providerName}'");
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
        $config = require __DIR__ . '/../../config.php';
        
        $providerClasses = [
            'ollama' => ['class' => OllamaProvider::class, 'config_key' => 'ollamaApiUrl'],
            'groq' => ['class' => GroqProvider::class, 'config_key' => 'groqApiKey'],
            'huggingface' => ['class' => HuggingFaceProvider::class, 'config_key' => 'huggingfaceApiKey'],
            'togetherai' => ['class' => TogetherAIProvider::class, 'config_key' => 'togetheraiApiKey'],
            'openrouter' => ['class' => OpenRouterProvider::class, 'config_key' => 'openrouterApiKey'],
        ];
        
        foreach ($providerClasses as $id => $providerInfo) {
            try {
                $provider = self::getProvider($id);
                if ($provider->isAvailable()) {
                    $available[] = [
                        'name' => $provider->getName(),
                        'id' => $id,
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
        // Detect provider based on model name patterns
        if (strpos($model, 'llama') !== false || strpos($model, 'mistral') !== false || strpos($model, 'phi') !== false) {
            return 'ollama';
        } elseif (strpos($model, 'groq') !== false || strpos($model, 'llama3-') === 0) {
            return 'groq';
        } elseif (strpos($model, '/') !== false && strpos($model, 'meta-llama') !== false) {
            return 'huggingface';
        } elseif (strpos($model, 'openai/') !== false || strpos($model, 'anthropic/') !== false || strpos($model, 'google/') !== false) {
            return 'openrouter';
        }
        
        // Default to ollama
        return 'ollama';
    }
}

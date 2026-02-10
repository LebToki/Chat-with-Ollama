<?php

namespace App\Services\GenAI;

use Exception;

class ImageGenerationFactory
{
    private static $providers = [];

    /**
     * Get an image generation provider instance
     * 
     * @param string $providerName Name of the provider (dalle, stable-diffusion)
     * @return ImageGenerationProviderInterface
     * @throws Exception
     */
    public static function getProvider(string $providerName): ImageGenerationProviderInterface
    {
        $providerName = strtolower($providerName);
        
        if (isset(self::$providers[$providerName])) {
            return self::$providers[$providerName];
        }

        $config = require __DIR__ . '/../../config.php';
        
        switch ($providerName) {
            case 'dalle':
                self::$providers[$providerName] = new DALLEProvider(
                    $config['openaiApiKey'] ?? null
                );
                break;
            case 'stable-diffusion':
            case 'stablediffusion':
                self::$providers[$providerName] = new StableDiffusionProvider(
                    $config['stabilityApiKey'] ?? null
                );
                break;
            default:
                throw new Exception("Unknown image generation provider: '{$providerName}'");
        }

        return self::$providers[$providerName];
    }

    /**
     * Get all available image generation providers
     * 
     * @return array Array of provider names that are configured and available
     */
    public static function getAvailableProviders(): array
    {
        $available = [];
        $config = require __DIR__ . '/../../config.php';
        
        $providerClasses = [
            'dalle' => ['class' => DALLEProvider::class, 'config_key' => 'openaiApiKey'],
            'stable-diffusion' => ['class' => StableDiffusionProvider::class, 'config_key' => 'stabilityApiKey'],
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
}

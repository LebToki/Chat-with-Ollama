<?php

namespace App\Services\GenAI;

interface ImageGenerationProviderInterface
{
    /**
     * Generate an image from a text prompt
     * 
     * @param string $prompt The text prompt for image generation
     * @param array $options Additional options (size, style, etc.)
     * @return array Response array with 'image_url' or 'image_data' key
     */
    public function generateImage(string $prompt, array $options = []): array;
    
    /**
     * Get list of available image models
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

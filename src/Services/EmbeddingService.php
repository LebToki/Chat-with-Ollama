<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class EmbeddingService
{
    private $client;
    private $ollamaApiUrl;
    private $jwtToken;
    public $defaultModel = 'nomic-embed-text';

    public function __construct($ollamaApiUrl, $jwtToken)
    {
        $this->ollamaApiUrl = rtrim($ollamaApiUrl, '/') . '/';
        $this->jwtToken = $jwtToken;
        
        $this->client = new Client([
            'base_uri' => $this->ollamaApiUrl,
            'timeout' => 60.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtToken,
            ],
        ]);
    }

    public function generateEmbedding($text, $model = null)
    {
        $model = $model ?? $this->defaultModel;
        
        try {
            $response = $this->client->post('embeddings', [
                'json' => [
                    'model' => $model,
                    'prompt' => $text
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['embedding'])) {
                return $result['embedding'];
            }
            
            throw new Exception("Invalid embedding response");
        } catch (Exception $e) {
            error_log("Embedding generation failed: " . $e->getMessage());
            throw new Exception("Failed to generate embedding: " . $e->getMessage());
        }
    }

    public function cosineSimilarity($embedding1, $embedding2)
    {
        if (count($embedding1) !== count($embedding2)) {
            return 0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $norm1 += $embedding1[$i] * $embedding1[$i];
            $norm2 += $embedding2[$i] * $embedding2[$i];
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }
}

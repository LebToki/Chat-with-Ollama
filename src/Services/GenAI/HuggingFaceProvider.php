<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class HuggingFaceProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $apiUrl;
    private $client;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['HUGGINGFACE_API_KEY'] ?? '';
        $this->apiUrl = 'https://api-inference.huggingface.co/models/';
        
        if (empty($this->apiKey)) {
            throw new Exception('HuggingFace API key is required');
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 300.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }

    public function generate(string $prompt, string $model, array $options = []): array
    {
        try {
            $response = $this->client->post($model, [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => $options['max_tokens'] ?? 2048,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'return_full_text' => false,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Handle different response formats
            $responseText = '';
            if (is_array($data)) {
                if (isset($data[0]['generated_text'])) {
                    $responseText = $data[0]['generated_text'];
                } elseif (isset($data['generated_text'])) {
                    $responseText = $data['generated_text'];
                } elseif (isset($data[0]['summary_text'])) {
                    $responseText = $data[0]['summary_text'];
                }
            } elseif (is_string($data)) {
                $responseText = $data;
            }
            
            return [
                'response' => $responseText,
                'model_used' => $model,
                'provider' => 'huggingface',
            ];
        } catch (Exception $e) {
            throw new Exception('HuggingFace API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        try {
            $response = $this->client->post($model, [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => $options['max_tokens'] ?? 2048,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'return_full_text' => false,
                    ],
                    'stream' => true,
                ],
                'stream' => true,
            ]);

            $stream = $response->getBody();
            $buffer = '';

            while (!$stream->eof()) {
                $chunk = $stream->read(1024);
                $buffer .= $chunk;
                
                // Process JSON chunks
                while (($pos = strpos($buffer, '}')) !== false) {
                    $jsonStr = substr($buffer, 0, $pos + 1);
                    $buffer = substr($buffer, $pos + 1);
                    
                    $data = json_decode($jsonStr, true);
                    if (isset($data['token']['text'])) {
                        $text = $data['token']['text'];
                        if ($callback) {
                            $callback($text, false);
                        }
                    }
                }
            }
            
            if ($callback) {
                $callback('', true);
            }
        } catch (Exception $e) {
            throw new Exception('HuggingFace streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        // Return popular text generation models
        return [
            'meta-llama/Llama-2-70b-chat-hf',
            'meta-llama/Llama-2-13b-chat-hf',
            'mistralai/Mistral-7B-Instruct-v0.2',
            'tiiuae/falcon-180B-chat',
            'google/gemma-7b',
            'Qwen/Qwen1.5-72B-Chat',
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'HuggingFace';
    }
}

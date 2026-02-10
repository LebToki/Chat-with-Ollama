<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class GroqProvider implements GenAIProviderInterface
{
    private $apiKey;
    private $apiUrl;
    private $client;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['GROQ_API_KEY'] ?? '';
        $this->apiUrl = 'https://api.groq.com/openai/v1/';
        
        if (empty($this->apiKey)) {
            throw new Exception('Groq API key is required');
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
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => false,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'model_used' => $model,
                'provider' => 'groq',
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $data['usage']['total_tokens'] ?? 0,
            ];
        } catch (Exception $e) {
            throw new Exception('Groq API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => true,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ],
                'stream' => true,
            ]);

            $stream = $response->getBody();

            while (!$stream->eof()) {
                $line = $stream->readLine();
                if (empty(trim($line))) {
                    continue;
                }

                // Remove "data: " prefix
                $line = preg_replace('/^data: /', '', $line);
                
                // Skip [DONE] marker
                if (trim($line) === '[DONE]') {
                    break;
                }

                $data = json_decode($line, true);
                if (isset($data['choices'][0]['delta']['content'])) {
                    $chunk = $data['choices'][0]['delta']['content'];
                    
                    if ($callback) {
                        $callback($chunk, false);
                    }
                }
                
                // Check if this is the last chunk
                if (isset($data['choices'][0]['finish_reason'])) {
                    if ($callback) {
                        $callback('', true);
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            throw new Exception('Groq streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        try {
            $response = $this->client->get('models');
            $data = json_decode($response->getBody()->getContents(), true);
            
            $models = [];
            if (isset($data['data'])) {
                foreach ($data['data'] as $model) {
                    $models[] = $model['id'];
                }
            }
            return $models ?: ['llama3-70b-8192', 'llama3-8b-8192', 'mixtral-8x7b-32768'];
        } catch (Exception $e) {
            return ['llama3-70b-8192', 'llama3-8b-8192', 'mixtral-8x7b-32768'];
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'Groq';
    }
}

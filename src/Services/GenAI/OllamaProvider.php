<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class OllamaProvider implements GenAIProviderInterface
{
    private $apiUrl;
    private $jwtToken;
    private $client;

    public function __construct(?string $apiUrl = null, ?string $jwtToken = null)
    {
        $this->apiUrl = $apiUrl ?? $_ENV['OLLAMA_API_URL'] ?? 'http://localhost:11434/api/';
        $this->jwtToken = $jwtToken ?? $_ENV['OLLAMA_JWT_TOKEN'] ?? '';
        
        if (substr($this->apiUrl, -1) !== '/') {
            $this->apiUrl .= '/';
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        
        if (!empty($this->jwtToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->jwtToken;
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 300.0,
            'headers' => $headers,
        ]);
    }

    public function generate(string $prompt, string $model, array $options = []): array
    {
        try {
            $response = $this->client->post('generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'num_thread' => $options['num_thread'] ?? 8,
                        'num_ctx' => $options['num_ctx'] ?? 4096,
                        'temperature' => $options['temperature'] ?? 0.7,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'response' => $data['response'] ?? '',
                'model_used' => $model,
                'provider' => 'ollama',
            ];
        } catch (Exception $e) {
            throw new Exception('Ollama API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        try {
            $response = $this->client->post('generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'num_thread' => $options['num_thread'] ?? 8,
                        'num_ctx' => $options['num_ctx'] ?? 4096,
                        'temperature' => $options['temperature'] ?? 0.7,
                    ],
                ],
                'stream' => true,
            ]);

            $stream = $response->getBody();
            $fullResponse = '';

            while (!$stream->eof()) {
                $line = $stream->readLine();
                if (empty(trim($line))) {
                    continue;
                }

                $data = json_decode($line, true);
                if (isset($data['response'])) {
                    $chunk = $data['response'];
                    $fullResponse .= $chunk;
                    
                    if ($callback) {
                        $callback($chunk, $data['done'] ?? false);
                    }

                    if (isset($data['done']) && $data['done']) {
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Ollama streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        // This would ideally fetch from Ollama API, but for now return common models
        return [
            'llama3',
            'llama2',
            'mistral',
            'phi3',
            'tinyllama',
        ];
    }

    public function isAvailable(): bool
    {
        // Ollama is available if URL is configured (even without JWT)
        return !empty($this->apiUrl);
    }

    public function getName(): string
    {
        return 'Ollama';
    }
}

<?php

namespace App\Services\GenAI;

use GuzzleHttp\Client;
use Exception;

class OllamaProvider implements GenAIProviderInterface
{
    private $apiUrl;
    private $jwtToken;
    private $client;
    private $isCloud;
    private $cloudApiKey;

    public function __construct(?string $apiUrl = null, ?string $jwtToken = null, ?string $cloudApiKey = null)
    {
        $this->apiUrl = $apiUrl ?? $_ENV['OLLAMA_API_URL'] ?? 'http://localhost:11434/api/';
        $this->jwtToken = $jwtToken ?? $_ENV['OLLAMA_JWT_TOKEN'] ?? '';
        $this->cloudApiKey = $cloudApiKey ?? $_ENV['OLLAMA_CLOUD_API_KEY'] ?? '';
        
        // Detect if using cloud API
        $this->isCloud = $this->detectCloudMode();
        
        // Adjust URL for cloud mode
        if ($this->isCloud) {
            $this->apiUrl = 'https://api.ollama.com/v1/';
        }
        
        if (substr($this->apiUrl, -1) !== '/') {
            $this->apiUrl .= '/';
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        
        // Add JWT token for local Ollama
        if (!empty($this->jwtToken) && !$this->isCloud) {
            $headers['Authorization'] = 'Bearer ' . $this->jwtToken;
        }
        
        // Add API key for cloud Ollama
        if (!empty($this->cloudApiKey) && $this->isCloud) {
            $headers['Authorization'] = 'Bearer ' . $this->cloudApiKey;
        }

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 300.0,
            'headers' => $headers,
        ]);
    }
    
    /**
     * Detect if using cloud mode
     */
    private function detectCloudMode(): bool
    {
        // Check if cloud API key is provided
        if (!empty($this->cloudApiKey)) {
            return true;
        }
        
        // Check if URL is explicitly set to cloud
        $url = strtolower($this->apiUrl);
        if (strpos($url, 'api.ollama.com') !== false || strpos($url, 'cloud') !== false) {
            return true;
        }
        
        // Check environment variable
        $cloudMode = $_ENV['OLLAMA_CLOUD_MODE'] ?? 'false';
        return filter_var($cloudMode, FILTER_VALIDATE_BOOLEAN);
    }

    public function generate(string $prompt, string $model, array $options = []): array
    {
        try {
            if ($this->isCloud) {
                // Cloud API uses OpenAI-compatible format
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
                    'provider' => 'ollama-cloud',
                    'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ];
            } else {
                // Local Ollama API format
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
            }
        } catch (Exception $e) {
            throw new Exception('Ollama API error: ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model, array $options = [], callable $callback = null): void
    {
        try {
            if ($this->isCloud) {
                // Cloud API uses OpenAI-compatible streaming format
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
            } else {
                // Local Ollama streaming format
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

                while (!$stream->eof()) {
                    $line = $stream->readLine();
                    if (empty(trim($line))) {
                        continue;
                    }

                    $data = json_decode($line, true);
                    if (isset($data['response'])) {
                        $chunk = $data['response'];
                        
                        if ($callback) {
                            $callback($chunk, $data['done'] ?? false);
                        }

                        if (isset($data['done']) && $data['done']) {
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Ollama streaming error: ' . $e->getMessage());
        }
    }

    public function getModels(): array
    {
        try {
            if ($this->isCloud) {
                // Fetch models from cloud API
                $response = $this->client->get('models');
                $data = json_decode($response->getBody()->getContents(), true);
                
                $models = [];
                if (isset($data['data'])) {
                    foreach ($data['data'] as $model) {
                        $models[] = $model['id'];
                    }
                }
                return $models ?: ['llama3.2', 'llama3.1', 'mistral', 'phi3'];
            } else {
                // Fetch models from local Ollama API
                $response = $this->client->get('tags');
                $data = json_decode($response->getBody()->getContents(), true);
                
                $models = [];
                if (isset($data['models'])) {
                    foreach ($data['models'] as $model) {
                        $models[] = $model['name'];
                    }
                }
                return $models ?: ['llama3', 'llama2', 'mistral', 'phi3', 'tinyllama'];
            }
        } catch (Exception $e) {
            // Return default models if API call fails
            return $this->isCloud 
                ? ['llama3.2', 'llama3.1', 'mistral', 'phi3']
                : ['llama3', 'llama2', 'mistral', 'phi3', 'tinyllama'];
        }
    }

    public function isAvailable(): bool
    {
        // Ollama is available if URL is configured
        return !empty($this->apiUrl);
    }

    public function getName(): string
    {
        return $this->isCloud ? 'Ollama Cloud' : 'Ollama';
    }
    
    /**
     * Check if using cloud mode
     */
    public function isCloud(): bool
    {
        return $this->isCloud;
    }
}

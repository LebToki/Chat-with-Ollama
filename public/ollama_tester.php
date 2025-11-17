<?php
/**
 * Ollama Connection & Model Tester
 * 
 * Simple web-accessible version to test Ollama connectivity
 * 
 * Access: http://chat-with-ollama.local/ollama_tester.php
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers early
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ollama Tester</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; line-height: 1.6; }
        .test-section { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #444; }
        .success { border-left-color: #4ade80; color: #4ade80; }
        .error { border-left-color: #f87171; color: #f87171; }
        .warning { border-left-color: #fbbf24; color: #fbbf24; }
        .info { border-left-color: #60a5fa; color: #60a5fa; }
        pre { background: #1a1a1a; padding: 10px; border-radius: 5px; overflow-x: auto; border: 1px solid #444; }
        h1, h2 { color: #fff; }
        code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🦙 Ollama Connection Tester</h1>
    
<?php

try {
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (Exception $e) {
    echo "<div class='test-section error'><strong>[ERROR]</strong> Failed to load autoloader: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

// Load config
try {
    $config = require __DIR__ . '/../src/config.php';
    $ollamaApiUrl = $config['ollamaApiUrl'] ?? 'http://localhost:11434/api/';
    $jwtToken = $config['jwtToken'] ?? '';
    
    echo "<div class='test-section info'><strong>[INFO]</strong> Ollama API URL: <code>" . htmlspecialchars($ollamaApiUrl) . "</code></div>";
    echo "<div class='test-section " . ($jwtToken ? 'success' : 'warning') . "'><strong>[" . ($jwtToken ? 'SUCCESS' : 'WARNING') . "]</strong> JWT Token: " . ($jwtToken ? "Set (" . substr($jwtToken, 0, 10) . "...)" : "Not set (not required for local)") . "</div>";
} catch (Exception $e) {
    echo "<div class='test-section error'><strong>[ERROR]</strong> Failed to load config: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

// Test 1: API Connectivity
echo "<h2>=== Test 1: API Connectivity ===</h2>";

try {
    $client = new Client([
        'base_uri' => $ollamaApiUrl,
        'timeout' => 5.0,
        'headers' => $jwtToken ? ['Authorization' => 'Bearer ' . $jwtToken] : []
    ]);
    
    $response = $client->get('tags', ['http_errors' => false]);
    $statusCode = $response->getStatusCode();
    
    if ($statusCode === 200) {
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> Ollama API is reachable (Status: $statusCode)</div>";
    } else {
        echo "<div class='test-section warning'><strong>[WARNING]</strong> Ollama API responded with status: $statusCode</div>";
    }
} catch (GuzzleException $e) {
    echo "<div class='test-section error'><strong>[ERROR]</strong> Cannot connect to Ollama API: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='test-section info'><strong>[INFO]</strong> Check if Ollama is running: <code>ollama serve</code></div>";
    echo "<div class='test-section info'><strong>[INFO]</strong> Or verify the API URL in .env file</div>";
    echo "</body></html>";
    exit;
}

// Test 2: List Models
echo "<h2>=== Test 2: Available Models ===</h2>";

try {
    $response = $client->get('tags');
    $data = json_decode($response->getBody()->getContents(), true);
    
    if (isset($data['models']) && is_array($data['models'])) {
        $models = $data['models'];
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> Found " . count($models) . " model(s):</div>";
        
        foreach ($models as $model) {
            $name = $model['name'] ?? 'Unknown';
            $size = isset($model['size']) ? number_format($model['size'] / 1024 / 1024 / 1024, 2) . ' GB' : 'Unknown size';
            echo "<div class='test-section info'><strong>[INFO]</strong> • $name ($size)</div>";
        }
        
        if (empty($models)) {
            echo "<div class='test-section warning'><strong>[WARNING]</strong> No models found. Install a model: <code>ollama pull llama3.2:latest</code></div>";
        }
    } else {
        echo "<div class='test-section warning'><strong>[WARNING]</strong> Unexpected response format from Ollama API</div>";
    }
} catch (GuzzleException $e) {
    echo "<div class='test-section error'><strong>[ERROR]</strong> Failed to list models: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Test 3: Generate Request
echo "<h2>=== Test 3: Generate Request Test ===</h2>";

$testModel = 'llama3.2:latest';
try {
    $response = $client->get('tags');
    $data = json_decode($response->getBody()->getContents(), true);
    if (isset($data['models'][0]['name'])) {
        $testModel = $data['models'][0]['name'];
    }
} catch (Exception $e) {
    // Use default
}

echo "<div class='test-section info'><strong>[INFO]</strong> Testing with model: <code>$testModel</code></div>";

try {
    $generateData = [
        'model' => $testModel,
        'prompt' => 'Say "Hello" in one word.',
        'stream' => false,
        'options' => [
            'num_thread' => 4,
            'num_ctx' => 2048,
        ]
    ];
    
    echo "<div class='test-section info'><strong>[INFO]</strong> Sending generate request...</div>";
    
    $startTime = microtime(true);
    $response = $client->post('generate', [
        'json' => $generateData,
        'timeout' => 30.0
    ]);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['response'])) {
        $responseText = trim($result['response']);
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> Generate request successful!</div>";
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> Response: \"$responseText\"</div>";
        echo "<div class='test-section info'><strong>[INFO]</strong> Duration: {$duration}ms</div>";
    } else {
        echo "<div class='test-section warning'><strong>[WARNING]</strong> Generate request completed but no response field found</div>";
        echo "<div class='test-section info'><pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre></div>";
    }
    
} catch (GuzzleException $e) {
    $errorMsg = $e->getMessage();
    echo "<div class='test-section error'><strong>[ERROR]</strong> Generate request failed: " . htmlspecialchars($errorMsg) . "</div>";
    
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        echo "<div class='test-section error'><strong>[ERROR]</strong> Status Code: $statusCode</div>";
        echo "<div class='test-section error'><pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre></div>";
        
        if (strpos($body, 'model') !== false && strpos($body, 'not found') !== false) {
            echo "<div class='test-section warning'><strong>[WARNING]</strong> 💡 Model '$testModel' might not be installed!</div>";
            echo "<div class='test-section warning'><strong>[WARNING]</strong> Install it: <code>ollama pull $testModel</code></div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='test-section error'><strong>[ERROR]</strong> Unexpected error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Test 4: Configuration
echo "<h2>=== Test 4: Configuration Check ===</h2>";

$configFile = __DIR__ . '/../.env';
if (file_exists($configFile)) {
    echo "<div class='test-section success'><strong>[SUCCESS]</strong> .env file exists</div>";
    $envContent = file_get_contents($configFile);
    if (strpos($envContent, 'OLLAMA_API_URL') !== false) {
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> OLLAMA_API_URL is set in .env</div>";
    } else {
        echo "<div class='test-section warning'><strong>[WARNING]</strong> OLLAMA_API_URL not found in .env (using default)</div>";
    }
    if (strpos($envContent, 'OLLAMA_JWT_TOKEN') !== false) {
        echo "<div class='test-section success'><strong>[SUCCESS]</strong> OLLAMA_JWT_TOKEN is set in .env</div>";
    } else {
        echo "<div class='test-section info'><strong>[INFO]</strong> OLLAMA_JWT_TOKEN not set (not required for local Ollama)</div>";
    }
} else {
    echo "<div class='test-section warning'><strong>[WARNING]</strong> .env file not found (using defaults)</div>";
    echo "<div class='test-section info'><strong>[INFO]</strong> Create .env file with: <code>OLLAMA_API_URL=http://localhost:11434/api/</code></div>";
}

echo "<h2>=== Test Summary ===</h2>";
echo "<div class='test-section info'><strong>[INFO]</strong> All tests completed. Review the results above.</div>";

?>

</body>
</html>


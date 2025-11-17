<?php
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	use GuzzleHttp\Promise;
	use App\Services\RAGService;
	use App\Services\EmbeddingService;
	use App\Services\GenAI\GenAIFactory;
	use App\Database\Database;
	
	$config = require __DIR__ . '/../../src/config.php';
	
	$ollamaApiUrl = $config['ollamaApiUrl'];
	$jwtToken = $config['jwtToken'];
	
	header('Content-Type: application/json');
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$input = json_decode(file_get_contents('php://input'), true);
		$message = $_POST['message'] ?? $input['message'] ?? '';
		$model = $_POST['model'] ?? $input['model'] ?? 'llama3';
		$provider = $_POST['provider'] ?? $input['provider'] ?? $config['defaultProvider'] ?? 'ollama';
		$models = $_POST['models'] ?? $input['models'] ?? null; // Multiple models for parallel inference
		$useParallel = isset($_POST['use_parallel']) ? filter_var($_POST['use_parallel'], FILTER_VALIDATE_BOOLEAN) : false;
		$stream = isset($_POST['stream']) ? filter_var($_POST['stream'], FILTER_VALIDATE_BOOLEAN) : false;
		$file = $_FILES['file'] ?? null;
		$useRAG = isset($_POST['use_rag']) ? filter_var($_POST['use_rag'], FILTER_VALIDATE_BOOLEAN) : true;
		$sessionId = $_POST['session_id'] ?? $input['session_id'] ?? null;
		
		// Try to detect provider from model name if not specified
		if ($provider === 'ollama' || empty($provider)) {
			$detectedProvider = GenAIFactory::detectProviderFromModel($model);
			if ($detectedProvider !== 'ollama') {
				$provider = $detectedProvider;
			}
		}
		
		// Get the appropriate provider
		try {
			$genAIProvider = GenAIFactory::getProvider($provider);
		} catch (Exception $e) {
			// Fallback to Ollama if provider not available
			$provider = 'ollama';
			$genAIProvider = GenAIFactory::getProvider('ollama');
		}
		
		// Keep Ollama client for RAG (embeddings still use Ollama)
		$client = new Client([
			'base_uri' => $ollamaApiUrl,
			'timeout' => 120.0,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $jwtToken,
			],
		]);
		
		// RAG: Retrieve relevant context with parallel processing
		$contextChunks = [];
		if ($useRAG && !empty($message)) {
			try {
				$embeddingService = new EmbeddingService($ollamaApiUrl, $jwtToken);
				$ragService = new RAGService($embeddingService);
				
				// Use optimized parallel retrieval
				$contextChunks = $ragService->retrieveRelevantChunksOptimized($message, 5);
				
				if (!empty($contextChunks)) {
					$contextText = "\n\nRelevant context from documents:\n";
					foreach ($contextChunks as $chunk) {
						$contextText .= "- " . substr($chunk['content'], 0, 200) . "...\n";
					}
					$message = $contextText . "\n\nQuestion: " . $message;
				}
			} catch (Exception $e) {
				error_log("RAG retrieval failed: " . $e->getMessage());
			}
		}
		
		// Prepare base data
		$baseData = [
			'prompt' => $message,
			'stream' => $stream,
			'options' => [
				'num_thread' => 8,
				'num_ctx' => 4096,
			],
		];
		
		if ($file && is_uploaded_file($file['tmp_name'])) {
			$imageData = base64_encode(file_get_contents($file['tmp_name']));
			$baseData['images'] = [$imageData];
		}
		
		try {
			// Use GenAI provider for generation
			$options = [
				'temperature' => 0.7,
				'max_tokens' => 2048,
			];
			
			// For Ollama, add specific options
			if ($provider === 'ollama') {
				$options['num_thread'] = 8;
				$options['num_ctx'] = 4096;
			}
			
			// Single model inference
			$result = $genAIProvider->generate($message, $model, $options);
			$botResponse = $result['response'] ?? '';
			$model = $result['model_used'] ?? $model;
			
			// Store in database if session exists
			if ($sessionId) {
				try {
					$db = Database::getInstance()->getConnection();
					
					// Store user message
					$stmt = $db->prepare("
						INSERT INTO chat_messages (session_id, role, content, model_used, context_chunks)
						VALUES (:session_id, 'user', :content, :model, :context)
					");
					$stmt->execute([
						':session_id' => $sessionId,
						':content' => $message,
						':model' => $model,
						':context' => json_encode($contextChunks)
					]);
					
					// Store bot response
					$stmt = $db->prepare("
						INSERT INTO chat_messages (session_id, role, content, model_used)
						VALUES (:session_id, 'assistant', :content, :model)
					");
					$stmt->execute([
						':session_id' => $sessionId,
						':content' => $botResponse,
						':model' => $model
					]);
					
					// Update session timestamp
					$stmt = $db->prepare("UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
					$stmt->execute([':id' => $sessionId]);
				} catch (Exception $e) {
					error_log("Failed to store chat message: " . $e->getMessage());
				}
			}
			
			echo json_encode([
				'response' => $botResponse,
				'model_used' => $model,
				'provider' => $provider,
				'context_used' => !empty($contextChunks),
				'context_count' => count($contextChunks),
				'parallel_mode' => $useParallel
			]);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}
	} else {
		http_response_code(405);
		echo json_encode(['error' => 'Invalid request method']);
	}

<?php
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	use App\Services\RAGService;
	use App\Services\EmbeddingService;
	use App\Services\GenAI\GenAIFactory;
	use App\Database\Database;
	
	$config = require __DIR__ . '/../../src/config.php';
	
	$ollamaApiUrl = $config['ollamaApiUrl'];
	$jwtToken = $config['jwtToken'];
	
	// Set headers for Server-Sent Events (SSE)
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache');
	header('Connection: keep-alive');
	header('X-Accel-Buffering: no'); // Disable buffering for nginx
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$input = json_decode(file_get_contents('php://input'), true);
		$message = $_POST['message'] ?? $input['message'] ?? '';
		$model = $_POST['model'] ?? $input['model'] ?? 'llama3';
		$provider = $_POST['provider'] ?? $input['provider'] ?? $config['defaultProvider'] ?? 'ollama';
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
			'timeout' => 300.0,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $jwtToken,
			],
		]);
		
		// RAG: Retrieve relevant context
		$contextChunks = [];
		if ($useRAG && !empty($message)) {
			try {
				$embeddingService = new EmbeddingService($ollamaApiUrl, $jwtToken);
				$ragService = new RAGService($embeddingService);
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
		
		$options = [
			'temperature' => 0.7,
			'max_tokens' => 2048,
		];
		
		// For Ollama, add specific options
		if ($provider === 'ollama') {
			$options['num_thread'] = 8;
			$options['num_ctx'] = 4096;
		}
		
		try {
			$fullResponse = '';
			
			// Use provider's streaming method
			$genAIProvider->stream($message, $model, $options, function($chunk, $done) use (&$fullResponse) {
				$fullResponse .= $chunk;
				
				// Send chunk to client
				echo "data: " . json_encode([
					'type' => 'chunk',
					'content' => $chunk,
					'done' => $done
				]) . "\n\n";
				
				// Flush output immediately
				if (ob_get_level() > 0) {
					ob_flush();
				}
				flush();
			});
			
			// Store in database if session exists
			if ($sessionId && !empty($fullResponse)) {
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
						':content' => $fullResponse,
						':model' => $model
					]);
					
					// Update session timestamp
					$stmt = $db->prepare("UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
					$stmt->execute([':id' => $sessionId]);
					
					// Send completion event
					echo "data: " . json_encode([
						'type' => 'done',
						'provider' => $provider,
						'context_used' => !empty($contextChunks),
						'context_count' => count($contextChunks)
					]) . "\n\n";
					
				} catch (Exception $e) {
					error_log("Failed to store chat message: " . $e->getMessage());
				}
			}
			
		} catch (Exception $e) {
			echo "data: " . json_encode([
				'type' => 'error',
				'error' => $e->getMessage()
			]) . "\n\n";
		}
	} else {
		http_response_code(405);
		echo "data: " . json_encode(['error' => 'Invalid request method']) . "\n\n";
	}

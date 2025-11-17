<?php
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	use App\Services\RAGService;
	use App\Services\EmbeddingService;
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
		$file = $_FILES['file'] ?? null;
		$useRAG = isset($_POST['use_rag']) ? filter_var($_POST['use_rag'], FILTER_VALIDATE_BOOLEAN) : true;
		$sessionId = $_POST['session_id'] ?? $input['session_id'] ?? null;
		
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
		
		$data = [
			'model' => $model,
			'prompt' => $message,
			'stream' => true,
			'options' => [
				'num_thread' => 8,
				'num_ctx' => 4096,
			],
		];
		
		if ($file && is_uploaded_file($file['tmp_name'])) {
			$imageData = base64_encode(file_get_contents($file['tmp_name']));
			$data['images'] = [$imageData];
		}
		
		try {
			$response = $client->post('generate', [
				'json' => $data,
				'stream' => true,
			]);
			
			$fullResponse = '';
			$stream = $response->getBody();
			
			// Stream response chunks
			while (!$stream->eof()) {
				$line = $stream->readLine();
				if (empty(trim($line))) continue;
				
				$data = json_decode($line, true);
				if (isset($data['response'])) {
					$fullResponse .= $data['response'];
					
					// Send chunk to client
					echo "data: " . json_encode([
						'type' => 'chunk',
						'content' => $data['response'],
						'done' => $data['done'] ?? false
					]) . "\n\n";
					
					// Flush output immediately
					if (ob_get_level() > 0) {
						ob_flush();
					}
					flush();
					
					if (isset($data['done']) && $data['done']) {
						break;
					}
				}
			}
			
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

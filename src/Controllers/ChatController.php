<?php
	// Ensure no output before headers
	if (ob_get_level()) {
		ob_clean();
	}
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	use GuzzleHttp\Exception\GuzzleException;
	use App\Services\RAGService;
	use App\Services\EmbeddingService;
	use App\Database\Database;
	use App\Http\RequestHelper;
	use App\Http\ApiResponse;
	
	try {
		$config = require __DIR__ . '/../../src/config.php';
		
		if (!isset($config['ollamaApiUrl']) || !isset($config['jwtToken'])) {
			throw new Exception('Configuration missing: ollamaApiUrl or jwtToken not found');
		}
		
		$ollamaApiUrl = $config['ollamaApiUrl'];
		$jwtToken = $config['jwtToken'];
	} catch (Exception $e) {
		header('Content-Type: application/json');
		http_response_code(500);
		error_log("ChatController Config Error: " . $e->getMessage());
		echo json_encode([
			'success' => false,
			'error' => 'Configuration error',
			'code' => 500,
			'details' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? ['message' => $e->getMessage()] : []
		]);
		exit;
	}
	
	header('Content-Type: application/json');
	
	// Enable detailed error reporting
	$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
	ini_set('display_errors', 0); // Don't display, but log
	ini_set('log_errors', 1);
	error_reporting(E_ALL);
	
	try {
		// Enhanced error logging at the start
		error_log("ChatController: Request received - " . print_r([
			'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
			'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
			'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
			'has_post_data' => !empty($_POST),
			'has_files' => !empty($_FILES),
			'post_keys' => !empty($_POST) ? array_keys($_POST) : [],
			'file_keys' => !empty($_FILES) ? array_keys($_FILES) : []
		], true));
		
		if (RequestHelper::isMethod('POST')) {
			$message = RequestHelper::getInput('message', '');
			$model = RequestHelper::getInput('model', 'llama3.2:latest');
			$file = RequestHelper::getFile('file');
			
			// Enhanced debug logging
			error_log("ChatController: Processing POST request - " . print_r([
				'message' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
				'message_length' => strlen($message),
				'model' => $model,
				'has_file' => !empty($file),
				'file_name' => $file ? $file['name'] : 'none',
				'file_size' => $file ? $file['size'] : 0,
				'session_id' => RequestHelper::getInput('session_id'),
				'use_rag' => RequestHelper::getInput('use_rag', true)
			], true));
			
			// Handle use_rag - can be string 'true'/'false' or boolean
			$useRAGInput = RequestHelper::getInput('use_rag', true);
			if (is_string($useRAGInput)) {
				$useRAG = filter_var($useRAGInput, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($useRAG === null) {
					// String is not 'true' or 'false', check for '1' or '0'
					$useRAG = ($useRAGInput === '1' || strtolower($useRAGInput) === 'true');
				}
			} else {
				$useRAG = (bool)$useRAGInput;
			}
			
			$sessionId = RequestHelper::getInput('session_id');
			
			$client = new Client([
			'base_uri' => $ollamaApiUrl,
			'timeout' => 60.0,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $jwtToken,
			],
		]);
		
		// RAG: Retrieve relevant context
		$contextChunks = [];
		$documentIds = null;
		
		// Check if session is linked to a specific document
		if ($sessionId) {
			try {
				$db = Database::getInstance()->getConnection();
				$stmt = $db->prepare("SELECT document_id FROM chat_sessions WHERE id = :id");
				$stmt->execute([':id' => $sessionId]);
				$session = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($session && !empty($session['document_id'])) {
					$documentIds = [(int)$session['document_id']];
				}
			} catch (Exception $e) {
				error_log("Failed to check session document: " . $e->getMessage());
			}
		}
		
		// Parse @ mentions in message (keep original message for display, extract IDs for RAG filtering)
		$originalMessage = $message;
		$ragQuery = $message;
		if (preg_match_all('/@(\d+)/', $message, $matches)) {
			$mentionedDocIds = array_map('intval', $matches[1]);
			if ($documentIds) {
				$documentIds = array_unique(array_merge($documentIds, $mentionedDocIds));
			} else {
				$documentIds = $mentionedDocIds;
			}
			// Keep @ mentions in the message for user visibility, but use clean version for RAG query
			$ragQuery = preg_replace('/@\d+\s*/', '', $message);
			$ragQuery = preg_replace('/\s+/', ' ', trim($ragQuery));
		}
		
		if ($useRAG && !empty($ragQuery)) {
			try {
				$embeddingService = new EmbeddingService($ollamaApiUrl, $jwtToken);
				$ragService = new RAGService($embeddingService);
				$contextChunks = $ragService->retrieveRelevantChunks($ragQuery, 5, $documentIds);
				
				if (!empty($contextChunks)) {
					$contextText = "\n\nRelevant context from documents:\n";
					foreach ($contextChunks as $chunk) {
						$contextText .= "- " . substr($chunk['content'], 0, 200) . "...\n";
					}
					$message = $contextText . "\n\nQuestion: " . $originalMessage;
				}
			} catch (Exception $e) {
				error_log("RAG retrieval failed: " . $e->getMessage());
			}
		}
		
		$data = [
			'model' => $model,
			'prompt' => $message,
			'stream' => false,
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
			// Validate message is not empty
			if (empty($message) && !$file) {
				ApiResponse::error('Message is required', 400);
			}
			
			// Retry logic for model loading
			$maxRetries = 5;
			$retryDelay = 2; // Start with 2 seconds
			$response = null;
			$lastError = null;
			
			for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
				try {
					$response = $client->post('generate', [
						'json' => $data,
						'timeout' => 60.0,
					]);
					break; // Success, exit retry loop
				} catch (GuzzleException $e) {
					$lastError = $e;
					
					// Check if it's a model loading error
					if ($e->hasResponse()) {
						$errorBody = $e->getResponse()->getBody()->getContents();
						$errorData = json_decode($errorBody, true);
						
						// Check for model loading error
						if (isset($errorData['error']) && 
							(strpos($errorData['error'], 'loading model') !== false || 
							 strpos($errorData['error'], 'llm server loading') !== false)) {
							
							if ($attempt < $maxRetries) {
								error_log("ChatController: Model is loading, retry attempt $attempt/$maxRetries in {$retryDelay}s");
								sleep($retryDelay);
								$retryDelay = min($retryDelay * 1.5, 10); // Exponential backoff, max 10 seconds
								continue; // Retry
							} else {
								throw new Exception('Model is still loading after ' . $maxRetries . ' attempts. Please wait a moment and try again.');
							}
						}
					}
					
					// If it's not a loading error, throw immediately
					throw $e;
				}
			}
			
			if (!$response) {
				throw $lastError ?: new Exception('Failed to get response from Ollama API');
			}
			
			$result = json_decode($response->getBody()->getContents(), true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Invalid JSON response from Ollama API: ' . json_last_error_msg());
			}
			
			$botResponse = $result['response'] ?? '';
			
			if (empty($botResponse)) {
				throw new Exception('Empty response from Ollama API');
			}
			
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
						':content' => $originalMessage, // Store original message with @ mentions
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
				'context_used' => !empty($contextChunks),
				'context_count' => count($contextChunks)
			]);
		} catch (Exception $e) {
			// Enhanced error logging
			error_log("ChatController Error: " . $e->getMessage());
			error_log("ChatController Error File: " . $e->getFile() . " Line: " . $e->getLine());
			error_log("ChatController Error Trace: " . $e->getTraceAsString());
			
			// Use ApiResponse for consistent error format
			$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
			$details = $isDebug ? [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			] : [];
			
			ApiResponse::serverError($e->getMessage(), $details);
		}
		} else {
			ApiResponse::methodNotAllowed('POST');
		}
	} catch (Exception $e) {
		// Enhanced error logging for fatal errors
		error_log("ChatController Fatal Error: " . $e->getMessage());
		error_log("ChatController Fatal Error File: " . $e->getFile() . " Line: " . $e->getLine());
		error_log("ChatController Fatal Error Trace: " . $e->getTraceAsString());
		
		$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
		$details = $isDebug ? [
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		] : [];
		
		ApiResponse::serverError($e->getMessage(), $details);
	}

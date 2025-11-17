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
	use App\Services\GenAI\GenAIFactory;
	use App\Database\Database;
	use App\Http\RequestHelper;
	use App\Http\ApiResponse;
	
	try {
		$config = require __DIR__ . '/../../src/config.php';
		
		// Ollama config is still needed for RAG embeddings
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
			// Free version: Force Ollama provider only
			$provider = 'ollama';
			$file = RequestHelper::getFile('file');
			
			// Enhanced debug logging
			error_log("ChatController: Processing POST request - " . print_r([
				'message' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
				'message_length' => strlen($message),
				'model' => $model,
				'provider' => $provider,
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
			
			// Free version: Only Ollama provider is available
			try {
				$genAIProvider = GenAIFactory::getProvider('ollama');
			} catch (Exception $e) {
				error_log("ChatController: Ollama provider not available: " . $e->getMessage());
				throw $e;
			}
		
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
		
		// Prepare options for the provider
		$options = [
			'temperature' => 0.7,
			'max_tokens' => 2048,
		];
		
		// For Ollama, add specific options
		if ($provider === 'ollama') {
			$options['num_thread'] = 8;
			$options['num_ctx'] = 4096;
		}
		
		// Handle file uploads (currently only supported by Ollama)
		if ($file && is_uploaded_file($file['tmp_name'])) {
			if ($provider !== 'ollama') {
				// For now, only Ollama supports image inputs
				error_log("ChatController: File uploads only supported with Ollama provider, switching to Ollama");
				$provider = 'ollama';
				$genAIProvider = GenAIFactory::getProvider('ollama');
			}
			// Note: Image handling would need to be added to OllamaProvider
		}
		
		try {
			// Validate message is not empty
			if (empty($message) && !$file) {
				ApiResponse::error('Message is required', 400);
			}
			
			// Use provider to generate response
			$result = $genAIProvider->generate($message, $model, $options);
			
			$botResponse = $result['response'] ?? '';
			$modelUsed = $result['model_used'] ?? $model;
			$providerUsed = $result['provider'] ?? $provider;
			$inputTokens = $result['input_tokens'] ?? 0;
			$outputTokens = $result['output_tokens'] ?? 0;
			$totalTokens = $result['total_tokens'] ?? ($inputTokens + $outputTokens);
			$cost = $result['cost_usd'] ?? 0.0;
			
			if (empty($botResponse)) {
				throw new Exception('Empty response from ' . $providerUsed . ' API');
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
						':model' => $modelUsed . ' (' . $providerUsed . ')',
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
						':model' => $modelUsed . ' (' . $providerUsed . ')'
					]);
					
					// Provider usage tracking removed - premium feature (NexusAI only)
					// Usage analytics available in NexusAI Chat: https://2tinteractive.com
					
					// Update session timestamp
					$stmt = $db->prepare("UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
					$stmt->execute([':id' => $sessionId]);
				} catch (Exception $e) {
					error_log("Failed to store chat message or usage: " . $e->getMessage());
				}
			}
			
			echo json_encode([
				'response' => $botResponse,
				'context_used' => !empty($contextChunks),
				'context_count' => count($contextChunks),
				'provider' => $providerUsed,
				'model_used' => $modelUsed
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

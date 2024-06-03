<?php
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	
	$config = require __DIR__ . '/../../src/config.php';
	
	$ollamaApiUrl = $config['ollamaApiUrl'];
	$jwtToken = $config['jwtToken'];
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$input = json_decode(file_get_contents('php://input'), true);
		$message = $_POST['message'] ?? '';
		$model = $_POST['model'] ?? 'llama3';
		$file = $_FILES['file'] ?? null;
		
		$client = new Client([
			'base_uri' => $ollamaApiUrl,
			'timeout' => 30.0,
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $jwtToken,
			],
		]);
		
		$data = [
			'model' => $model,
			'prompt' => $message,
			'stream' => false,
			'options' => [
				'num_thread' => 8,
				'num_ctx' => 2024,
			],
		];
		
		if ($file) {
			$data['file'] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
		}
		
		try {
			$response = $client->post('generate', [
				'json' => $data,
			]);
			
			$result = json_decode($response->getBody()->getContents(), true);
			echo json_encode($result['response']);
		} catch (Exception $e) {
			echo json_encode(['error' => $e->getMessage()]);
		}
	} else {
		echo json_encode(['error' => 'Invalid request method']);
	}

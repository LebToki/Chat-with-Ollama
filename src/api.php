<?php
	require __DIR__ . '/../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	
	$config = require __DIR__ . '/config.php';
	
	$ollamaApiUrl = $config['ollamaApiUrl'];
	$jwtToken = $config['jwtToken'];
	
	if (substr($ollamaApiUrl, -1) !== '/') {
		$ollamaApiUrl .= '/';
	}
	
	$client = new Client([
		'base_uri' => $ollamaApiUrl,
		'timeout' => 30.0,
		'headers' => [
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $jwtToken,
		],
	]);
	
	$data = json_decode(file_get_contents('php://input'), true);
	
	try {
		$response = $client->post('generate', [
			'json' => $data,
		]);
		
		$result = json_decode($response->getBody()->getContents(), true);
		header('Content-Type: application/json');
		echo json_encode($result);
	} catch (Exception $e) {
		header('Content-Type: application/json');
		echo json_encode(['error' => $e->getMessage()]);
	}

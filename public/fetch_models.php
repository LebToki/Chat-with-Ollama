<?php
	require __DIR__ . '/../vendor/autoload.php';
	
	use GuzzleHttp\Client;
	
	$config = require __DIR__ . '/../src/config.php';
	
	$ollamaApiUrl = $config['ollamaApiUrl'];
	$jwtToken = $config['jwtToken'];
	
	$client = new Client([
		'base_uri' => $ollamaApiUrl,
		'timeout'  => 30.0,
		'headers' => [
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $jwtToken,
		],
	]);
	
	try {
		$response = $client->get('models');
		$models = json_decode($response->getBody()->getContents(), true);
		
		$modelsData = [];
		foreach ($models as $model) {
			$modelsData[] = ['name' => $model['name']];
		}
		
		file_put_contents(__DIR__ . '/../src/Models/models.json', json_encode($modelsData));
		echo json_encode(['success' => 'Models fetched and saved successfully.']);
	} catch (Exception $e) {
		echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
	}
?>

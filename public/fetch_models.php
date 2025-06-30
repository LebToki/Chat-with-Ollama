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
                // Use the tags endpoint to list locally installed models
                $response = $client->get('tags');
                $result = json_decode($response->getBody()->getContents(), true);

                $modelsData = [];
                foreach ($result['models'] ?? [] as $model) {
                        $modelsData[] = [
                                'name'        => $model['name'],
                                'size'        => $model['size'] ?? 0,
                                'modified_at' => $model['modified_at'] ?? '',
                                'digest'      => $model['digest'] ?? '',
                                'ready'       => true,
                        ];
                }

                $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
                file_put_contents(__DIR__ . '/../src/Models/models.json', json_encode($modelsData, $jsonOptions));
                echo json_encode(['success' => true, 'count' => count($modelsData)]);
        } catch (Exception $e) {
                echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
?>

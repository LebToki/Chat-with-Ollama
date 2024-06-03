<?php
	
	require __DIR__ . '/vendor/autoload.php';
	
	use Dotenv\Dotenv;
	
	$dotenv = Dotenv::createImmutable(__DIR__);
	$dotenv->load();
	
	$ollamaApiUrl = getenv('OLLAMA_API_URL');
	$jwtToken = getenv('OLLAMA_JWT_TOKEN');
	
	echo "OLLAMA_API_URL: " . $ollamaApiUrl . PHP_EOL;
	echo "OLLAMA_JWT_TOKEN: " . $jwtToken . PHP_EOL;

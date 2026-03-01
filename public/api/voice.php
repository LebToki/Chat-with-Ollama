<?php

require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Services/VoiceService.php';
require __DIR__ . '/../src/Http/RequestHelper.php';
require __DIR__ . '/../src/Http/Response.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$voiceService = new \App\Services\VoiceService();

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'text_to_speech':
            $text = RequestHelper::getInput('text', '');
            $language = RequestHelper::getInput('language', 'en');
            $voice = RequestHelper::getInput('voice', 'default');
            
            if (empty($text)) {
                Response::json(['success' => false, 'error' => 'Text is required'], 400);
                exit;
            }
            
            try {
                $result = $voiceService->textToSpeech($text, $language, $voice);
                Response::json($result);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'speech_to_text':
            if (!isset($_FILES['audio_file'])) {
                Response::json(['success' => false, 'error' => 'Audio file is required'], 400);
                exit;
            }
            
            $audioFile = $_FILES['audio_file']['tmp_name'];
            $language = RequestHelper::getInput('language', 'en');
            
            try {
                $result = $voiceService->speechToText($audioFile, $language);
                Response::json($result);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'get_voices':
            try {
                $voices = $voiceService->getAvailableVoices();
                Response::json(['success' => true, 'voices' => $voices]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('GET')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'status':
            $providers = [
                'tts' => $voiceService->getTTSProvider(),
                'stt' => $voiceService->getSTTProvider()
            ];
            
            Response::json([
                'success' => true,
                'providers' => $providers,
                'browser_support' => [
                    'speech_recognition' => class_exists('SpeechRecognition') || class_exists('webkitSpeechRecognition'),
                    'speech_synthesis' => class_exists('speechSynthesis'),
                    'audio_context' => class_exists('AudioContext') || class_exists('webkitAudioContext')
                ]
            ]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
}
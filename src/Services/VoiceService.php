<?php

namespace App\Services;

use Exception;

class VoiceService
{
    private $ttsProvider;
    private $sttProvider;
    private $audioDir;
    
    public function __construct()
    {
        $this->audioDir = __DIR__ . '/../../data/audio';
        if (!file_exists($this->audioDir)) {
            mkdir($this->audioDir, 0755, true);
        }
        
        // Initialize providers based on configuration
        $this->initProviders();
    }
    
    private function initProviders()
    {
        // Check for available TTS providers
        if (function_exists('shell_exec') && $this->isCommandAvailable('espeak')) {
            $this->ttsProvider = 'espeak';
        } elseif (function_exists('shell_exec') && $this->isCommandAvailable('say')) {
            $this->ttsProvider = 'say'; // macOS
        } else {
            $this->ttsProvider = null;
        }
        
        // Check for available STT providers
        if (function_exists('shell_exec') && $this->isCommandAvailable('whisper')) {
            $this->sttProvider = 'whisper';
        } else {
            $this->sttProvider = null;
        }
    }
    
    private function isCommandAvailable($command)
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        return shell_exec("$which $command") !== null;
    }
    
    /**
     * Convert text to speech
     */
    public function textToSpeech(string $text, string $language = 'en', string $voice = 'default'): array
    {
        if (!$this->ttsProvider) {
            return [
                'success' => false,
                'error' => 'No TTS provider available'
            ];
        }
        
        $filename = uniqid('tts_') . '.mp3';
        $filepath = $this->audioDir . '/' . $filename;
        
        try {
            switch ($this->ttsProvider) {
                case 'espeak':
                    $command = sprintf(
                        'espeak -v %s -s 140 -p 50 -g 10 -w %s "%s"',
                        escapeshellarg($language),
                        escapeshellarg($filepath),
                        escapeshellarg($text)
                    );
                    break;
                    
                case 'say':
                    $command = sprintf(
                        'say -v %s -o %s %s',
                        escapeshellarg($voice),
                        escapeshellarg($filepath),
                        escapeshellarg($text)
                    );
                    break;
                    
                default:
                    return ['success' => false, 'error' => 'Unsupported TTS provider'];
            }
            
            $result = shell_exec($command);
            
            if (file_exists($filepath)) {
                return [
                    'success' => true,
                    'audio_url' => '/data/audio/' . $filename,
                    'file_path' => $filepath
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to generate audio file'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Convert speech to text
     */
    public function speechToText(string $audioFile, string $language = 'en'): array
    {
        if (!$this->sttProvider) {
            return [
                'success' => false,
                'error' => 'No STT provider available'
            ];
        }
        
        if (!file_exists($audioFile)) {
            return [
                'success' => false,
                'error' => 'Audio file not found'
            ];
        }
        
        try {
            $outputFile = tempnam(sys_get_temp_dir(), 'stt_') . '.txt';
            
            switch ($this->sttProvider) {
                case 'whisper':
                    $command = sprintf(
                        'whisper %s --model base --language %s --output_dir %s',
                        escapeshellarg($audioFile),
                        escapeshellarg($language),
                        escapeshellarg(sys_get_temp_dir())
                    );
                    break;
                    
                default:
                    return ['success' => false, 'error' => 'Unsupported STT provider'];
            }
            
            $result = shell_exec($command);
            
            if (file_exists($outputFile)) {
                $transcript = file_get_contents($outputFile);
                unlink($outputFile);
                
                return [
                    'success' => true,
                    'transcript' => $transcript,
                    'confidence' => 0.85 // Placeholder confidence score
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to transcribe audio'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get available voices
     */
    public function getAvailableVoices(): array
    {
        if ($this->ttsProvider === 'say') {
            $output = shell_exec('say -v ?');
            $voices = [];
            
            if ($output) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (preg_match('/^(\w+)\s+(.+)$/', trim($line), $matches)) {
                        $voices[] = [
                            'name' => $matches[1],
                            'description' => $matches[2]
                        ];
                    }
                }
            }
            
            return $voices;
        }
        
        return [];
    }
    
    /**
     * Clean up old audio files
     */
    public function cleanupOldFiles(int $maxAgeHours = 24): int
    {
        $deleted = 0;
        $currentTime = time();
        $maxAge = $maxAgeHours * 3600;
        
        $files = glob($this->audioDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($currentTime - filemtime($file) > $maxAge) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
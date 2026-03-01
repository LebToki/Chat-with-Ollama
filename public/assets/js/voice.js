// Voice Service JavaScript Module
class VoiceService {
    constructor() {
        this.recognition = null;
        this.audioContext = null;
        this.mediaRecorder = null;
        this.isRecording = false;
        this.audioChunks = [];
        
        this.initSpeechRecognition();
        this.initAudioContext();
    }
    
    initSpeechRecognition() {
        // Initialize Web Speech API for speech recognition
        if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';
            
            this.recognition.onresult = (event) => {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                
                // Update UI with interim results
                this.updateTranscript(transcript, event.results[event.results.length - 1].isFinal);
            };
            
            this.recognition.onend = () => {
                this.isRecording = false;
                this.updateRecordingState(false);
            };
        }
    }
    
    initAudioContext() {
        // Initialize Web Audio API for TTS
        if ('AudioContext' in window || 'webkitAudioContext' in window) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
    }
    
    // Speech to Text functionality
    startRecording() {
        if (!this.recognition) {
            this.showError('Speech recognition not supported in this browser');
            return;
        }
        
        this.audioChunks = [];
        this.isRecording = true;
        this.updateRecordingState(true);
        
        try {
            this.recognition.start();
        } catch (error) {
            this.showError('Failed to start recording: ' + error.message);
            this.isRecording = false;
            this.updateRecordingState(false);
        }
    }
    
    stopRecording() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
        }
    }
    
    // Text to Speech functionality
    async speakText(text, voice = 'default', rate = 1, pitch = 1) {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            
            // Set voice
            if (voice !== 'default') {
                const voices = speechSynthesis.getVoices();
                const selectedVoice = voices.find(v => v.name === voice);
                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                }
            }
            
            // Set speech parameters
            utterance.rate = rate;
            utterance.pitch = pitch;
            utterance.volume = 1;
            
            // Speak
            speechSynthesis.speak(utterance);
            
            return true;
        } else {
            console.warn('Speech synthesis not supported in this browser');
            return false;
        }
    }
    
    // Audio recording for TTS output
    async recordAudio(duration = 5000) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };
            
            this.mediaRecorder.onstop = () => {
                const blob = new Blob(this.audioChunks, { type: 'audio/wav' });
                this.uploadAudioBlob(blob);
            };
            
            this.mediaRecorder.start();
            
            // Stop recording after specified duration
            setTimeout(() => {
                this.mediaRecorder.stop();
            }, duration);
            
        } catch (error) {
            this.showError('Failed to access microphone: ' + error.message);
        }
    }
    
    // Upload recorded audio for processing
    async uploadAudioBlob(blob) {
        const formData = new FormData();
        formData.append('audio_file', blob, 'recording.wav');
        formData.append('action', 'speech_to_text');
        
        try {
            const response = await fetch('/api/voice/process', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.processVoiceInput(result.transcript);
            } else {
                this.showError('Voice processing failed: ' + result.error);
            }
            
        } catch (error) {
            this.showError('Failed to upload audio: ' + error.message);
        }
    }
    
    // Process voice input in chat
    processVoiceInput(transcript) {
        // Add transcript to chat input
        const chatInput = document.getElementById('user-input');
        if (chatInput) {
            chatInput.value = transcript;
        }
        
        // Trigger chat submission
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.dispatchEvent(new Event('submit'));
        }
    }
    
    // Update UI state
    updateRecordingState(isRecording) {
        const button = document.getElementById('voice-record-btn');
        if (button) {
            if (isRecording) {
                button.classList.add('recording');
                button.innerHTML = '<i class="fas fa-stop"></i> Stop Recording';
            } else {
                button.classList.remove('recording');
                button.innerHTML = '<i class="fas fa-microphone"></i> Voice Input';
            }
        }
    }
    
    updateTranscript(text, isFinal) {
        const transcriptElement = document.getElementById('transcript-display');
        if (transcriptElement) {
            transcriptElement.textContent = text;
            if (isFinal) {
                transcriptElement.classList.add('final');
            } else {
                transcriptElement.classList.remove('final');
            }
        }
    }
    
    showError(message) {
        const errorElement = document.getElementById('voice-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 5000);
        }
    }
    
    // Get available voices for TTS
    getAvailableVoices() {
        if ('speechSynthesis' in window) {
            return speechSynthesis.getVoices();
        }
        return [];
    }
    
    // Cleanup resources
    cleanup() {
        if (this.recognition) {
            this.recognition.stop();
        }
        
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }
        
        if (this.audioContext) {
            this.audioContext.close();
        }
    }
}

// Initialize voice service when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.voiceService = new VoiceService();
    
    // Setup voice input button
    const voiceBtn = document.getElementById('voice-record-btn');
    if (voiceBtn) {
        voiceBtn.addEventListener('click', function() {
            if (window.voiceService.isRecording) {
                window.voiceService.stopRecording();
            } else {
                window.voiceService.startRecording();
            }
        });
    }
    
    // Setup TTS toggle
    const ttsToggle = document.getElementById('tts-toggle');
    if (ttsToggle) {
        ttsToggle.addEventListener('change', function() {
            localStorage.setItem('tts_enabled', this.checked);
        });
    }
    
    // Auto-enable TTS if previously enabled
    const ttsEnabled = localStorage.getItem('tts_enabled') === 'true';
    if (ttsToggle) {
        ttsToggle.checked = ttsEnabled;
    }
    
    // Listen for new chat messages to enable TTS
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('bot-message')) {
                        if (ttsEnabled && window.voiceService) {
                            const text = node.textContent || node.innerText;
                            window.voiceService.speakText(text);
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.getElementById('chatbox'), {
        childList: true,
        subtree: true
    });
});

// Add voice controls to chat interface
function addVoiceControls() {
    const chatControls = document.querySelector('.chat-controls');
    if (chatControls) {
        const voiceControls = document.createElement('div');
        voiceControls.className = 'voice-controls';
        voiceControls.innerHTML = `
            <div class="voice-status">
                <button id="voice-record-btn" class="btn btn-outline-primary">
                    <i class="fas fa-microphone"></i> Voice Input
                </button>
                <div id="transcript-display" class="transcript-display"></div>
                <div id="voice-error" class="voice-error"></div>
            </div>
            <div class="tts-controls">
                <label class="tts-toggle">
                    <input type="checkbox" id="tts-toggle">
                    <span>Enable Text-to-Speech</span>
                </label>
            </div>
        `;
        
        chatControls.appendChild(voiceControls);
    }
}

// Call this function to add voice controls to the chat interface
addVoiceControls();
// Modern Chat with RAG Integration
let currentSessionId = null;
let ragEnabled = true;
let uploadedFiles = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
    loadChatSessions();
    loadDocuments();
    setupEventListeners();
    loadProviders();
});

function initializeChat() {
    // Auto-resize textarea
    const chatInput = document.getElementById('chat-input');
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
    
    // Enter to send, Shift+Enter for new line
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Load session from URL or create new
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session');
    if (sessionId) {
        loadChatSession(sessionId);
    } else {
        createNewChat();
    }
}

function setupEventListeners() {
    document.getElementById('send-btn').addEventListener('click', sendMessage);
    
    const attachBtn = document.getElementById('attach-file-btn');
    if (attachBtn) {
        attachBtn.addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = 'image/*,.pdf,.docx,.txt,.xlsx,.csv,.md';
            input.addEventListener('change', (e) => {
                Array.from(e.target.files).forEach(file => {
                    uploadedFiles.push(file);
                });
                updateFilePreview();
            });
            input.click();
        });
    }
    
    document.getElementById('rag-toggle').addEventListener('click', toggleRAG);
    
    // File input for document modal
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    // Drag and drop (for document modal)
    const uploadZone = document.getElementById('upload-zone');
    if (uploadZone) {
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        uploadZone.addEventListener('click', () => {
            if (fileInput) fileInput.click();
        });
    }
}

function handleFileSelect(e) {
    handleFiles(e.target.files);
}

function handleFiles(files) {
    Array.from(files).forEach(file => {
        uploadDocument(file);
    });
}

function uploadDocument(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload');
    
    showNotification(`Uploading ${file.name}...`, 'info');
    
    axios.post('/src/Controllers/RAGController.php', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(response => {
        if (response.data.success) {
            showNotification(`${file.name} uploaded successfully!`, 'success');
            loadDocuments();
        } else {
            showNotification(`Upload failed: ${response.data.error}`, 'error');
        }
    })
    .catch(error => {
        showNotification(`Upload error: ${error.message}`, 'error');
    });
}

function sendMessage(useStreaming = true) {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message && uploadedFiles.length === 0) return;
    
    // Add user message to UI
    if (message) {
        addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
    }
    
    // Check if streaming is enabled (default: true for better UX)
    const enableStreaming = useStreaming && localStorage.getItem('enableStreaming') !== 'false';
    
    if (enableStreaming) {
        sendMessageStreaming(message);
    } else {
        sendMessageStandard(message);
    }
}

function sendMessageStreaming(message) {
    // Show loading indicator with playful animated message
    const loadingId = addMessage('', 'assistant', true);
    const messageElement = document.getElementById(loadingId);
    const contentDiv = messageElement.querySelector('.message-content');
    contentDiv.innerHTML = '<div class="playful-loading"><div class="playful-message"></div><div class="typing-indicator"><span></span><span></span><span></span></div></div>';
    
    // Start showing playful messages
    showPlayfulMessages(loadingId);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('message', message);
    formData.append('model', document.getElementById('model-select')?.value || 'llama3');
    formData.append('provider', document.getElementById('provider-select')?.value || 'ollama');
    formData.append('use_rag', ragEnabled);
    formData.append('stream', true);
    if (currentSessionId) {
        formData.append('session_id', currentSessionId);
    }
    
    // Add files if any
    uploadedFiles.forEach(file => {
        formData.append('file', file);
    });
    
    // Use fetch for streaming
    fetch('/src/Controllers/StreamChatController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Stream failed');
        
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullResponse = '';
        
        function readStream() {
            reader.read().then(({ done, value }) => {
                if (done) {
                    // Stop playful messages
                    stopPlayfulMessages();
                    // Update final message
                    contentDiv.innerHTML = formatMessage(fullResponse);
                    uploadedFiles = [];
                    updateFilePreview();
                    loadChatSessions();
                    return;
                }
                
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop(); // Keep incomplete line in buffer
                
                lines.forEach(line => {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.slice(6));
                            if (data.type === 'chunk') {
                                // Stop playful messages when content starts streaming
                                if (fullResponse === '') {
                                    stopPlayfulMessages();
                                }
                                fullResponse += data.content;
                                contentDiv.innerHTML = formatMessage(fullResponse) + '<div class="typing-indicator"><span></span><span></span><span></span></div>';
                                scrollToBottom();
                            } else if (data.type === 'done') {
                                stopPlayfulMessages();
                                contentDiv.innerHTML = formatMessage(fullResponse);
                                if (data.context_used) {
                                    showRAGIndicator(data.context_count);
                                }
                            } else if (data.type === 'error') {
                                stopPlayfulMessages();
                                contentDiv.innerHTML = `<span style="color: var(--danger);">Error: ${data.error}</span>`;
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                        }
                    }
                });
                
                readStream();
            }).catch(error => {
                contentDiv.innerHTML = `<span style="color: var(--danger);">Error: ${error.message}</span>`;
            });
        }
        
        readStream();
    })
    .catch(error => {
        stopPlayfulMessages();
        removeMessage(loadingId);
        addMessage(`Error: ${error.message}`, 'assistant');
    });
}

function sendMessageStandard(message) {
    // Show loading indicator with playful animated message
    const loadingId = addMessage('', 'assistant', true);
    const messageElement = document.getElementById(loadingId);
    const contentDiv = messageElement.querySelector('.message-content');
    contentDiv.innerHTML = '<div class="playful-loading"><div class="playful-message"></div><div class="typing-indicator"><span></span><span></span><span></span></div></div>';
    
    // Start showing playful messages
    showPlayfulMessages(loadingId);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('message', message);
    formData.append('model', document.getElementById('model-select')?.value || 'llama3');
    formData.append('provider', document.getElementById('provider-select')?.value || 'ollama');
    formData.append('use_rag', ragEnabled);
    
    // Check for parallel mode
    const useParallel = localStorage.getItem('useParallelMode') === 'true';
    if (useParallel) {
        const selectedModels = getSelectedModels();
        if (selectedModels.length > 1) {
            selectedModels.forEach(model => {
                formData.append('models[]', model);
            });
            formData.append('use_parallel', true);
        }
    }
    
    if (currentSessionId) {
        formData.append('session_id', currentSessionId);
    }
    
    // Add files if any
    uploadedFiles.forEach(file => {
        formData.append('file', file);
    });
    
    axios.post('/src/Controllers/ChatController.php', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(response => {
        // Stop playful messages
        stopPlayfulMessages();
        removeMessage(loadingId);
        
        if (response.data.error) {
            addMessage(`Error: ${response.data.error}`, 'assistant');
        } else {
            const botResponse = response.data.response || response.data;
            addMessage(botResponse, 'assistant');
            
            // Show RAG indicator if context was used
            if (response.data.context_used) {
                showRAGIndicator(response.data.context_count);
            }
            
            // Show model used if parallel mode
            if (response.data.parallel_mode && response.data.model_used) {
                showNotification(`Used model: ${response.data.model_used}`, 'info');
            }
        }
        
        uploadedFiles = [];
        updateFilePreview();
        loadChatSessions();
    })
    .catch(error => {
        stopPlayfulMessages();
        removeMessage(loadingId);
        addMessage(`Error: ${error.message}`, 'assistant');
    });
}

function formatMessage(content) {
    // Enhanced markdown formatting with code highlighting
    return content
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-family: monospace;">$1</code>')
        .replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; overflow-x: auto; margin: 8px 0;"><code>$2</code></pre>');
}

function scrollToBottom() {
    const messagesContainer = document.getElementById('chat-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function getSelectedModels() {
    // Get selected models from UI (if multi-select is implemented)
    const modelSelect = document.getElementById('model-select');
    return modelSelect ? [modelSelect.value] : ['llama3'];
}

// Playful messages to show while waiting for Ollama response - all positive and smiley! 😊
const playfulMessages = [
    { text: '✨ Thinking deeply...', emoji: '🤔' },
    { text: '🌟 Gathering wisdom...', emoji: '🧠' },
    { text: '💫 Processing your question...', emoji: '⚡' },
    { text: '🎯 Finding the perfect answer...', emoji: '😊' },
    { text: '🚀 Almost there...', emoji: '🌈' },
    { text: '🎨 Crafting a beautiful response...', emoji: '✨' },
    { text: '🔍 Searching through knowledge...', emoji: '📚' },
    { text: '💡 Connecting the dots...', emoji: '🔗' },
    { text: '🎭 Preparing something special...', emoji: '🎬' },
    { text: '🌊 Riding the waves of thought...', emoji: '🌊' },
    { text: '🎪 Working my magic...', emoji: '🎩' },
    { text: '🎯 Zeroing in on the answer...', emoji: '😄' },
    { text: '🌟 Sparkling with ideas...', emoji: '💎' },
    { text: '🎨 Painting with words...', emoji: '🖌️' },
    { text: '🚀 Launching into response mode...', emoji: '🚀' },
    { text: '🎵 Composing the perfect reply...', emoji: '🎵' },
    { text: '🎪 The show is about to begin...', emoji: '🎭' },
    { text: '🌈 Creating something colorful...', emoji: '🎨' },
    { text: '✨ Adding a sprinkle of magic...', emoji: '✨' },
    { text: '🎯 Bullseye incoming...', emoji: '🎯' },
    { text: '😊 This is going to be great!', emoji: '😊' },
    { text: '🌟 Excited to share this with you!', emoji: '😄' },
    { text: '💫 Good things come to those who wait!', emoji: '😊' },
    { text: '🎉 Almost ready to amaze you!', emoji: '🎉' },
    { text: '✨ Worth the wait, I promise!', emoji: '😊' },
    { text: '🚀 Preparing something awesome!', emoji: '😄' },
    { text: '🎨 This will be worth it!', emoji: '😊' },
    { text: '🌟 Great question! Let me think...', emoji: '🤔' },
    { text: '💡 I\'ve got something good coming!', emoji: '😄' },
    { text: '🎯 You\'re going to love this!', emoji: '😊' }
];

let playfulMessageInterval = null;
let currentMessageIndex = 0;

function showPlayfulMessages(messageId) {
    const messageElement = document.getElementById(messageId);
    if (!messageElement) return;
    
    const playfulMessageDiv = messageElement.querySelector('.playful-message');
    if (!playfulMessageDiv) return;
    
    // Clear any existing interval
    if (playfulMessageInterval) {
        clearInterval(playfulMessageInterval);
    }
    
    // Show first message immediately
    updatePlayfulMessage(playfulMessageDiv);
    
    // Rotate messages every 2-3 seconds
    playfulMessageInterval = setInterval(() => {
        if (document.getElementById(messageId)) {
            updatePlayfulMessage(playfulMessageDiv);
        } else {
            clearInterval(playfulMessageInterval);
            playfulMessageInterval = null;
        }
    }, 2500);
}

function updatePlayfulMessage(element) {
    const message = playfulMessages[currentMessageIndex % playfulMessages.length];
    currentMessageIndex++;
    
    // Fade out
    element.style.opacity = '0';
    element.style.transform = 'translateY(-10px)';
    
    setTimeout(() => {
        element.innerHTML = `<span class="playful-emoji">${message.emoji}</span> <span class="playful-text">${message.text}</span>`;
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
    }, 200);
}

function stopPlayfulMessages() {
    if (playfulMessageInterval) {
        clearInterval(playfulMessageInterval);
        playfulMessageInterval = null;
    }
}

function updateFilePreview() {
    const preview = document.getElementById('file-preview');
    if (uploadedFiles.length === 0) {
        preview.style.display = 'none';
        preview.innerHTML = '';
    } else {
        preview.style.display = 'block';
        preview.innerHTML = uploadedFiles.map((file, index) => `
            <div style="display: inline-flex; align-items: center; background: var(--glass-bg); padding: 8px 12px; border-radius: 8px; margin-right: 8px; margin-bottom: 8px;">
                <i class="fas fa-file" style="margin-right: 8px;"></i>
                <span style="font-size: 14px;">${file.name}</span>
                <button onclick="removeFile(${index})" style="background: none; border: none; color: var(--danger); margin-left: 8px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }
}

function removeFile(index) {
    uploadedFiles.splice(index, 1);
    updateFilePreview();
}

function addMessage(content, role, isLoading = false) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageId = 'msg-' + Date.now();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role}`;
    messageDiv.id = messageId;
    
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    avatar.innerHTML = role === 'user' 
        ? '<i class="fas fa-user"></i>' 
        : '<i class="fas fa-robot"></i>';
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    
    if (isLoading) {
        contentDiv.innerHTML = '<div class="spinner" style="width: 20px; height: 20px; margin: 0 auto;"></div>';
    } else {
        // Convert markdown-like formatting and preserve line breaks
        const formattedContent = content
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">$1</code>');
        contentDiv.innerHTML = formattedContent;
    }
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);
    
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    return messageId;
}

function removeMessage(messageId) {
    const message = document.getElementById(messageId);
    if (message) {
        message.remove();
    }
}

function toggleRAG() {
    ragEnabled = !ragEnabled;
    const btn = document.getElementById('rag-toggle');
    btn.dataset.ragEnabled = ragEnabled;
    btn.style.background = ragEnabled ? 'var(--primary-gradient)' : 'var(--glass-bg)';
    btn.style.color = ragEnabled ? 'white' : 'var(--text-primary)';
    
    showNotification(
        ragEnabled ? 'RAG enabled - Using document context' : 'RAG disabled - Standard mode',
        'info'
    );
}

function toggleStreaming() {
    const currentState = localStorage.getItem('enableStreaming') !== 'false';
    const newState = !currentState;
    localStorage.setItem('enableStreaming', newState.toString());
    
    const btn = document.getElementById('streaming-toggle');
    if (btn) {
        btn.dataset.streamingEnabled = newState;
        btn.style.background = newState ? 'var(--primary-gradient)' : 'var(--glass-bg)';
        btn.style.color = newState ? 'white' : 'var(--text-primary)';
    }
    
    showNotification(
        newState ? 'Streaming enabled - Faster responses' : 'Streaming disabled - Standard mode',
        'info'
    );
}

// Initialize streaming toggle state
document.addEventListener('DOMContentLoaded', function() {
    const streamingEnabled = localStorage.getItem('enableStreaming') !== 'false';
    const btn = document.getElementById('streaming-toggle');
    if (btn) {
        btn.dataset.streamingEnabled = streamingEnabled;
        if (streamingEnabled) {
            btn.style.background = 'var(--primary-gradient)';
            btn.style.color = 'white';
        }
    }
});

function loadProviders() {
    axios.get('/src/Controllers/GenAIProviderController.php')
        .then(response => {
            if (response.data.success && response.data.providers) {
                const providerSelect = document.getElementById('provider-select');
                providerSelect.innerHTML = '';
                
                response.data.providers.forEach(provider => {
                    const option = document.createElement('option');
                    option.value = provider.id;
                    option.textContent = provider.name;
                    providerSelect.appendChild(option);
                });
                
                // Load saved provider or use default
                const savedProvider = localStorage.getItem('defaultProvider');
                if (savedProvider) {
                    providerSelect.value = savedProvider;
                } else {
                    providerSelect.value = response.data.providers[0]?.id || 'ollama';
                }
                
                // Update models when provider changes
                providerSelect.addEventListener('change', function() {
                    localStorage.setItem('defaultProvider', this.value);
                    updateModelList();
                });
                
                // Initial model list load
                updateModelList();
            } else {
                // Fallback to Ollama if providers endpoint fails
                updateModelList();
            }
        })
        .catch(error => {
            console.error('Failed to load providers:', error);
            // Fallback to Ollama
            updateModelList();
        });
}

function updateModelList() {
    const providerSelect = document.getElementById('provider-select');
    const provider = providerSelect?.value || 'ollama';
    const modelSelect = document.getElementById('model-select');
    
    // For Ollama, use the existing models.json endpoint
    if (provider === 'ollama') {
        axios.get('/src/Models/models.json')
            .then(response => {
                const models = response.data;
                modelSelect.innerHTML = '';
                
                if (models && models.length > 0) {
                    models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.name;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });
                    
                    const savedModel = localStorage.getItem('defaultModel');
                    if (savedModel) {
                        modelSelect.value = savedModel;
                    } else {
                        modelSelect.value = models[0].name;
                    }
                } else {
                    modelSelect.innerHTML = '<option value="">No models available</option>';
                }
            })
            .catch(error => {
                console.error('Failed to load models:', error);
                modelSelect.innerHTML = '<option value="">Error loading models</option>';
            });
    } else {
        // For other providers, get models from provider endpoint
        axios.get('/src/Controllers/GenAIProviderController.php')
            .then(response => {
                if (response.data.success && response.data.providers) {
                    const selectedProvider = response.data.providers.find(p => p.id === provider);
                    if (selectedProvider && selectedProvider.models) {
                        modelSelect.innerHTML = '';
                        selectedProvider.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.textContent = model;
                            modelSelect.appendChild(option);
                        });
                        
                        if (selectedProvider.models.length > 0) {
                            modelSelect.value = selectedProvider.models[0];
                        }
                    } else {
                        modelSelect.innerHTML = '<option value="">No models available</option>';
                    }
                }
            })
            .catch(error => {
                console.error('Failed to load provider models:', error);
                modelSelect.innerHTML = '<option value="">Error loading models</option>';
            });
    }
    
    modelSelect.addEventListener('change', function() {
        localStorage.setItem('defaultModel', this.value);
    });
}

function syncModels() {
    showNotification('Syncing models...', 'info');
    axios.get('/public/fetch_models.php')
        .then(response => {
            if (response.data.success) {
                showNotification(`Synced ${response.data.count} models`, 'success');
                updateModelList();
            } else {
                showNotification('Sync failed', 'error');
            }
        })
        .catch(error => {
            showNotification('Sync error: ' + error.message, 'error');
        });
}

function createNewChat() {
    axios.post('/src/Controllers/ChatSessionController.php', {
        action: 'create',
        title: 'New Chat'
    })
    .then(response => {
        if (response.data.success) {
            currentSessionId = response.data.session_id;
            window.history.pushState({}, '', `?session=${currentSessionId}`);
            clearChat();
            loadChatSessions();
        }
    })
    .catch(error => {
        console.error('Failed to create chat:', error);
    });
}

function loadChatSessions() {
    axios.post('/src/Controllers/ChatSessionController.php', { action: 'list' })
        .then(response => {
            if (response.data.success) {
                renderChatSessions(response.data.sessions);
            }
        })
        .catch(error => {
            console.error('Failed to load sessions:', error);
        });
}

function renderChatSessions(sessions) {
    const container = document.getElementById('chat-sessions-list');
    container.innerHTML = '';
    
    sessions.forEach(session => {
        const item = document.createElement('div');
        item.className = 'chat-session-item';
        if (session.id == currentSessionId) {
            item.classList.add('active');
        }
        item.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 4px;">${session.title || 'Untitled'}</div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                ${session.message_count || 0} messages
            </div>
        `;
        item.addEventListener('click', () => {
            loadChatSession(session.id);
        });
        container.appendChild(item);
    });
}

function loadChatSession(sessionId) {
    currentSessionId = sessionId;
    window.history.pushState({}, '', `?session=${sessionId}`);
    
    axios.post('/src/Controllers/ChatSessionController.php', {
        action: 'get',
        session_id: sessionId
    })
    .then(response => {
        if (response.data.success) {
            clearChat();
            response.data.messages.forEach(msg => {
                addMessage(msg.content, msg.role);
            });
            loadChatSessions();
        }
    })
    .catch(error => {
        console.error('Failed to load session:', error);
    });
}

function clearChat() {
    const container = document.getElementById('chat-messages');
    container.innerHTML = '';
}

function openDocumentsModal() {
    const modal = document.getElementById('documents-modal');
    if (modal) {
        modal.style.display = 'flex';
        loadDocuments();
    } else {
        window.location.href = '/public/documents.php';
    }
}

function closeDocumentsModal() {
    document.getElementById('documents-modal').style.display = 'none';
}

function loadDocuments() {
    axios.post('/src/Controllers/RAGController.php', { action: 'list' })
        .then(response => {
            if (response.data.success) {
                renderDocuments(response.data.documents);
            }
        })
        .catch(error => {
            console.error('Failed to load documents:', error);
        });
}

function renderDocuments(documents) {
    const container = document.getElementById('document-list');
    container.innerHTML = '';
    
    if (documents.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">No documents uploaded yet</p>';
        return;
    }
    
    documents.forEach(doc => {
        const card = document.createElement('div');
        card.className = 'document-card';
        card.innerHTML = `
            <div class="document-icon">
                <i class="fas fa-file-${getFileIcon(doc.file_type)}"></i>
            </div>
            <div class="document-name">${doc.original_filename}</div>
            <div class="document-meta">
                <span class="status-badge ${doc.status}">${doc.status}</span>
                <span>${formatFileSize(doc.file_size)}</span>
            </div>
            ${doc.chunk_count ? `<div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">${doc.chunk_count} chunks</div>` : ''}
            <button class="btn-icon" onclick="deleteDocument(${doc.id})" style="position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(card);
    });
}

function getFileIcon(type) {
    const icons = {
        'pdf': 'pdf',
        'docx': 'word',
        'txt': 'alt',
        'xlsx': 'excel',
        'csv': 'csv',
        'md': 'markdown'
    };
    return icons[type] || 'file';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function deleteDocument(id) {
    if (!confirm('Are you sure you want to delete this document?')) return;
    
    axios.post('/src/Controllers/RAGController.php', {
        action: 'delete',
        document_id: id
    })
    .then(response => {
        if (response.data.success) {
            showNotification('Document deleted', 'success');
            loadDocuments();
        }
    })
    .catch(error => {
        showNotification('Delete failed: ' + error.message, 'error');
    });
}

function showRAGIndicator(count) {
    const indicator = document.createElement('div');
    indicator.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--primary-gradient);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        font-size: 14px;
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    `;
    indicator.innerHTML = `<i class="fas fa-brain"></i> Used ${count} document chunks`;
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        indicator.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => indicator.remove(), 300);
    }, 3000);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--accent)'};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        z-index: 2000;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Close modal on outside click
document.getElementById('documents-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentsModal();
    }
});

// Modern Chat with RAG Integration
let currentSessionId = null;
let ragEnabled = true;
let uploadedFiles = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
    loadChatSessions();
    loadDocuments();
    setupEventListeners();
    updateModelList();
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

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message && uploadedFiles.length === 0) return;
    
    // Add user message to UI
    if (message) {
        addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
    }
    
    // Show loading indicator
    const loadingId = addMessage('...', 'assistant', true);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('message', message);
    formData.append('model', document.getElementById('model-select').value || 'llama3');
    formData.append('use_rag', ragEnabled);
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
        }
        
        uploadedFiles = [];
        updateFilePreview();
        loadChatSessions();
    });
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

function updateModelList() {
    axios.get('/src/Models/models.json')
        .then(response => {
            const models = response.data;
            const select = document.getElementById('model-select');
            select.innerHTML = '';
            
            if (models && models.length > 0) {
                models.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.name;
                    option.textContent = model.name;
                    select.appendChild(option);
                });
                
                const savedModel = localStorage.getItem('defaultModel');
                if (savedModel) {
                    select.value = savedModel;
                } else {
                    select.value = models[0].name;
                }
            } else {
                select.innerHTML = '<option value="">No models available</option>';
            }
        })
        .catch(error => {
            console.error('Failed to load models:', error);
        });
    
    select.addEventListener('change', function() {
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

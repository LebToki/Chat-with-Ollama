// Modern Chat with RAG Integration
// Suppress Chrome extension errors
(function() {
    if (typeof chrome !== 'undefined' && chrome.runtime) {
        try {
            // Suppress runtime.lastError warnings
            if (chrome.runtime.lastError) {
                // Silently ignore
            }
            // Add error listener to catch and suppress extension errors
            chrome.runtime.onMessage.addListener(function() {
                return true; // Keep channel open
            });
        } catch(e) {
            // Ignore extension errors silently
        }
    }
})();

// Prevent running in iframe (browser extension protection)
if (window.self !== window.top) {
    console.log('Running in iframe, skipping chat initialization');
    // Don't return here, just log - allow the page to load but skip chat features
}

let currentSessionId = null;
let ragEnabled = true;
let streamingEnabled = true;
let uploadedFiles = [];

// Cache for API responses to reduce unnecessary calls
const apiCache = {
    sessions: null,
    documents: null,
    lastSessionUpdate: 0,
    lastDocumentUpdate: 0,
    cacheTimeout: 5000 // 5 seconds
};

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener( 'DOMContentLoaded', function () {
    // Skip initialization if in iframe
    if (window.self !== window.top) {
        console.log('Chat initialization skipped - running in iframe');
        return;
    }
    initializeChat();
    setupEventListeners();
    loadProviders(); // Load providers first, then models (will load Ollama models)
    loadDocuments();
    
    // Ensure models are loaded even if provider loading fails
    // This is a fallback to ensure model selector works
    setTimeout(() => {
        const modelSelect = document.getElementById('model-select');
        if (modelSelect && (!modelSelect.options || modelSelect.options.length <= 1)) {
            // If model selector is empty or only has "Loading models...", load them
            updateModelListForProvider('ollama');
        }
    }, 1000);
    
    // Initialize streaming toggle button state
    const streamingBtn = document.getElementById('streaming-toggle');
    if (streamingBtn) {
        streamingBtn.dataset.streamingEnabled = streamingEnabled;
        streamingBtn.style.background = streamingEnabled ? 'var(--primary-gradient)' : 'var(--glass-bg)';
        streamingBtn.style.color = streamingEnabled ? 'white' : 'var(--text-primary)';
    }
    
    // Load sessions after initialization (will auto-load first session with messages if no URL session)
    loadChatSessions();
} );

let availableDocuments = [];
let mentionDropdown = null;

function initializeChat() {

    // Auto-resize textarea
    const chatInput = document.getElementById( 'chat-input' );
    chatInput.addEventListener( 'input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min( this.scrollHeight, 200 ) + 'px';
        handleMentionInput(this);
    } );

    // Enter to send, Shift+Enter for new line
    chatInput.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Enter' && ! e.shiftKey ) {
            if (mentionDropdown && mentionDropdown.style.display !== 'none') {
                // If mention dropdown is open, don't send
                return;
            }
            e.preventDefault();
            sendMessage();
        }
    } );
    
    // Load documents for @ mentions
    loadDocumentsForMentions();
    
    // Close mention dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (mentionDropdown && !mentionDropdown.contains(e.target) && e.target !== chatInput) {
            hideMentionDropdown();
        }
    });

    // Load session from URL if exists
    // Otherwise, wait for loadChatSessions() to auto-load the most recent session with messages
    const urlParams = new URLSearchParams( window.location.search );
    const sessionId = urlParams.get( 'session' );
    if ( sessionId ) {
        currentSessionId = sessionId;
        loadChatSession( sessionId );
    } else {
        // Don't create session yet - loadChatSessions() will auto-load first session with messages
        currentSessionId = null;
        clearChat();
    }
}

function setupEventListeners() {
    document.getElementById( 'send-btn' ).addEventListener( 'click', sendMessage );

    const attachBtn = document.getElementById( 'attach-file-btn' );
    if ( attachBtn ) {
        attachBtn.addEventListener( 'click', () => {
            const input = document.createElement( 'input' );
            input.type = 'file';
            input.multiple = true;
            input.accept = 'image/*,.pdf,.docx,.txt,.xlsx,.csv,.md';
            input.addEventListener( 'change', ( e ) => {
                Array.from( e.target.files ).forEach( file => {
                    uploadedFiles.push( file );
                } );
                updateFilePreview();
            } );
            input.click();
        } );
    }

    document.getElementById( 'rag-toggle' ).addEventListener( 'click', toggleRAG );

    // File input for document modal
    const fileInput = document.getElementById( 'file-input' );
    if ( fileInput ) {
        fileInput.addEventListener( 'change', handleFileSelect );
    }

    // Drag and drop (for document modal)
    const uploadZone = document.getElementById( 'upload-zone' );
    if ( uploadZone ) {
        uploadZone.addEventListener( 'dragover', ( e ) => {
            e.preventDefault();
            uploadZone.classList.add( 'dragover' );
        } );

        uploadZone.addEventListener( 'dragleave', () => {
            uploadZone.classList.remove( 'dragover' );
        } );

        uploadZone.addEventListener( 'drop', ( e ) => {
            e.preventDefault();
            uploadZone.classList.remove( 'dragover' );
            handleFiles( e.dataTransfer.files );
        } );

        uploadZone.addEventListener( 'click', () => {
            if ( fileInput ) 
                fileInput.click();
            


        } );
    }
}

function handleFileSelect( e ) {
    handleFiles( e.target.files );
}

function handleFiles( files ) {
    Array.from( files ).forEach( file => {
        uploadDocument( file );
    } );
}

function uploadDocument( file ) {
    const formData = new FormData();
    formData.append( 'file', file );
    formData.append( 'action', 'upload' );

    showNotification( `Uploading ${
        file.name
    }...`, 'info' );

    axios.post( '/api/rag.php', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    } ).then( response => {
        if ( response.data.success ) {
            showNotification( `${
                file.name
            } uploaded successfully!`, 'success' );
            loadDocuments(true); // Force refresh after upload
        } else {
            const errorMsg = response.data.error || 'Unknown error';
            let userMessage = `Upload failed: ${errorMsg}`;
            
            // Provide actionable error messages
            if (errorMsg.includes('size') || errorMsg.includes('large')) {
                userMessage = `File too large. Maximum size: 10MB. Please choose a smaller file.`;
            } else if (errorMsg.includes('type') || errorMsg.includes('format')) {
                userMessage = `Invalid file type. Supported formats: PDF, DOCX, TXT, XLSX, CSV, MD`;
            } else if (errorMsg.includes('permission') || errorMsg.includes('access')) {
                userMessage = `Permission denied. Please check file permissions and try again.`;
            }
            
            showNotification(userMessage, 'error');
        }
    }).catch(error => {
        let userMessage = `Upload error: ${error.message}`;
        if (error.message.includes('Network') || error.message.includes('timeout')) {
            userMessage = 'Network error. Please check your connection and try again.';
        } else if (error.message.includes('413')) {
            userMessage = 'File too large. Maximum size: 10MB.';
        }
        showNotification(userMessage, 'error');
    });
}

function sendMessage() {
    const input = document.getElementById( 'chat-input' );
    const message = input.value.trim();

    if ( ! message && uploadedFiles.length === 0 ) 
        return;
    
    // If no session exists, create one before sending
    if ( ! currentSessionId ) {
        // Create session first, then send message
        axios.post( '/api/chat-session.php', {
            action: 'create',
            title: 'New Chat'
        } ).then( response => {
            if ( response.data.success ) {
                currentSessionId = response.data.session_id;
                window.history.pushState( {}, '', `?session=${ currentSessionId }` );
                // Now send the message
                sendMessageWithSession( message );
            }
        } ).catch( error => {
            console.error( 'Failed to create chat session:', error );
            addMessage( 'Error: Failed to create chat session', 'assistant' );
        } );
        return;
    }
    
    sendMessageWithSession( message );
}

function sendMessageWithSession( message ) {
    const input = document.getElementById( 'chat-input' );
    
    // Add user message to UI
    if ( message ) {
        addMessage( message, 'user' );
        input.value = '';
        input.style.height = 'auto';
    }

    // Show loading indicator
    const loadingId = addMessage( '...', 'assistant', true );

    // Prepare form data
    const formData = new FormData();
    formData.append( 'message', message );
    formData.append( 'model', document.getElementById( 'model-select' ).value || 'llama3.2:latest' );
    // Free version: Always use Ollama provider
    formData.append( 'provider', 'ollama' );
    formData.append( 'use_rag', ragEnabled );
    if ( currentSessionId ) {
        formData.append( 'session_id', currentSessionId );
    }

    // Add files if any
    uploadedFiles.forEach( file => {
        formData.append( 'file', file );
    } );

    axios.post( '/api/chat.php', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    } ).then( response => {
        ProcessingMessages.stop();
        removeMessage( loadingId );

        if ( response.data.error ) {
            addMessage( `Error: ${
                response.data.error
            }`, 'assistant' );
        } else {
            const botResponse = response.data.response || response.data;
            addMessage( botResponse, 'assistant' );

            // Show RAG indicator if context was used
            if ( response.data.context_used ) {
                showRAGIndicator( response.data.context_count );
            }
        } 
        uploadedFiles = [];
        updateFilePreview();
        loadChatSessions();
    } ).catch( error => {
        ProcessingMessages.stop();
        removeMessage( loadingId );
        addMessage( `Error: ${
            error.message
        }`, 'assistant' );
    } );
}

function updateFilePreview() {
    const preview = document.getElementById( 'file-preview' );
    if ( uploadedFiles.length === 0 ) {
        preview.style.display = 'none';
        preview.innerHTML = '';
    } else {
        preview.style.display = 'block';
        preview.innerHTML = uploadedFiles.map( ( file, index ) => {
            const ext = IconHelper.getFileExtension( file.name );
            const fileIcon = IconHelper.getFileIcon( ext );
            return `
            <div style="display: inline-flex; align-items: center; background: var(--glass-bg); padding: 8px 12px; border-radius: 8px; margin-right: 8px; margin-bottom: 8px;">
                ${
                IconHelper.icon( fileIcon, '', 'margin-right: 8px;' )
            }
                <span style="font-size: 14px;">${
                file.name
            }</span>
                <button onclick="removeFile(${ index })" style="background: none; border: none; color: var(--danger); margin-left: 8px; cursor: pointer;">
                    ${
                IconHelper.icon( IconHelper.getActionIcon( 'close' ) )
            }
                </button>
            </div>
        `;
        } ).join( '' );
    }
}

function removeFile( index ) {
    uploadedFiles.splice( index, 1 );
    updateFilePreview();
}

function addMessage( content, role, isLoading = false ) {
    const messagesContainer = document.getElementById( 'chat-messages' );
    const emptyState = document.getElementById( 'empty-state' );

    // Hide empty state when first message is added
    if ( emptyState ) {
        emptyState.style.display = 'none';
    }

    const messageId = 'msg-' + Date.now();

    const messageDiv = document.createElement( 'div' );
    messageDiv.className = `message ${ role }`;
    messageDiv.id = messageId;

    const avatar = document.createElement( 'div' );
    avatar.className = 'message-avatar';
    const avatarIcon = role === 'user' ? IconHelper.getUserIcon() : IconHelper.getBotIcon( 'assistant' );
    avatar.innerHTML = IconHelper.icon( avatarIcon );

    const contentDiv = document.createElement( 'div' );
    contentDiv.className = 'message-content';

    if ( isLoading ) {

        // Use playful processing messages
        ProcessingMessages.start( contentDiv );
    } else {

        // Safely render markdown-like formatting (XSS protection)
        // HtmlSanitizer escapes all HTML first, then applies safe markdown formatting
        if (typeof HtmlSanitizer !== 'undefined') {
            HtmlSanitizer.setSafeHTML(contentDiv, content, true);
        } else {
            // Fallback: escape HTML and apply basic formatting
            const escaped = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#x27;');
            
            const formatted = escaped
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/(?<!\*)\*([^*]+?)\*(?!\*)/g, '<em>$1</em>')
                .replace(/`([^`]+?)`/g, '<code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">$1</code>');
            
            contentDiv.innerHTML = formatted;
        }
    } messageDiv.appendChild( avatar );
    messageDiv.appendChild( contentDiv );
    messagesContainer.appendChild( messageDiv );

    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    return messageId;
}

function removeMessage( messageId ) {
    const message = document.getElementById( messageId );
    if ( message ) {
        message.remove();
    }
}

function toggleRAG() {
    ragEnabled = ! ragEnabled;
    const btn = document.getElementById( 'rag-toggle' );
    btn.dataset.ragEnabled = ragEnabled;
    btn.style.background = ragEnabled ? 'var(--primary-gradient)' : 'var(--glass-bg)';
    btn.style.color = ragEnabled ? 'white' : 'var(--text-primary)';

    showNotification( ragEnabled ? 'RAG enabled - Using document context' : 'RAG disabled - Standard mode', 'info' );
}

function toggleStreaming() {
    streamingEnabled = !streamingEnabled;
    const btn = document.getElementById( 'streaming-toggle' );
    if (btn) {
        btn.dataset.streamingEnabled = streamingEnabled;
        btn.style.background = streamingEnabled ? 'var(--primary-gradient)' : 'var(--glass-bg)';
        btn.style.color = streamingEnabled ? 'white' : 'var(--text-primary)';
        showNotification( streamingEnabled ? 'Streaming enabled' : 'Streaming disabled', 'info' );
    }
}

// Load available providers
// Free version: Only Ollama is available, so hide provider selector
function loadProviders() {
    const providerSelect = document.getElementById( 'provider-select' );
    if ( providerSelect ) {
        // Free version: Only Ollama available - hide provider selector
        const providerContainer = providerSelect.closest( '.provider-container' ) || providerSelect.parentElement;
        if ( providerContainer ) {
            providerContainer.style.display = 'none';
        } else {
            providerSelect.style.display = 'none';
        }
    }

    // Always use Ollama in free version and load models
    const providerId = 'ollama';
    updateModelListForProvider( providerId );
}

// Update model list based on selected provider
function updateModelListForProvider( providerId ) {
    const select = document.getElementById( 'model-select' );
    if ( ! select ) {
        console.error( 'Model select element not found' );
        return;
    }

    select.innerHTML = '<option value="">Loading models...</option>';

    // Free version: Only Ollama is supported
    if ( providerId === 'ollama' || !providerId ) {
        axios.get( '/api/models.php' ).then( response => {
            const models = response.data;
            select.innerHTML = '';

            if ( models && models.length > 0 ) {
                models.forEach( model => {
                    const option = document.createElement( 'option' );
                    // Handle both object format {name: "model"} and string format
                    const modelName = typeof model === 'string' ? model : (model.name || model);
                    option.value = modelName;
                    option.textContent = modelName;
                    select.appendChild( option );
                } );

                const savedModel = localStorage.getItem( 'defaultModel' );
                if ( savedModel && Array.from(select.options).some(opt => opt.value === savedModel)) {
                    select.value = savedModel;
                } else if ( models.length > 0 ) {
                    const firstModel = typeof models[0] === 'string' ? models[0] : (models[0].name || models[0]);
                    select.value = firstModel;
                }
            } else {
                select.innerHTML = '<option value="">No models available. Sync models in Settings.</option>';
            }
        } ).catch( error => {
            console.error( 'Failed to load models:', error );
            select.innerHTML = '<option value="">Failed to load models. Check Ollama connection.</option>';
        } );
    } else {
        // For cloud providers, get models from providers endpoint
        axios.get( '/api/providers.php' ).then( response => {
            if ( response.data.success && response.data.providers ) {
                const provider = response.data.providers.find( p => p.id === providerId );
                if ( provider && provider.models ) {
                    select.innerHTML = '';
                    provider.models.forEach( model => {
                        const option = document.createElement( 'option' );
                        option.value = model;
                        option.textContent = model;
                        select.appendChild( option );
                    } );

                    if ( provider.models.length > 0 ) {
                        select.value = provider.models[ 0 ];
                    }
                } else {
                    select.innerHTML = '<option value="">No models available</option>';
                }
            } else {
                select.innerHTML = '<option value="">Failed to load models</option>';
            }
        } ).catch( error => {
            console.error( 'Failed to load provider models:', error );
            select.innerHTML = '<option value="">Failed to load models</option>';
        } );
    }

    // Add change event listener (only once)
    if ( ! select.dataset.listenerAdded ) {
        select.addEventListener( 'change', function () {
            localStorage.setItem( 'defaultModel', this.value );
        } );
        select.dataset.listenerAdded = 'true';
    }
}

// Legacy function for backward compatibility
function updateModelList() {
    const providerSelect = document.getElementById( 'provider-select' );
    const providerId = providerSelect ? providerSelect.value : 'ollama';
    updateModelListForProvider( providerId );
}

function syncModels() {
    showNotification( 'Syncing models...', 'info' );
    axios.get( '/fetch_models.php' ).then( response => {
        if ( response.data.success ) {
            showNotification( `Synced ${
                response.data.count
            } models`, 'success' );
            updateModelList();
        } else {
            showNotification( 'Sync failed', 'error' );
        }
    } ).catch( error => {
        showNotification( 'Sync error: ' + error.message, 'error' );
    } );
}

function createNewChat() {
    axios.post( '/api/chat-session.php', {
        action: 'create',
        title: 'New Chat'
    } ).then( response => {
        if ( response.data.success ) {
            currentSessionId = response.data.session_id;
            window.history.pushState( {}, '', `?session=${ currentSessionId }` );
            clearChat();
            loadChatSessions();
        }
    } ).catch( error => {
        console.error( 'Failed to create chat:', error );
    } );
}

function loadChatSessions(forceRefresh = false) {
    // Check cache first
    const now = Date.now();
    if (!forceRefresh && apiCache.sessions && (now - apiCache.lastSessionUpdate) < apiCache.cacheTimeout) {
        renderChatSessions(apiCache.sessions);
        // Auto-load first session with messages if no session is currently loaded
        if (!currentSessionId && apiCache.sessions.length > 0) {
            const sessionWithMessages = apiCache.sessions.find(s => s.message_count > 0);
            if (sessionWithMessages) {
                loadChatSession(sessionWithMessages.id);
            }
        }
        return Promise.resolve(apiCache.sessions);
    }
    
    return axios.post('/api/chat-session.php', { action: 'list' }).then(response => {
        if (response.data.success) {
            const sessions = response.data.sessions;
            // Update cache
            apiCache.sessions = sessions;
            apiCache.lastSessionUpdate = now;
            
            renderChatSessions(sessions);
            
            // Auto-load first session with messages if no session is currently loaded
            if (!currentSessionId && sessions.length > 0) {
                // Find first session with messages
                const sessionWithMessages = sessions.find(s => s.message_count > 0);
                if (sessionWithMessages) {
                    // Auto-load the most recent session with messages
                    loadChatSession(sessionWithMessages.id);
                }
            }
            
            return sessions;
        }
    }).catch(error => {
        console.error('Failed to load sessions:', error);
        return [];
    });
}

function renderChatSessions( sessions ) {
    const container = document.getElementById( 'chat-sessions-list' );
    container.innerHTML = '';

    // Filter out empty sessions (0 messages) unless it's the current session
    const sessionsToShow = sessions.filter( session => {
        const messageCount = parseInt(session.message_count || 0);
        // Show if it has messages OR it's the currently active session
        return messageCount > 0 || session.id == currentSessionId;
    });

    if (sessionsToShow.length === 0) {
        container.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-secondary); font-size: 14px;">No chats yet</div>';
        return;
    }

    sessionsToShow.forEach( session => {
        const item = document.createElement( 'div' );
        item.className = 'chat-session-item';
        if ( session.id == currentSessionId ) {
            item.classList.add( 'active' );
        }
        
        const titleDiv = document.createElement( 'div' );
        titleDiv.style.cssText = 'font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;';
        
        // Show document icon if session is linked to a document
        const docIcon = session.document_id ? '<iconify-icon icon="mdi:file-document" style="font-size: 16px; color: var(--accent); flex-shrink: 0;"></iconify-icon>' : '';
        
        titleDiv.innerHTML = `
            ${docIcon}
            <span class="session-title-text" style="flex: 1; cursor: pointer;">${
            session.title || 'Untitled'
        }</span>
            <button class="session-rename-btn" style="opacity: 0; transition: opacity 0.2s; background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 4px; display: flex; align-items: center;" 
                    aria-label="Rename chat" title="Rename chat">
                <iconify-icon icon="mdi:pencil" style="font-size: 14px;"></iconify-icon>
            </button>
        `;
        
        const messageCountDiv = document.createElement( 'div' );
        messageCountDiv.style.cssText = 'font-size: 12px; color: var(--text-secondary);';
        const docInfo = session.document_id ? ` • <span style="color: var(--accent);">📄 ${session.document_name || 'Document'}</span>` : '';
        messageCountDiv.innerHTML = `${session.message_count || 0} messages${docInfo}`;
        
        item.appendChild( titleDiv );
        item.appendChild( messageCountDiv );
        
        // Show edit button on hover
        item.addEventListener( 'mouseenter', () => {
            const renameBtn = item.querySelector( '.session-rename-btn' );
            if ( renameBtn ) renameBtn.style.opacity = '1';
        } );
        item.addEventListener( 'mouseleave', () => {
            const renameBtn = item.querySelector( '.session-rename-btn' );
            if ( renameBtn && !item.querySelector( '.session-rename-input' ) ) {
                renameBtn.style.opacity = '0';
            }
        } );
        
        // Click to load session (but not when clicking rename button)
        item.addEventListener( 'click', ( e ) => {
            if ( !e.target.closest( '.session-rename-btn' ) && !e.target.closest( '.session-rename-input' ) ) {
                loadChatSession( session.id );
            }
        } );
        
        // Rename button click
        const renameBtn = titleDiv.querySelector( '.session-rename-btn' );
        renameBtn.addEventListener( 'click', ( e ) => {
            e.stopPropagation();
            renameChatSession( item, session.id, session.title || 'Untitled' );
        } );
        
        container.appendChild( item );
    } );

    // Show cleanup option if there are empty sessions
    const emptySessions = sessions.filter(s => parseInt(s.message_count || 0) === 0 && s.id != currentSessionId);
    if (emptySessions.length > 0) {
        const cleanupBtn = document.createElement('button');
        cleanupBtn.className = 'btn-modern';
        cleanupBtn.style.cssText = 'width: 100%; margin-top: 12px; padding: 8px; font-size: 12px;';
        cleanupBtn.textContent = `Clean up ${emptySessions.length} empty chat${emptySessions.length > 1 ? 's' : ''}`;
        cleanupBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            cleanupEmptySessions(emptySessions.map(s => s.id));
        });
        container.appendChild(cleanupBtn);
    }
}

function loadChatSession( sessionId ) {
    currentSessionId = sessionId;
    window.history.pushState( {}, '', `?session=${ sessionId }` );

    axios.post('/api/chat-session.php', {
        action: 'get',
        session_id: sessionId
    }).then(response => {
        if (response.data.success) {
            clearChat();
            response.data.messages.forEach(msg => {
                addMessage(msg.content, msg.role);
            });
            loadChatSessions(true); // Force refresh after loading session
        }
    }).catch(error => {
        console.error('Failed to load session:', error);
        showNotification('Failed to load chat session. Please try again.', 'error');
    });
}

function clearChat() {
    const container = document.getElementById( 'chat-messages' );
    const emptyState = document.getElementById( 'empty-state' );
    container.innerHTML = '';
    if ( emptyState ) {
        container.appendChild( emptyState );
        emptyState.style.display = 'flex';
    }
}

function renameChatSession(itemElement, sessionId, currentTitle) {
    const titleDiv = itemElement.querySelector('.session-title-text').parentElement;
    const titleText = itemElement.querySelector('.session-title-text');
    const renameBtn = itemElement.querySelector('.session-rename-btn');
    
    // Create input field
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'session-rename-input';
    input.value = currentTitle;
    input.style.cssText = 'flex: 1; background: var(--dark-surface); border: 1px solid var(--accent); border-radius: 4px; padding: 4px 8px; color: var(--text-primary); font-size: 14px; font-weight: 600; outline: none;';
    input.maxLength = 100;
    
    // Replace title text with input
    titleText.style.display = 'none';
    renameBtn.style.display = 'none';
    titleDiv.insertBefore(input, titleText);
    input.focus();
    input.select();
    
    const finishRename = () => {
        const newTitle = input.value.trim() || 'Untitled';
        
        if (newTitle === currentTitle) {
            // No change, just cancel
            input.remove();
            titleText.style.display = '';
            renameBtn.style.display = '';
            return;
        }
        
        // Save new title
        axios.post('/api/chat-session.php', {
            action: 'rename',
            session_id: sessionId,
            title: newTitle
        }).then(response => {
            if (response.data.success) {
                titleText.textContent = response.data.title;
                input.remove();
                titleText.style.display = '';
                renameBtn.style.display = '';
                // Refresh sessions list to update cache
                loadChatSessions(true);
            } else {
                throw new Error(response.data.error || 'Rename failed');
            }
        }).catch(error => {
            console.error('Failed to rename session:', error);
            showNotification('Failed to rename chat. Please try again.', 'error');
            input.remove();
            titleText.style.display = '';
            renameBtn.style.display = '';
        });
    };
    
    // Finish on Enter or blur
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            finishRename();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            input.remove();
            titleText.style.display = '';
            renameBtn.style.display = '';
        }
    });
    
    input.addEventListener('blur', finishRename);
}

function cleanupEmptySessions(sessionIds) {
    if (!sessionIds || sessionIds.length === 0) {
        return;
    }

    if (!confirm(`Delete ${sessionIds.length} empty chat session${sessionIds.length > 1 ? 's' : ''}?`)) {
        return;
    }

    showNotification('Cleaning up empty chats...', 'info');
    
    // Use bulk cleanup endpoint if available, otherwise delete one by one
    axios.post('/api/chat-session.php', {
        action: 'cleanup_empty'
    }).then(response => {
        if (response.data.success) {
            const deletedCount = response.data.deleted_count || sessionIds.length;
            showNotification(`Cleaned up ${deletedCount} empty chat${deletedCount > 1 ? 's' : ''}`, 'success');
            loadChatSessions(true); // Force refresh
        } else {
            throw new Error(response.data.error || 'Cleanup failed');
        }
    }).catch(error => {
        console.error('Bulk cleanup failed, trying individual deletes:', error);
        // Fallback to individual deletes
        const deletePromises = sessionIds.map(sessionId => 
            axios.post('/api/chat-session.php', {
                action: 'delete',
                session_id: sessionId
            })
        );

        Promise.all(deletePromises).then(() => {
            showNotification(`Cleaned up ${sessionIds.length} empty chat${sessionIds.length > 1 ? 's' : ''}`, 'success');
            loadChatSessions(true); // Force refresh
        }).catch(err => {
            console.error('Failed to cleanup sessions:', err);
            showNotification('Failed to cleanup some sessions', 'error');
            loadChatSessions(true); // Refresh anyway
        });
    });
}

function openDocumentsModal() {
    const modal = document.getElementById( 'documents-modal' );
    if ( modal ) {
        modal.style.display = 'flex';
        loadDocuments();
    } else {
        window.location.href = '/documents.php';
    }
}

function closeDocumentsModal() {
    document.getElementById( 'documents-modal' ).style.display = 'none';
}

function loadDocumentsForMentions() {
    // Load processed documents for @ mention autocomplete
    axios.post('/api/rag.php', { action: 'list_for_mentions' })
        .then(response => {
            if (response.data.success) {
                availableDocuments = response.data.documents || [];
            }
        })
        .catch(error => {
            console.error('Failed to load documents for mentions:', error);
        });
}

function handleMentionInput(input) {
    const value = input.value;
    const cursorPos = input.selectionStart;
    const textBeforeCursor = value.substring(0, cursorPos);
    
    // Check if user is typing @
    const lastAt = textBeforeCursor.lastIndexOf('@');
    if (lastAt === -1) {
        hideMentionDropdown();
        return;
    }
    
    // Get text after @
    const textAfterAt = textBeforeCursor.substring(lastAt + 1);
    
    // Check if there's a space or newline after @ (meaning @ is complete)
    if (textAfterAt.includes(' ') || textAfterAt.includes('\n')) {
        hideMentionDropdown();
        return;
    }
    
    // Show mention dropdown
    showMentionDropdown(input, textAfterAt, lastAt);
}

function showMentionDropdown(input, searchText, atPosition) {
    if (!mentionDropdown) {
        mentionDropdown = document.createElement('div');
        mentionDropdown.id = 'mention-dropdown';
        mentionDropdown.style.cssText = `
            position: absolute;
            background: var(--dark-surface);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            display: none;
        `;
        document.body.appendChild(mentionDropdown);
    }
    
    // Filter documents
    const filtered = availableDocuments.filter(doc => {
        const name = (doc.name || '').toLowerCase();
        const search = searchText.toLowerCase();
        return name.includes(search) || doc.id.toString().includes(search);
    }).slice(0, 5); // Limit to 5 results
    
    if (filtered.length === 0) {
        hideMentionDropdown();
        return;
    }
    
    // Build dropdown HTML
    mentionDropdown.innerHTML = filtered.map(doc => {
        const displayName = doc.name || `Document ${doc.id}`;
        return `
            <div class="mention-item" data-doc-id="${doc.id}" style="
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid var(--glass-border);
                transition: background 0.2s;
            " onmouseover="this.style.background='var(--glass-bg)'" onmouseout="this.style.background='transparent'">
                <div style="font-weight: 600; color: var(--text-primary);">${displayName}</div>
                <div style="font-size: 12px; color: var(--text-secondary);">@${doc.id}</div>
            </div>
        `;
    }).join('');
    
    // Position dropdown
    const rect = input.getBoundingClientRect();
    mentionDropdown.style.top = (rect.bottom + window.scrollY + 8) + 'px';
    mentionDropdown.style.left = (rect.left + window.scrollX) + 'px';
    mentionDropdown.style.width = Math.min(300, rect.width) + 'px';
    mentionDropdown.style.display = 'block';
    
    // Add click handlers
    mentionDropdown.querySelectorAll('.mention-item').forEach(item => {
        item.addEventListener('click', function() {
            const docId = this.getAttribute('data-doc-id');
            const value = input.value;
            const beforeAt = value.substring(0, atPosition);
            const afterCursor = value.substring(input.selectionStart);
            input.value = beforeAt + '@' + docId + ' ' + afterCursor;
            input.focus();
            input.setSelectionRange(beforeAt.length + docId.length + 2, beforeAt.length + docId.length + 2);
            hideMentionDropdown();
        });
    });
}

function hideMentionDropdown() {
    if (mentionDropdown) {
        mentionDropdown.style.display = 'none';
    }
}

function loadDocuments(forceRefresh = false) {
    // Check cache first
    const now = Date.now();
    if (!forceRefresh && apiCache.documents && (now - apiCache.lastDocumentUpdate) < apiCache.cacheTimeout) {
        renderDocuments(apiCache.documents);
        return Promise.resolve(apiCache.documents);
    }
    
    return axios.post('/api/rag.php', { action: 'list' }).then(response => {
        if (response.data.success) {
            const documents = response.data.documents;
            // Update cache
            apiCache.documents = documents;
            apiCache.lastDocumentUpdate = now;
            renderDocuments(documents);
            return documents;
        }
    }).catch(error => {
        console.error('Failed to load documents:', error);
        showNotification('Failed to load documents. Please try again.', 'error');
        return [];
    });
}

function renderDocuments( documents ) {
    const container = document.getElementById( 'document-list' );
    container.innerHTML = '';

    if ( documents.length === 0 ) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">No documents uploaded yet</p>';
        return;
    }

    documents.forEach( doc => {
        const card = document.createElement( 'div' );
        card.className = 'document-card';
        const fileIcon = IconHelper.getFileIcon( doc.file_type );
        card.innerHTML = `
            <div class="document-icon">
                ${
            IconHelper.icon( fileIcon )
        }
            </div>
            <div class="document-name">${
            doc.original_filename
        }</div>
            <div class="document-meta">
                <span class="status-badge ${
            doc.status
        }">${
            doc.status
        }</span>
                <span>${
            formatFileSize( doc.file_size )
        }</span>
            </div>
            ${
            doc.chunk_count ? `<div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">${
                doc.chunk_count
            } chunks</div>` : ''
        }
            <button class="btn-icon" onclick="deleteDocument(${
            doc.id
        })" style="position: absolute; top: 12px; right: 12px; width: 32px; height: 32px;">
                ${
            IconHelper.icon( IconHelper.getActionIcon( 'delete' ) )
        }
            </button>
        `;
        container.appendChild( card );
    } );
}

function getFileIcon( type ) {
    const icons = {
        'pdf': 'pdf',
        'docx': 'word',
        'txt': 'alt',
        'xlsx': 'excel',
        'csv': 'csv',
        'md': 'markdown'
    };
    return icons[ type ] || 'file';
}

function formatFileSize( bytes ) {
    if ( bytes < 1024 ) 
        return bytes + ' B';
    


    if ( bytes < 1024 * 1024 ) 
        return( bytes / 1024 ).toFixed( 1 ) + ' KB';
    


    return( bytes / ( 1024 * 1024 ) ).toFixed( 1 ) + ' MB';
}

function deleteDocument( id ) {
    if ( !confirm( 'Are you sure you want to delete this document?' ) ) 
        return;
    


    axios.post( '/api/rag.php', {
        action: 'delete',
        document_id: id
    } ).then( response => {
        if ( response.data.success ) {
            showNotification( 'Document deleted', 'success' );
            loadDocuments(true); // Force refresh after upload
        }
    } ).catch( error => {
        showNotification( 'Delete failed: ' + error.message, 'error' );
    } );
}

function showRAGIndicator( count ) {
    const indicator = document.createElement( 'div' );
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
        display: flex;
        align-items: center;
        gap: 8px;
    `;
    indicator.innerHTML = `${
        IconHelper.icon( IconHelper.getRAGIcon() )
    } Used ${ count } document chunks`;
    document.body.appendChild( indicator );

    setTimeout( () => {
        indicator.style.animation = 'fadeOut 0.3s ease';
        setTimeout( () => indicator.remove(), 300 );
    }, 3000 );
}

function showNotification( message, type = 'info' ) {
    const notification = document.createElement( 'div' );
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${
        type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--accent)'
    };
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        z-index: 2000;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild( notification );

    setTimeout( () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout( () => notification.remove(), 300 );
    }, 3000 );
}

// Close modal on outside click
document.getElementById( 'documents-modal' ) ?. addEventListener( 'click', function ( e ) {
    if ( e.target === this ) {
        closeDocumentsModal();
    }
} );

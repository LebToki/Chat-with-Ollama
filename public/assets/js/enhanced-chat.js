// Enhanced Chat Features
// This file contains all the new UI enhancements and feature additions

// Global state for enhanced features
const EnhancedChat = {
    autoScrollEnabled: true,
    isRecording: false,
    recognition: null,
    messageHistory: [],
    searchQuery: '',
    editingMessageId: null
};

// Initialize enhanced features
document.addEventListener('DOMContentLoaded', function() {
    if (window.self !== window.top) {
        console.log('Enhanced features skipped - running in iframe');
        return;
    }
    
    initializeMobileMenu();
    initializeAutoScroll();
    initializeVoiceInput();
    initializeKeyboardShortcuts();
    initializeMessageActions();
    initializeCodeBlockCopy();
    initializeSearch();
    initializeExport();
    initializeNotifications();
});

// ============================================
// Mobile Menu
// ============================================
function initializeMobileMenu() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    if (!toggle || !sidebar || !overlay) return;
    
    // Show toggle on mobile
    if (window.innerWidth <= 768) {
        toggle.style.display = 'flex';
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            toggle.style.display = 'flex';
        } else {
            toggle.style.display = 'none';
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
        }
    });
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    const toggle = document.getElementById('mobile-menu-toggle');
    
    if (!sidebar || !overlay || !toggle) return;
    
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    toggle.classList.toggle('active');
}

// ============================================
// Auto-Scroll
// ============================================
function initializeAutoScroll() {
    const chatMessages = document.getElementById('chat-messages');
    const autoScrollBtn = document.getElementById('auto-scroll-btn');
    
    if (!chatMessages || !autoScrollBtn) return;
    
    // Show/hide button based on scroll position
    chatMessages.addEventListener('scroll', function() {
        const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 100;
        
        if (isAtBottom) {
            autoScrollBtn.classList.remove('visible');
        } else {
            autoScrollBtn.classList.add('visible');
        }
    });
    
    // Set initial state
    autoScrollBtn.classList.add('active');
}

function toggleAutoScroll() {
    EnhancedChat.autoScrollEnabled = !EnhancedChat.autoScrollEnabled;
    const btn = document.getElementById('auto-scroll-btn');
    const headerBtn = document.getElementById('auto-scroll-toggle');
    
    if (btn) {
        btn.classList.toggle('active', EnhancedChat.autoScrollEnabled);
    }
    
    if (headerBtn) {
        headerBtn.dataset.autoScrollEnabled = EnhancedChat.autoScrollEnabled;
        headerBtn.style.background = EnhancedChat.autoScrollEnabled ? 'var(--primary-gradient)' : 'var(--glass-bg)';
        headerBtn.style.color = EnhancedChat.autoScrollEnabled ? 'white' : 'var(--text-primary)';
    }
    
    showNotification(
        EnhancedChat.autoScrollEnabled ? 'Auto-scroll enabled' : 'Auto-scroll disabled',
        'info'
    );
    
    // Scroll to bottom if enabled
    if (EnhancedChat.autoScrollEnabled) {
        scrollToBottom();
    }
}

function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

// ============================================
// Voice Input
// ============================================
function initializeVoiceInput() {
    const voiceBtn = document.getElementById('voice-input-btn');
    
    if (!voiceBtn) return;
    
    // Check if browser supports speech recognition
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        voiceBtn.style.display = 'none';
        return;
    }
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    EnhancedChat.recognition = new SpeechRecognition();
    EnhancedChat.recognition.continuous = false;
    EnhancedChat.recognition.interimResults = false;
    EnhancedChat.recognition.lang = 'en-US';
    
    EnhancedChat.recognition.onstart = function() {
        EnhancedChat.isRecording = true;
        voiceBtn.classList.add('recording');
        showNotification('Listening...', 'info');
    };
    
    EnhancedChat.recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const chatInput = document.getElementById('chat-input');
        
        if (chatInput) {
            chatInput.value += (chatInput.value ? ' ' : '') + transcript;
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
            chatInput.focus();
        }
    };
    
    EnhancedChat.recognition.onerror = function(event) {
        console.error('Speech recognition error:', event.error);
        EnhancedChat.isRecording = false;
        voiceBtn.classList.remove('recording');
        
        let errorMsg = 'Voice input failed';
        if (event.error === 'no-speech') {
            errorMsg = 'No speech detected';
        } else if (event.error === 'audio-capture') {
            errorMsg = 'No microphone found';
        } else if (event.error === 'not-allowed') {
            errorMsg = 'Microphone access denied';
        }
        
        showNotification(errorMsg, 'error');
    };
    
    EnhancedChat.recognition.onend = function() {
        EnhancedChat.isRecording = false;
        voiceBtn.classList.remove('recording');
    };
    
    voiceBtn.addEventListener('click', function() {
        if (EnhancedChat.isRecording) {
            EnhancedChat.recognition.stop();
        } else {
            EnhancedChat.recognition.start();
        }
    });
}

// ============================================
// Keyboard Shortcuts
// ============================================
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K - Show keyboard shortcuts
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            showKeyboardShortcuts();
        }
        
        // Ctrl/Cmd + / - Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            const searchInput = document.getElementById('chat-search');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape - Close modals/menus
        if (e.key === 'Escape') {
            closeAllModals();
        }
        
        // Ctrl/Cmd + Enter - Send message
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const chatInput = document.getElementById('chat-input');
            if (chatInput && document.activeElement === chatInput) {
                e.preventDefault();
                if (typeof sendMessage === 'function') {
                    sendMessage();
                }
            }
        }
    });
}

function showKeyboardShortcuts() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content glass-card shortcuts-modal">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 class="text-gradient">Keyboard Shortcuts</h2>
                <button class="btn-icon" onclick="this.closest('.modal').remove()" aria-label="Close">
                    ${IconHelper.icon(IconHelper.getActionIcon('close'))}
                </button>
            </div>
            <div class="shortcuts-grid">
                <div class="shortcut-item">
                    <span>Send message</span>
                    <div class="shortcut-key">
                        <kbd>Ctrl</kbd><kbd>Enter</kbd>
                    </div>
                </div>
                <div class="shortcut-item">
                    <span>New line</span>
                    <div class="shortcut-key">
                        <kbd>Shift</kbd><kbd>Enter</kbd>
                    </div>
                </div>
                <div class="shortcut-item">
                    <span>Show shortcuts</span>
                    <div class="shortcut-key">
                        <kbd>Ctrl</kbd><kbd>K</kbd>
                    </div>
                </div>
                <div class="shortcut-item">
                    <span>Focus search</span>
                    <div class="shortcut-key">
                        <kbd>Ctrl</kbd><kbd>/</kbd>
                    </div>
                </div>
                <div class="shortcut-item">
                    <span>New chat</span>
                    <div class="shortcut-key">
                        <kbd>Ctrl</kbd><kbd>N</kbd>
                    </div>
                </div>
                <div class="shortcut-item">
                    <span>Close modal</span>
                    <div class="shortcut-key">
                        <kbd>Esc</kbd>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// ============================================
// Message Actions
// ============================================
function initializeMessageActions() {
    // This will be called when messages are added
    // The actual implementation will be in the addMessage function
}

function addMessageActions(messageElement, messageId, content, role) {
    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'message-actions';
    
    // Copy button
    const copyBtn = document.createElement('button');
    copyBtn.className = 'message-action-btn';
    copyBtn.title = 'Copy message';
    copyBtn.innerHTML = IconHelper.icon('mdi:content-copy');
    copyBtn.onclick = function() {
        copyToClipboard(content, copyBtn);
    };
    
    // Edit button (only for user messages)
    if (role === 'user') {
        const editBtn = document.createElement('button');
        editBtn.className = 'message-action-btn';
        editBtn.title = 'Edit message';
        editBtn.innerHTML = IconHelper.icon('mdi:pencil');
        editBtn.onclick = function() {
            editMessage(messageElement, messageId, content);
        };
        actionsDiv.appendChild(editBtn);
    }
    
    // Regenerate button (only for assistant messages)
    if (role === 'assistant') {
        const regenerateBtn = document.createElement('button');
        regenerateBtn.className = 'message-action-btn';
        regenerateBtn.title = 'Regenerate response';
        regenerateBtn.innerHTML = IconHelper.icon('mdi:refresh');
        regenerateBtn.onclick = function() {
            regenerateMessage(messageId);
        };
        actionsDiv.appendChild(regenerateBtn);
    }
    
    actionsDiv.appendChild(copyBtn);
    messageElement.appendChild(actionsDiv);
    
    // Add timestamp
    const timestampDiv = document.createElement('div');
    timestampDiv.className = 'message-timestamp';
    const now = new Date();
    timestampDiv.innerHTML = `${IconHelper.icon('mdi:clock-outline', '', 'font-size: 12px;')} ${formatTime(now)}`;
    messageElement.appendChild(timestampDiv);
}

function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        button.classList.add('copied');
        button.innerHTML = IconHelper.icon('mdi:check');
        showNotification('Copied to clipboard', 'success');
        
        setTimeout(function() {
            button.classList.remove('copied');
            button.innerHTML = IconHelper.icon('mdi:content-copy');
        }, 2000);
    }).catch(function() {
        showNotification('Failed to copy', 'error');
    });
}

function editMessage(messageElement, messageId, content) {
    if (EnhancedChat.editingMessageId) {
        showNotification('Another message is being edited', 'warning');
        return;
    }
    
    EnhancedChat.editingMessageId = messageId;
    messageElement.classList.add('editing');
    
    const contentDiv = messageElement.querySelector('.message-content');
    const originalContent = contentDiv.innerHTML;
    
    // Replace content with textarea
    contentDiv.innerHTML = `
        <textarea class="edit-textarea" id="edit-textarea-${messageId}">${escapeHtml(content)}</textarea>
        <div class="edit-actions">
            <button class="edit-btn cancel" onclick="cancelEdit('${messageId}')">Cancel</button>
            <button class="edit-btn save" onclick="saveEdit('${messageId}')">Save</button>
        </div>
    `;
    
    const textarea = document.getElementById(`edit-textarea-${messageId}`);
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    
    // Handle Enter key
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            saveEdit(messageId);
        } else if (e.key === 'Escape') {
            cancelEdit(messageId);
        }
    });
}

function saveEdit(messageId) {
    const textarea = document.getElementById(`edit-textarea-${messageId}`);
    if (!textarea) return;
    
    const newContent = textarea.value.trim();
    if (!newContent) {
        showNotification('Message cannot be empty', 'error');
        return;
    }
    
    // Update the message in the UI
    const messageElement = document.getElementById(messageId);
    if (messageElement) {
        const contentDiv = messageElement.querySelector('.message-content');
        
        // Re-render with new content
        if (typeof HtmlSanitizer !== 'undefined') {
            HtmlSanitizer.setSafeHTML(contentDiv, newContent, true);
        } else {
            contentDiv.innerHTML = formatMessage(newContent);
        }
        
        messageElement.classList.remove('editing');
    }
    
    EnhancedChat.editingMessageId = null;
    showNotification('Message updated', 'success');
    
    // TODO: Send update to server
}

function cancelEdit(messageId) {
    const messageElement = document.getElementById(messageId);
    if (!messageElement) return;
    
    // Reload the original message
    // For now, just remove editing class
    messageElement.classList.remove('editing');
    EnhancedChat.editingMessageId = null;
}

function regenerateMessage(messageId) {
    showNotification('Regenerating response...', 'info');
    
    // TODO: Implement actual regeneration
    // This would need to call the API with the previous context
    setTimeout(function() {
        showNotification('Regeneration feature coming soon', 'info');
    }, 1000);
}

// ============================================
// Code Block Copy
// ============================================
function initializeCodeBlockCopy() {
    // This will be called when code blocks are rendered
    // The actual implementation will be in the HtmlSanitizer or message rendering
}

function addCodeBlockCopyButton(codeBlock) {
    const header = document.createElement('div');
    header.className = 'code-block-header';
    
    // Detect language
    const codeElement = codeBlock.querySelector('code');
    let language = 'code';
    if (codeElement && codeElement.className) {
        const match = codeElement.className.match(/language-(\w+)/);
        if (match) {
            language = match[1];
        }
    }
    
    header.innerHTML = `
        <span class="code-block-language">${language}</span>
        <button class="code-block-copy" title="Copy code">
            ${IconHelper.icon('mdi:content-copy')}
        </button>
    `;
    
    codeBlock.insertBefore(header, codeBlock.firstChild);
    
    const copyBtn = header.querySelector('.code-block-copy');
    copyBtn.addEventListener('click', function() {
        const code = codeElement.textContent;
        copyToClipboard(code, copyBtn);
    });
}

// ============================================
// Search
// ============================================
function initializeSearch() {
    // Add search bar to sidebar
    const chatSessions = document.querySelector('.chat-sessions');
    if (!chatSessions) return;
    
    const searchBar = document.createElement('div');
    searchBar.className = 'search-bar';
    searchBar.innerHTML = `
        <iconify-icon icon="mdi:magnify" class="search-icon"></iconify-icon>
        <input type="text" id="chat-search" class="search-input" placeholder="Search chats...">
        <button class="search-clear" onclick="clearSearch()">
            ${IconHelper.icon('mdi:close')}
        </button>
    `;
    
    chatSessions.insertBefore(searchBar, chatSessions.firstChild);
    
    const searchInput = document.getElementById('chat-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            searchChats(this.value);
        }, 300));
    }
}

function searchChats(query) {
    EnhancedChat.searchQuery = query.toLowerCase();
    const sessions = document.querySelectorAll('.chat-session-item');
    
    sessions.forEach(function(session) {
        const title = session.querySelector('.session-title-text')?.textContent.toLowerCase() || '';
        
        if (title.includes(EnhancedChat.searchQuery)) {
            session.style.display = '';
        } else {
            session.style.display = 'none';
        }
    });
}

function clearSearch() {
    const searchInput = document.getElementById('chat-search');
    if (searchInput) {
        searchInput.value = '';
        searchChats('');
    }
}

// ============================================
// Export
// ============================================
function initializeExport() {
    // Add export button to header
    const headerActions = document.querySelector('.header-actions');
    if (!headerActions) return;
    
    const exportBtn = document.createElement('button');
    exportBtn.className = 'btn-icon';
    exportBtn.title = 'Export chat';
    exportBtn.innerHTML = IconHelper.icon('mdi:download');
    exportBtn.onclick = function(e) {
        e.stopPropagation();
        toggleExportMenu(this);
    };
    
    headerActions.insertBefore(exportBtn, headerActions.firstChild);
    
    // Create export menu
    const exportMenu = document.createElement('div');
    exportMenu.className = 'export-menu';
    exportMenu.id = 'export-menu';
    exportMenu.innerHTML = `
        <div class="export-option" onclick="exportChat('text')">
            ${IconHelper.icon('mdi:file-document-outline')}
            <span>Export as Text</span>
        </div>
        <div class="export-option" onclick="exportChat('markdown')">
            ${IconHelper.icon('mdi:language-markdown')}
            <span>Export as Markdown</span>
        </div>
        <div class="export-option" onclick="exportChat('json')">
            ${IconHelper.icon('mdi:code-json')}
            <span>Export as JSON</span>
        </div>
    `;
    
    document.body.appendChild(exportMenu);
}

function toggleExportMenu(button) {
    const menu = document.getElementById('export-menu');
    if (!menu) return;
    
    const rect = button.getBoundingClientRect();
    menu.style.top = (rect.bottom + 8) + 'px';
    menu.style.right = (window.innerWidth - rect.right) + 'px';
    menu.classList.toggle('active');
    
    // Close on outside click
    setTimeout(function() {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && e.target !== button) {
                menu.classList.remove('active');
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

function exportChat(format) {
    const messages = document.querySelectorAll('.message');
    if (messages.length === 0) {
        showNotification('No messages to export', 'warning');
        return;
    }
    
    let content = '';
    let filename = `chat-export-${Date.now()}`;
    let mimeType = 'text/plain';
    
    switch (format) {
        case 'text':
            content = exportAsText(messages);
            filename += '.txt';
            break;
        case 'markdown':
            content = exportAsMarkdown(messages);
            filename += '.md';
            mimeType = 'text/markdown';
            break;
        case 'json':
            content = exportAsJson(messages);
            filename += '.json';
            mimeType = 'application/json';
            break;
    }
    
    downloadFile(content, filename, mimeType);
    showNotification(`Exported as ${format.toUpperCase()}`, 'success');
    
    // Close menu
    const menu = document.getElementById('export-menu');
    if (menu) menu.classList.remove('active');
}

function exportAsText(messages) {
    let text = '';
    messages.forEach(function(msg) {
        const role = msg.classList.contains('user') ? 'You' : 'Assistant';
        const content = msg.querySelector('.message-content')?.textContent || '';
        text += `${role}:\n${content}\n\n`;
    });
    return text;
}

function exportAsMarkdown(messages) {
    let md = '# Chat Export\n\n';
    messages.forEach(function(msg) {
        const role = msg.classList.contains('user') ? 'You' : 'Assistant';
        const content = msg.querySelector('.message-content')?.textContent || '';
        md += `## ${role}\n\n${content}\n\n---\n\n`;
    });
    return md;
}

function exportAsJson(messages) {
    const data = [];
    messages.forEach(function(msg) {
        const role = msg.classList.contains('user') ? 'user' : 'assistant';
        const content = msg.querySelector('.message-content')?.textContent || '';
        data.push({ role, content, timestamp: new Date().toISOString() });
    });
    return JSON.stringify(data, null, 2);
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ============================================
// Notifications
// ============================================
function initializeNotifications() {
    // Replace the old showNotification function
    window.showNotification = showEnhancedNotification;
}

function showEnhancedNotification(message, type = 'info', duration = 4000) {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icons = {
        success: 'mdi:check-circle',
        error: 'mdi:alert-circle',
        warning: 'mdi:alert',
        info: 'mdi:information'
    };
    
    const titles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Info'
    };
    
    notification.innerHTML = `
        <div class="notification-icon">
            ${IconHelper.icon(icons[type] || icons.info)}
        </div>
        <div class="notification-content">
            <div class="notification-title">${titles[type] || titles.info}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            ${IconHelper.icon('mdi:close')}
        </button>
    `;
    
    container.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(function() {
        notification.classList.add('hiding');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, duration);
}

// ============================================
// Utility Functions
// ============================================
function formatTime(date) {
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatMessage(text) {
    // Basic markdown-like formatting
    return text
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/(?<!\*)\*([^*]+?)\*(?!\*)/g, '<em>$1</em>')
        .replace(/`([^`]+?)`/g, '<code class="inline-code">$1</code>');
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.remove();
    });
    
    document.querySelectorAll('.export-menu, .quick-actions-menu').forEach(function(menu) {
        menu.classList.remove('active');
    });
}

// Debounce function (if not already defined)
if (typeof debounce === 'undefined') {
    window.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
}

// Export EnhancedChat for use in other files
window.EnhancedChat = EnhancedChat;

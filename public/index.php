<?php require __DIR__ . '/header.php'; ?>

<div class="main-content">
    <div class="chat-container">
        <div class="chat-messages" id="chat-messages">
            <div class="message assistant">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <p>Hello! I'm your AI assistant powered by Ollama. Upload documents to enable RAG (Retrieval-Augmented Generation) and get context-aware responses!</p>
                </div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <button class="btn-icon" id="attach-file-btn" title="Attach file">
                    <i class="fas fa-paperclip"></i>
                </button>
                <textarea 
                    id="chat-input" 
                    class="chat-input" 
                    placeholder="Type your message..."
                    rows="1"
                ></textarea>
                <div class="input-actions">
                    <button class="btn-icon" id="rag-toggle" title="Toggle RAG" data-rag-enabled="true">
                        <i class="fas fa-brain"></i>
                    </button>
                    <button class="btn-icon primary" id="send-btn" title="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <div id="file-preview" style="display: none; margin-top: 12px;"></div>
        </div>
    </div>
</div>

<!-- Document Management Modal -->
<div id="documents-modal" class="modal" style="display: none;">
    <div class="modal-content glass-card" style="max-width: 800px; margin: 50px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 class="text-gradient">Document Management</h2>
            <button class="btn-icon" onclick="closeDocumentsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="documents-panel">
            <div class="upload-zone" id="upload-zone">
                <i class="fas fa-cloud-upload-alt"></i>
                <p style="margin-top: 16px; font-weight: 600;">Drag & drop files here</p>
                <p style="margin-top: 8px; color: var(--text-secondary); font-size: 14px;">
                    or click to browse (PDF, DOCX, TXT, XLSX, CSV, MD)
                </p>
                <input type="file" id="file-input" multiple accept=".pdf,.docx,.txt,.xlsx,.csv,.md" style="display: none;">
            </div>
            
            <div class="document-list" id="document-list">
                <!-- Documents will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/modern-chat.js"></script>

<?php require __DIR__ . '/footer.php'; ?>

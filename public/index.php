<?php 
require_once __DIR__ . '/icon_helper.php';
require __DIR__ . '/header.php';
$config = require dirname(__DIR__) . '/src/config.php';
$developerName = $config['developerName'] ?? '{{DEVELOPER_NAME}}';
$companyName = $config['companyName'] ?? '{{COMPANY_NAME}}';
$companyUrl = $config['companyUrl'] ?? '{{COMPANY_URL}}'; 
?>

<main class="main-content" role="main">
    <div class="container-fluid" style="padding: 0; height: 100%; display: flex; flex-direction: column;">
        <div class="chat-container" style="flex: 1; height: 100%;">
        <div class="chat-messages" id="chat-messages" role="log" aria-live="polite" aria-label="Chat messages">
            <div class="chat-empty-state" id="empty-state">
                <div class="bot-avatar-large">
                    <?php echo IconHelper::icon(IconHelper::getBotIcon('assistant'), '', 'font-size: 100px;'); ?>
                </div>
                <h2>Chat with Ollama</h2>
                <p>Start a conversation or upload documents to enable RAG-powered responses</p>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <button class="btn-icon" id="attach-file-btn" title="Attach file" aria-label="Attach file">
                    <?php echo IconHelper::icon(IconHelper::getActionIcon('attach')); ?>
                </button>
                <textarea 
                    id="chat-input" 
                    class="chat-input" 
                    placeholder="Send a message (Press Enter to send, Shift+Enter for new line)"
                    rows="1"
                    aria-label="Chat message input"
                    aria-describedby="chat-input-help"
                ></textarea>
                <div class="input-actions">
                    <button class="btn-icon" id="voice-input-btn" title="Voice input" aria-label="Voice input">
                        <?php echo IconHelper::icon('mdi:microphone'); ?>
                    </button>
                    <button class="btn-icon" id="rag-toggle" title="Toggle RAG" data-rag-enabled="true" aria-label="Toggle RAG (Retrieval Augmented Generation)" aria-pressed="true">
                        <?php echo IconHelper::icon(IconHelper::getRAGIcon()); ?>
                    </button>
                    <button class="btn-icon primary" id="send-btn" title="Send message" aria-label="Send message">
                        <?php echo IconHelper::icon(IconHelper::getActionIcon('send')); ?>
                    </button>
                </div>
            </div>
            <div id="file-preview" style="display: none; margin-top: 12px;"></div>
            <div class="chat-footer-content">
                <small style="line-height: 0.4; display: block; margin-top: 8px; color: var(--text-secondary); text-align: center;">Chatbots do make mistakes • Press <kbd style="background: var(--glass-bg); padding: 2px 6px; border-radius: 4px; font-size: 11px;">Ctrl+K</kbd> for keyboard shortcuts</small>
                <div class="footer-credits-inline" style="display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                    <span style="font-size: 11px; color: var(--text-secondary);">Made with</span>
                    <iconify-icon icon="mdi:heart" style="color: #f85149; font-size: 12px;"></iconify-icon>
                    <span style="font-size: 11px; color: var(--text-secondary);">
                        by <?php echo htmlspecialchars($developerName); ?> from
                    </span>
                    <a href="<?php echo htmlspecialchars($companyUrl); ?>" target="_blank" style="display: inline-flex; align-items: center;">
                        <?php echo htmlspecialchars($companyName); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Document Management Modal -->
<div id="documents-modal" class="modal" style="display: none;">
    <div class="modal-content glass-card" style="max-width: 800px; margin: 50px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 class="text-gradient">Document Management</h2>
            <button class="btn-icon" onclick="closeDocumentsModal()" aria-label="Close document management modal">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('close')); ?>
            </button>
        </div>
        
        <div class="documents-panel">
            <div class="upload-zone" id="upload-zone" role="button" tabindex="0" aria-label="Upload document" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); document.getElementById('file-input').click(); }">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('upload'), '', 'font-size: 48px; color: var(--accent);'); ?>
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

<?php
require_once __DIR__ . '/path_helper.php';
$assetsPath = getAssetsPath();
?>
<script src="<?php echo $assetsPath; ?>/js/processing-messages.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo $assetsPath; ?>/js/modern-chat.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo $assetsPath; ?>/js/enhanced-chat.js?v=<?php echo time(); ?>"></script>

<?php require __DIR__ . '/footer.php'; ?>

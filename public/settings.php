<?php require __DIR__ . '/header.php'; ?>

<div class="main-content">
    <div class="chat-container">
        <h1 class="text-gradient" style="margin-bottom: 32px;">Settings</h1>
        
        <div class="glass-card" style="max-width: 600px;">
            <h2 style="margin-bottom: 24px; font-size: 20px;">Model Configuration</h2>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">
                    Default Model
                </label>
                <div style="display: flex; gap: 12px;">
                    <select id="model-select" class="model-select" style="flex: 1;">
                        <option value="">Loading models...</option>
                    </select>
                    <button class="btn-icon" onclick="syncModels()" title="Sync Models">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" id="rag-enabled" checked style="width: 20px; height: 20px; cursor: pointer;">
                    <span>Enable RAG by default</span>
                </label>
                <p style="margin-top: 8px; font-size: 14px; color: var(--text-secondary);">
                    When enabled, the AI will automatically search your documents for relevant context.
                </p>
            </div>
            
            <button class="btn-modern" onclick="saveSettings()">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
        
        <div class="glass-card" style="max-width: 600px; margin-top: 24px;">
            <h2 style="margin-bottom: 24px; font-size: 20px;">About</h2>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                This application uses Ollama for local AI inference and implements RAG (Retrieval-Augmented Generation) 
                to provide context-aware responses based on your uploaded documents.
            </p>
            <p style="margin-top: 16px; color: var(--text-secondary); line-height: 1.8;">
                Upload documents in PDF, DOCX, TXT, XLSX, CSV, or MD format to enable intelligent document-based conversations.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    updateModelList();
    
    // Load saved RAG setting
    const ragEnabled = localStorage.getItem('ragEnabled') !== 'false';
    document.getElementById('rag-enabled').checked = ragEnabled;
});

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

function loadSettings() {
    const savedModel = localStorage.getItem('defaultModel');
    if (savedModel) {
        setTimeout(() => {
            document.getElementById('model-select').value = savedModel;
        }, 500);
    }
}

function saveSettings() {
    const model = document.getElementById('model-select').value;
    const ragEnabled = document.getElementById('rag-enabled').checked;
    
    localStorage.setItem('defaultModel', model);
    localStorage.setItem('ragEnabled', ragEnabled);
    
    showNotification('Settings saved successfully!', 'success');
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
</script>

<?php require __DIR__ . '/footer.php'; ?>

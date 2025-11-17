<?php require __DIR__ . '/header.php'; ?>

<div class="main-content">
    <div class="chat-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <h1 class="text-gradient">Document Management</h1>
            <button class="btn-modern" onclick="openUploadDialog()">
                <i class="fas fa-plus"></i> Upload Document
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
                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    <div class="spinner" style="margin: 0 auto;"></div>
                    <p style="margin-top: 16px;">Loading documents...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments();
    
    const fileInput = document.getElementById('file-input');
    const uploadZone = document.getElementById('upload-zone');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFiles);
    
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
});

function handleFiles(files) {
    Array.from(files).forEach(file => uploadDocument(file));
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

function loadDocuments() {
    axios.post('/src/Controllers/RAGController.php', { action: 'list' })
        .then(response => {
            if (response.data.success) {
                renderDocuments(response.data.documents);
            }
        })
        .catch(error => {
            document.getElementById('document-list').innerHTML = 
                '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">Failed to load documents</p>';
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
        card.style.position = 'relative';
        card.innerHTML = `
            <div class="document-icon">
                <i class="fas fa-file-${getFileIcon(doc.file_type)}"></i>
            </div>
            <div class="document-name" title="${doc.original_filename}">${doc.original_filename}</div>
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

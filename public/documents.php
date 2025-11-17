<?php 
require_once __DIR__ . '/icon_helper.php';
require __DIR__ . '/header.php'; 
?>
<script>
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
</script>

<main class="main-content" role="main" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
    <div class="container-fluid" style="padding: 24px 32px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Header Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <h1 class="text-gradient" style="margin: 0;">Document Management</h1>
            <button class="btn-modern" onclick="openUploadDialog()">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('add')); ?> Upload Document
            </button>
        </div>

        <!-- Search and Filter Bar -->
        <div class="glass-card" style="margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; position: relative;">
                    <?php echo IconHelper::icon('mdi:magnify', '', 'position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 20px;'); ?>
                    <input 
                        type="text" 
                        id="search-input" 
                        placeholder="Search documents by name, type, or status..." 
                        class="search-input"
                        style="width: 100%; padding: 10px 16px 10px 44px; background: var(--dark-surface); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text-primary); font-size: 14px; outline: none; transition: all 0.2s;"
                        onkeyup="handleSearch()"
                    >
                </div>
                <select id="filter-status" class="filter-select" onchange="handleFilter()" style="padding: 10px 16px; background: var(--dark-surface); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text-primary); font-size: 14px; cursor: pointer; outline: none;">
                    <option value="">All Status</option>
                    <option value="processed">Processed</option>
                    <option value="processing">Processing</option>
                    <option value="pending">Pending</option>
                </select>
                <select id="filter-type" class="filter-select" onchange="handleFilter()" style="padding: 10px 16px; background: var(--dark-surface); border: 1px solid var(--glass-border); border-radius: 12px; color: var(--text-primary); font-size: 14px; cursor: pointer; outline: none;">
                    <option value="">All Types</option>
                    <option value="pdf">PDF</option>
                    <option value="docx">DOCX</option>
                    <option value="txt">TXT</option>
                    <option value="xlsx">XLSX</option>
                    <option value="csv">CSV</option>
                    <option value="md">Markdown</option>
                </select>
                <button class="btn-icon" onclick="clearFilters()" title="Clear filters">
                    <?php echo IconHelper::icon('mdi:filter-off'); ?>
                </button>
            </div>
        </div>

        <!-- Upload Zone (Collapsible) -->
        <div class="glass-card" id="upload-zone" style="margin-bottom: 24px; padding: 32px; text-align: center; cursor: pointer; border: 2px dashed var(--glass-border); transition: all 0.3s;" onclick="document.getElementById('file-input').click()">
            <?php echo IconHelper::icon(IconHelper::getActionIcon('upload'), '', 'font-size: 48px; color: var(--accent);'); ?>
            <p style="margin-top: 16px; font-weight: 600; color: var(--text-primary);">Drag & drop files here</p>
            <p style="margin-top: 8px; color: var(--text-secondary); font-size: 14px;">
                or click to browse (PDF, DOCX, TXT, XLSX, CSV, MD, PPT, DOC)
            </p>
            <input type="file" id="file-input" multiple accept=".pdf,.docx,.txt,.xlsx,.csv,.md,.ppt,.doc" style="display: none;">
        </div>

        <!-- Documents Table -->
        <div class="glass-card" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">
            <div style="flex: 1; overflow-y: auto; overflow-x: auto;">
            <table class="documents-table" id="documents-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <?php echo IconHelper::icon('mdi:file-document', '', 'font-size: 18px;'); ?>
                        </th>
                        <th onclick="sortTable('name')" style="cursor: pointer; user-select: none;">
                            Name <span class="sort-indicator" id="sort-name"></span>
                        </th>
                        <th onclick="sortTable('type')" style="cursor: pointer; user-select: none;">
                            Type <span class="sort-indicator" id="sort-type"></span>
                        </th>
                        <th onclick="sortTable('size')" style="cursor: pointer; user-select: none;">
                            Size <span class="sort-indicator" id="sort-size"></span>
                        </th>
                        <th onclick="sortTable('status')" style="cursor: pointer; user-select: none;">
                            Status <span class="sort-indicator" id="sort-status"></span>
                        </th>
                        <th onclick="sortTable('chunks')" style="cursor: pointer; user-select: none;">
                            Chunks <span class="sort-indicator" id="sort-chunks"></span>
                        </th>
                        <th onclick="sortTable('date')" style="cursor: pointer; user-select: none;">
                            Uploaded <span class="sort-indicator" id="sort-date"></span>
                        </th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="documents-tbody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <div class="spinner" style="margin: 0 auto;"></div>
                            <p style="margin-top: 16px;">Loading documents...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div id="table-empty" style="display: none; text-align: center; padding: 40px; color: var(--text-secondary);">
                <p>No documents found</p>
            </div>
        </div>

        <!-- Document Count and Footer -->
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
            <div style="margin-bottom: 12px; color: var(--text-secondary); font-size: 14px;">
                <span id="document-count">0</span> document(s) found
            </div>
            <div class="chat-footer-content">
                <small style="line-height: 0.4; display: block; margin-top: 8px; color: var(--text-secondary); text-align: center;">Chatbots do make mistakes</small>
                <div class="footer-credits-inline" style="display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                    <span style="font-size: 11px; color: var(--text-secondary);">Made with</span>
                    <iconify-icon icon="mdi:heart" style="color: #f85149; font-size: 12px;"></iconify-icon>
                    <span style="font-size: 11px; color: var(--text-secondary);">
                        by Tarek Tarabichi from
                    </span>
                    <a href="https://2tinteractive.com" target="_blank" style="display: inline-flex; align-items: center;">
                        2TInteractive
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Rename Document Modal -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content glass-card" style="max-width: 500px; margin: 50px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 class="text-gradient">Rename Document</h2>
            <button class="btn-icon" onclick="closeEditModal()">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('close')); ?>
            </button>
        </div>
        <form id="edit-form" onsubmit="saveDocument(event)">
            <input type="hidden" id="edit-doc-id">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">Document Name</label>
                <input 
                    type="text" 
                    id="edit-doc-name" 
                    required
                    style="width: 100%; padding: 12px; background: var(--dark-surface); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary); font-size: 14px; outline: none; box-sizing: border-box;"
                >
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-modern secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-modern">
                    <?php echo IconHelper::icon(IconHelper::getActionIcon('save')); ?> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Document Preview Modal -->
<div id="preview-modal" class="modal" style="display: none; z-index: 2000;">
    <div class="modal-content glass-card" style="max-width: 90vw; max-height: 90vh; margin: 20px auto; display: flex; flex-direction: column; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-shrink: 0;">
            <h2 class="text-gradient" id="preview-title">Document Preview</h2>
            <button class="btn-icon" onclick="closePreviewModal()">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('close')); ?>
            </button>
        </div>
        <div style="flex: 1; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
            <div id="preview-content" style="color: var(--text-primary); line-height: 1.6;">
                <div class="spinner" style="margin: 40px auto;"></div>
            </div>
        </div>
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--glass-border); flex-shrink: 0; color: var(--text-secondary); font-size: 13px;">
            <div id="preview-meta"></div>
        </div>
    </div>
</div>

<script>
// Fast Index for O(1) lookups
const documentIndex = {
    byId: new Map(),
    byName: new Map(),
    byType: new Map(),
    byStatus: new Map(),
    searchIndex: new Map() // For full-text search
};

let allDocuments = [];
let filteredDocuments = [];
let currentSort = { column: null, direction: 'asc' };
let statusPollInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDateTimeSettings();
    loadDocuments();
    
    // Start polling for status updates if there are processing documents
    startStatusPolling();
    
    const fileInput = document.getElementById('file-input');
    const uploadZone = document.getElementById('upload-zone');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFiles);
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--accent)';
        uploadZone.style.background = 'var(--glass-bg)';
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.style.borderColor = 'var(--glass-border)';
        uploadZone.style.background = 'transparent';
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--glass-border)';
        uploadZone.style.background = 'transparent';
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
    
    // Don't set Content-Type header - let axios set it automatically with boundary
    axios.post('/api/rag.php', formData)
    .then(response => {
        if (response.data.success) {
            showNotification(`${file.name} uploaded successfully!`, 'success');
            loadDocuments(true); // Force refresh and start polling
        } else {
            const errorMsg = response.data.error || 'Unknown error';
            showNotification(`Upload failed: ${errorMsg}`, 'error');
            console.error('Upload error response:', response.data);
        }
    })
    .catch(error => {
        let errorMsg = error.message;
        if (error.response && error.response.data && error.response.data.error) {
            errorMsg = error.response.data.error;
        }
        showNotification(`Upload error: ${errorMsg}`, 'error');
        console.error('Upload error:', error);
    });
}

function loadDocuments(forceRefresh = false) {
    axios.post('/api/rag.php', { action: 'list' })
        .then(response => {
            if (response.data.success) {
                allDocuments = response.data.documents || [];
                buildIndex(allDocuments);
                applyFilters();
                
                // Manage status polling based on processing documents
                manageStatusPolling();
            }
        })
        .catch(error => {
            document.getElementById('documents-tbody').innerHTML = 
                '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">Failed to load documents</td></tr>';
        });
}

function startStatusPolling() {
    // Poll every 3 seconds for status updates
    if (statusPollInterval) {
        clearInterval(statusPollInterval);
    }
    
    statusPollInterval = setInterval(() => {
        // Only refresh if there are documents with 'processing' status
        const hasProcessing = allDocuments.some(doc => doc.status === 'processing' || doc.status === 'pending');
        if (hasProcessing) {
            loadDocuments(true);
        }
    }, 3000); // Poll every 3 seconds
}

function manageStatusPolling() {
    // Check if there are any processing documents
    const hasProcessing = allDocuments.some(doc => doc.status === 'processing' || doc.status === 'pending');
    
    if (hasProcessing) {
        // Start polling if not already started
        if (!statusPollInterval) {
            startStatusPolling();
        }
    } else {
        // Stop polling if no processing documents
        if (statusPollInterval) {
            clearInterval(statusPollInterval);
            statusPollInterval = null;
        }
    }
}

// Build fast indexes for O(1) lookups
function buildIndex(documents) {
    // Clear existing indexes
    documentIndex.byId.clear();
    documentIndex.byName.clear();
    documentIndex.byType.clear();
    documentIndex.byStatus.clear();
    documentIndex.searchIndex.clear();
    
    documents.forEach(doc => {
        // Index by ID
        documentIndex.byId.set(doc.id, doc);
        
        // Index by name (lowercase for case-insensitive search)
        const nameLower = doc.original_filename.toLowerCase();
        if (!documentIndex.byName.has(nameLower)) {
            documentIndex.byName.set(nameLower, []);
        }
        documentIndex.byName.get(nameLower).push(doc);
        
        // Index by type
        const type = doc.file_type || 'unknown';
        if (!documentIndex.byType.has(type)) {
            documentIndex.byType.set(type, []);
        }
        documentIndex.byType.get(type).push(doc);
        
        // Index by status
        const status = doc.status || 'pending';
        if (!documentIndex.byStatus.has(status)) {
            documentIndex.byStatus.set(status, []);
        }
        documentIndex.byStatus.get(status).push(doc);
        
        // Build search index (tokenize for fast search)
        const searchText = `${doc.original_filename} ${type} ${status}`.toLowerCase();
        const tokens = searchText.split(/\s+/);
        tokens.forEach(token => {
            if (token.length > 1) { // Ignore single characters
                if (!documentIndex.searchIndex.has(token)) {
                    documentIndex.searchIndex.set(token, new Set());
                }
                documentIndex.searchIndex.get(token).add(doc.id);
            }
        });
    });
}

function applyFilters() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    const statusFilter = document.getElementById('filter-status').value;
    const typeFilter = document.getElementById('filter-type').value;
    
    let results = [...allDocuments];
    
    // Fast search using index
    if (searchTerm) {
        const searchTokens = searchTerm.split(/\s+/).filter(t => t.length > 1);
        const matchingIds = new Set();
        
        if (searchTokens.length > 0) {
            // Find documents matching all tokens (AND logic)
            const tokenMatches = searchTokens.map(token => {
                const matches = new Set();
                documentIndex.searchIndex.forEach((docIds, indexedToken) => {
                    if (indexedToken.includes(token) || token.includes(indexedToken)) {
                        docIds.forEach(id => matches.add(id));
                    }
                });
                return matches;
            });
            
            // Intersection of all token matches
            if (tokenMatches.length > 0) {
                tokenMatches[0].forEach(id => {
                    if (tokenMatches.every(match => match.has(id))) {
                        matchingIds.add(id);
                    }
                });
            }
        }
        
        results = results.filter(doc => matchingIds.has(doc.id));
    }
    
    // Apply status filter
    if (statusFilter) {
        results = results.filter(doc => (doc.status || 'pending') === statusFilter);
    }
    
    // Apply type filter
    if (typeFilter) {
        results = results.filter(doc => (doc.file_type || '').toLowerCase() === typeFilter.toLowerCase());
    }
    
    filteredDocuments = results;
    updateDocumentCount(results.length);
    renderTable(results);
}

function handleSearch() {
    applyFilters();
}

function handleFilter() {
    applyFilters();
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-type').value = '';
    applyFilters();
}

function sortTable(column) {
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = column;
        currentSort.direction = 'asc';
    }
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(el => el.textContent = '');
    const indicator = document.getElementById(`sort-${column}`);
    if (indicator) {
        indicator.textContent = currentSort.direction === 'asc' ? ' ▲' : ' ▼';
    }
    
    // Sort filtered documents
    filteredDocuments.sort((a, b) => {
        let aVal, bVal;
        
        switch(column) {
            case 'name':
                aVal = (a.original_filename || '').toLowerCase();
                bVal = (b.original_filename || '').toLowerCase();
                break;
            case 'type':
                aVal = (a.file_type || '').toLowerCase();
                bVal = (b.file_type || '').toLowerCase();
                break;
            case 'size':
                aVal = a.file_size || 0;
                bVal = b.file_size || 0;
                break;
            case 'status':
                aVal = (a.status || 'pending').toLowerCase();
                bVal = (b.status || 'pending').toLowerCase();
                break;
            case 'chunks':
                aVal = a.chunk_count || 0;
                bVal = b.chunk_count || 0;
                break;
            case 'date':
                aVal = new Date(a.uploaded_at || 0).getTime();
                bVal = new Date(b.uploaded_at || 0).getTime();
                break;
            default:
                return 0;
        }
        
        if (aVal < bVal) return currentSort.direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return currentSort.direction === 'asc' ? 1 : -1;
        return 0;
    });
    
    renderTable(filteredDocuments);
}

// Date/Time formatting with timezone support
let userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
let dateFormat = 'short'; // 'short', 'medium', 'long', 'full'
let timeFormat = 'short'; // 'short', 'medium'

// Load timezone and format settings from localStorage or settings
function loadDateTimeSettings() {
    const savedTimezone = localStorage.getItem('userTimezone');
    const savedDateFormat = localStorage.getItem('dateFormat');
    const savedTimeFormat = localStorage.getItem('timeFormat');
    
    if (savedTimezone) {
        userTimezone = savedTimezone;
    } else {
        // Default to browser timezone if not set
        userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
    
    if (savedDateFormat) {
        dateFormat = savedDateFormat;
    } else {
        dateFormat = 'medium'; // Default to medium
    }
    
    if (savedTimeFormat) {
        timeFormat = savedTimeFormat;
    } else {
        timeFormat = 'short'; // Default to short
    }
}

// Listen for storage changes to update date formats in real-time (from other tabs/windows)
window.addEventListener('storage', function(e) {
    if (e.key === 'userTimezone' || e.key === 'dateFormat' || e.key === 'timeFormat') {
        loadDateTimeSettings();
        // Re-render table if documents are loaded
        if (typeof allDocuments !== 'undefined' && allDocuments.length > 0) {
            renderTable(filteredDocuments && filteredDocuments.length > 0 ? filteredDocuments : allDocuments);
        }
    }
});

// Also listen for custom event when settings are saved on the same page
document.addEventListener('settingsUpdated', function() {
    loadDateTimeSettings();
    // Re-render table if documents are loaded
    if (typeof allDocuments !== 'undefined' && allDocuments.length > 0) {
        renderTable(filteredDocuments && filteredDocuments.length > 0 ? filteredDocuments : allDocuments);
    }
});

// Also listen for localStorage changes in the same tab (using a custom event)
const originalSetItem = localStorage.setItem;
localStorage.setItem = function(key, value) {
    originalSetItem.apply(this, arguments);
    if (key === 'userTimezone' || key === 'dateFormat' || key === 'timeFormat') {
        // Dispatch custom event for same-tab updates
        window.dispatchEvent(new CustomEvent('localStorageChange', { 
            detail: { key, value } 
        }));
    }
};

window.addEventListener('localStorageChange', function(e) {
    if (e.detail.key === 'userTimezone' || e.detail.key === 'dateFormat' || e.detail.key === 'timeFormat') {
        loadDateTimeSettings();
        // Re-render table if documents are loaded
        if (typeof allDocuments !== 'undefined' && allDocuments.length > 0) {
            renderTable(filteredDocuments && filteredDocuments.length > 0 ? filteredDocuments : allDocuments);
        }
    }
});

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    
    // Reload settings in case they changed
    loadDateTimeSettings();
    
    try {
        const date = new Date(dateString);
        
        // Configure date format based on user preference
        let dateOptions = {
            timeZone: userTimezone || Intl.DateTimeFormat().resolvedOptions().timeZone
        };
        
        switch (dateFormat) {
            case 'short':
                dateOptions = {
                    ...dateOptions,
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric'
                };
                break;
            case 'medium':
                dateOptions = {
                    ...dateOptions,
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                break;
            case 'long':
                dateOptions = {
                    ...dateOptions,
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                break;
            case 'full':
                dateOptions = {
                    ...dateOptions,
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                break;
            default:
                dateOptions = {
                    ...dateOptions,
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
        }
        
        // Configure time format
        const timeOptions = {
            timeZone: userTimezone || Intl.DateTimeFormat().resolvedOptions().timeZone,
            hour: 'numeric',
            minute: 'numeric',
            ...(timeFormat === 'medium' && { second: 'numeric' })
        };
        
        const formattedDate = date.toLocaleDateString('en-US', dateOptions);
        const formattedTime = date.toLocaleTimeString('en-US', timeOptions);
        
        return `${formattedDate} ${formattedTime}`;
    } catch (e) {
        console.error('Error formatting date:', e);
        return new Date(dateString).toLocaleString();
    }
}

function getFileTypeIcon(fileType) {
    const ext = (fileType || '').toLowerCase();
    // Using modern icon sets that match WowDash style
    const icons = {
        'pdf': 'vscode-icons:file-type-pdf2',
        'doc': 'vscode-icons:file-type-word2',
        'docx': 'vscode-icons:file-type-word2',
        'txt': 'vscode-icons:file-type-text',
        'md': 'vscode-icons:file-type-markdown',
        'xls': 'vscode-icons:file-type-excel2',
        'xlsx': 'vscode-icons:file-type-excel2',
        'csv': 'vscode-icons:file-type-csv',
        'ppt': 'vscode-icons:file-type-powerpoint',
        'pptx': 'vscode-icons:file-type-powerpoint',
        'rtf': 'vscode-icons:file-type-word2',
        'odt': 'vscode-icons:file-type-word2',
        'default': 'vscode-icons:file-type-document'
    };
    return icons[ext] || icons['default'];
}

function formatFileTypeName(fileType) {
    const ext = (fileType || 'unknown').toLowerCase();
    const names = {
        'pdf': 'PDF',
        'doc': 'Word',
        'docx': 'Word',
        'txt': 'Text',
        'md': 'Markdown',
        'xls': 'Excel',
        'xlsx': 'Excel',
        'csv': 'CSV',
        'ppt': 'PowerPoint',
        'pptx': 'PowerPoint',
        'rtf': 'Rich Text',
        'odt': 'OpenDocument'
    };
    return names[ext] || ext.toUpperCase();
}

function getFilenameWithoutExtension(filename) {
    if (!filename) return 'Unknown';
    const lastDot = filename.lastIndexOf('.');
    if (lastDot === -1) return filename;
    return filename.substring(0, lastDot);
}

function renderTable(documents) {
    const tbody = document.getElementById('documents-tbody');
    const emptyState = document.getElementById('table-empty');
    
    if (documents.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    tbody.innerHTML = documents.map(doc => {
        const fileTypeIcon = getFileTypeIcon(doc.file_type || '');
        const fileTypeName = formatFileTypeName(doc.file_type || '');
        const status = doc.status || 'pending';
        const uploadedDate = formatDateTime(doc.uploaded_at);
        
        return `
            <tr data-doc-id="${doc.id}">
                <td style="text-align: center;">
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                        <div class="file-type-icon-wrapper" style="width: 32px; height: 32px; border-radius: 8px; background: var(--glass-bg); border: 1px solid var(--glass-border); display: inline-flex; align-items: center; justify-content: center;">
                            <iconify-icon icon="${fileTypeIcon}" style="font-size: 18px; color: var(--accent);"></iconify-icon>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight: 600; color: var(--text-primary);">${escapeHtml(getFilenameWithoutExtension(doc.original_filename || 'Unknown'))}</div>
                </td>
                <td>
                    <span style="color: var(--text-secondary); font-size: 13px; font-weight: 500;">${fileTypeName}</span>
                </td>
                <td style="color: var(--text-secondary);">${formatFileSize(doc.file_size || 0)}</td>
                <td>
                    <span class="status-badge ${status}">${status}</span>
                </td>
                <td style="text-align: center; color: var(--text-secondary);">
                    ${doc.chunk_count || 0}
                </td>
                <td style="color: var(--text-secondary); font-size: 13px;">${uploadedDate}</td>
                <td style="text-align: center;">
                    <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            <a href="javascript:void(0)" class="action-btn action-btn-view" onclick="viewDocument(${doc.id})" title="View">
                                <iconify-icon icon="iconamoon:eye-light" style="font-size: 16px;"></iconify-icon>
                            </a>
                            <a href="javascript:void(0)" class="action-btn action-btn-edit" onclick="editDocument(${doc.id})" title="Rename">
                                <iconify-icon icon="lucide:edit" style="font-size: 16px;"></iconify-icon>
                            </a>
                            <a href="javascript:void(0)" class="action-btn action-btn-chat" onclick="startChatWithDocument(${doc.id}, '${escapeHtml(getFilenameWithoutExtension(doc.original_filename || ''))}')" title="Start Chat">
                                <iconify-icon icon="mdi:message-text" style="font-size: 16px;"></iconify-icon>
                            </a>
                            <a href="javascript:void(0)" class="action-btn action-btn-delete" onclick="deleteDocument(${doc.id})" title="Delete">
                                <iconify-icon icon="mingcute:delete-2-line" style="font-size: 16px;"></iconify-icon>
                            </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function updateDocumentCount(count) {
    document.getElementById('document-count').textContent = count;
}

function editDocument(id) {
    const doc = documentIndex.byId.get(id);
    if (!doc) return;
    
    document.getElementById('edit-doc-id').value = id;
    document.getElementById('edit-doc-name').value = doc.original_filename || '';
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.getElementById('edit-form').reset();
}

function saveDocument(event) {
    event.preventDefault();
    const id = document.getElementById('edit-doc-id').value;
    const newName = document.getElementById('edit-doc-name').value.trim();
    
    if (!newName) {
        showNotification('Document name cannot be empty', 'error');
        return;
    }
    
    // Update document (for now, just update locally - backend update would go here)
    const doc = documentIndex.byId.get(parseInt(id));
    if (doc) {
        doc.original_filename = newName;
        buildIndex(allDocuments);
        applyFilters();
        showNotification('Document updated successfully', 'success');
        closeEditModal();
    }
}

function viewDocument(id) {
    const doc = documentIndex.byId.get(id);
    if (!doc) return;
    
    // Show preview modal
    const modal = document.getElementById('preview-modal');
    const title = document.getElementById('preview-title');
    const content = document.getElementById('preview-content');
    const meta = document.getElementById('preview-meta');
    
    title.textContent = doc.original_filename || 'Document Preview';
    content.innerHTML = '<div class="spinner" style="margin: 40px auto;"></div>';
    meta.innerHTML = '';
    modal.style.display = 'flex';
    
    // Load preview data
    axios.post('/api/rag.php', {
        action: 'preview',
        document_id: id,
        limit: 50
    })
    .then(response => {
        if (response.data.success && response.data.preview) {
            const preview = response.data.preview;
            const document = preview.document;
            const chunks = preview.chunks || [];
            const totalChunks = preview.total_chunks || 0;
            
            // Render chunks
            if (chunks.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">No content available. Document may still be processing.</p>';
            } else {
                let html = '';
                chunks.forEach((chunk, index) => {
                    html += `
                        <div class="md-card" style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">Chunk #${chunk.chunk_index + 1}</span>
                                <span style="font-size: 11px; color: var(--text-secondary);">${chunk.token_count || 0} tokens</span>
                            </div>
                            <div style="color: var(--text-primary); white-space: pre-wrap; word-wrap: break-word; font-size: 14px; line-height: 1.6;">${escapeHtml(chunk.content)}</div>
                        </div>
                    `;
                });
                
                if (totalChunks > chunks.length) {
                    html += `<p style="text-align: center; color: var(--text-secondary); margin-top: 16px; font-size: 13px;">Showing first ${chunks.length} of ${totalChunks} chunks</p>`;
                }
                
                content.innerHTML = html;
            }
            
            // Render metadata
            const uploadedDate = formatDateTime(document.uploaded_at);
            const processedDate = document.processed_at ? formatDateTime(document.processed_at) : 'Not processed';
            meta.innerHTML = `
                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                    <div><strong>Type:</strong> ${formatFileTypeName(document.file_type || '')}</div>
                    <div><strong>Size:</strong> ${formatFileSize(document.file_size || 0)}</div>
                    <div><strong>Status:</strong> <span class="status-badge ${document.status || 'pending'}">${document.status || 'pending'}</span></div>
                    <div><strong>Chunks:</strong> ${totalChunks}</div>
                    <div><strong>Uploaded:</strong> ${uploadedDate}</div>
                    ${document.processed_at ? `<div><strong>Processed:</strong> ${processedDate}</div>` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">Failed to load document preview</p>';
        }
    })
    .catch(error => {
        content.innerHTML = `<p style="text-align: center; color: var(--danger); padding: 40px;">Error loading preview: ${error.message}</p>`;
        console.error('Preview error:', error);
    });
}

function closePreviewModal() {
    document.getElementById('preview-modal').style.display = 'none';
}

function startChatWithDocument(documentId, documentName) {
    // Suppress Chrome extension errors in this function
    try {
        if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.lastError) {
            // Silently ignore
            void chrome.runtime.lastError;
        }
    } catch(e) {
        // Ignore
    }
    
    // Create a new chat session linked to this document
    axios.post('/api/chat-session.php', {
        action: 'create',
        title: documentName,
        document_id: documentId
    })
    .then(response => {
        if (response.data && response.data.success) {
            // Redirect to chat with the new session
            window.location.href = `/index.php?session=${response.data.session_id}`;
        } else {
            showNotification('Failed to create chat session: ' + (response.data?.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        const errorMsg = error.response?.data?.error || error.message || 'Unknown error';
        showNotification('Error creating chat: ' + errorMsg, 'error');
        console.error('Chat creation error:', error);
    });
}

function deleteDocument(id) {
    if (!confirm('Are you sure you want to delete this document?')) return;
    
    axios.post('/api/rag.php', {
        action: 'delete',
        document_id: id
    })
    .then(response => {
        if (response.data.success) {
            showNotification('Document deleted', 'success');
            // Remove from arrays
            allDocuments = allDocuments.filter(doc => doc.id !== id);
            buildIndex(allDocuments);
            applyFilters();
        }
    })
    .catch(error => {
        showNotification('Delete failed: ' + error.message, 'error');
    });
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

function openUploadDialog() {
    document.getElementById('file-input').click();
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

// Icon Helper for Iconify Icons
// Provides JavaScript functions to render icons dynamically

// Prevent redeclaration if script is loaded multiple times
if (typeof IconHelper === 'undefined') {
    window.IconHelper = {
    // Bot/AI Icons
    getBotIcon: (type = 'default') => {
        const icons = {
            'default': 'hugeicons:chat-bot',
            'assistant': 'hugeicons:chat-bot',
            'chatbot': 'hugeicons:chat-bot',
            'ai': 'hugeicons:chat-bot',
            'brain': 'mdi:brain',
            'rag': 'mdi:database-search',
        };
        return icons[type] || icons['default'];
    },
    
    // File Type Icons
    getFileIcon: (extension) => {
        const ext = extension.toLowerCase();
        const icons = {
            'pdf': 'mdi:file-pdf-box',
            'doc': 'mdi:file-word',
            'docx': 'mdi:file-word',
            'txt': 'mdi:file-document',
            'md': 'mdi:language-markdown',
            'xls': 'mdi:file-excel',
            'xlsx': 'mdi:file-excel',
            'csv': 'mdi:file-delimited',
            'ppt': 'mdi:file-powerpoint',
            'pptx': 'mdi:file-powerpoint',
            'jpg': 'mdi:file-image',
            'jpeg': 'mdi:file-image',
            'png': 'mdi:file-image',
            'gif': 'mdi:file-image',
            'svg': 'mdi:file-image',
            'zip': 'mdi:folder-zip',
            'js': 'mdi:language-javascript',
            'php': 'mdi:language-php',
            'html': 'mdi:language-html5',
            'css': 'mdi:language-css3',
            'json': 'mdi:code-json',
            'default': 'mdi:file',
        };
        return icons[ext] || icons['default'];
    },
    
    // Action Icons
    getActionIcon: (action) => {
        const icons = {
            'send': 'mdi:send',
            'attach': 'mdi:paperclip',
            'upload': 'mdi:cloud-upload',
            'download': 'mdi:cloud-download',
            'delete': 'mdi:delete',
            'edit': 'mdi:pencil',
            'save': 'mdi:content-save',
            'cancel': 'mdi:close',
            'close': 'mdi:close',
            'settings': 'mdi:cog',
            'sync': 'mdi:sync',
            'refresh': 'mdi:refresh',
            'search': 'mdi:magnify',
            'add': 'mdi:plus',
            'remove': 'mdi:minus',
            'check': 'mdi:check',
            'info': 'mdi:information',
            'warning': 'mdi:alert',
            'error': 'mdi:alert-circle',
            'success': 'mdi:check-circle',
        };
        return icons[action] || 'mdi:help-circle';
    },
    
    // Status Icons
    getStatusIcon: (status) => {
        const icons = {
            'online': 'mdi:circle',
            'offline': 'mdi:circle-outline',
            'processing': 'mdi:loading',
            'pending': 'mdi:clock-outline',
            'completed': 'mdi:check-circle',
            'error': 'mdi:alert-circle',
            'success': 'mdi:check-circle',
            'warning': 'mdi:alert',
        };
        return icons[status] || 'mdi:help-circle';
    },
    
    // Render icon HTML
    icon: (iconName, className = '', style = '') => {
        const classAttr = className ? ` class="${className}"` : '';
        const styleAttr = style ? ` style="${style}"` : '';
        return `<iconify-icon icon="${iconName}"${classAttr}${styleAttr}></iconify-icon>`;
    },
    
    // Get file extension from filename
    getFileExtension: (filename) => {
        return filename.split('.').pop().toLowerCase();
    },
    
    // Get user icon
    getUserIcon: () => 'mdi:account-circle',
    
    // Get RAG icon
    getRAGIcon: () => 'mdi:brain',
};
} else {
    console.warn('IconHelper already defined, skipping redefinition');
}


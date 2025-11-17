<?php
/**
 * Icon Helper - Iconify Icon Configuration
 * Provides icon mappings for bots, file types, actions, etc.
 */

class IconHelper {
    
    /**
     * Icon sets and mappings
     */
    private static $iconSets = [
        // Material Design Icons (mdi)
        'mdi' => 'material-symbols',
        // Heroicons (heroicons)
        'hero' => 'heroicons',
        // Carbon Icons (carbon)
        'carbon' => 'carbon',
        // Tabler Icons (tabler)
        'tabler' => 'tabler',
        // Lucide Icons (lucide)
        'lucide' => 'lucide',
    ];
    
    /**
     * Bot/AI Icons
     */
    public static function getBotIcon($type = 'default') {
        $icons = [
            'default' => 'mdi:robot',
            'assistant' => 'mdi:robot-happy',
            'chatbot' => 'mdi:chatbot',
            'ai' => 'mdi:artificial-intelligence',
            'brain' => 'mdi:brain',
            'rag' => 'mdi:database-search',
        ];
        return $icons[$type] ?? $icons['default'];
    }
    
    /**
     * File Type Icons
     */
    public static function getFileIcon($extension) {
        $ext = strtolower($extension);
        $icons = [
            // Documents
            'pdf' => 'mdi:file-pdf-box',
            'doc' => 'mdi:file-word',
            'docx' => 'mdi:file-word',
            'txt' => 'mdi:file-document',
            'md' => 'mdi:language-markdown',
            'rtf' => 'mdi:file-document',
            
            // Spreadsheets
            'xls' => 'mdi:file-excel',
            'xlsx' => 'mdi:file-excel',
            'csv' => 'mdi:file-delimited',
            
            // Presentations
            'ppt' => 'mdi:file-powerpoint',
            'pptx' => 'mdi:file-powerpoint',
            
            // Images
            'jpg' => 'mdi:file-image',
            'jpeg' => 'mdi:file-image',
            'png' => 'mdi:file-image',
            'gif' => 'mdi:file-image',
            'svg' => 'mdi:file-image',
            'webp' => 'mdi:file-image',
            
            // Archives
            'zip' => 'mdi:folder-zip',
            'rar' => 'mdi:folder-zip',
            '7z' => 'mdi:folder-zip',
            
            // Code
            'js' => 'mdi:language-javascript',
            'php' => 'mdi:language-php',
            'py' => 'mdi:language-python',
            'html' => 'mdi:language-html5',
            'css' => 'mdi:language-css3',
            'json' => 'mdi:code-json',
            
            // Default
            'default' => 'mdi:file',
        ];
        return $icons[$ext] ?? $icons['default'];
    }
    
    /**
     * Action Icons
     */
    public static function getActionIcon($action) {
        $icons = [
            'send' => 'mdi:send',
            'attach' => 'mdi:paperclip',
            'upload' => 'mdi:cloud-upload',
            'download' => 'mdi:cloud-download',
            'delete' => 'mdi:delete',
            'edit' => 'mdi:pencil',
            'save' => 'mdi:content-save',
            'cancel' => 'mdi:close',
            'close' => 'mdi:close',
            'settings' => 'mdi:cog',
            'sync' => 'mdi:sync',
            'refresh' => 'mdi:refresh',
            'search' => 'mdi:magnify',
            'filter' => 'mdi:filter',
            'sort' => 'mdi:sort',
            'add' => 'mdi:plus',
            'remove' => 'mdi:minus',
            'check' => 'mdi:check',
            'check-circle' => 'mdi:check-circle',
            'info' => 'mdi:information',
            'warning' => 'mdi:alert',
            'error' => 'mdi:alert-circle',
            'success' => 'mdi:check-circle',
        ];
        return $icons[$action] ?? 'mdi:help-circle';
    }
    
    /**
     * Navigation Icons
     */
    public static function getNavIcon($page) {
        $icons = [
            'chat' => 'mdi:message-text',
            'messages' => 'mdi:message-text',
            'documents' => 'mdi:file-document-multiple',
            'files' => 'mdi:file-multiple',
            'settings' => 'mdi:cog',
            'help' => 'mdi:help-circle',
            'docs' => 'mdi:book-open-variant',
            'documentation' => 'mdi:book-open-variant',
            'profile' => 'mdi:account',
            'home' => 'mdi:home',
            'dashboard' => 'mdi:view-dashboard',
        ];
        return $icons[$page] ?? 'mdi:circle';
    }
    
    /**
     * Status Icons
     */
    public static function getStatusIcon($status) {
        $icons = [
            'online' => 'mdi:circle',
            'offline' => 'mdi:circle-outline',
            'processing' => 'mdi:loading',
            'pending' => 'mdi:clock-outline',
            'completed' => 'mdi:check-circle',
            'error' => 'mdi:alert-circle',
            'success' => 'mdi:check-circle',
            'warning' => 'mdi:alert',
        ];
        return $icons[$status] ?? 'mdi:help-circle';
    }
    
    /**
     * Discussion/Chat Type Icons
     */
    public static function getDiscussionIcon($type) {
        $icons = [
            'chat' => 'mdi:message-text',
            'group' => 'mdi:account-group',
            'private' => 'mdi:lock',
            'public' => 'mdi:earth',
            'thread' => 'mdi:forum',
            'conversation' => 'mdi:chat',
            'rag' => 'mdi:database-search',
            'document' => 'mdi:file-document',
        ];
        return $icons[$type] ?? 'mdi:message';
    }
    
    /**
     * Render iconify icon HTML
     */
    public static function icon($iconName, $class = '', $style = '') {
        $classAttr = $class ? " class=\"$class\"" : '';
        $styleAttr = $style ? " style=\"$style\"" : '';
        return "<iconify-icon icon=\"$iconName\"$classAttr$styleAttr></iconify-icon>";
    }
    
    /**
     * Get icon for user avatar
     */
    public static function getUserIcon() {
        return 'mdi:account-circle';
    }
    
    /**
     * Get icon for RAG toggle
     */
    public static function getRAGIcon() {
        return 'mdi:brain';
    }
    
    /**
     * Get icon for model selection
     */
    public static function getModelIcon() {
        return 'mdi:robot';
    }
}


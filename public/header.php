<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Ollama - RAG Powered</title>
    <link rel="icon" type="image/png" href="/public/assets/img/bot-avatar.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/libs/font-awesome/css/all.css">
    <link rel="stylesheet" href="/public/assets/css/modern.css">
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="modern-sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">Chat with Ollama</div>
            <p style="color: var(--text-secondary); font-size: 14px;">RAG-Powered AI Assistant</p>
        </div>
        
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="/public/index.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/public/documents.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Documents</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/public/settings.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="chat-sessions">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-weight: 600; font-size: 14px; color: var(--text-secondary);">Recent Chats</span>
                <button class="btn-icon" onclick="createNewChat()" style="width: 32px; height: 32px; font-size: 14px;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div id="chat-sessions-list">
                <!-- Chat sessions will be loaded here -->
            </div>
        </div>
    </div>
    
    <div class="modern-header">
        <div>
            <select id="model-select" class="model-select">
                <option value="">Loading models...</option>
            </select>
        </div>
        <div class="header-actions">
            <button class="btn-icon" onclick="openDocumentsModal()" title="Manage Documents">
                <i class="fas fa-folder-open"></i>
            </button>
            <button class="btn-icon" onclick="syncModels()" title="Sync Models">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

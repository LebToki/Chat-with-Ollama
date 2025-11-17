<?php
// Security headers
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com https://api.iconify.design https://iconify.design; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://api.iconify.design https://iconify.design;");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/path_helper.php';
require_once __DIR__ . '/icon_helper.php';
$assetsPath = getAssetsPath();

// Debug mode - uncomment to enable console logging
$debugAssets = isset($_GET['debug']) && $_GET['debug'] === 'assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Ollama - RAG Powered</title>
    <?php if ($debugAssets): ?>
    <script>
        console.log('=== ASSET DEBUG MODE ===');
        console.log('Assets Path:', '<?php echo $assetsPath; ?>');
        console.log('Document Root:', '<?php echo $_SERVER['DOCUMENT_ROOT']; ?>');
        console.log('Current Dir:', '<?php echo __DIR__; ?>');
    </script>
    <?php endif; ?>
    <link rel="icon" type="image/png" href="<?php echo $assetsPath; ?>/img/bot-avatar.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $assetsPath; ?>/libs/bootstrap/css/bootstrap.min.css" onerror="console.error('Failed to load Bootstrap CSS:', this.href)">
    <link rel="stylesheet" href="<?php echo $assetsPath; ?>/css/modern.css" onerror="console.error('Failed to load Modern CSS:', this.href)">
    <!-- Iconify Icons - 200+ icon sets, 275k+ icons -->
    <!-- Multiple CDN fallbacks for reliability -->
    <script>
        // Enhanced Iconify loading with multiple CDN fallbacks
        (function() {
            let iconifyLoaded = false;
            let currentCDNIndex = 0;
            
            // List of CDN sources to try
            const iconifyCDNs = [
                'https://cdn.jsdelivr.net/npm/@iconify/iconify@latest/dist/iconify-icon.min.js',
                'https://unpkg.com/@iconify/iconify@latest/dist/iconify-icon.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/iconify/3.1.1/iconify-icon.min.js'
            ];
            
            // Check if Iconify loaded successfully
            function checkIconifyLoaded() {
                if (typeof customElements !== 'undefined') {
                    try {
                        const test = customElements.get('iconify-icon');
                        if (test) {
                            iconifyLoaded = true;
                            console.log('✅ Iconify loaded successfully');
                            return true;
                        }
                    } catch(e) {
                        // Element not registered
                    }
                }
                return false;
            }
            
            // Try loading from next CDN
            function tryNextCDN() {
                if (iconifyLoaded || currentCDNIndex >= iconifyCDNs.length) {
                    // All CDNs failed, use custom fallback
                    if (!iconifyLoaded) {
                        createCustomFallback();
                    }
                    return;
                }
                
                const script = document.createElement('script');
                script.src = iconifyCDNs[currentCDNIndex];
                script.defer = true;
                
                // Add timeout to detect connection timeouts
                const timeout = setTimeout(function() {
                    console.warn('Iconify CDN ' + (currentCDNIndex + 1) + ' timed out, trying next...');
                    script.onerror(); // Trigger error handler
                }, 5000); // 5 second timeout
                
                script.onload = function() {
                    clearTimeout(timeout);
                    // Check if it actually loaded
                    setTimeout(function() {
                        if (checkIconifyLoaded()) {
                            console.log('✅ Iconify loaded from CDN ' + (currentCDNIndex + 1));
                        } else {
                            // CDN loaded but element not registered, try next
                            currentCDNIndex++;
                            tryNextCDN();
                        }
                    }, 100);
                };
                script.onerror = function() {
                    clearTimeout(timeout);
                    console.warn('Iconify CDN ' + (currentCDNIndex + 1) + ' failed, trying next...');
                    currentCDNIndex++;
                    tryNextCDN();
                };
                document.head.appendChild(script);
            }
            
            // Create custom fallback element
            function createCustomFallback() {
                if (iconifyLoaded) return;
                
                console.warn('All Iconify CDNs failed, using custom fallback...');
                if (typeof customElements !== 'undefined' && !customElements.get('iconify-icon')) {
                    class IconifyIconFallback extends HTMLElement {
                        connectedCallback() {
                            if (!this.hasAttribute('data-processed')) {
                                this.setAttribute('data-processed', 'true');
                                const icon = this.getAttribute('icon') || '';
                                const style = this.getAttribute('style') || '';
                                const width = this.style.width || this.getAttribute('width') || '1em';
                                const height = this.style.height || this.getAttribute('height') || '1em';
                                
                                if (icon) {
                                    // Try multiple API endpoints
                                    const apiEndpoints = [
                                        `https://api.iconify.design/${icon}.svg`,
                                        `https://iconify.design/api/icon/${icon}.svg`
                                    ];
                                    
                                    let endpointIndex = 0;
                                    const tryFetch = () => {
                                        if (endpointIndex >= apiEndpoints.length) {
                                            // All endpoints failed, hide element
                                            this.style.display = 'none';
                                            return;
                                        }
                                        
                                        fetch(apiEndpoints[endpointIndex])
                                            .then(response => {
                                                if (!response.ok) throw new Error('Failed to fetch icon');
                                                return response.text();
                                            })
                                            .then(svg => {
                                                const parser = new DOMParser();
                                                const svgDoc = parser.parseFromString(svg, 'image/svg+xml');
                                                const svgElement = svgDoc.documentElement;
                                                
                                                // Apply styles and dimensions
                                                if (style) svgElement.setAttribute('style', style);
                                                if (width !== '1em') svgElement.setAttribute('width', width);
                                                if (height !== '1em') svgElement.setAttribute('height', height);
                                                
                                                this.innerHTML = '';
                                                this.appendChild(svgElement);
                                            })
                                            .catch(() => {
                                                endpointIndex++;
                                                tryFetch();
                                            });
                                    };
                                    
                                    tryFetch();
                                }
                            }
                        }
                    }
                    customElements.define('iconify-icon', IconifyIconFallback);
                    console.log('✅ Iconify custom fallback element created');
                }
            }
            
            // Suppress Chrome extension errors
            if (typeof chrome !== 'undefined' && chrome.runtime) {
                try {
                    chrome.runtime.onMessage.addListener(function() {});
                } catch(e) {
                    // Ignore extension errors
                }
            }
            
            // Start loading from first CDN
            tryNextCDN();
            
            // Also check when DOM is ready as backup
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        if (!checkIconifyLoaded() && currentCDNIndex >= iconifyCDNs.length) {
                            createCustomFallback();
                        }
                    }, 2000);
                });
            } else {
                setTimeout(function() {
                    if (!checkIconifyLoaded() && currentCDNIndex >= iconifyCDNs.length) {
                        createCustomFallback();
                    }
                }, 2000);
            }
        })();
    </script>
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
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <a href="https://2tinteractive.com" target="_blank" style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; font-size: 12px; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <img src="/2tinteractive-logo.png.webp" alt="2TInteractive" class="header-logo" style="padding: 5px !important; width: 200px !important; height: auto !important;">
                </a>
                <div style="margin-top: 4px; font-size: 11px; color: var(--text-secondary); opacity: 0.7;">
                    by Tarek Tarabichi
                </div>
            </div>
        </div>
        
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="/index.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <?php echo IconHelper::icon(IconHelper::getNavIcon('chat')); ?>
                    <span>Chat</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/documents.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : ''; ?>">
                    <?php echo IconHelper::icon(IconHelper::getNavIcon('documents')); ?>
                    <span>Documents</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/settings.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <?php echo IconHelper::icon(IconHelper::getNavIcon('settings')); ?>
                    <span>Settings</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="/help.php" class="sidebar-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'help.php' ? 'active' : ''; ?>">
                    <?php echo IconHelper::icon(IconHelper::getNavIcon('help')); ?>
                    <span>Help & Docs</span>
                </a>
            </li>
        </ul>
        
        <div class="chat-sessions">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-weight: 600; font-size: 14px; color: var(--text-secondary);">Recent Chats</span>
                <button class="btn-icon" onclick="createNewChat()" style="width: 32px; height: 32px; font-size: 14px;">
                    <?php echo IconHelper::icon(IconHelper::getActionIcon('add')); ?>
                </button>
            </div>
            <div id="chat-sessions-list">
                <!-- Chat sessions will be loaded here -->
            </div>
        </div>
        
        <!-- GitHub Community Section -->
        <div class="github-community" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--glass-border);">
            <div style="font-weight: 600; font-size: 14px; color: var(--text-secondary); margin-bottom: 16px;">
                <i class="fab fa-github"></i> Community
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="https://github.com/yourusername/chat-with-ollama" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-star"></i>
                    <span style="font-size: 13px;">Star on GitHub</span>
                </a>
                <a href="https://github.com/yourusername/chat-with-ollama/fork" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-code-branch"></i>
                    <span style="font-size: 13px;">Fork & Contribute</span>
                </a>
                <a href="https://github.com/yourusername/chat-with-ollama/issues" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-bug"></i>
                    <span style="font-size: 13px;">Report Issues</span>
                </a>
                <a href="https://github.com/yourusername/chat-with-ollama/discussions" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-comments"></i>
                    <span style="font-size: 13px;">Discussions</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="modern-header">
        <div style="display: flex; gap: 12px; align-items: center;">
            <select id="provider-select" class="model-select" style="min-width: 140px;">
                <option value="ollama">Ollama</option>
            </select>
            <select id="model-select" class="model-select">
                <option value="">Loading models...</option>
            </select>
        </div>
        <div class="header-actions">
            <button class="btn-icon" onclick="openDocumentsModal()" title="Manage Documents">
                <?php echo IconHelper::icon('mdi:folder-open'); ?>
            </button>
            <button class="btn-icon" onclick="toggleStreaming()" id="streaming-toggle" title="Toggle Streaming" data-streaming-enabled="true">
                <i class="fas fa-stream"></i>
            </button>
            <button class="btn-icon" onclick="syncModels()" title="Sync Models">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('sync')); ?>
            </button>
            <a href="https://github.com/yourusername/chat-with-ollama" target="_blank" class="btn-icon" title="View on GitHub" style="text-decoration: none; color: inherit;">
                <i class="fab fa-github"></i>
            </a>
        </div>
    </div>

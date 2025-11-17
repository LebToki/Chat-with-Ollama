<?php
// Security headers
// CSP includes all Iconify CDN fallbacks: cdn.iconify.design, cdn.jsdelivr.net, unpkg.com, cdnjs.cloudflare.com
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdn.iconify.design https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com https://api.iconify.design https://iconify.design; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://api.iconify.design https://iconify.design https://cdn.iconify.design https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com;");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/path_helper.php';
require_once __DIR__ . '/icon_helper.php';
$config = require dirname(__DIR__) . '/src/config.php';
$assetsPath = getAssetsPath();

// Branding variables
$developerName = $config['developerName'] ?? '{{DEVELOPER_NAME}}';
$companyName = $config['companyName'] ?? '{{COMPANY_NAME}}';
$companyUrl = $config['companyUrl'] ?? '{{COMPANY_URL}}';
$githubUsername = $config['githubUsername'] ?? '{{GITHUB_USERNAME}}';
$githubRepo = $config['githubRepo'] ?? 'chat-with-ollama';

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
    <!-- Official CDN with custom fallback for reliability -->
    <script>
        // Enhanced Iconify loading with multiple CDN fallbacks and custom fallback
        (function() {
            let iconifyLoaded = false;
            
            // Multiple CDN fallbacks for reliability
            const iconifyCDNs = [
                'https://cdn.iconify.design/3.1.1/iconify.min.js',
                'https://cdn.jsdelivr.net/npm/@iconify/iconify@3.1.1/dist/iconify.min.js',
                'https://unpkg.com/@iconify/iconify@3.1.1/dist/iconify.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/iconify/3.1.1/iconify.min.js'
            ];
            
            let currentCDNIndex = 0;
            
            // Suppress network errors in console
            const originalError = console.error;
            console.error = function(...args) {
                // Suppress Iconify CDN errors
                if (args[0] && typeof args[0] === 'string' && args[0].includes('iconify')) {
                    return;
                }
                originalError.apply(console, args);
            };
            
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
            
            // Try loading from CDN with fallbacks
            function loadIconify() {
                if (iconifyLoaded) return;
                
                if (currentCDNIndex >= iconifyCDNs.length) {
                    // All CDNs failed, use custom fallback
                    console.warn('All Iconify CDNs failed, using custom fallback...');
                    createCustomFallback();
                    return;
                }
                
                const script = document.createElement('script');
                script.src = iconifyCDNs[currentCDNIndex];
                script.defer = true;
                
                // Add timeout to detect connection timeouts (reduced to 3 seconds)
                const timeout = setTimeout(function() {
                    script.onerror(); // Trigger error handler
                }, 3000);
                
                script.onload = function() {
                    clearTimeout(timeout);
                    // Check if it actually loaded
                    setTimeout(function() {
                        if (checkIconifyLoaded()) {
                            console.log('✅ Iconify loaded from CDN');
                        } else {
                            // CDN loaded but element not registered, try next CDN
                            currentCDNIndex++;
                            loadIconify();
                        }
                    }, 100);
                };
                
                script.onerror = function() {
                    clearTimeout(timeout);
                    // Try next CDN
                    currentCDNIndex++;
                    loadIconify();
                };
                
                // Suppress error logging for this script
                script.addEventListener('error', function(e) {
                    e.stopPropagation();
                }, true);
                
                document.head.appendChild(script);
            }
            
            // Create custom fallback element
            function createCustomFallback() {
                if (iconifyLoaded) return;
                
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
            
            // Start loading from official CDN
            loadIconify();
            
            // Also check when DOM is ready as backup
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        if (!checkIconifyLoaded()) {
                            createCustomFallback();
                        }
                    }, 2000);
                });
            } else {
                setTimeout(function() {
                    if (!checkIconifyLoaded()) {
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
                <a href="<?php echo htmlspecialchars($companyUrl); ?>" target="_blank" style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; font-size: 12px; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <img src="/2tinteractive-logo.png.webp" alt="<?php echo htmlspecialchars($companyName); ?>" class="header-logo" style="padding: 5px !important; width: 200px !important; height: auto !important;">
                </a>
                <div style="margin-top: 4px; font-size: 11px; color: var(--text-secondary); opacity: 0.7;">
                    by <?php echo htmlspecialchars($developerName); ?>
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
        <div class="github-community">
            <div style="font-weight: 600; font-size: 14px; color: var(--text-secondary); margin-bottom: 16px;">
                <i class="fab fa-github"></i> Community
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="https://github.com/<?php echo htmlspecialchars($githubUsername); ?>/<?php echo htmlspecialchars($githubRepo); ?>" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-star"></i>
                    <span style="font-size: 13px;">Star on GitHub</span>
                </a>
                <a href="https://github.com/<?php echo htmlspecialchars($githubUsername); ?>/<?php echo htmlspecialchars($githubRepo); ?>/fork" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-code-branch"></i>
                    <span style="font-size: 13px;">Fork & Contribute</span>
                </a>
                <a href="https://github.com/<?php echo htmlspecialchars($githubUsername); ?>/<?php echo htmlspecialchars($githubRepo); ?>/issues" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-bug"></i>
                    <span style="font-size: 13px;">Report Issues</span>
                </a>
                <a href="https://github.com/<?php echo htmlspecialchars($githubUsername); ?>/<?php echo htmlspecialchars($githubRepo); ?>/discussions" target="_blank" class="github-link" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: var(--text-primary); background: var(--glass-bg); transition: all 0.2s;">
                    <i class="fas fa-comments"></i>
                    <span style="font-size: 13px;">Discussions</span>
                </a>
            </div>
        </div>
    </div>
    
    <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
    $isChatPage = $currentPage === 'index.php';
    $isDocumentsPage = $currentPage === 'documents.php';
    $isSettingsPage = $currentPage === 'settings.php';
    $isHelpPage = $currentPage === 'help.php';
    ?>
    <div class="modern-header">
        <div style="display: flex; gap: 12px; align-items: center; padding-left: 24px; flex: 1; min-width: 0;">
            <?php if ($isChatPage): ?>
                <!-- Model selector only on chat page -->
                <select id="provider-select" class="model-select" style="min-width: 140px;">
                    <option value="ollama">Ollama</option>
                </select>
                <select id="model-select" class="model-select">
                    <option value="">Loading models...</option>
                </select>
            <?php else: ?>
                <!-- Page title on other pages -->
                <h1 class="text-gradient" style="margin: 0; font-size: 20px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php
                    if ($isDocumentsPage) echo 'Document Management';
                    elseif ($isSettingsPage) echo 'Settings';
                    elseif ($isHelpPage) echo 'Help & Documentation';
                    else echo 'Chat with Ollama';
                    ?>
                </h1>
            <?php endif; ?>
        </div>
        <div class="header-actions" style="flex-shrink: 0;">
            <?php if ($isChatPage): ?>
                <button class="btn-icon" onclick="openDocumentsModal()" title="Manage Documents">
                    <?php echo IconHelper::icon('mdi:folder-open'); ?>
                </button>
                <button class="btn-icon" onclick="toggleStreaming()" id="streaming-toggle" title="Toggle Streaming" data-streaming-enabled="true">
                    <?php echo IconHelper::icon('hugeicons:live-streaming-02'); ?>
                </button>
            <?php endif; ?>
            <button class="btn-icon" onclick="syncModels()" title="Sync Local Models">
                <?php echo IconHelper::icon(IconHelper::getActionIcon('sync')); ?>
            </button>
            <a href="https://github.com/<?php echo htmlspecialchars($githubUsername); ?>/<?php echo htmlspecialchars($githubRepo); ?>" target="_blank" class="btn-icon" title="View on GitHub" style="text-decoration: none; color: inherit;">
                <?php echo IconHelper::icon('mdi:github'); ?>
            </a>
        </div>
    </div>

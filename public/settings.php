<?php 
require_once __DIR__ . '/icon_helper.php';
require __DIR__ . '/header.php'; 
?>

<div class="main-content">
    <div class="container-fluid" style="padding: 24px 32px;">
        <!-- Header Section -->
        <div style="margin-bottom: 32px;">
            <h1 class="text-gradient" style="margin: 0;">Settings</h1>
        </div>
        
        <!-- Two Column Layout -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
            <!-- Left Column: Model Configuration -->
            <div class="glass-card">
                <h2 style="margin-bottom: 24px; font-size: 20px;">Model Configuration</h2>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">
                        Default Model
                    </label>
                    <div style="display: flex; gap: 12px;">
                        <select id="settings-model-select" class="model-select" style="flex: 1;">
                            <option value="">Loading models...</option>
                        </select>
                        <button class="btn-icon" onclick="syncModels()" title="Sync Models">
                            <?php echo IconHelper::icon(IconHelper::getActionIcon('sync')); ?>
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
                    <?php echo IconHelper::icon(IconHelper::getActionIcon('save')); ?> Save Settings
                </button>
            </div>
            
            <!-- Middle Column: Date & Time Settings -->
            <div class="glass-card">
                <h2 style="margin-bottom: 24px; font-size: 20px;">Date & Time Format</h2>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">
                        Timezone
                    </label>
                    <select id="timezone-select" class="model-select" style="width: 100%;">
                        <option value="">Loading timezones...</option>
                    </select>
                    <p style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                        Current: <span id="current-timezone-display"></span>
                    </p>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">
                        Date Format
                    </label>
                    <select id="date-format-select" class="model-select" style="width: 100%;">
                        <option value="short">Short (1/17/2024)</option>
                        <option value="medium">Medium (Jan 17, 2024)</option>
                        <option value="long">Long (January 17, 2024)</option>
                        <option value="full">Full (Thursday, January 17, 2024)</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-secondary);">
                        Time Format
                    </label>
                    <select id="time-format-select" class="model-select" style="width: 100%;">
                        <option value="short">Short (3:45 PM)</option>
                        <option value="medium">Medium (3:45:30 PM)</option>
                    </select>
                </div>
                
                <button class="btn-modern" onclick="saveDateTimeSettings()">
                    <?php echo IconHelper::icon(IconHelper::getActionIcon('save')); ?> Save Date/Time Settings
                </button>
            </div>
            
            <!-- Right Column: About & Info -->
            <div class="glass-card">
                <h2 style="margin-bottom: 24px; font-size: 20px;">About</h2>
                
                <div style="margin-bottom: 24px; padding: 16px; background: var(--glass-bg); border-radius: 12px; border: 1px solid var(--glass-border);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <img src="/2tinteractive-logo.png.webp" alt="2TInteractive" class="settings-logo" style="padding: 5px !important; height: 32px !important; width: auto !important;">
                        <div>
                            <p style="margin: 0; font-size: 12px; color: var(--text-secondary);">Professional Development Services</p>
                        </div>
                    </div>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.6;">
                        Custom web development, AI integrations, and premium solutions for your business needs.
                    </p>
                    <div style="display: flex; gap: 12px;">
                        <a href="https://2tinteractive.com" target="_blank" class="btn-modern" style="flex: 1; text-align: center; text-decoration: none;">
                            Visit Website
                        </a>
                    </div>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--glass-border); font-size: 12px; color: var(--text-secondary);">
                        Developed by <strong style="color: var(--accent);">Tarek Tarabichi</strong>
                    </div>
                </div>
                
                <h2 style="margin-bottom: 24px; font-size: 20px;">Application Info</h2>
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
</div>

<script>
// Prevent multiple calls
let modelListLoading = false;
let modelListLoaded = false;

document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    loadDateTimeSettings();
    getTimeZones();
    
    // Load saved RAG setting
    const ragEnabled = localStorage.getItem('ragEnabled') !== 'false';
    document.getElementById('rag-enabled').checked = ragEnabled;
    
    // Wait a bit for axios to load, then update model list (only once)
    setTimeout(function() {
        if (!modelListLoading && !modelListLoaded) {
            modelListLoading = true;
            if (typeof axios === 'undefined') {
                console.error('Axios not loaded! Trying fetch API fallback...');
                updateModelListWithFetch();
            } else {
                updateModelList();
            }
        }
    }, 100);
});

// Fallback function using fetch API
function updateModelListWithFetch() {
    // Prevent duplicate calls
    if (modelListLoading) {
        console.log('Model list already loading, skipping...');
        return;
    }
    
    modelListLoading = true;
    console.log('Using fetch API fallback...');
    const apiUrl = '/api/models.php';
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(models => {
            populateModelSelect(models);
        })
        .catch(error => {
            console.error('Fetch API error:', error);
            handleModelLoadError(error);
            modelListLoading = false;
        });
}

// Main function using axios
function updateModelList() {
    // Prevent duplicate calls
    if (modelListLoading) {
        console.log('Model list already loading, skipping...');
        return;
    }
    
    if (modelListLoaded) {
        console.log('Model list already loaded, skipping...');
        return;
    }
    
    modelListLoading = true;
    console.log('Loading models from /api/models.php...');
    console.log('Axios available:', typeof axios !== 'undefined');
    console.log('Current URL:', window.location.href);
    
    // Try the API endpoint
    const apiUrl = '/api/models.php';
    console.log('Fetching from:', apiUrl);
    
    if (typeof axios === 'undefined') {
        console.warn('Axios not available, using fetch fallback');
        updateModelListWithFetch();
        return;
    }
    
    axios.get(apiUrl)
        .then(response => {
            console.log('Models API response:', response);
            console.log('Response data:', response.data);
            console.log('Response data type:', typeof response.data);
            console.log('Response data is array:', Array.isArray(response.data));
            
            // Handle error response
            if (response.data && response.data.error) {
                console.error('API returned error:', response.data.error);
                handleModelLoadError({ message: response.data.error });
                return;
            }
            
            // Ensure we have valid data
            if (!response.data) {
                console.error('No data in response');
                handleModelLoadError({ message: 'No data received from API' });
                return;
            }
            
            populateModelSelect(response.data);
        })
        .catch(error => {
            console.error('Failed to load models:', error);
            console.error('Error details:', {
                message: error.message,
                response: error.response,
                status: error.response?.status,
                data: error.response?.data
            });
            handleModelLoadError(error);
        });
}

// Helper function to populate the model select
function populateModelSelect(models) {
    const select = document.getElementById('settings-model-select');
    if (!select) {
        console.error('Model select element not found');
        modelListLoading = false;
        return;
    }
    
    console.log('populateModelSelect called with:', models);
    console.log('Type:', typeof models, 'Is Array:', Array.isArray(models));
    
    // Handle different response formats
    let modelsArray = models;
    if (models && models.data && Array.isArray(models.data)) {
        modelsArray = models.data;
    } else if (models && models.models && Array.isArray(models.models)) {
        modelsArray = models.models;
    } else if (!Array.isArray(models)) {
        console.error('Models is not an array:', models);
        select.innerHTML = '<option value="">Invalid models format</option>';
        modelListLoading = false;
        modelListLoaded = true;
        return;
    }
    
    // Check if models is an array
    if (Array.isArray(modelsArray) && modelsArray.length > 0) {
        console.log(`Found ${modelsArray.length} models:`, modelsArray);
        
        // Clear existing options
        select.innerHTML = '';
        
        // Populate with models
        modelsArray.forEach(model => {
            const option = document.createElement('option');
            const modelName = model.name || model;
            option.value = modelName;
            option.textContent = modelName;
            select.appendChild(option);
            console.log('Added model option:', modelName);
        });
        
        // Set selected value
        const savedModel = localStorage.getItem('defaultModel');
        if (savedModel && Array.from(select.options).some(opt => opt.value === savedModel)) {
            select.value = savedModel;
            console.log('Set saved model:', savedModel);
        } else if (modelsArray.length > 0) {
            const firstModel = modelsArray[0].name || modelsArray[0];
            select.value = firstModel;
            console.log('Set first model:', firstModel);
        }
        
        console.log('Model select populated successfully. Selected:', select.value);
        console.log('Total options:', select.options.length);
        modelListLoaded = true;
    } else {
        console.warn('No models found or invalid format:', models);
        select.innerHTML = '<option value="">No models available</option>';
        showNotification('No models found. Click sync to fetch models from Ollama.', 'info');
        modelListLoaded = true;
    }
    
    modelListLoading = false;
}

// Helper function to handle errors
function handleModelLoadError(error) {
    const select = document.getElementById('settings-model-select');
    if (select) {
        const errorMsg = error.response?.data?.error || error.message || 'Unknown error';
        select.innerHTML = `<option value="">Failed to load: ${errorMsg}</option>`;
    }
    showNotification('Failed to load models. Check console for details.', 'error');
}

function syncModels() {
    showNotification('Syncing models...', 'info');
    axios.get('/fetch_models.php')
        .then(response => {
            console.log('Sync response:', response);
            console.log('Sync response data:', response.data);
            
            if (response.data && response.data.success) {
                const count = response.data.count || 0;
                showNotification(`Synced ${count} models`, 'success');
                // Reset flags and reload after a short delay to ensure file is written
                modelListLoaded = false;
                modelListLoading = false;
                setTimeout(() => {
                    updateModelList();
                }, 500);
            } else {
                const errorMsg = response.data?.error || 'Sync failed';
                console.error('Sync failed:', errorMsg);
                showNotification('Sync failed: ' + errorMsg, 'error');
            }
        })
        .catch(error => {
            console.error('Sync error:', error);
            const errorMsg = error.response?.data?.error || error.message || 'Unknown error';
            showNotification('Sync error: ' + errorMsg, 'error');
        });
}

function loadSettings() {
    const savedModel = localStorage.getItem('defaultModel');
    if (savedModel) {
        setTimeout(() => {
            const select = document.getElementById('settings-model-select');
            if (select) {
                select.value = savedModel;
            }
        }, 500);
    }
}

function saveSettings() {
    const select = document.getElementById('settings-model-select');
    const model = select ? select.value : '';
    const ragEnabled = document.getElementById('rag-enabled').checked;
    
    localStorage.setItem('defaultModel', model);
    localStorage.setItem('ragEnabled', ragEnabled);
    
    showNotification('Settings saved successfully!', 'success');
}

// Get all available timezones
function getTimeZones() {
    const timezoneSelect = document.getElementById('timezone-select');
    if (!timezoneSelect) return;
    
    try {
        // Get all IANA timezone identifiers
        const timezones = Intl.supportedValuesOf('timeZone');
        
        // Sort timezones alphabetically
        timezones.sort();
        
        // Clear existing options
        timezoneSelect.innerHTML = '';
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select timezone...';
        timezoneSelect.appendChild(defaultOption);
        
        // Group timezones by region for better organization
        const grouped = {};
        timezones.forEach(tz => {
            const parts = tz.split('/');
            const region = parts[0] || 'Other';
            if (!grouped[region]) {
                grouped[region] = [];
            }
            grouped[region].push(tz);
        });
        
        // Sort regions
        const sortedRegions = Object.keys(grouped).sort();
        
        // Populate select with grouped timezones
        sortedRegions.forEach(region => {
            // Add optgroup for region
            const optgroup = document.createElement('optgroup');
            optgroup.label = region;
            
            grouped[region].forEach(tz => {
                const option = document.createElement('option');
                option.value = tz;
                // Format timezone name for display (remove region prefix if it's the same)
                const displayName = tz.replace(region + '/', '');
                option.textContent = `${displayName} (${tz})`;
                optgroup.appendChild(option);
            });
            
            timezoneSelect.appendChild(optgroup);
        });
        
        // Load saved timezone
        const savedTimezone = localStorage.getItem('userTimezone');
        if (savedTimezone) {
            timezoneSelect.value = savedTimezone;
            updateCurrentTimezoneDisplay(savedTimezone);
        } else {
            // Default to browser's timezone
            const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            timezoneSelect.value = browserTimezone;
            updateCurrentTimezoneDisplay(browserTimezone);
        }
        
        // Update display when timezone changes
        timezoneSelect.addEventListener('change', function() {
            updateCurrentTimezoneDisplay(this.value);
        });
        
    } catch (error) {
        console.error('Error loading timezones:', error);
        timezoneSelect.innerHTML = '<option value="">Error loading timezones</option>';
        
        // Fallback: Use a basic list of common timezones
        const commonTimezones = [
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'Asia/Dubai',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Australia/Sydney',
            'UTC'
        ];
        
        commonTimezones.forEach(tz => {
            const option = document.createElement('option');
            option.value = tz;
            option.textContent = tz;
            timezoneSelect.appendChild(option);
        });
    }
}

// Update the current timezone display
function updateCurrentTimezoneDisplay(timezone) {
    const display = document.getElementById('current-timezone-display');
    if (!display || !timezone) return;
    
    try {
        const now = new Date();
        const formatter = new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            timeZoneName: 'short'
        });
        const parts = formatter.formatToParts(now);
        const tzName = parts.find(part => part.type === 'timeZoneName')?.value || timezone;
        display.textContent = `${timezone} (${tzName})`;
    } catch (error) {
        display.textContent = timezone;
    }
}

// Load date/time settings from localStorage
function loadDateTimeSettings() {
    const savedTimezone = localStorage.getItem('userTimezone');
    const savedDateFormat = localStorage.getItem('dateFormat');
    const savedTimeFormat = localStorage.getItem('timeFormat');
    
    if (savedTimezone) {
        const timezoneSelect = document.getElementById('timezone-select');
        if (timezoneSelect) {
            // Wait for timezones to load
            setTimeout(() => {
                timezoneSelect.value = savedTimezone;
                updateCurrentTimezoneDisplay(savedTimezone);
            }, 200);
        }
    }
    
    if (savedDateFormat) {
        const dateFormatSelect = document.getElementById('date-format-select');
        if (dateFormatSelect) {
            dateFormatSelect.value = savedDateFormat;
        }
    }
    
    if (savedTimeFormat) {
        const timeFormatSelect = document.getElementById('time-format-select');
        if (timeFormatSelect) {
            timeFormatSelect.value = savedTimeFormat;
        }
    }
}

// Save date/time settings to localStorage
function saveDateTimeSettings() {
    const timezoneSelect = document.getElementById('timezone-select');
    const dateFormatSelect = document.getElementById('date-format-select');
    const timeFormatSelect = document.getElementById('time-format-select');
    
    const timezone = timezoneSelect ? timezoneSelect.value : '';
    const dateFormat = dateFormatSelect ? dateFormatSelect.value : 'medium';
    const timeFormat = timeFormatSelect ? timeFormatSelect.value : 'short';
    
    if (timezone) {
        localStorage.setItem('userTimezone', timezone);
        updateCurrentTimezoneDisplay(timezone);
    }
    localStorage.setItem('dateFormat', dateFormat);
    localStorage.setItem('timeFormat', timeFormat);
    
    // Dispatch custom event to notify other pages
    window.dispatchEvent(new Event('storage'));
    document.dispatchEvent(new CustomEvent('settingsUpdated'));
    
    showNotification('Date/Time settings saved successfully! Refresh documents page to see updated date formats.', 'success');
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


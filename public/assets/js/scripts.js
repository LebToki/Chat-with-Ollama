document.addEventListener('DOMContentLoaded', function() {
    const modelBadge = document.getElementById('model-badge');
    const modelSelect = document.getElementById('model-select');
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const fileButton = document.getElementById('file-button');
    const filePreview = document.getElementById('file-preview');
    const syncModelsBtn = document.getElementById('sync-models-btn');
    let uploadedFile = null;

    function updateModelStatus() {
        axios.get('/src/Models/models.json')
            .then(function(response) {
                const models = response.data;
                if (models && models.length > 0) {
                    modelSelect.innerHTML = '';
                    models.forEach(function(model) {
                        const option = document.createElement('option');
                        option.value = model.name;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });

                    const defaultModel = localStorage.getItem('defaultModel') || models[0].name;
                    modelBadge.textContent = `${defaultModel} (Loading...)`;
                    modelSelect.value = defaultModel;
                    checkModelStatus(defaultModel);
                } else {
                    modelBadge.textContent = 'No models available';
                }
            })
            .catch(function(error) {
                modelBadge.textContent = 'Error loading models';
                console.error('Failed to fetch models:', error);
            });
    }

    function checkModelStatus(model) {
        axios.post('/src/Controllers/ChatController.php', { message: '', model: model })
            .then(function(response) {
                const data = response.data;
                if (data.error) {
                    modelBadge.textContent = `${model} (Loading...)`;
                } else {
                    modelBadge.textContent = `${model} (Ready)`;
                }
            })
            .catch(function(error) {
                modelBadge.textContent = 'Error checking model status';
                console.error('Error during POST request:', error);
            });
    }

    modelSelect.addEventListener('change', function() {
        const selectedModel = modelSelect.value;
        localStorage.setItem('defaultModel', selectedModel);
        modelBadge.textContent = `${selectedModel} (Loading...)`;
        checkModelStatus(selectedModel);
    });

    updateModelStatus();

    syncModelsBtn.addEventListener('click', function() {
        axios.get('/public/fetch_models.php')
            .then(function(response) {
                var data = response.data;
                if (data.success) {
                    updateModelStatus();
                } else {
                    console.error(data.error || 'Model sync failed');
                }
            })
            .catch(function(error) {
                console.error('Failed to sync models:', error);
            });
    });

    chatForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const userMessage = userInput.value.trim();
        if (userMessage || uploadedFile) {
            if (userMessage) {
                appendMessage(userMessage, 'user-message');
                userInput.value = '';
            }
            const formData = new FormData();
            formData.append('message', userMessage);
            formData.append('model', modelSelect.value);
            if (uploadedFile) {
                formData.append('file', uploadedFile);
            }

            axios.post('/src/Controllers/ChatController.php', formData)
                .then(function(response) {
                    appendMessage(response.data, 'bot-message');
                    filePreview.style.display = 'none';
                    uploadedFile = null;
                })
                .catch(function(error) {
                    appendMessage('Error: ' + error.message, 'bot-message');
                });
        }
    });

    function appendMessage(message, messageType) {
        const messageElement = document.createElement('div');
        messageElement.classList.add(messageType);
        messageElement.textContent = typeof message === 'string' ? message : JSON.stringify(message, null, 2);
        document.getElementById('chatbox').appendChild(messageElement);
        messageElement.scrollIntoView();
    }

    fileButton.addEventListener('click', function() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        fileInput.addEventListener('change', function() {
            const file = fileInput.files[0];
            if (file) {
                filePreview.style.display = 'flex';
                uploadedFile = file;
            }
        });
        document.body.appendChild(fileInput);
        fileInput.click();
    });

    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('delete-file')) {
            filePreview.style.display = 'none';
            uploadedFile = null;
        }
    });
});

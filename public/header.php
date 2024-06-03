		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Chat with Ollama</title>
			<meta name="description" content="Chat with Ollama - A Vanilla Approach to a RAG that connects to Ollama API">
			<meta name="author" content="Tarek Tarabichi from 2TInteractive">
			
			<!-- Bootstrap 5.3.3 -->
			<link rel="stylesheet" href="/public/assets/libs/bootstrap/css/bootstrap.min.css">
			<link rel="stylesheet" href="/public/assets/libs/bootstrap/css/bootstrap-utilities.min.css">
			<link rel="stylesheet" href="/public/assets/libs/bootstrap/css/bootstrap-grid.min.css">
			<!-- FontAwesome 6.5.2 -->
			<link rel="stylesheet" href="/public/assets/libs/font-awesome/css/all.min.css">
			<link rel="stylesheet" href="/public/assets/libs/font-awesome/css/brands.min.css">
			<link rel="stylesheet" href="/public/assets/libs/font-awesome/css/regular.min.css">
			<link rel="stylesheet" href="/public/assets/css/styles.css">
			<!-- Favicon -->
			<link rel="icon" type="image/x-icon" href="/public/assets/img/Logo.png">
		</head>
		<body class="mode-dark">
		<nav class="navbar navbar-dark bg-dark">
			<div class="container-fluid">
				<a class="navbar-brand" href="#">
					<img src="/public/assets/img/bot-avatar.png" alt="Bot Avatar" width="30" height="30" class="d-inline-block align-text-top">
					Chat with Ollama
				</a>
				<div class="d-flex align-items-center">
					<div id="model-status" class="d-flex justify-content-end mt-3">
						<select id="model-select" class="form-select bg-dark text-white">
							<option value="">Select a model</option>
						</select>
						<span id="model-badge" class="badge bg-secondary ms-2">Loading...</span>
					</div>
					<div class="dropdown ms-3">
						<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="/public/assets/img/user-avatar.png" class="avatar" alt="User Avatar">
						</a>
						<ul class="dropdown-menu" aria-labelledby="navbarDropdown">
							<li><a class="dropdown-item" href="./profile.php">Profile</a></li>
							<li><a class="dropdown-item" href="./settings.php">Settings</a></li>
						</ul>
					</div>
				</div>
			</div>
		</nav>
		
		<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modelBadge = document.getElementById('model-badge');

        function updateModelStatus() {
            axios.get('/src/Models/models.json')
                .then(function(response) {
                    const models = response.data;
                    if (models && models.length > 0) {
                        const defaultModel = localStorage.getItem('defaultModel') || models[0].name;
                        modelBadge.textContent = `${defaultModel} (Loading...)`;
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

        updateModelStatus();

        const chatForm = document.getElementById('chat-form');
        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const userInput = document.getElementById('user-input').value.trim();
            if (userInput) {
                appendMessage(userInput, 'user-message');
                document.getElementById('user-input').value = '';
                const model = modelBadge.textContent.split(' ')[0];
                axios.post('/src/Controllers/ChatController.php', { message: userInput, model: model })
                    .then(function(response) {
                        appendMessage(response.data, 'bot-message');
                    })
                    .catch(function(error) {
                        appendMessage('Error: ' + error.message, 'bot-message');
                    });
            }
        });

        function appendMessage(message, messageType) {
            const messageElement = document.createElement('div');
            messageElement.classList.add(messageType);
            messageElement.textContent = message;
            document.getElementById('chatbox').appendChild(messageElement);
            messageElement.scrollIntoView();
        }
    });

</script>
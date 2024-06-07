<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>LiveLink</title>
	<link rel="icon" type="image/png" href="./assets/img/bot-avatar.png">
	<link rel="stylesheet" href="./assets/libs/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="./assets/libs/font-awesome/css/fontawesome.css">
	<link rel="stylesheet" href="./assets/libs/font-awesome/css/all.css">
	<link rel="stylesheet" href="./assets/libs/font-awesome/css/brands.css">
	<link rel="stylesheet" href="./assets/libs/font-awesome/css/regular.css">
	<link rel="stylesheet" href="./assets/libs/font-awesome/css/solid.css">
	<link rel="stylesheet" href="./assets/css/styles.css">
</head>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
	<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6" href="#">
		<img src="./assets/img/Chat-With-Ollama-wide-Logo-Dark.png" style="height: 40px;" alt="LiveLink Logo">
	</a>
	<div class="d-flex align-items-center">
		<div id="model-status" class="d-flex justify-content-end mt-3 me-2">
			<select id="model-select" class="form-select bg-dark text-white">
				<option value="">Select a model</option>
				<?php
					$modelsJson = @file_get_contents('models/models.json');
					if ($modelsJson === false) {
						echo '<option value="" disabled>Error loading models</option>';
					} else {
						$models = json_decode($modelsJson, true);
						$readyModels = array_filter($models, function ($model) {
							return $model['ready'];
						});
						
						if (empty($readyModels)) {
							echo '<option value="" disabled>No models available</option>';
						} else {
							foreach ($readyModels as $model) {
								$selected = ($model['name'] === 'your_default_model') ? 'selected' : ''; // Set your default model here
								echo "<option value='{$model['name']}' $selected>{$model['name']} ({$model['description']})</option>";
							}
						}
					}
				?>
			</select>
			<button id="sync-models-btn" class="btn btn-sm btn-outline-secondary ms-2" type="button">
				<i class="fas fa-sync-alt"></i> Sync
			</button>
		</div>
		
		<div class="dropdown ms-3">
			<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<img src="./assets/img/user-avatar.png" class="avatar" alt="User Avatar">
			</a>
			<ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDropdown">
				<li><a class="dropdown-item" href="./profile.php">Profile</a></li>
				<li><a class="dropdown-item" href="./settings.php">Settings</a></li>
				<li><a class="dropdown-item" href="./logout.php">Logout</a></li>
			</ul>
		</div>
	</div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modelBadge = document.getElementById('model-badge');
        const modelSelect = document.getElementById('model-select');

        function updateModelStatus() {
            fetch('http://LiveLink.local/models/models.json')
                .then(response => response.json())
                .then(models => {
                    if (models && models.length > 0) {
                        const defaultModel = localStorage.getItem('defaultModel') || models[0].name;
                        modelBadge.textContent = `${defaultModel} (Loading...)`;
                        checkModelStatus(defaultModel);
                    } else {
                        modelBadge.textContent = 'No models available';
                    }
                })
                .catch(error => {
                    modelBadge.textContent = 'Error loading models';
                    console.error('Failed to fetch models:', error);
                });
        }

        function checkModelStatus(model) {
            fetch('http://LiveLink.local/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=&model=${model}`,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modelBadge.textContent = `${model} (Loading...)`;
                    } else {
                        modelBadge.textContent = `${model} (Ready)`;
                    }
                })
                .catch(error => {
                    modelBadge.textContent = 'Error checking model status';
                    console.error('Error during POST request:', error);
                });
        }

        updateModelStatus();
    });
</script>

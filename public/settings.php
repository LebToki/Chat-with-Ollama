<?php require __DIR__ . '/header.php'; ?>

<div class="container-fluid">
	<div class="row">
		<?php require __DIR__ . '/sidebar.php'; ?>
		<div class="col-md-9 ml-sm-auto col-lg-10 px-4">
			<h1 class="mt-5">Settings</h1>
			<form id="model-selection-form" class="mt-3 p-4 border rounded">
				<div class="form-group">
					<label for="model-select">Select Default Model:</label>
					<div class="input-group">
						<select id="model-select" class="form-control">
							<option value="">Loading models...</option>
						</select>
						<button type="button" id="update-models" class="btn btn-outline-secondary">
							<i class="fas fa-sync-alt"></i>
						</button>
					</div>
				</div>
				<button type="submit" class="btn btn-primary mt-3">Save Settings</button>
			</form>
		</div>
	</div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

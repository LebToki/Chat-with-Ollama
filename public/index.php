<?php require __DIR__ . '/header.php'; ?>

<div class="container-fluid">
	<div class="row">
		<?php require __DIR__ . '/sidebar.php'; ?>
		<div class="col-md-9 ml-sm-auto col-lg-10 px-4">
			<div id="chatbox" class="mt-3 mb-3 border rounded p-3">
				<!-- Chat messages will be appended here -->
			</div>
			<!-- Chat Input Form -->
			<form id="chat-form" class="input-group mb-3">
				<button class="btn btn-light" type="button" id="file-button">
					<img src="/public/assets/img/file-upload.png" alt="File Upload" width="24">
				</button>
				<input type="text" id="user-input" class="form-control" placeholder="Type a message...">
				<button class="btn btn-primary" type="submit">
					<i class="fas fa-paper-plane"></i>
				</button>
			</form>
			<!-- File Upload Preview -->
			<div id="file-preview" class="file-preview mt-3" style="display: none;">
				<img src="/public/assets/img/File.png" alt="File Icon" width="40">
				<span class="delete-file">X</span>
			</div>
		</div>
	</div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

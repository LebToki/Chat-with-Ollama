<?php
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$uploadDir = __DIR__ . '/../public/uploads/';
		$uploadFile = $uploadDir . basename($_FILES['file']['name']);
		
		if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
			echo "File is valid, and was successfully uploaded.\n";
		} else {
			echo "Possible file upload attack!\n";
		}
	} else {
		echo "Invalid request method.";
	}

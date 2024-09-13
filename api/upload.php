<?php
// Load config.php
require_once 'config.php';
require_once 'token.php';

// Generate or retrieve a token
$token = generateToken($_SERVER['REMOTE_ADDR']);

// Check if a directory corresponding to the token exists
$dir = getDirectoryByToken($token);

if (!$dir) {
	// Create a new directory
	$dir = uniqid('realm_');
	mkdir(UPLOAD_PATH . "/$dir", 0777, true);

	// Save the token and directory mapping
	saveTokenMapping($token, $dir);
} else {
	// Delete files in the existing directory
	array_map('unlink', glob(UPLOAD_PATH . "/$dir/*.*"));
}

// Calculate the total size of the uploaded files
$totalSize = 0;
foreach ($_FILES as $file) {
	$totalSize += $file['size'];
}

// Check the total size limit
if ($totalSize > MAX_TOTAL_SIZE) {
	echo json_encode(['error' => 'The total file size exceeds the limit of 20MB.']);
	exit;
}

// Check each file's size
foreach ($_FILES as $file) {
	if ($file['size'] > MAX_FILE_SIZE) {
		echo json_encode(['error' => 'One of the files exceeds the size limit of 5MB.']);
		exit;
	}

	// Upload the file
	move_uploaded_file($file['tmp_name'], UPLOAD_PATH . "/$dir/" . basename($file['name']));
}

// Set the expiration time
$expiry = time() + EXPIRY_TIME;
file_put_contents(UPLOAD_PATH . "/$dir/expiry.json", json_encode(['expiry' => $expiry]));

// After uploading, return the directory URL
echo json_encode(['url' => BASE_URL . '/' . UPLOAD_PATH . "/$dir/"]);

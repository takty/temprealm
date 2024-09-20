<?php
/**
 * Upload
 *
 * @author Takuto Yanagida
 * @version 2024-09-20
 */

require_once 'inc/config.php';
require_once 'inc/token.php';
require_once 'inc/expiry.php';
require_once 'inc/util.php';

cleanUpExpiredFiles();

$ip  = $_SERVER['REMOTE_ADDR'];
$id  = $_GET['id'] ?? '';
$act = $_GET['action'] ?? 'new';

// Count how many tokens have already been issued for this IP
if (countTokensByIP($ip) >= MAX_TOKEN_COUNT && 'new' === $act) {
	echo json_encode(['error' => 'Maximum number of tokens (10) reached for this IP.']);
	exit;
}

$token = generateToken($ip, $id);
$dir   = getDirectoryByToken($token);

if ('remove' === $act) {
	$baseDir = UPLOAD_PATH . '/' . $dir;
	if (file_exists($baseDir)) {
		deleteFiles($token, $baseDir);
		echo json_encode(['success' => 'Directory and files have been removed.']);
	} else {
		echo json_encode(['error' => 'Directory not found.']);
	}
	exit;
}

// 'new' or 'update' action.
if (!$dir) {
	$dir = uniqid_ex();
	mkdir(UPLOAD_PATH . "/$dir", 0777, true);
	saveTokenMapping($token, $ip, $dir);
}

$baseDir = UPLOAD_PATH . '/' . $dir;

// Handle the update action
if ('update' === $act) {
	// Calculate the total size of the existing files in the directory
	$totalSize = calculateTotalSize($baseDir);

	// Calculate the size of the uploaded files
	foreach ($_FILES['files']['name'] as $key => $name) {
		$filePath = $_POST['paths'][$key];
		if (empty($filePath)) {
			$filePath = $_FILES['files']['name'][$key];
		}
		$fullPath = $baseDir . '/' . $filePath;

		if (file_exists($fullPath)) {
			$totalSize -= filesize($fullPath);
		}
		$totalSize += $_FILES['files']['size'][$key];
	}

	// Check if the new total size exceeds the limit
	if ($totalSize > MAX_TOTAL_SIZE) {
		echo json_encode(['error' => 'The total file size exceeds the limit of 20MB.']);
		exit;
	}
	// Check if any individual file exceeds the size limit
	foreach ($_FILES['files']['name'] as $key => $name) {
		if ($_FILES['files']['size'][$key] > MAX_FILE_SIZE) {
			echo json_encode(['error' => 'One of the files exceeds the size limit of 5MB.']);
			exit;
		}
		// Recreate the directory structure
		$filePath = $_POST['paths'][$key];
		if (empty($filePath)) {
			$filePath = $_FILES['files']['name'][$key];
		}
		$fullPath = $baseDir . '/' . $filePath;
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);  // Create directories if they don't exist
		}
		// Overwrite the file in the directory
		move_uploaded_file($_FILES['files']['tmp_name'][$key], $fullPath);
	}
	// Return success response
	echo json_encode(['success' => 'Files and directories have been uploaded successfully.']);
	exit;
}

if ('new' === $act) {
	deleteAllInDirectory($baseDir);

	// Calculate the total size of the uploaded files
	$totalSize = 0;
	foreach ($_FILES['files']['size'] as $size) {
		$totalSize += $size;
	}

	// Check the total size limit
	if ($totalSize > MAX_TOTAL_SIZE) {
		echo json_encode(['error' => 'The total file size exceeds the limit of 20MB.']);
		exit;
	}
	// ob_start();
	// var_dump( $_FILES['files'] );
	// $result = ob_get_clean();

	// Check each file's size
	foreach ($_FILES['files']['name'] as $key => $name) {
		if ($_FILES['files']['size'][$key] > MAX_FILE_SIZE) {
			echo json_encode(['error' => 'One of the files exceeds the size limit of 5MB.']);
			exit;
		}
		// Upload the file
		$filePath = $_POST['paths'][$key];
		if (empty($filePath)) {
			$filePath = $_FILES['files']['name'][$key];
		}
		$fullPath = $baseDir . '/' . $filePath;
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		move_uploaded_file($_FILES['files']['tmp_name'][$key], $fullPath);
	}
	// Save the expiration time in expiry_data/<token>.json using the function
	saveExpiry($token);
	// After uploading, return the directory URL
	echo json_encode(['url' => BASE_URL . "/$dir/"]);
}

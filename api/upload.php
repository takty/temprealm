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

$token   = generateToken($ip, $id);
$dir     = getDirectoryByToken($token);
$baseDir = UPLOAD_PATH . '/' . $dir;

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

// Handle the update action
if ('update' === $act) {
	$fs = normalizeFileArray($_FILES['files'], $_POST['paths']);

	$totalSize = calculateTotalSize($baseDir);
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = $baseDir . '/' . $filePath;

		if (file_exists($fullPath)) {
			$totalSize -= filesize($fullPath);
		}
		if ($f['size'] > MAX_FILE_SIZE) {
			echo json_encode(['error' => 'One of the files exceeds the size limit of 5MB.']);
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		echo json_encode(['error' => 'The total file size exceeds the limit of 20MB.']);
		exit;
	}
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = $baseDir . '/' . $filePath;
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		move_uploaded_file($f['tmp_name'], $fullPath);
	}
	// Return success response
	echo json_encode(['success' => 'Files and directories have been uploaded successfully.']);
	exit;
}

if ('new' === $act) {
	deleteAllInDirectory($baseDir);
	$fs = normalizeFileArray($_FILES['files'], $_POST['paths']);

	$totalSize = 0;
	foreach ($fs as $f) {
		if ($f['size'] > MAX_FILE_SIZE) {
			echo json_encode(['error' => 'One of the files exceeds the size limit of 5MB.']);
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		echo json_encode(['error' => 'The total file size exceeds the limit of 20MB.']);
		exit;
	}
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = $baseDir . '/' . $filePath;
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		move_uploaded_file($f['tmp_name'], $fullPath);
	}
	saveExpiry($token);
	echo json_encode(['url' => BASE_URL . "/$dir/"]);
}

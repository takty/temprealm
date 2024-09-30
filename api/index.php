<?php
/**
 * Index
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

require_once 'inc/config.php';
require_once 'inc/secret.php';
require_once 'inc/util.php';

checkAllowedAccess(ALLOWED_ORIGIN);

$addr    = $_SERVER['REMOTE_ADDR'];
$reqPath = getRequestPath();

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		get($reqPath);
		break;
	case 'POST':
		post($addr, $reqPath);
		break;
	case 'PUT':
		put($addr, $reqPath);
		break;
	case 'DELETE':
		delete($addr, $reqPath);
		break;
}


// -----------------------------------------------------------------------------


function get(string $reqPath): void {
	$reqPath = removeQueryAndHash($reqPath);

	$parts       = explode('/', trim($reqPath, '/'));
	$secret      = array_shift($parts);  // The first part is the secret
	$reqFilePath = implode('/', $parts);  // Remaining part is the file path (e.g., lib/test.js)

	$dir = $secret ? getDirectoryBySecret($secret) : null;

	if ($dir) {
		$baseDir = realpath(UPLOAD_PATH . '/' . $dir);

		if (!deleteExpiredFiles($secret, $baseDir)) {
			if (!empty($reqFilePath) && !str_ends_with($reqPath, '/') && is_dir($baseDir . '/' . $reqFilePath)) {
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . get_request_url() . '/');
				exit;
			}
			if (empty($reqFilePath)) {
				$reqFilePath = 'index.html';
			}
			$fullReqPath = realpath($baseDir . '/' . $reqFilePath);
			if (is_dir($fullReqPath)) {
				$fullReqPath = $fullReqPath . '/index.html';
			}

			// Check if the requested file exists and is within the base directory
			if ($fullReqPath && 0 === strpos($fullReqPath, $baseDir) && file_exists($fullReqPath)) {
				header('HTTP/1.1 200 OK');
				header('Content-Type: ' . mime_content_type($fullReqPath));
				readfile($fullReqPath);
				return;
			}
		}
	}
	// If the file doesn't exist or is outside the allowed directory, deny access
	header('HTTP/1.1 403 Forbidden');
}


// -----------------------------------------------------------------------------


function post(string $addr): void {
	cleanUpExpiredFiles();

	if (countSecretsByAddr($addr) >= MAX_SECRET_COUNT) {
		sendError('Maximum number of secrets (10) reached for this IP.');
		exit;
	}
	$secret = generateSecret($addr);

	$dir = uniqid_ex();
	saveSecretMapping($secret, $addr, $dir);

	$baseDir = UPLOAD_PATH . '/' . $dir;
	mkdir($baseDir, 0777, true);

	$fs = normalizeFileArray($_FILES['files'], $_POST['paths']);

	$totalSize = 0;
	foreach ($fs as $f) {
		if ($f['size'] > MAX_FILE_SIZE) {
			sendError('One of the files exceeds the size limit of 5MB.');
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		sendError('The total file size exceeds the limit of 20MB.');
		exit;
	}
	moveUploadedFiles($baseDir, $fs);

	$reqUrl = rtrim( get_request_url(), '/');
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	if (str_ends_with($reqUrl, $script)) {
		$url = substr($reqUrl, 0, -strlen($script)) . "$secret/";
	} else {
		$url = $reqUrl . "/$secret/";
	}

	header('HTTP/1.1 200 OK');
	header('Content-Type: application/json');
	echo json_encode([
		'url'     => $url,
		'success' => 'Files and directories have been uploaded successfully.',
	]);
}


// -----------------------------------------------------------------------------


function put(string $addr, string $reqPath): void {
	cleanUpExpiredFiles();

	$parts  = explode('/', trim($reqPath, '/'));
	$secret = array_shift($parts);  // The first part is the secret

	if ($addr !== getAddrBySecret($secret)) {
		sendError('Directory not found.');
		exit;
	}
	$dir = getDirectoryBySecret($secret);
	if (!$dir) {
		sendError('Directory not found.');
		exit;
	}
	$baseDir = UPLOAD_PATH . '/' . $dir;

	$fs = normalizeFileArray($_FILES['files'], $_POST['paths']);

	$totalSize = calculateTotalSize($baseDir);
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = $baseDir . '/' . $filePath;

		if (file_exists($fullPath)) {
			$totalSize -= filesize($fullPath);
		}
		if ($f['size'] > MAX_FILE_SIZE) {
			sendError('One of the files exceeds the size limit of 5MB.');
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		sendError('The total file size exceeds the limit of 20MB.');
		exit;
	}
	moveUploadedFiles($baseDir, $fs);
	header('HTTP/1.1 200 OK');
	header('Content-Type: application/json');
	echo json_encode(['success' => 'Files and directories have been uploaded successfully.']);
}


// -----------------------------------------------------------------------------


function delete(string $addr, string $reqPath): void {
	cleanUpExpiredFiles();

	$parts  = explode('/', trim($reqPath, '/'));
	$secret = array_shift($parts);  // The first part is the secret

	if ($addr === getAddrBySecret($secret)) {
		$dir = getDirectoryBySecret($secret);

		if ($dir) {
			$baseDir = UPLOAD_PATH . '/' . $dir;

			if (file_exists($baseDir)) {
				deleteFiles($secret, $baseDir);
				header('HTTP/1.1 200 OK');
				header('Content-Type: application/json');
				echo json_encode(['success' => 'Directory and files have been removed.']);
				return;
			}
		}
	}
	sendError('Directory not found.');
}

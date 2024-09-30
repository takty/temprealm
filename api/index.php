<?php
/**
 * Index
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

require_once 'inc/lib/file.php';
require_once 'inc/lib/upload.php';
require_once 'inc/config.php';
require_once 'inc/secret.php';
require_once 'inc/util.php';

check_allowed_access(ALLOWED_ORIGIN);

$addr = $_SERVER['REMOTE_ADDR'];

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		get($reqPath);
		break;
	case 'POST':
		post($addr);
		break;
	case 'PUT':
		put($addr);
		break;
	case 'DELETE':
		delete($addr);
		break;
}


// -----------------------------------------------------------------------------


function get(): void {
	$reqPath = get_request_path();

	$parts       = explode('/', trim($reqPath, '/'));
	$secret      = array_shift($parts);  // The first part is the secret
	$reqFilePath = implode('/', $parts);  // Remaining part is the file path (e.g., lib/test.js)

	$dir = $secret ? get_directory_by_secret($secret) : null;

	if ($dir) {
		$baseDir = realpath(join_paths(UPLOAD_PATH, $dir));

		if (!delete_files_if_expired($secret, $baseDir)) {
			if (!empty($reqFilePath) && !str_ends_with($reqPath, '/') && is_dir(join_paths($baseDir, $reqFilePath))) {
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . get_request_url() . '/');
				exit;
			}
			if (empty($reqFilePath)) {
				$reqFilePath = 'index.html';
			}
			$fullReqPath = realpath(join_paths($baseDir, $reqFilePath));
			if (is_dir($fullReqPath)) {
				$fullReqPath = $fullReqPath . '/index.html';
			}
			// Check if the requested file exists and is within the base directory
			if ($fullReqPath && str_starts_with($fullReqPath, $baseDir) && file_exists($fullReqPath)) {
				header('HTTP/1.1 200 OK');
				header('Content-Type: ' . mime_content_type($fullReqPath));
				readfile($fullReqPath);
				return;
			}
		}
	}
	// If the file doesn't exist or is outside the allowed directory, deny access
	send_response(403);
}


// -----------------------------------------------------------------------------


function post(string $addr): void {
	clean_up_expired_files();

	if (count_secrets_by_addr($addr) >= MAX_SECRET_COUNT) {
		send_response(400, 'Maximum number of secrets (10) reached for this IP.');
		exit;
	}
	$secret = generate_secret($addr);

	$dir = uniqid_ex();
	save_secret_mapping($secret, $addr, $dir);

	$baseDir = join_paths(UPLOAD_PATH, $dir);
	mkdir($baseDir, 0777, true);

	$fs = normalize_file_array($_FILES['files'], $_POST['paths']);

	$totalSize = 0;
	foreach ($fs as $f) {
		if ($f['size'] > MAX_FILE_SIZE) {
			send_response(400, 'One of the files exceeds the size limit of 5MB.');
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		send_response(400, 'The total file size exceeds the limit of 20MB.');
		exit;
	}
	move_uploaded_files($baseDir, $fs);

	$reqUrl = rtrim( get_request_url(), '/');
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	if (str_ends_with($reqUrl, $script)) {
		$url = substr($reqUrl, 0, -strlen($script)) . "$secret/";
	} else {
		$url = $reqUrl . "/$secret/";
	}
	send_response(200, 'Files and directories have been uploaded successfully.', ['url' => $url]);
}


// -----------------------------------------------------------------------------


function put(string $addr): void {
	clean_up_expired_files();
	$secret = get_request_path_first();  // The first part is the secret

	if ($addr !== get_addr_by_secret($secret)) {
		send_response(400, 'Directory not found.');
		exit;
	}
	$dir = get_directory_by_secret($secret);
	if (!$dir) {
		send_response(400, 'Directory not found.');
		exit;
	}
	$baseDir = join_paths(UPLOAD_PATH, $dir);

	$fs = normalize_file_array($_FILES['files'], $_POST['paths']);

	$totalSize = calculate_total_size($baseDir);
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = join_paths($baseDir, $filePath);

		if (file_exists($fullPath)) {
			$totalSize -= filesize($fullPath);
		}
		if ($f['size'] > MAX_FILE_SIZE) {
			send_response(400, 'One of the files exceeds the size limit of 5MB.');
			exit;
		}
		$totalSize += $f['size'];
	}
	if ($totalSize > MAX_TOTAL_SIZE) {
		send_response(400, 'The total file size exceeds the limit of 20MB.');
		exit;
	}
	move_uploaded_files($baseDir, $fs);
	send_response(200, 'Files and directories have been uploaded successfully.');
}


// -----------------------------------------------------------------------------


function delete(string $addr): void {
	clean_up_expired_files();
	$secret = get_request_path_first();  // The first part is the secret

	if ($addr === get_addr_by_secret($secret)) {
		$dir = get_directory_by_secret($secret);

		if ($dir) {
			$baseDir = join_paths(UPLOAD_PATH, $dir);

			if (file_exists($baseDir)) {
				delete_files($secret, $baseDir);
				header('HTTP/1.1 200 OK');
				header('Content-Type: application/json');
				echo json_encode(['success' => 'Directory and files have been removed.']);
				return;
			}
		}
	}
	send_response(400, 'Directory not found.');
}

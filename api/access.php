<?php
require_once 'config.php';

// Get the path of the requested file
$requestedFile = $_GET['file'] ?? '';

// Dynamically retrieve the directory from the referrer or request
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$directory = basename(parse_url($referrer, PHP_URL_PATH)); // Get the directory name from the path part of the referrer

// Get the base directory
$baseDir = realpath(UPLOAD_PATH . '/' . $directory);

// Get the absolute path of the requested file
$requestedPath = realpath($baseDir . '/' . $requestedFile);

// Check the expiration time
$expiryFile = $baseDir . '/expiry.json';
if (file_exists($expiryFile)) {
	$expiryData = json_decode(file_get_contents($expiryFile), true);
	if (time() > $expiryData['expiry']) {
		array_map('unlink', glob("$baseDir/*.*"));
		rmdir($baseDir);
		header('HTTP/1.0 410 Gone');
		echo 'File expired';
		exit;
	}
}

// Check if the file exists and is within the base directory
if ($requestedPath && strpos($requestedPath, $baseDir) === 0) {
	header('Content-Type: ' . mime_content_type($requestedPath));
	readfile($requestedPath);
} else {
	header('HTTP/1.0 403 Forbidden');
	echo 'Access denied';
}

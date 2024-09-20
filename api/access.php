<?php
/**
 * Access
 *
 * @author Takuto Yanagida
 * @version 2024-09-18
 */

require_once 'inc/config.php';
require_once 'inc/token.php';
require_once 'inc/expiry.php';

// Get the requested path (e.g., /<token>/lib/test.js)
$reqPath = $_GET['file'] ?? '';

// Extract the hash and the file path
$parts       = explode('/', $reqPath);
$token       = array_shift($parts);  // The first part is the token
$reqFilePath = implode('/', $parts);  // Remaining part is the file path (e.g., lib/test.js)

// Retrieve the directory associated with the token
$dir = getDirectoryByToken($token);

if (!$dir) {
	// If the token is invalid, deny access
	header('HTTP/1.0 403 Forbidden');
	echo 'Access denied';
	exit;
}

// Base directory path
$baseDir = realpath(UPLOAD_PATH . '/' . $dir);

if (deleteExpiredFiles($token, $baseDir)) {
	// If files were deleted due to expiry, deny access
	header('HTTP/1.0 410 Gone');
	echo 'File expired';
	exit;
}

// Determine the path to the requested file
if (empty($reqFilePath)) {
	// If no specific file is requested (e.g., /<hash>), serve index.html by default
	$reqFilePath = 'index.html';
}
$fullReqPath = realpath($baseDir . '/' . $reqFilePath);

// Check if the requested file exists and is within the base directory
if ($fullReqPath && strpos($fullReqPath, $baseDir) === 0 && file_exists($fullReqPath)) {
	// Serve the file
	header('Content-Type: ' . mime_content_type($fullReqPath));
	readfile($fullReqPath);
}

// If the file doesn't exist or is outside the allowed directory, deny access
header('HTTP/1.0 403 Forbidden');
echo 'Access denied';

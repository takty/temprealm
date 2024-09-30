<?php
/**
 * Unique Identifier
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

function checkAllowedAccess(string $origin) {
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $origin) {
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
			header('Access-Control-Allow-Headers: Content-Type');
		} else {
			header('HTTP/1.1 403 Forbidden');
			echo 'Access denied';
			exit;
		}
	}
}

function getRequestPath() {
	$script_name = dirname($_SERVER['SCRIPT_NAME']);
	$request_uri = $_SERVER['REQUEST_URI'];

	if (strpos($request_uri, $script_name) === 0) {
		$request_uri = substr($request_uri, strlen($script_name));
	}
	return $request_uri;
}

function removeQueryAndHash(string $path): string {
	if (($pos = strpos($path, '?')) !== false) {
		$path = substr($path, 0, $pos);
	}
	if (($pos = strpos($path, '#')) !== false) {
		$path = substr($path, 0, $pos);
	}
	return $path;
}

function sendError(string $msg) {
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json');
	echo json_encode(['error' => $msg]);
}

function get_request_url(): string {
	$host = $_SERVER['HTTP_HOST'] ?? '';
	$req  = $_SERVER['REQUEST_URI'] ?? '';
	return ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . stripslashes( $host ) . stripslashes( $req );
}


// -----------------------------------------------------------------------------


/**
 * Calculates the total size of files.
 *
 * @param string $baseDir
 * @return int
 */
function calculateTotalSize(string $baseDir): int {
	$totalSize = 0;
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir)) as $file) {
		if ($file->isFile()) {
			$totalSize += $file->getSize();
		}
	}
	return $totalSize;
}

function deleteAllInDirectory(string $dir) {
	if (!is_dir($dir)) {
		return false;
	}
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($files as $fileinfo) {
		if ($fileinfo->isDir()) {
			rmdir($fileinfo->getRealPath());
		} else {
			unlink($fileinfo->getRealPath());
		}
	}
}


// -----------------------------------------------------------------------------


/**
 * Normalizes File Array.
 *
 * @param array $files
 * @param array $paths
 * @return array
 */
function normalizeFileArray(array $files, array $paths = []): array {
	$ret = [];

	if (!is_array($files['name'])) {
		$ret[] = $files;
		return $ret;
	}
	foreach($files['name'] as $idx => $name) {
		$ret[$idx] = [
			'name'     => $name,
			'type'     => $files['type'][$idx],
			'tmp_name' => $files['tmp_name'][$idx],
			'error'    => $files['error'][$idx],
			'size'     => $files['size'][$idx],
			'path'     => $paths[$idx],
		];
	}
	return $ret;
}

function moveUploadedFiles(string $baseDir, array $fs) {
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = $baseDir . '/' . $filePath;
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		move_uploaded_file($f['tmp_name'], $fullPath);
	}
}

<?php
/**
 * Unique Identifier
 *
 * @author Takuto Yanagida
 * @version 2024-09-24
 */

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

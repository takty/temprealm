<?php
/**
 * Unique Identifier
 *
 * @author Takuto Yanagida
 * @version 2024-09-20
 */

/**
 * Generates a secure identifier.
 *
 * @param string $prefix
 * @param bool   $more_entropy
 * @return string
 */
function uniqid_ex(string $prefix = '', bool $more_entropy = false): string {
	$len    = $more_entropy ? 23 : 13;
	$bytes  = (int) ceil($len / 2);
	$rb     = random_bytes($bytes);
	$hex    = bin2hex($rb);
	$uniqid = substr($hex, 0, $len);
	return $prefix . $uniqid;
}

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

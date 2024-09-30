<?php
/**
 * File Operations
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

/**
 * Concatenates two or more paths, ensuring proper directory separators without using regular expressions.
 *
 * @param string ...$paths One or more paths to concatenate.
 * @return string The concatenated path.
 */
function join_paths(string ...$paths): string {
	$res = '';

	foreach ($paths as $p) {
		$p = ltrim($p, DIRECTORY_SEPARATOR);
		if ('' !== $res) {
			$res = rtrim($res, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
		$res .= $p;
	}
	return $res;
}

/**
 * Calculates the total size of all files in the specified directory.
 *
 * @param string $dir The base directory to calculate the total size.
 * @return int The total size of files in bytes.
 */
function calculate_total_size(string $dir): int {
	$size = 0;
	$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
	foreach ($iter as $file) {
		if ($file->isFile()) {
			$size += $file->getSize();
		}
	}
	return $size;
}

/**
 * Deletes all files and directories.
 *
 * @param string $dir The directory to delete contents from.
 * @return bool True if the directory exists, false otherwise.
 */
function remove_all(string $dir): bool {
	if (!is_dir($dir)) {
		return false;
	}
	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iter as $fi) {
		if ($fi->isDir()) {
			rmdir($fi->getRealPath());
		} else {
			unlink($fi->getRealPath());
		}
	}
	return rmdir($dir);
}

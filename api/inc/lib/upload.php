<?php
/**
 * Uploading
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

require_once 'file.php';

/**
 * Normalizes the uploaded file array structure.
 *
 * @param array $files The file array from the $_FILES super-global.
 * @param array $paths Optional paths corresponding to the files.
 * @return array The normalized array of files with their paths.
 */
function normalize_file_array(array $files, array $paths = null): array {
	$ret = [];

	if (!is_array($files['name'])) {
		$ret[] = $files;
		if ($paths && !is_array($paths)) {
			$ret[0]['path'] = $paths;
		}
		return $ret;
	}
	foreach($files['name'] as $idx => $name) {
		$ret[$idx] = [
			'name'     => $name,
			'type'     => $files['type'][$idx],
			'tmp_name' => $files['tmp_name'][$idx],
			'error'    => $files['error'][$idx],
			'size'     => $files['size'][$idx],
			'path'     => $paths ? $paths[$idx] : null,
		];
	}
	return $ret;
}

/**
 * Moves the uploaded files to the specified base directory.
 *
 * @param string $baseDir The base directory where the files will be moved.
 * @param array $fs The array of uploaded files to move.
 */
function move_uploaded_files(string $baseDir, array $fs): void {
	foreach ($fs as $f) {
		$filePath = empty($f['path']) ? $f['name'] : $f['path'];
		$fullPath = join_paths($baseDir, $filePath);
		$dirPath  = dirname($fullPath);

		if (!is_dir($dirPath)) {
			mkdir($dirPath, 0777, true);
		}
		move_uploaded_file($f['tmp_name'], $fullPath);
	}
}

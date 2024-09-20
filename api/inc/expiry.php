<?php
/**
 * Expiry
 *
 * @author Takuto Yanagida
 * @version 2024-09-18
 */

require_once 'config.php';
require_once 'token.php';

/**
 * Create or update the expiry file for a specific token.
 *
 * @param string $token Token.
 */
function saveExpiry(string $token): void {
	$f = EXPIRY_DATA_PATH . '/' . $token . '.json';
	$t = time() + EXPIRY_TIME;
	file_put_contents($f, json_encode(['expiry' => $t]));
}

/**
 * Check if the file has expired based on the expiry file.
 *
 * @param string $token Token.
 * @return bool Whether the token is expired.
 */
function isExpired(string $token): bool {
	$f = EXPIRY_DATA_PATH . '/' . $token . '.json';
	if (!file_exists($f)) {
		return false; // If the expiry file doesn't exist, consider it not expired
	}
	$d = json_decode(file_get_contents($f), true);
	if (time() > $d['expiry']) {
		return true;
	}
	return false;
}

/**
 * Clean up expired files across all tokens.
 */
function cleanUpExpiredFiles(): void {
	$fs = glob(EXPIRY_DATA_PATH . '/*.json');

	foreach ($fs as $f) {
		$token = basename($f, '.json');
		$dir   = getDirectoryByToken($token);

		if ($dir) {
			$baseDir = realpath(UPLOAD_PATH . "/$dir");
			deleteExpiredFiles($token, $baseDir);
		}
	}
}

/**
 * Delete the files and expiry data if the file has expired.
 *
 * @param string $token   Token.
 * @param string $baseDir Base directory.
 * @return bool Whether the deletion is succeeded.
 */
function deleteExpiredFiles(string $token, string $baseDir): bool {
	if (isExpired($token)) {
		deleteFiles($token, $baseDir);
		return true; // Files were deleted
	}
	return false; // Files were not deleted (not expired)
}

/**
 * Delete the files and expiry data.
 *
 * @param string $token   Token.
 * @param string $baseDir Base directory.
 */
function deleteFiles(string $token, string $baseDir): void {
	// Delete files in the base directory
	array_map('unlink', glob("$baseDir/*.*"));
	rmdir($baseDir);

	// Also delete the expiry file
	$f = EXPIRY_DATA_PATH . '/' . $token . '.json';
	if (file_exists($f)) {
		unlink($f);
	}
}

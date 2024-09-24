<?php
/**
 * Secret
 *
 * @author Takuto Yanagida
 * @version 2024-09-24
 */

require_once 'config.php';

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


// -----------------------------------------------------------------------------


/**
 * Generate a secret.
 *
 * @param string $addr IP address.
 * @return string Secret.
 */
function generateSecret($addr): string {
	$secretBase = $addr . microtime();
	return hash('sha256', $secretBase);
}

/**
 * Counts how many secrets have been issued for a given IP.
 *
 * @param string $addr IP address.
 * @return int Issued secret count.
 */
function countSecretsByAddr(string $addr): int {
	$f = SECRET_MAPPING_FILE;
	$c = 0;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		foreach ($map as $d) {
			if (isset($d['addr']) && $d['addr'] === $addr) {
				$c++;
			}
		}
	}
	return $c;
}

/**
 * Retrieves IP address by the secret.
 *
 * @param string $secret Secret.
 * @return string IP address.
 */
function getAddrBySecret(string $secret): string {
	$f = SECRET_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (isset($map[$secret])) {
			return $map[$secret]['addr'];
		}
	}
	return false;
}

/**
 * Retrieves directory by the secret.
 *
 * @param string $secret Secret.
 * @return string Directory.
 */
function getDirectoryBySecret(string $secret): string {
	$f = SECRET_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (isset($map[$secret])) {
			return $map[$secret]['dir'];
		}
	}
	return false;
}

/**
 * Map the secret to a IP address and a directory.
 *
 * @param string $secret Secret.
 * @param string $addr   IP address.
 * @param string $dir    Directory.
 */
function saveSecretMapping(string $secret, string $addr, string $dir): void {
	$f   = SECRET_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
	}
	$map[$secret] = [
		'expiry' => time() + EXPIRY_TIME,
		'addr'   => $addr,
		'dir'    => $dir
	];
	file_put_contents($f, json_encode($map));
}


// -----------------------------------------------------------------------------


/**
 * Check if the file has expired based on the expiry file.
 *
 * @param string $secret Secret.
 * @return bool Whether the secret is expired.
 */
function isExpired(string $secret): bool {
	$f   = SECRET_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (time() > $map[$secret]['expiry']) {
			return true;
		}
	}
	return false;
}

/**
 * Clean up expired files across all secrets.
 */
function cleanUpExpiredFiles(): void {
	$f   = SECRET_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		$new_map = [];

		foreach ($map as $secret => $d) {
			if (time() > $d['expiry']) {
				$baseDir = realpath(UPLOAD_PATH . '/' . $d['dir']);
				deleteAllInDirectory($baseDir);
			} else {
				$new_map[$secret] = $d;
			}
		}
		file_put_contents($f, json_encode($new_map));
	}
}

/**
 * Delete the files and expiry data if the file has expired.
 *
 * @param string $secret   Secret.
 * @param string $baseDir Base directory.
 * @return bool Whether the deletion is succeeded.
 */
function deleteExpiredFiles(string $secret, string $baseDir): bool {
	$f   = SECRET_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (time() > $map[$secret]['expiry']) {
			deleteAllInDirectory($baseDir);
			unset($map[$secret]);
			file_put_contents($f, json_encode($map));
			return true;
		}
	}
	return false;
}

/**
 * Delete the files and expiry data.
 *
 * @param string $secret   Secret.
 * @param string $baseDir Base directory.
 */
function deleteFiles(string $secret, string $baseDir): void {
	$f   = SECRET_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		deleteAllInDirectory($baseDir);
		unset($map[$secret]);
		file_put_contents($f, json_encode($map));
	}
}

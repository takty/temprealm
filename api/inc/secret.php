<?php
/**
 * Secret
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

require_once 'lib/file.php';
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

/**
 * Generate a secret.
 *
 * @param string $addr IP address.
 * @return string Secret.
 */
function generate_secret(string $addr): string {
	$secretBase = $addr . microtime();
	return hash('sha256', $secretBase);
}


// -----------------------------------------------------------------------------


/**
 * Counts how many secrets have been issued for a given IP.
 *
 * @param string $addr IP address.
 * @return int Issued secret count.
 */
function count_secrets_by_addr(string $addr): int {
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
function get_addr_by_secret(string $secret): string {
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
function get_directory_by_secret(string $secret): string {
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
function save_secret_mapping(string $secret, string $addr, string $dir): void {
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
 * Clean up expired files across all secrets.
 */
function clean_up_expired_files(): void {
	$f = SECRET_MAPPING_FILE;

	if (file_exists($f)) {
		$map     = json_decode(file_get_contents($f), true);
		$new_map = [];

		foreach ($map as $secret => $d) {
			if (time() > $d['expiry']) {
				$baseDir = realpath(join_paths(UPLOAD_PATH, $d['dir']));
				remove_all($baseDir);
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
function delete_files_if_expired(string $secret, string $baseDir): bool {
	$f = SECRET_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);

		if (time() > $map[$secret]['expiry']) {
			remove_all($baseDir);
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
function delete_files(string $secret, string $baseDir): void {
	$f = SECRET_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		remove_all($baseDir);
		unset($map[$secret]);
		file_put_contents($f, json_encode($map));
	}
}

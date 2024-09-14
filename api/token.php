<?php
/**
 * Token
 *
 * @author Takuto Yanagida
 * @version 2024-09-13
 */

/**
 * Generate or retrieve a token.
 *
 * @param string $ip
 */
function generateToken(string $ip): void {
	// Hash the IP address to create a token
	return hash('sha256', $ip);
}

/**
 * Check if the token exists.
 *
 * @param string $token
 */
function getDirectoryByToken(string $token): void {
	// File that stores the mapping between tokens and URLs
	$f = TOKEN_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (isset($map[$token])) {
			return $map[$token];  // Return the directory corresponding to the token
		}
	}
	return false;
}

/**
 * Map the token to a URL.
 *
 * @param string $token
 * @param string $dir
 */
function saveTokenMapping(string $token, string $dir): void {
	$f   = TOKEN_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
	}
	$map[$token] = $dir;
	file_put_contents($f, json_encode($map));
}

<?php
/**
 * Token
 *
 * @author Takuto Yanagida
 * @version 2024-09-18
 */

require_once 'config.php';

/**
 * Generate a token.
 *
 * @param string $ip IP address.
 * @param string $id Identifier.
 * @return string Token.
 */
function generateToken($ip, $id = ''): string {
	$tokenBase = $ip . $id;
	return hash('sha256', $tokenBase);
}

/**
 * Check how many tokens have been issued for a given IP.
 *
 * @param string $ip IP address.
 * @return int Issued token count.
 */
function countTokensByIP(string $ip): int {
	$f = TOKEN_MAPPING_FILE;
	$c = 0;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		foreach ($map as $token => $d) {
			// Check if the IP matches
			if (isset($d['ip']) && $d['ip'] === $ip) {
				$c++;
			}
		}
	}
	return $c;
}

/**
 * Check if the token exists.
 *
 * @param string $token Token.
 * @return string Directory.
 */
function getDirectoryByToken(string $token): string {
	$f = TOKEN_MAPPING_FILE;

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
		if (isset($map[$token])) {
			return $map[$token]['dir'];
		}
	}
	return false;
}

/**
 * Map the token to a URL.
 *
 * @param string $token Token.
 * @param string $ip    IP address.
 * @param string $dir   Directory.
 */
function saveTokenMapping(string $token, string $ip, string $dir): void {
	$f   = TOKEN_MAPPING_FILE;
	$map = [];

	if (file_exists($f)) {
		$map = json_decode(file_get_contents($f), true);
	}
	$map[$token] = ['dir' => $dir, 'ip' => $ip];
	file_put_contents($f, json_encode($map));
}

<?php
// Generate or retrieve a token
function generateToken($ip) {
	return hash('sha256', $ip);  // Hash the IP address to create a token
}

// Check if the token exists
function getDirectoryByToken($token) {
	$mappingFile = TOKEN_MAPPING_FILE;  // File that stores the mapping between tokens and URLs
	if (file_exists($mappingFile)) {
		$mappings = json_decode(file_get_contents($mappingFile), true);
		if (isset($mappings[$token])) {
			return $mappings[$token];  // Return the directory corresponding to the token
		}
	}
	return false;
}

// Map the token to a URL
function saveTokenMapping($token, $directory) {
	$mappingFile = TOKEN_MAPPING_FILE;
	$mappings = [];
	if (file_exists($mappingFile)) {
		$mappings = json_decode(file_get_contents($mappingFile), true);
	}
	$mappings[$token] = $directory;
	file_put_contents($mappingFile, json_encode($mappings));
}

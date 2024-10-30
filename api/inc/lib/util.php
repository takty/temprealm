<?php
/**
 * Unique Identifier
 *
 * @author Takuto Yanagida
 * @version 2024-10-30
 */

/**
 * Gets the full request URL including the host and request URI.
 *
 * @return string The full request URL.
 */
function get_request_url(): string {
	return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
		. ($_SERVER['HTTP_HOST'] ?? '')
		. ($_SERVER['REQUEST_URI'] ?? '');
}

/**
 * Retrieves the request path without the script name.
 *
 * @return string The request path without the script name.
 */
function get_request_path(): string {
	$rel  = dirname($_SERVER['SCRIPT_NAME']);  // /sub/index.php -> /sub
	$path = $_SERVER['REQUEST_URI'];

	if (str_starts_with($path, $rel)) {
		$path = substr($path, strlen($rel));
	}
	return remove_query_and_hash($path);
}

/**
 * Retrieves the first component of the request path without the script name.
 *
 * @return string The first component of the request path without the script name.
 */
function get_request_path_first(): string {
	$reqPath = get_request_path();
	$parts   = explode('/', trim($reqPath, '/'));
	return array_shift($parts);
}

/**
 * Removes query string and fragment from the given path.
 *
 * @param string $path The URL path to clean.
 * @return string The cleaned path without query string or fragment.
 */
function remove_query_and_hash(string $path): string {
	$ps   = explode( '#', $path );
	$path = $ps[0];

	$ps   = explode( '?', $path );
	$path = $ps[0];

	return $path;
}


// -----------------------------------------------------------------------------


/**
 * Checks if the request has allowed access based on the origin.
 *
 * @param string $origin The allowed origin for cross-origin requests.
 */
function check_allowed_access(string $origin): void {
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		if ($_SERVER['HTTP_ORIGIN'] ?? '' === $origin) {
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
			header('Access-Control-Allow-Headers: Content-Type');
		} else {
			send_response(403, 'Access denied');
			exit;
		}
	}
}

/**
 * Sends a JSON response with the specified status code and message.
 *
 * @param int $code The HTTP status code to send.
 * @param string|null $msg The custom message to include in the response. If null, a default message based on the status code is used.
 * @param array|null $additional Additional data to be sent.
 */
function send_response(int $code, string $msg = null, array $additional = null): void {
	$sms = [
		200 => 'OK',
		201 => 'Created',
		204 => 'No Content',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		500 => 'Internal Server Error',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
	];
	header("HTTP/1.1 $code $sms[$code]");
	header('Content-Type: application/json');
	$ret = [
		'status'  => $code,
		'message' => $msg ?? ($sms[$code] ?? 'Unknown Status')
	] + ($additional ?? []);
	echo json_encode($ret);
}

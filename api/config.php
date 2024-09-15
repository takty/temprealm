<?php
/**
 * Config
 *
 * @author Takuto Yanagida
 * @version 2024-09-15
 */

/**
 * Basic settings
 */

// Base URL of the site
define('BASE_URL', 'https://takty.net');

// Path to the upload directory
define('UPLOAD_PATH', 'uploads');

// Expiry time (set in seconds, 1 hour = 3600 seconds)
define('EXPIRY_TIME', 3600);

// Path to the token mapping file
define('TOKEN_MAPPING_FILE', 'token-mapping.json');

/**
 * File size limits
 */

// Max file size of each file
define('MAX_FILE_SIZE', 5 * 1024 * 1024);    // 5MB

// Max file size of the total of all files
define('MAX_TOTAL_SIZE', 20 * 1024 * 1024);  // 20MB

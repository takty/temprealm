<?php
/**
 * Config
 *
 * @author Takuto Yanagida
 * @version 2024-09-30
 */

/**
 * Basic settings
 */

// Origin allow to access
define('ALLOWED_ORIGIN', 'https://takty.net');

// Base URL of the site
define('BASE_URL', 'https://takty.net');

// Path to the upload directory
define('UPLOAD_PATH', 'uploads');

// Expiry time (set in seconds, 1 hour = 3600 seconds)
define('EXPIRY_TIME', 3600);

// Path to the secret mapping file
define('SECRET_MAPPING_FILE', 'secret-mapping.json');

// Expiry settings
define('EXPIRY_DATA_PATH', 'expiry-data');  // Directory where expiry.json files are stored

// Max secret count
define('MAX_SECRET_COUNT', 10);

/**
 * File size limits
 */

// Max file size of each file
define('MAX_FILE_SIZE', 5 * 1024 * 1024);    // 5MB

// Max file size of the total of all files
define('MAX_TOTAL_SIZE', 20 * 1024 * 1024);  // 20MB

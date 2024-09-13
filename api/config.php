<?php
// Basic settings
define('BASE_URL', 'https://takty.net');             // Base URL of the site
define('UPLOAD_PATH', 'uploads');                    // Path to the upload directory
define('EXPIRY_TIME', 3600);                         // Expiry time (set in seconds, 1 hour = 3600 seconds)
define('TOKEN_MAPPING_FILE', 'token-mapping.json');  // Path to the token mapping file

// File size limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024);    // 5MB
define('MAX_TOTAL_SIZE', 20 * 1024 * 1024);  // 20MB

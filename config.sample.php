<?php
/**
 * DrSerita Monitor Configuration
 *
 * Copy this file to config.php and customize these values.
 *
 * IMPORTANT: After editing config.php, set proper permissions:
 *   chown www-data:www-data config.php
 *   chmod 640 config.php
 */

// Base directory (current directory - where config.php is located)
// Note: __DIR__ is the directory containing this config file
define('BASE_DIR', __DIR__);

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Database path - SQLite database file location
define('DB_PATH', BASE_DIR . '/cache/logs.db');


// ============================================
// LOG FILES CONFIGURATION
// ============================================

// Apache/Nginx access log directory
// For VPS (Ubuntu/Debian): define('LOG_DIR', '/var/log/apache2/');
// For cPanel: define('LOG_DIR', '/home/username/access-logs/');
// For custom: define('LOG_DIR', '/path/to/your/logs/');
define('LOG_DIR', '/var/log/apache2/');

// Log file pattern (used in cron parser)
// The parser will look for files like: drserita_access.log, drserita_access.log.1, etc.
// If your logs have different naming, update cron/parse_logs.php


// ============================================
// PATH CONFIGURATION
// ============================================

// Path to the cron/parser script
define('CRON_PATH', BASE_DIR . '/cron/parse_logs.php');

// Path to the parser log file
define('PARSER_LOG_PATH', BASE_DIR . '/logs/parser.log');


// ============================================
// APPLICATION SETTINGS
// ============================================

// Application title (displayed in UI)
define('APP_TITLE', 'DrSerita');


// ============================================
// SECURITY - PASSWORD HASH
// ============================================

// Generate your password hash using:
// php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
//
// Example: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
// Output: $2y$10$abc...xyz...
//
// Then paste the output below:
define('MONITOR_PASSWORD_HASH', 'REPLACE_WITH_YOUR_GENERATED_HASH');


// ============================================
// CRON JOB REFERENCE
// ============================================

/*
 * Cron Job Command (update paths as needed):
 *
 * VPS (default):
 * 0 */1 * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php >> /var/www/drserita-monitoring/logs/parser.log 2>&1
 *
 * Using Constants (for dynamic paths):
 * 0 */1 * * * /usr/bin/php <?php echo CRON_PATH; ?> >> <?php echo PARSER_LOG_PATH; ?> 2>&1
 *
 * cPanel (update home directory):
 * 0 */1 * * * /usr/bin/php /home/username/public_html/drserita-monitoring/cron/parse_logs.php >> /home/username/public_html/drserita-monitoring/logs/parser.log 2>&1
 */

?>

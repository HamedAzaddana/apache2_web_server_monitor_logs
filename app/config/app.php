<?php
/**
 * Application Configuration
 */

// App constants (will be loaded from main config.php if not already defined)
if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Web crawl Monitor');
}

// Timezone
date_default_timezone_set('Asia/Tehran');

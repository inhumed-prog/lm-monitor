<?php
/**
 * Plugin Constants
 * All magic numbers and configuration values centralized
 */

if (!defined('ABSPATH')) {
    exit;
}

// Already defined in main file:
// LM_MONITOR_VERSION
// LM_MONITOR_PLUGIN_DIR
// LM_MONITOR_PLUGIN_URL
// LM_MONITOR_PLUGIN_FILE
// LM_MONITOR_PLUGIN_BASENAME
// LM_MONITOR_MIN_PHP_VERSION
// LM_MONITOR_MIN_WP_VERSION

/**
 * Database
 */
define('LM_MONITOR_TABLE_NAME', 'lm_monitor_sites');

/**
 * Cron & Intervals (in seconds)
 */
define('LM_MONITOR_CRON_FIVE_MINUTES', 300);
define('LM_MONITOR_CRON_ONE_MINUTE', 60);
define('LM_MONITOR_CRON_THIRTY_SECONDS', 30);

/**
 * Batch Processing
 */
define('LM_MONITOR_BATCH_SIZE_DEFAULT', 10);
define('LM_MONITOR_BATCH_SIZE_FAST', 5);
define('LM_MONITOR_BATCH_SIZE_VERY_FAST', 3);

/**
 * Timeouts (in seconds)
 */
define('LM_MONITOR_HTTP_TIMEOUT', 30);
define('LM_MONITOR_WEBHOOK_TIMEOUT', 10);
define('LM_MONITOR_SSL_TIMEOUT', 10);
define('LM_MONITOR_CRON_LOCK_DURATION', 300); // 5 minutes
define('LM_MONITOR_HTTP_MAX_REDIRECTS', 5);

/**
 * Performance Thresholds (in milliseconds)
 */
define('LM_MONITOR_RESPONSE_FAST', 1000);      // < 1s = fast
define('LM_MONITOR_RESPONSE_SLOW', 3000);      // < 3s = medium
define('LM_MONITOR_RESPONSE_VERY_SLOW', 5000); // < 5s = slow

/**
 * SSL Certificate
 */
define('LM_MONITOR_SSL_CRITICAL_DAYS', 7);  // Red alert
define('LM_MONITOR_SSL_WARNING_DAYS', 30);  // Orange warning
define('LM_MONITOR_SSL_PORT', 443);

/**
 * Validation Limits
 */
define('LM_MONITOR_URL_MAX_LENGTH', 255);
define('LM_MONITOR_EMAIL_MAX_LENGTH', 100);
define('LM_MONITOR_WEBHOOK_MAX_LENGTH', 500);

/**
 * Default Settings
 */
define('LM_MONITOR_DEFAULT_CHECK_INTERVAL', 5);      // minutes
define('LM_MONITOR_DEFAULT_COOLDOWN', 24);           // hours
define('LM_MONITOR_DEFAULT_NOTIFICATION_DELAY', DAY_IN_SECONDS);

/**
 * HTTP Status Code Ranges
 */
define('LM_MONITOR_HTTP_SUCCESS_MIN', 200);
define('LM_MONITOR_HTTP_SUCCESS_MAX', 299);
define('LM_MONITOR_HTTP_REDIRECT_MIN', 300);
define('LM_MONITOR_HTTP_REDIRECT_MAX', 399);
define('LM_MONITOR_HTTP_CLIENT_ERROR_MIN', 400);
define('LM_MONITOR_HTTP_CLIENT_ERROR_MAX', 499);
define('LM_MONITOR_HTTP_SERVER_ERROR_MIN', 500);
define('LM_MONITOR_HTTP_SERVER_ERROR_MAX', 599);

/**
 * Uptime Quality Thresholds (percentage)
 */
define('LM_MONITOR_UPTIME_EXCELLENT', 99.9);  // Green
define('LM_MONITOR_UPTIME_GOOD', 99.0);       // Light green
define('LM_MONITOR_UPTIME_FAIR', 95.0);       // Orange

/**
 * Logging
 */
define('LM_MONITOR_ENABLE_LOGGING', true);
define('LM_MONITOR_LOG_PREFIX', 'LM Monitor');

/**
 * AJAX & UI
 */
define('LM_MONITOR_AJAX_TIMEOUT', 30000);     // milliseconds
define('LM_MONITOR_TOAST_DURATION', 3000);    // milliseconds

/**
 * Colors (Hex)
 */
define('LM_MONITOR_COLOR_SUCCESS', '#10b981');
define('LM_MONITOR_COLOR_ERROR', '#ef4444');
define('LM_MONITOR_COLOR_WARNING', '#f59e0b');
define('LM_MONITOR_COLOR_INFO', '#3b82f6');
define('LM_MONITOR_COLOR_GRAY', '#9ca3af');

/**
 * Discord Webhook Colors (Decimal for embeds)
 */
define('LM_MONITOR_DISCORD_COLOR_BLUE', 0x3B82F6);
define('LM_MONITOR_DISCORD_COLOR_GREEN', 0x10B981);
define('LM_MONITOR_DISCORD_COLOR_RED', 0xEF4444);
define('LM_MONITOR_DISCORD_COLOR_ORANGE', 0xF59E0B);

/**
 * Blocked Hosts (Security)
 */
define('LM_MONITOR_BLOCKED_HOSTS', array('localhost', '127.0.0.1', '0.0.0.0'));

/**
 * Disposable Email Domains (Optional - can be expanded)
 */
define('LM_MONITOR_DISPOSABLE_DOMAINS', array(
    'tempmail.com',
    '10minutemail.com',
    'guerrillamail.com'
));

/**
 * User Agent
 */
define('LM_MONITOR_USER_AGENT', 'LM-Monitor/' . LM_MONITOR_VERSION);

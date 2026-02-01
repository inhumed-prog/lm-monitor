<?php
/**
 * Helper Functions
 * Centralized reusable functions to avoid code duplication
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Security: Verify user has admin capabilities
 *
 * @param bool $ajax True if AJAX request, false for regular page
 * @return void Dies if unauthorized
 */
function lm_monitor_verify_admin_access($ajax = false) {
	if (!current_user_can('manage_options')) {
		if ($ajax) {
			wp_send_json_error(array(
				'message' => __('Unauthorized access.', 'lm-monitor')
			), 403);
		} else {
			wp_die(
				esc_html__('You do not have sufficient permissions to access this page.', 'lm-monitor'),
				esc_html__('Permission Denied', 'lm-monitor'),
				array('response' => 403)
			);
		}
	}
}

/**
 * Security: Verify AJAX nonce
 *
 * @param string $nonce_action Nonce action name
 * @return void Dies if invalid
 */
function lm_monitor_verify_ajax_nonce($nonce_action = 'lm_monitor_nonce') {
	if (!check_ajax_referer($nonce_action, 'nonce', false)) {
		wp_send_json_error(array(
			'message' => __('Security check failed. Please refresh the page.', 'lm-monitor')
		), 403);
	}
}

/**
 * Get plugin settings with defaults
 *
 * @param bool $force_refresh Force reload from database
 * @return array Plugin settings
 */
function lm_monitor_get_settings($force_refresh = false) {
	static $settings = null;

	if ($settings === null || $force_refresh) {
		$settings = get_option('lm_monitor_settings', array());

		// Apply defaults
		$defaults = array(
			'webhook_url' => '',
			'check_interval' => LM_MONITOR_DEFAULT_CHECK_INTERVAL,
			'notification_cooldown' => LM_MONITOR_DEFAULT_COOLDOWN,
			'version' => LM_MONITOR_VERSION
		);

		$settings = wp_parse_args($settings, $defaults);
	}

	return $settings;
}

/**
 * Get specific setting value
 *
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function lm_monitor_get_setting($key, $default = null) {
	$settings = lm_monitor_get_settings();
	return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Get database table name
 *
 * @return string Full table name with prefix
 */
function lm_monitor_get_table_name() {
	global $wpdb;
	return $wpdb->prefix . LM_MONITOR_TABLE_NAME;
}

/**
 * Get email recipient for site alerts
 * Checks site-specific email, falls back to admin email
 *
 * @param object $site Site object from database
 * @return string|false Email address or false if none found
 */
function lm_monitor_get_alert_recipient($site) {
	// Site-specific email
	if (!empty($site->notify_email) && is_email($site->notify_email)) {
		return $site->notify_email;
	}

	// Fallback to admin email
	$admin_email = get_option('admin_email');
	if (!empty($admin_email) && is_email($admin_email)) {
		return $admin_email;
	}

	return false;
}

/**
 * Build email headers
 *
 * @return array Email headers
 */
function lm_monitor_get_email_headers() {
	return array(
		'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
		'Content-Type: text/plain; charset=UTF-8'
	);
}

/**
 * Log with plugin prefix
 *
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 * @return void
 */
function lm_monitor_log($message, $level = 'info') {
	if (!LM_MONITOR_ENABLE_LOGGING && $level === 'info') {
		return;
	}

	$prefix = 'LM Monitor';
	if ($level === 'error') {
		$prefix .= ' ERROR';
	} elseif ($level === 'warning') {
		$prefix .= ' WARNING';
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log(sprintf('%s: %s', $prefix, $message));
}

/**
 * Format bytes to human readable
 *
 * @param int $bytes Bytes
 * @param int $precision Decimal precision
 * @return string Formatted string
 */
function lm_monitor_format_bytes($bytes, $precision = 2) {
	$units = array('B', 'KB', 'MB', 'GB');

	for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
		$bytes /= 1024;
	}

	return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get severity color for status
 *
 * @param string $status Status string
 * @return string Hex color code
 */
function lm_monitor_get_status_color($status) {
	if ($status === 'UP') {
		return '#10b981'; // Green
	}

	if ($status === 'DOWN') {
		return '#ef4444'; // Red
	}

	if (strpos($status, 'ERROR') !== false) {
		return '#f59e0b'; // Orange
	}

	return '#9ca3af'; // Gray
}

/**
 * Get response time performance level
 *
 * @param float $response_time Response time in milliseconds
 * @return string Performance level (fast, medium, slow, very_slow)
 */
function lm_monitor_get_performance_level($response_time) {
	if ($response_time === null) {
		return 'unknown';
	}

	if ($response_time < LM_MONITOR_RESPONSE_FAST) {
		return 'fast';
	}

	if ($response_time < LM_MONITOR_RESPONSE_SLOW) {
		return 'medium';
	}

	if ($response_time < LM_MONITOR_RESPONSE_VERY_SLOW) {
		return 'slow';
	}

	return 'very_slow';
}

/**
 * Get SSL urgency level
 *
 * @param int $days_remaining Days until expiration
 * @return string Urgency level (safe, warning, critical, expired)
 */
function lm_monitor_get_ssl_urgency($days_remaining) {
	if ($days_remaining === null) {
		return 'none';
	}

	if ($days_remaining < 0) {
		return 'expired';
	}

	if ($days_remaining <= LM_MONITOR_SSL_CRITICAL_DAYS) {
		return 'critical';
	}

	if ($days_remaining <= LM_MONITOR_SSL_WARNING_DAYS) {
		return 'warning';
	}

	return 'safe';
}

/**
 * Sanitize and validate ID
 *
 * @param mixed $id ID to validate
 * @return int|false Sanitized ID or false if invalid
 */
function lm_monitor_sanitize_id($id) {
	$id = absint($id);
	return $id > 0 ? $id : false;
}

/**
 * Check if debugging is enabled
 *
 * @return bool True if debug mode
 */
function lm_monitor_is_debug() {
	return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Build admin page URL
 *
 * @param string $page Page slug (without lm-monitor- prefix)
 * @param array $args Additional URL arguments
 * @return string Admin URL
 */
function lm_monitor_admin_url($page = '', $args = array()) {
	$base_page = 'lm-monitor';

	if (!empty($page)) {
		$base_page .= '-' . $page;
	}

	$args['page'] = $base_page;

	return add_query_arg($args, admin_url('admin.php'));
}

/**
 * Get site name from URL
 *
 * @param string $url Website URL
 * @return string Site name (hostname)
 */
function lm_monitor_get_site_name($url) {
	$parsed = wp_parse_url($url);
	return isset($parsed['host']) ? $parsed['host'] : $url;
}

/**
 * Check if SSL monitoring is applicable
 *
 * @param string $url Website URL
 * @return bool True if HTTPS
 */
function lm_monitor_is_https($url) {
	return strpos($url, 'https://') === 0;
}

/**
 * Get transient key with prefix
 *
 * @param string $key Transient key
 * @return string Prefixed key
 */
function lm_monitor_transient_key($key) {
	return 'lm_monitor_' . $key;
}

/**
 * Safely get transient with prefix
 *
 * @param string $key Transient key
 * @return mixed Transient value or false
 */
function lm_monitor_get_transient($key) {
	return get_transient(lm_monitor_transient_key($key));
}

/**
 * Safely set transient with prefix
 *
 * @param string $key Transient key
 * @param mixed $value Transient value
 * @param int $expiration Expiration in seconds
 * @return bool True on success
 */
function lm_monitor_set_transient($key, $value, $expiration) {
	return set_transient(lm_monitor_transient_key($key), $value, $expiration);
}

/**
 * Safely delete transient with prefix
 *
 * @param string $key Transient key
 * @return bool True on success
 */
function lm_monitor_delete_transient($key) {
	return delete_transient(lm_monitor_transient_key($key));
}

/**
 * Get user display name for logging
 *
 * @param int|null $user_id User ID, null for current user
 * @return string User display name or 'Unknown'
 */
function lm_monitor_get_user_name($user_id = null) {
	if ($user_id === null) {
		$user_id = get_current_user_id();
	}

	$user = get_userdata($user_id);
	return $user ? $user->display_name : __('Unknown', 'lm-monitor');
}

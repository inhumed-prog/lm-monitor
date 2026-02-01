<?php
/**
 * Validation functions
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Validate URL
 *
 * @param string $url URL to validate
 * @return array Validation result with 'valid' and 'message' or 'url'
 */
function lm_monitor_validate_url($url) {
	// Check if URL is empty
	if (empty($url)) {
		return array(
			'valid' => false,
			'message' => __('URL cannot be empty.', 'lm-monitor')
		);
	}

	// Trim whitespace
	$url = trim($url);

	// Check if URL has http:// or https://
	if (!preg_match('/^https?:\/\//i', $url)) {
		return array(
			'valid' => false,
			'message' => __('URL must start with http:// or https://', 'lm-monitor')
		);
	}

	// Sanitize URL
	$url = esc_url_raw($url);

	// Check if sanitization removed the URL (invalid characters)
	if (empty($url)) {
		return array(
			'valid' => false,
			'message' => __('URL contains invalid characters.', 'lm-monitor')
		);
	}

	// Check if URL has a valid domain
	$parsed = wp_parse_url($url);
	if (!isset($parsed['host'])) {
		return array(
			'valid' => false,
			'message' => __('URL must have a valid domain name.', 'lm-monitor')
		);
	}

	// Check if host is valid
	if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $parsed['host'])) {
		return array(
			'valid' => false,
			'message' => __('Invalid domain name format.', 'lm-monitor')
		);
	}

	// Check for localhost/private IPs (security measure)
	if (in_array(strtolower($parsed['host']), LM_MONITOR_BLOCKED_HOSTS)) {
		return array(
			'valid' => false,
			'message' => __('Cannot monitor localhost or local IP addresses.', 'lm-monitor')
		);
	}

	// Check for private IP ranges
	if (filter_var($parsed['host'], FILTER_VALIDATE_IP)) {
		if (!filter_var($parsed['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return array(
				'valid' => false,
				'message' => __('Cannot monitor private or reserved IP addresses.', 'lm-monitor')
			);
		}
	}

	// Check for duplicate
	if (lm_monitor_url_exists($url)) {
		return array(
			'valid' => false,
			'message' => __('This URL is already being monitored.', 'lm-monitor')
		);
	}

	// Check URL length
	if (strlen($url) > LM_MONITOR_URL_MAX_LENGTH) {
		return array(
			'valid' => false,
			'message' => __('URL is too long (maximum 255 characters).', 'lm-monitor')
		);
	}

	return array(
		'valid' => true,
		'url' => $url
	);
}

/**
 * Validate email
 *
 * @param string $email Email to validate
 * @return array Validation result
 */
function lm_monitor_validate_email($email) {
	if (empty($email)) {
		return array(
			'valid' => true,
			'email' => ''
		); // Email is optional
	}

	$email = sanitize_email(trim($email));

	if (!is_email($email)) {
		return array(
			'valid' => false,
			'message' => __('Please enter a valid email address.', 'lm-monitor')
		);
	}

	// Check email length
	if (strlen($email) > LM_MONITOR_EMAIL_MAX_LENGTH) {
		return array(
			'valid' => false,
			'message' => __('Email address is too long (maximum 100 characters).', 'lm-monitor')
		);
	}

	// Check for disposable email domains
	$email_parts = explode('@', $email);
	if (isset($email_parts[1]) && in_array(strtolower($email_parts[1]), LM_MONITOR_DISPOSABLE_DOMAINS)) {
		return array(
			'valid' => false,
			'message' => __('Disposable email addresses are not allowed.', 'lm-monitor')
		);
	}

	return array(
		'valid' => true,
		'email' => $email
	);
}

/**
 * Validate webhook URL
 *
 * @param string $url Webhook URL to validate
 * @return array Validation result
 */
function lm_monitor_validate_webhook_url($url) {
	if (empty($url)) {
		return array(
			'valid' => true,
			'url' => ''
		); // Webhook is optional
	}

	$url = trim($url);

	// Check URL format
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return array(
			'valid' => false,
			'message' => __('Please enter a valid webhook URL.', 'lm-monitor')
		);
	}

	// Must start with http:// or https://
	if (!preg_match('/^https?:\/\//i', $url)) {
		return array(
			'valid' => false,
			'message' => __('Webhook URL must start with http:// or https://', 'lm-monitor')
		);
	}

	// Parse URL
	$parsed = wp_parse_url($url);
	if (!isset($parsed['host'])) {
		return array(
			'valid' => false,
			'message' => __('Webhook URL must have a valid domain.', 'lm-monitor')
		);
	}

	// Block localhost/private IPs for security
	if (in_array(strtolower($parsed['host']), LM_MONITOR_BLOCKED_HOSTS)) {
		return array(
			'valid' => false,
			'message' => __('Cannot use localhost for webhook URL.', 'lm-monitor')
		);
	}

	// Check for private IP ranges
	if (filter_var($parsed['host'], FILTER_VALIDATE_IP)) {
		if (!filter_var($parsed['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return array(
				'valid' => false,
				'message' => __('Cannot use private IP addresses for webhook URL.', 'lm-monitor')
			);
		}
	}

	// Check URL length
	if (strlen($url) > LM_MONITOR_WEBHOOK_MAX_LENGTH) {
		return array(
			'valid' => false,
			'message' => __('Webhook URL is too long (maximum 500 characters).', 'lm-monitor')
		);
	}

	// Recommend HTTPS for webhooks
	if (strpos($url, 'https://') !== 0) {
		return array(
			'valid' => true,
			'url' => esc_url_raw($url),
			'warning' => __('HTTPS is recommended for webhook URLs for better security.', 'lm-monitor')
		);
	}

	return array(
		'valid' => true,
		'url' => esc_url_raw($url)
	);
}

/**
 * Sanitize settings array
 *
 * @param array $settings Settings to sanitize
 * @return array Sanitized settings
 */
function lm_monitor_sanitize_settings($settings) {
	$sanitized = array();

	if (isset($settings['webhook_url'])) {
		$webhook_validation = lm_monitor_validate_webhook_url($settings['webhook_url']);
		$sanitized['webhook_url'] = $webhook_validation['valid'] ? $webhook_validation['url'] : '';
	}

	if (isset($settings['check_interval'])) {
		$interval = intval($settings['check_interval']);
		// Minimum 5 minutes, maximum 60 minutes
		$sanitized['check_interval'] = max(5, min(60, $interval));
	}

	if (isset($settings['notification_cooldown'])) {
		$cooldown = intval($settings['notification_cooldown']);
		// Minimum 1 hour, maximum 168 hours (7 days)
		$sanitized['notification_cooldown'] = max(1, min(168, $cooldown));
	}

	return $sanitized;
}

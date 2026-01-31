<?php
/**
 * Validation functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if host is blocked (localhost, private IPs)
 *
 * @param string $host Hostname or IP
 * @return bool True if blocked
 */
function lm_monitor_is_blocked_host($host) {
    $host = strtolower($host);

    // Check blocked hostnames
    if (in_array($host, LM_MONITOR_BLOCKED_HOSTS)) {
        return true;
    }

    // Check if it's an IP address
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        // Block private and reserved ranges
        if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
    }

    return false;
}

/**
 * Validate URL
 *
 * @param string $url URL to validate
 * @return array Validation result with 'valid' and 'message' or 'url'
 */
function lm_monitor_validate_url($url) {
    if (empty($url)) {
        return array(
            'valid' => false,
            'message' => __('URL cannot be empty.', 'lm-monitor')
        );
    }

    $url = trim($url);

    if (!preg_match('/^https?:\/\//i', $url)) {
        return array(
            'valid' => false,
            'message' => __('URL must start with http:// or https://', 'lm-monitor')
        );
    }

    $url = esc_url_raw($url);

    if (empty($url)) {
        return array(
            'valid' => false,
            'message' => __('URL contains invalid characters.', 'lm-monitor')
        );
    }

    $parsed = parse_url($url);
    if (!isset($parsed['host'])) {
        return array(
            'valid' => false,
            'message' => __('URL must have a valid domain name.', 'lm-monitor')
        );
    }

    if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $parsed['host'])) {
        return array(
            'valid' => false,
            'message' => __('Invalid domain name format.', 'lm-monitor')
        );
    }

    // Use shared function for blocked host check
    if (lm_monitor_is_blocked_host($parsed['host'])) {
        return array(
            'valid' => false,
            'message' => __('Cannot monitor localhost or private IP addresses.', 'lm-monitor')
        );
    }

    if (lm_monitor_url_exists($url)) {
        return array(
            'valid' => false,
            'message' => __('This URL is already being monitored.', 'lm-monitor')
        );
    }

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
        );
    }

    $email = sanitize_email(trim($email));

    if (!is_email($email)) {
        return array(
            'valid' => false,
            'message' => __('Please enter a valid email address.', 'lm-monitor')
        );
    }

    if (strlen($email) > LM_MONITOR_EMAIL_MAX_LENGTH) {
        return array(
            'valid' => false,
            'message' => __('Email address is too long (maximum 100 characters).', 'lm-monitor')
        );
    }

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
        );
    }

    $url = trim($url);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return array(
            'valid' => false,
            'message' => __('Please enter a valid webhook URL.', 'lm-monitor')
        );
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        return array(
            'valid' => false,
            'message' => __('Webhook URL must start with http:// or https://', 'lm-monitor')
        );
    }

    $parsed = parse_url($url);
    if (!isset($parsed['host'])) {
        return array(
            'valid' => false,
            'message' => __('Webhook URL must have a valid domain.', 'lm-monitor')
        );
    }

    // Use shared function for blocked host check
    if (lm_monitor_is_blocked_host($parsed['host'])) {
        return array(
            'valid' => false,
            'message' => __('Cannot use localhost or private IPs for webhook URL.', 'lm-monitor')
        );
    }

    if (strlen($url) > LM_MONITOR_WEBHOOK_MAX_LENGTH) {
        return array(
            'valid' => false,
            'message' => __('Webhook URL is too long (maximum 500 characters).', 'lm-monitor')
        );
    }

    $result = array(
        'valid' => true,
        'url' => esc_url_raw($url)
    );

    if (strpos($url, 'https://') !== 0) {
        $result['warning'] = __('HTTPS is recommended for webhook URLs for better security.', 'lm-monitor');
    }

    return $result;
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
        $sanitized['check_interval'] = max(5, min(60, $interval));
    }

    if (isset($settings['notification_cooldown'])) {
        $cooldown = intval($settings['notification_cooldown']);
        $sanitized['notification_cooldown'] = max(1, min(168, $cooldown));
    }

    return $sanitized;
}

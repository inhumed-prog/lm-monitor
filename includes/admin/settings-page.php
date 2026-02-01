<?php
/**
 * Settings page with proper security and validation
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Render settings page
 */
function lm_monitor_settings_page() {
	// Security check
	lm_monitor_verify_admin_access();

	$error_message = '';
	$success_message = '';

	// Handle form submission
	if (isset($_POST['save_settings'])) {
		// Verify nonce
		if (!check_admin_referer('lm_monitor_settings')) {
			$error_message = __('Security check failed. Please try again.', 'lm-monitor');
		} else {
			// Process settings
			$result = lm_monitor_process_settings_form();

			if (is_wp_error($result)) {
				$error_message = $result->get_error_message();
			} elseif ($result === true) {
				$success_message = __('Settings saved successfully!', 'lm-monitor');
			}
		}
	}

	// Get current settings
	$webhook_url = lm_monitor_get_setting('webhook_url');

	// Get cron status
	$cron_status = lm_monitor_get_cron_status();

	// Render view
	include LM_MONITOR_PLUGIN_DIR . 'includes/admin/views/settings-page-view.php';
}

/**
 * Process settings form submission
 *
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function lm_monitor_process_settings_form() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in lm_monitor_settings_page() before calling this function
	$webhook_url = isset( $_POST['webhook_url'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_url'] ) ) : '';

	// Allow empty URL (to disable webhook)
	if ( ! empty( $webhook_url ) ) {
		$validation = lm_monitor_validate_webhook_url( $webhook_url );

		if ( ! $validation['valid'] ) {
			return new WP_Error( 'invalid_webhook', $validation['message'] );
		}

		$webhook_url = $validation['url'];
	}

	// Prepare new settings
	$new_settings = array(
		'webhook_url'           => $webhook_url,
		'check_interval'        => LM_MONITOR_DEFAULT_CHECK_INTERVAL,
		'notification_cooldown' => LM_MONITOR_DEFAULT_COOLDOWN,
		'version'               => LM_MONITOR_VERSION,
	);

	// Update settings
	update_option( 'lm_monitor_settings', $new_settings );

	// Force refresh the settings cache
	lm_monitor_get_settings( true );

	// Log the update
	lm_monitor_log( sprintf(
		'Settings updated by user %d - Webhook: %s',
		get_current_user_id(),
		! empty( $webhook_url ) ? 'configured' : 'disabled'
	) );

	return true;
}

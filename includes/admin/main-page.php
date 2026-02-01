<?php
/**
 * Main admin page (Dashboard) with security and error handling
 *
 * @package LM_Monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render main admin page
 */
function lm_monitor_main_page() {
	// Security check
	lm_monitor_verify_admin_access();

	$error_message   = '';
	$success_message = '';

	// Handle form submission
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['url'] ) ) {
		// Verify nonce
		if ( ! check_admin_referer( 'lm_monitor_add_site', '_wpnonce', false ) ) {
			$error_message = __( 'Security check failed. Please try again.', 'lm-monitor' );
		} else {
			// Get and sanitize form data
			$url   = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

			// Validate URL using the validator function
			$url_validation = lm_monitor_validate_url( $url );

			if ( ! $url_validation['valid'] ) {
				$error_message = $url_validation['message'];
			} else {
				// Use the validated/sanitized URL
				$url = $url_validation['url'];

				// Validate email
				$email_validation = lm_monitor_validate_email( $email );
				if ( ! $email_validation['valid'] ) {
					$error_message = $email_validation['message'];
				} else {
					$email = $email_validation['email'];

					// URL and email are valid, insert into database
					global $wpdb;
					$table = lm_monitor_get_table_name();

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$inserted = $wpdb->insert(
						$table,
						array(
							'url'               => $url,
							'notify_email'      => $email,
							'status'            => null,
							'last_checked'      => null,
							'response_time'     => null,
							'ssl_expiry_date'   => null,
							'ssl_issuer'        => null,
							'ssl_days_remaining' => null,
							'check_count'       => 0,
							'down_count'        => 0,
							'created_at'        => current_time( 'mysql' ),
							'updated_at'        => current_time( 'mysql' ),
						),
						array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
					);

					if ( false !== $inserted ) {
						$new_id = $wpdb->insert_id;
						$success_message = sprintf(
							/* translators: %d: Site ID number */
							__( 'Website added successfully! (ID: %d)', 'lm-monitor' ),
							$new_id
						);

						lm_monitor_log( sprintf(
							'Site added by user %d - ID: %d, URL: %s',
							get_current_user_id(),
							$new_id,
							$url
						) );
					} else {
						$error_message = __( 'Database error: Could not add website.', 'lm-monitor' );
						lm_monitor_log( 'Database error - ' . $wpdb->last_error, 'error' );
					}
				}
			}
		}
	}

	// Get all sites
	$sites = lm_monitor_get_sites( array(
		'orderby' => 'id',
		'order'   => 'DESC',
	) );

	// Get statistics
	$stats = lm_monitor_get_stats();

	// Get cron status
	$cron_status = lm_monitor_get_cron_status();

	// Render page
	include LM_MONITOR_PLUGIN_DIR . 'includes/admin/views/main-page-view.php';
}

<?php
/**
 * AJAX handlers for admin
 *
 * @package LM_Monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: Check website now
 */
add_action( 'wp_ajax_lm_monitor_check_now', 'lm_monitor_ajax_check_now' );

/**
 * Handle check now AJAX request
 */
function lm_monitor_ajax_check_now() {
	// Security checks - nonce first
	check_ajax_referer( 'lm_monitor_nonce', 'nonce' );
	lm_monitor_verify_admin_access( true );

	// Validate and sanitize ID
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid site ID.', 'lm-monitor' ),
		), 400 );
	}

	$id = lm_monitor_sanitize_id( absint( wp_unslash( $_POST['id'] ) ) );
	if ( ! $id ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid site ID.', 'lm-monitor' ),
		), 400 );
	}

	$site = lm_monitor_get_site( $id );

	if ( ! $site ) {
		wp_send_json_error( array(
			'message' => __( 'Website not found in database.', 'lm-monitor' ),
		), 404 );
	}

	// Check the website with extended data
	try {
		$check_result = lm_monitor_check_website( $site->url );

		if ( ! $check_result ) {
			throw new Exception( 'Check function returned no result' );
		}

		// Update database with all data
		$update_success = lm_monitor_update_status( $id, $check_result['status'], array(
			'response_time'      => $check_result['response_time'],
			'ssl_expiry_date'    => $check_result['ssl_expiry_date'],
			'ssl_issuer'         => $check_result['ssl_issuer'],
			'ssl_days_remaining' => $check_result['ssl_days_remaining'],
		) );

		if ( ! $update_success ) {
			throw new Exception( 'Failed to update database' );
		}

		wp_send_json_success( array(
			'status'             => $check_result['status'],
			'response_time'      => $check_result['response_time'],
			'ssl_days_remaining' => $check_result['ssl_days_remaining'],
			'ssl_expiry_date'    => $check_result['ssl_expiry_date'],
			'checked'            => current_time( 'mysql' ),
			'status_html'        => lm_monitor_render_status( $check_result['status'] ),
			'response_time_html' => lm_monitor_render_response_time( $check_result['response_time'] ),
			'ssl_html'           => lm_monitor_render_ssl_expiry( $check_result['ssl_days_remaining'], $check_result['ssl_expiry_date'] ),
			'message'            => __( 'Website checked successfully!', 'lm-monitor' ),
		) );

	} catch ( Exception $e ) {
		lm_monitor_log( 'Check Error (ID: ' . $id . '): ' . $e->getMessage(), 'error' );
		wp_send_json_error( array(
			'message' => sprintf(
				/* translators: %s: Error message */
				__( 'Error checking website: %s', 'lm-monitor' ),
				esc_html( $e->getMessage() )
			),
		), 500 );
	}
}

/**
 * AJAX: Delete website
 */
add_action( 'wp_ajax_lm_monitor_delete', 'lm_monitor_ajax_delete' );

/**
 * Handle delete AJAX request
 */
function lm_monitor_ajax_delete() {
	// Security checks - nonce first
	check_ajax_referer( 'lm_monitor_nonce', 'nonce' );
	lm_monitor_verify_admin_access( true );

	// Validate and sanitize ID
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid site ID.', 'lm-monitor' ),
		), 400 );
	}

	$id = lm_monitor_sanitize_id( absint( wp_unslash( $_POST['id'] ) ) );
	if ( ! $id ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid site ID.', 'lm-monitor' ),
		), 400 );
	}

	// Check if site exists
	$site = lm_monitor_get_site( $id );
	if ( ! $site ) {
		wp_send_json_error( array(
			'message' => __( 'Website not found.', 'lm-monitor' ),
		), 404 );
	}

	// Delete site
	$deleted = lm_monitor_delete_site( $id );

	if ( $deleted ) {
		lm_monitor_log( sprintf(
			'Site deleted by user %d - ID: %d, URL: %s',
			get_current_user_id(),
			$id,
			$site->url
		) );

		wp_send_json_success( array(
			'message' => __( 'Website deleted successfully.', 'lm-monitor' ),
		) );
	} else {
		wp_send_json_error( array(
			'message' => __( 'Database error: Could not delete website.', 'lm-monitor' ),
		), 500 );
	}
}

/**
 * AJAX: Test webhook
 */
add_action( 'wp_ajax_lm_monitor_test_webhook', 'lm_monitor_ajax_test_webhook' );

/**
 * Handle test webhook AJAX request
 */
function lm_monitor_ajax_test_webhook() {
	// Security checks - nonce first
	check_ajax_referer( 'lm_monitor_nonce', 'nonce' );
	lm_monitor_verify_admin_access( true );

	// Get webhook URL
	$webhook_url = lm_monitor_get_setting( 'webhook_url' );

	if ( empty( $webhook_url ) ) {
		wp_send_json_error( array(
			'message' => __( 'No webhook URL configured. Please save a webhook URL in settings first.', 'lm-monitor' ),
		), 400 );
	}

	// Validate webhook URL
	if ( ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid webhook URL format.', 'lm-monitor' ),
		), 400 );
	}

	// Test webhook
	$result = lm_monitor_test_webhook( $webhook_url );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array(
			'message' => $result->get_error_message(),
		), 500 );
	}

	wp_send_json_success( array(
		'message' => __( 'Test notification sent successfully!', 'lm-monitor' ),
	) );
}

/**
 * AJAX: Bulk check all sites
 */
add_action( 'wp_ajax_lm_monitor_bulk_check', 'lm_monitor_ajax_bulk_check' );

/**
 * Handle bulk check AJAX request
 */
function lm_monitor_ajax_bulk_check() {
	// Security checks - nonce first
	check_ajax_referer( 'lm_monitor_nonce', 'nonce' );
	lm_monitor_verify_admin_access( true );

	// Use the manual check function from cron.php
	$result = lm_monitor_manual_check_all();

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array(
			'message' => $result->get_error_message(),
		), 403 );
	}

	if ( ! $result['success'] ) {
		wp_send_json_error( array(
			'message' => $result['message'],
		), 400 );
	}

	wp_send_json_success( array(
		'message' => $result['message'],
		'total'   => $result['total'],
		'checked' => $result['checked'],
		'failed'  => $result['failed'],
	) );
}
